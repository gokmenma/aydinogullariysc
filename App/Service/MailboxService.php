<?php

namespace App\Service;

use PDO;
use RuntimeException;

final class MailboxService
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public static function isSuperAdmin(): bool
    {
        $perm = (int) (function_exists('sesset') ? sesset('permission') : 0);
        if ($perm === 13) {
            return true;
        }
        $sessionPerm = (int) ($_SESSION['permission'] ?? ($_SESSION['perm'] ?? 0));
        return $sessionPerm === 13;
    }

    public function accounts(): array
    {
        $stmt = $this->db->prepare("SELECT id, mail_address, description, account_type, sync_enabled, last_sync_at, last_sync_error FROM mail_accounts WHERE sync_enabled = 1 OR account_type = 1 ORDER BY account_type ASC, mail_address ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function messages(int $accountId, string $folder = 'inbox', int $limit = 100): array
    {
        $folder = $folder === 'sent' ? 'sent' : 'inbox';
        $stmt = $this->db->prepare("SELECT id, from_address, from_name, to_addresses, subject, received_at, is_read_local, has_attachments FROM mailbox_messages WHERE account_id = ? AND folder = ? ORDER BY received_at DESC, id DESC LIMIT " . max(1, min(200, $limit)));
        $stmt->execute([$accountId, $folder]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function message(int $id, int $accountId, bool $showRemoteImages = false): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM mailbox_messages WHERE id = ? AND account_id = ? LIMIT 1');
        $stmt->execute([$id, $accountId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }
        $this->db->prepare('UPDATE mailbox_messages SET is_read_local = 1 WHERE id = ?')->execute([$id]);
        $attachmentStmt = $this->db->prepare('SELECT id, file_name, mime_type, file_size FROM mailbox_attachments WHERE message_id = ? AND is_inline = 0 ORDER BY id');
        $attachmentStmt->execute([$id]);
        $row['attachments'] = $attachmentStmt->fetchAll(PDO::FETCH_ASSOC);
        $inlineStmt = $this->db->prepare('SELECT id, content_id, mime_type, file_size FROM mailbox_attachments WHERE message_id = ? AND is_inline = 1 AND content_id IS NOT NULL ORDER BY id');
        $inlineStmt->execute([$id]);
        $row['inline_attachments'] = $inlineStmt->fetchAll(PDO::FETCH_ASSOC);
        $remoteImageCount = 0;
        $row['safe_html'] = $this->sanitizeHtml((string) $row['body_html'], $row['inline_attachments'], $showRemoteImages, $remoteImageCount);
        $row['remote_image_count'] = $remoteImageCount;
        return $row;
    }

    public function syncAccount(int $accountId, int $maxNewMessages = 100): int
    {
        if (!function_exists('imap_open')) {
            throw new RuntimeException('Sunucuda PHP IMAP eklentisi etkin değil.');
        }
        $account = $this->accountCredentials($accountId);
        $this->validateTlsCertificate($account['imap_host'], (int) $account['imap_port']);
        // Eski c-client kendi CA deposunu kullandığı için doğrulamayı PHP/OpenSSL ile
        // yukarıda zorunlu tutup IMAP katmanındaki ikinci kontrolü devre dışı bırakıyoruz.
        $mailbox = sprintf('{%s:%d/imap/%s/novalidate-cert}INBOX', $account['imap_host'], (int) $account['imap_port'], $account['imap_encryption'] === 'ssl' ? 'ssl' : 'tls');
        $stream = @imap_open($mailbox, $account['mail_address'], $account['password'], OP_READONLY, 1);
        if (!$stream) {
            $error = implode('; ', imap_errors() ?: ['IMAP bağlantısı kurulamadı.']);
            $this->saveSyncState($accountId, $error);
            throw new RuntimeException($error);
        }

        $status = imap_status($stream, $mailbox, SA_UIDVALIDITY);
        $uidValidity = (int) ($status->uidvalidity ?? 0);
        $uids = imap_search($stream, 'ALL', SE_UID) ?: [];
        // En yeni mesajları önce işle; mevcut kayıtları atlayarak her çalışmada
        // geçmişte henüz alınmamış mesajlara doğru ilerle. Böylece ilk 200 sınırı
        // nedeniyle eski maillerin kalıcı biçimde dışarıda kalması engellenir.
        rsort($uids, SORT_NUMERIC);
        $maxNewMessages = max(1, min(500, $maxNewMessages));
        $inserted = 0;
        foreach ($uids as $uid) {
            $exists = $this->db->prepare("SELECT id, has_attachments, attachments_synced_at, body_html FROM mailbox_messages WHERE account_id = ? AND folder = 'inbox' AND uid_validity = ? AND imap_uid = ? LIMIT 1");
            $exists->execute([$accountId, $uidValidity, (int) $uid]);
            $existingMessage = $exists->fetch(PDO::FETCH_ASSOC);
            if ($existingMessage) {
                if (empty($existingMessage['attachments_synced_at']) && (!empty($existingMessage['has_attachments']) || stripos((string) $existingMessage['body_html'], 'cid:') !== false)) {
                    $body = $this->extractBody($stream, (int) $uid);
                    $this->saveAttachments((int) $existingMessage['id'], $body['attachments']);
                }
                continue;
            }
            $overview = imap_fetch_overview($stream, (string) $uid, FT_UID)[0] ?? null;
            if (!$overview) {
                continue;
            }
            $header = imap_headerinfo($stream, imap_msgno($stream, (int) $uid));
            $from = $header->from[0] ?? null;
            $fromAddress = $from ? (($from->mailbox ?? '') . '@' . ($from->host ?? '')) : '';
            $body = $this->extractBody($stream, (int) $uid);
            $stmt = $this->db->prepare("INSERT IGNORE INTO mailbox_messages (account_id, folder, imap_uid, uid_validity, message_id, from_address, from_name, to_addresses, subject, body_html, body_text, has_attachments, received_at) VALUES (?, 'inbox', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$accountId, (int) $uid, $uidValidity, (string) ($overview->message_id ?? ''), $fromAddress, $this->decode((string) ($from->personal ?? '')), $this->addresses($header->to ?? []), $this->decode((string) ($overview->subject ?? '(Konu yok)')), $body['html'], $body['text'], $body['attachments'] ? 1 : 0, date('Y-m-d H:i:s', (int) ($overview->udate ?? time()))]);
            $wasInserted = $stmt->rowCount() > 0;
            if ($wasInserted) {
                $this->saveAttachments((int) $this->db->lastInsertId(), $body['attachments']);
                $inserted++;
                if ($inserted >= $maxNewMessages) break;
            }
        }
        imap_close($stream);
        $this->saveSyncState($accountId, null);
        return $inserted;
    }

    public function send(int $accountId, string $to, string $subject, string $body): void
    {
        $account = $this->accountCredentials($accountId);
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Geçerli bir alıcı e-posta adresi giriniz.');
        }
        $mailer = get_configured_mailer($account['mail_address']);
        if (!empty($account['smtp_host'])) {
            $mailer->Host = $account['smtp_host'];
            $mailer->Port = (int) $account['smtp_port'];
        }
        $mailer->addAddress($to);
        $mailer->Subject = $subject;
        $mailer->Body = nl2br(htmlspecialchars($body, ENT_QUOTES, 'UTF-8'));
        $mailer->AltBody = $body;
        $mailer->send();
        $stmt = $this->db->prepare("INSERT INTO mailbox_messages (account_id, folder, message_id, from_address, to_addresses, subject, body_html, body_text, received_at, is_read_local) VALUES (?, 'sent', ?, ?, ?, ?, ?, ?, NOW(), 1)");
        $stmt->execute([$accountId, $mailer->getLastMessageID(), $account['mail_address'], $to, $subject, $mailer->Body, $body]);
    }

    private function accountCredentials(int $id): array
    {
        $stmt = $this->db->prepare('SELECT * FROM mail_accounts WHERE id = ? AND (sync_enabled = 1 OR account_type = 1) LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            throw new RuntimeException('Aktif mail hesabı bulunamadı.');
        }
        $row['imap_host'] = trim((string) ($row['imap_host'] ?: 'mail.guzel.net.tr'));
        $row['imap_port'] = (int) ($row['imap_port'] ?: 993);
        $row['imap_encryption'] = (string) ($row['imap_encryption'] ?: 'ssl');
        $row['smtp_host'] = trim((string) ($row['smtp_host'] ?: ($row['imap_host'] ?: set('mail_host'))));
        $row['smtp_port'] = (int) ($row['smtp_port'] ?: 465);
        $row['password'] = (string) ($row['mail_password'] ?: set('mail_password'));
        if ($row['password'] === '') {
            throw new RuntimeException('Mail hesabı parolası tanımlı değil.');
        }
        return $row;
    }

    private function saveSyncState(int $id, ?string $error): void
    {
        $stmt = $this->db->prepare('UPDATE mail_accounts SET last_sync_at = NOW(), last_sync_error = ? WHERE id = ?');
        $stmt->execute([$error, $id]);
    }

    private function validateTlsCertificate(string $host, int $port): void
    {
        $sslOptions = [
            'verify_peer' => true,
            'verify_peer_name' => true,
            'peer_name' => $host,
            'SNI_enabled' => true,
        ];

        // Dağıtıma özel tek bir CA yolu kullanmak paylaşımlı hostinglerde bağlantıyı
        // bozabilir. Önce PHP/OpenSSL ayarlarını, ardından yaygın sistem yollarını dene.
        $locations = function_exists('openssl_get_cert_locations') ? openssl_get_cert_locations() : [];
        $caFiles = [
            getenv('SSL_CERT_FILE') ?: '',
            (string) ($locations['ini_cafile'] ?? ''),
            (string) ($locations['default_cert_file'] ?? ''),
            '/etc/ssl/certs/ca-certificates.crt',
            '/etc/pki/tls/certs/ca-bundle.crt',
            '/etc/ssl/ca-bundle.pem',
            '/usr/local/share/certs/ca-root-nss.crt',
        ];
        foreach (array_unique(array_filter($caFiles)) as $caFile) {
            if (is_readable($caFile)) {
                $sslOptions['cafile'] = $caFile;
                break;
            }
        }

        $caPaths = [
            getenv('SSL_CERT_DIR') ?: '',
            (string) ($locations['ini_capath'] ?? ''),
            (string) ($locations['default_cert_dir'] ?? ''),
        ];
        foreach (array_unique(array_filter($caPaths)) as $caPath) {
            if (is_dir($caPath) && is_readable($caPath)) {
                $sslOptions['capath'] = $caPath;
                break;
            }
        }

        $context = stream_context_create(['ssl' => $sslOptions]);
        $socket = @stream_socket_client('ssl://' . $host . ':' . $port, $errorNo, $errorText, 10, STREAM_CLIENT_CONNECT, $context);
        if (!$socket) {
            throw new RuntimeException('Mail sunucusuna güvenli bağlantı kurulamadı. Hosting CA deposu ve 993 portu kontrol edilmeli: ' . ($errorText ?: (string) $errorNo));
        }
        fclose($socket);
    }

    private function decode(string $value): string
    {
        $parts = imap_mime_header_decode($value);
        $text = '';
        foreach ($parts as $part) {
            $charset = strtoupper((string) $part->charset);
            $piece = (string) $part->text;
            $text .= ($charset && $charset !== 'DEFAULT' && $charset !== 'UTF-8') ? (mb_convert_encoding($piece, 'UTF-8', $charset) ?: $piece) : $piece;
        }
        return trim($text);
    }

    private function addresses(array $addresses): string
    {
        return implode(', ', array_filter(array_map(static fn($a) => (($a->mailbox ?? '') . '@' . ($a->host ?? '')), $addresses)));
    }

    private function extractBody($stream, int $uid): array
    {
        $structure = imap_fetchstructure($stream, $uid, FT_UID);
        $result = ['html' => '', 'text' => '', 'attachments' => []];
        $walk = function ($part, string $number) use (&$walk, &$result, $stream, $uid): void {
            if (!empty($part->parts)) {
                foreach ($part->parts as $index => $child) {
                    $walk($child, $number === '' ? (string) ($index + 1) : $number . '.' . ($index + 1));
                }
                return;
            }
            $raw = imap_fetchbody($stream, $uid, $number ?: '1', FT_UID | FT_PEEK);
            if ((int) ($part->encoding ?? 0) === 3) $raw = base64_decode($raw) ?: '';
            if ((int) ($part->encoding ?? 0) === 4) $raw = quoted_printable_decode($raw);
            $disposition = strtolower((string) ($part->disposition ?? ''));
            $fileName = '';
            foreach (array_merge($part->dparameters ?? [], $part->parameters ?? []) as $parameter) {
                if (in_array(strtolower((string) $parameter->attribute), ['filename', 'name'], true)) {
                    $fileName = $this->decode((string) $parameter->value);
                    break;
                }
            }
            $contentId = trim((string) ($part->id ?? ''), "<> \t\r\n");
            $isInline = $disposition === 'inline' || ($contentId !== '' && $disposition !== 'attachment');
            if ($disposition === 'attachment' || $fileName !== '' || $isInline) {
                $mimeType = strtolower((string) ($part->type ?? 3)) === '0' ? 'text' : '';
                $primaryTypes = ['text', 'multipart', 'message', 'application', 'audio', 'image', 'video', 'other'];
                $mimeType = ($primaryTypes[(int) ($part->type ?? 7)] ?? 'application') . '/' . strtolower((string) ($part->subtype ?? 'octet-stream'));
                $result['attachments'][] = ['part_number' => $number ?: '1', 'file_name' => $fileName ?: ('inline-' . ($number ?: '1')), 'mime_type' => $mimeType, 'content_id' => $contentId, 'is_inline' => $isInline, 'content' => $raw];
                return;
            }
            $charset = 'UTF-8';
            foreach (($part->parameters ?? []) as $parameter) if (strtolower((string) $parameter->attribute) === 'charset') $charset = (string) $parameter->value;
            if (strtoupper($charset) !== 'UTF-8') $raw = mb_convert_encoding($raw, 'UTF-8', $charset) ?: $raw;
            $subtype = strtoupper((string) ($part->subtype ?? 'PLAIN'));
            if ($subtype === 'HTML') $result['html'] .= $raw; else if ($subtype === 'PLAIN') $result['text'] .= $raw;
        };
        $walk($structure, '');
        return $result;
    }

    private function saveAttachments(int $messageId, array $attachments): void
    {
        $existsStmt = $this->db->prepare('SELECT id FROM mailbox_attachments WHERE message_id = ? AND part_number = ? LIMIT 1');
        $updateStmt = $this->db->prepare('UPDATE mailbox_attachments SET content_id = ?, is_inline = ? WHERE id = ?');
        $stmt = $this->db->prepare('INSERT INTO mailbox_attachments (message_id, part_number, file_name, mime_type, content_id, is_inline, file_size, content) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $chunkStmt = $this->db->prepare('INSERT INTO mailbox_attachment_chunks (attachment_id, chunk_index, content) VALUES (?, ?, ?)');
        foreach ($attachments as $attachment) {
            $content = (string) ($attachment['content'] ?? '');
            if ($content === '' || strlen($content) > 25 * 1024 * 1024) continue;
            $partNumber = (string) $attachment['part_number'];
            $existsStmt->execute([$messageId, $partNumber]);
            $existingAttachmentId = (int) $existsStmt->fetchColumn();
            $contentId = trim((string) ($attachment['content_id'] ?? ''));
            $isInline = !empty($attachment['is_inline']) ? 1 : 0;
            if ($existingAttachmentId > 0) {
                $updateStmt->execute([$contentId !== '' ? $contentId : null, $isInline, $existingAttachmentId]);
                continue;
            }
            $fileName = preg_replace('~[\x00-\x1F\x7F\\/]+~u', '_', (string) ($attachment['file_name'] ?? 'ek')) ?: 'ek';
            $stmt->bindValue(1, $messageId, PDO::PARAM_INT);
            $stmt->bindValue(2, $partNumber);
            $stmt->bindValue(3, mb_substr($fileName, 0, 500, 'UTF-8'));
            $stmt->bindValue(4, (string) ($attachment['mime_type'] ?? 'application/octet-stream'));
            $stmt->bindValue(5, $contentId !== '' ? $contentId : null);
            $stmt->bindValue(6, $isInline, PDO::PARAM_INT);
            $stmt->bindValue(7, strlen($content), PDO::PARAM_INT);
            $stmt->bindValue(8, strlen($content) <= 512 * 1024 ? $content : '', PDO::PARAM_LOB);
            $stmt->execute();
            $attachmentId = (int) $this->db->lastInsertId();
            if (strlen($content) > 512 * 1024) {
                foreach (str_split($content, 512 * 1024) as $index => $chunk) {
                    $chunkStmt->bindValue(1, $attachmentId, PDO::PARAM_INT);
                    $chunkStmt->bindValue(2, $index, PDO::PARAM_INT);
                    $chunkStmt->bindValue(3, $chunk, PDO::PARAM_LOB);
                    $chunkStmt->execute();
                }
            }
        }
        $countStmt = $this->db->prepare('SELECT COUNT(*) FROM mailbox_attachments WHERE message_id = ? AND is_inline = 0');
        $countStmt->execute([$messageId]);
        $actualAttachmentCount = (int) $countStmt->fetchColumn();
        $this->db->prepare('UPDATE mailbox_messages SET has_attachments = ?, attachments_synced_at = NOW() WHERE id = ?')
            ->execute([$actualAttachmentCount > 0 ? 1 : 0, $messageId]);
    }

    private function sanitizeHtml(string $html, array $inlineAttachments, bool $showRemoteImages = false, ?int &$remoteImageCount = null): string
    {
        if (trim($html) === '' || !class_exists('DOMDocument')) return '';
        $remoteImageCount = 0;
        // Gövde extractBody aşamasında UTF-8'e çevrilir; Outlook'un HTML içinde
        // bıraktığı eski iso-8859-* bildirimi DOMDocument'in metni ikinci kez
        // dönüştürmesine ve Türkçe karakterleri bozmasına izin verilmemelidir.
        $html = preg_replace('/charset\s*=\s*["\']?[^\s;"\'>]+/iu', 'charset=UTF-8', $html) ?: $html;
        $cidMap = [];
        foreach ($inlineAttachments as $attachment) {
            $cid = strtolower(trim((string) ($attachment['content_id'] ?? ''), "<> \t\r\n"));
            $mimeType = strtolower((string) ($attachment['mime_type'] ?? ''));
            if ($cid !== '' && in_array($mimeType, ['image/png','image/jpeg','image/gif','image/webp'], true) && (int) ($attachment['file_size'] ?? 0) <= 5 * 1024 * 1024) {
                $content = $this->attachmentContent((int) $attachment['id']);
                if ($content !== '' && @getimagesizefromstring($content) !== false) {
                    $cidMap[$cid] = 'data:' . $mimeType . ';base64,' . base64_encode($content);
                }
            }
        }
        $previous = libxml_use_internal_errors(true);
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->loadHTML('<?xml encoding="UTF-8"><div id="mail-safe-root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        foreach (['script','style','iframe','frame','form','input','button','object','embed','link','meta','base'] as $tag) {
            while (($nodes = $dom->getElementsByTagName($tag))->length) $nodes->item(0)->parentNode->removeChild($nodes->item(0));
        }
        $xpath = new \DOMXPath($dom);
        foreach ($xpath->query('//comment()') as $comment) $comment->parentNode->removeChild($comment);
        foreach ($xpath->query('//*[@*]') as $node) {
            $remove = [];
            foreach ($node->attributes as $attribute) {
                $name = strtolower($attribute->name);
                $value = trim($attribute->value);
                if (strpos($name, 'on') === 0 || in_array($name, ['srcset','background','action','formaction'], true)) $remove[] = $name;
                if ($name !== 'src' && stripos($value, 'cid:') !== false) $remove[] = $name;
                if ($name === 'style' && preg_match('/url\s*\(|expression\s*\(|javascript:/i', $value)) $remove[] = $name;
                // Oltalama bağlantıları mail okuma alanından doğrudan açılamaz.
                if ($name === 'href') {
                    $node->setAttribute('title', 'Güvenlik nedeniyle bağlantı devre dışı');
                    $remove[] = $name;
                }
            }
            foreach ($remove as $name) $node->removeAttribute($name);
        }
        foreach ($dom->getElementsByTagName('img') as $image) {
            $src = trim($image->getAttribute('src'));
            if (stripos($src, 'cid:') === 0) {
                $cid = strtolower(rawurldecode(substr($src, 4)));
                if (isset($cidMap[$cid])) $image->setAttribute('src', $cidMap[$cid]); else $image->removeAttribute('src');
            } elseif (preg_match('~^https?://~i', $src)) {
                $remoteImageCount++;
                if ($showRemoteImages) {
                    try {
                        $remoteImage = MailRemoteImageService::fetchUrl($src);
                        $image->setAttribute('src', 'data:' . $remoteImage['mime_type'] . ';base64,' . base64_encode($remoteImage['content']));
                    } catch (\Throwable $e) {
                        $image->removeAttribute('src');
                    }
                } else {
                    $image->removeAttribute('src');
                }
            } else {
                $image->removeAttribute('src');
            }
            $image->removeAttribute('srcset');
            $image->setAttribute('loading', 'lazy');
            $image->setAttribute('referrerpolicy', 'no-referrer');
        }
        $root = $dom->getElementById('mail-safe-root');
        $output = '';
        if ($root) foreach ($root->childNodes as $child) $output .= $dom->saveHTML($child);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        return $output;
    }

    private function attachmentContent(int $attachmentId): string
    {
        $stmt = $this->db->prepare('SELECT content FROM mailbox_attachments WHERE id = ? LIMIT 1');
        $stmt->execute([$attachmentId]);
        $content = (string) ($stmt->fetchColumn() ?: '');
        if ($content !== '') return $content;
        $chunks = $this->db->prepare('SELECT content FROM mailbox_attachment_chunks WHERE attachment_id = ? ORDER BY chunk_index');
        $chunks->execute([$attachmentId]);
        while (($chunk = $chunks->fetchColumn()) !== false) $content .= $chunk;
        return $content;
    }
}
