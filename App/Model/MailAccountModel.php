<?php
namespace App\Model;

use App\Model\BaseModel;
use PDO;
use Exception;
use PDOException;

class MailAccountModel extends BaseModel
{
    protected $table = 'mail_accounts';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    /**
     * Tüm mail hesaplarını kullanıcı bilgileriyle birlikte getirir.
     */
    public function getAllAccounts()
    {
        try {
            $sql = "SELECT 
                        ma.*,
                        u_mail.username AS mail_user_name,
                        u_mail.Unvan AS mail_user_title,
                        u_creator.username AS creator_name,
                        u_creator.Unvan AS creator_title
                    FROM {$this->table} ma
                    LEFT JOIN users u_mail ON ma.mail_user = u_mail.id
                    LEFT JOIN users u_creator ON ma.creator = u_creator.id
                    ORDER BY ma.id DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("MailAccountModel getAllAccounts Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Aktif kullanıcıları (personel) listeler.
     */
    public function getActiveUsers()
    {
        try {
            $stmt = $this->db->prepare("SELECT id, username, Unvan FROM users WHERE statu = 1 ORDER BY username ASC");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("MailAccountModel getActiveUsers Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Mail hesapları istatistiklerini getirir.
     */
    public function getStats()
    {
        $stats = [
            'total' => 0,
            'general' => 0,
            'user' => 0,
            'last_account' => '-',
            'last_date' => '-'
        ];

        try {
            $sql = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN account_type = 1 THEN 1 ELSE 0 END) as general_count,
                        SUM(CASE WHEN account_type = 2 THEN 1 ELSE 0 END) as user_count
                    FROM {$this->table}";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $counts = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($counts) {
                $stats['total'] = (int)($counts['total'] ?? 0);
                $stats['general'] = (int)($counts['general_count'] ?? 0);
                $stats['user'] = (int)($counts['user_count'] ?? 0);
            }

            // Son eklenen kayıt
            $sqlLast = "SELECT mail_address, create_time FROM {$this->table} ORDER BY id DESC LIMIT 1";
            $stmtLast = $this->db->prepare($sqlLast);
            $stmtLast->execute();
            $lastRow = $stmtLast->fetch(PDO::FETCH_ASSOC);

            if ($lastRow) {
                $stats['last_account'] = $lastRow['mail_address'];
                $stats['last_date'] = $lastRow['create_time'];
            }
        } catch (PDOException $e) {
            error_log("MailAccountModel getStats Error: " . $e->getMessage());
        }

        return $stats;
    }

    /**
     * ID'ye göre tekil mail hesabı getirir.
     */
    public function getAccountById($id)
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = ? LIMIT 1");
            $stmt->execute([(int)$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("MailAccountModel getAccountById Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * E-Posta adresine göre hesap bilgilerini getirir.
     */
    public function getAccountByAddress($email)
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE LOWER(TRIM(mail_address)) = LOWER(TRIM(?)) LIMIT 1");
            $stmt->execute([trim((string)$email)]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("MailAccountModel getAccountByAddress Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Belirli e-posta adresinin sistemde zaten var olup olmadığını kontrol eder.
     */
    public function isEmailExists($email, $excludeId = null)
    {
        try {
            $sql = "SELECT id FROM {$this->table} WHERE LOWER(TRIM(mail_address)) = LOWER(TRIM(?))";
            $params = [$email];

            if ($excludeId !== null && (int)$excludeId > 0) {
                $sql .= " AND id != ?";
                $params[] = (int)$excludeId;
            }

            $sql .= " LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return (bool)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("MailAccountModel isEmailExists Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Yeni mail hesabı ekler.
     */
    public function createAccount(array $data)
    {
        try {
            $mailAddress  = trim($data['mail_address'] ?? '');
            $mailPassword = isset($data['mail_password']) && trim($data['mail_password']) !== '' ? trim($data['mail_password']) : null;
            $description  = trim($data['description'] ?? '');
            $accountType  = (int)($data['account_type'] ?? 1);
            $mailUser     = ($accountType == 2) ? (int)($data['mail_user'] ?? 1) : 1;
            $creator      = (int)($data['creator'] ?? ($_SESSION['lid'] ?? 1));
            $createTime   = date('Y-m-d H:i:s');

            $sql = "INSERT INTO {$this->table} (mail_address, mail_password, description, mail_user, account_type, creator, create_time) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                $mailAddress,
                $mailPassword,
                $description,
                $mailUser,
                $accountType,
                $creator,
                $createTime
            ]);

            if ($result) {
                $insertId = (int)$this->db->lastInsertId();
                if (function_exists('audit_log')) {
                    audit_log('create', 'send-mail-accounts', "Yeni mail hesabı eklendi: {$mailAddress}", $this->table, $insertId);
                }
                return $insertId;
            }
            return false;
        } catch (PDOException $e) {
            error_log("MailAccountModel createAccount Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mail hesabını günceller.
     */
    public function updateAccount($id, array $data)
    {
        try {
            $mailAddress = trim($data['mail_address'] ?? '');
            $description = trim($data['description'] ?? '');
            $accountType = (int)($data['account_type'] ?? 1);
            $mailUser    = ($accountType == 2) ? (int)($data['mail_user'] ?? 1) : 1;
            $updateTime  = date('Y-m-d H:i:s');

            // Şifre verilmişse güncelle, boş bırakılmışsa mevcut şifreyi koru
            if (isset($data['mail_password']) && trim($data['mail_password']) !== '') {
                $sql = "UPDATE {$this->table} SET 
                            mail_address = ?, 
                            mail_password = ?,
                            description = ?, 
                            mail_user = ?, 
                            account_type = ?, 
                            update_time = ? 
                        WHERE id = ?";
                $params = [
                    $mailAddress,
                    trim($data['mail_password']),
                    $description,
                    $mailUser,
                    $accountType,
                    $updateTime,
                    (int)$id
                ];
            } else {
                $sql = "UPDATE {$this->table} SET 
                            mail_address = ?, 
                            description = ?, 
                            mail_user = ?, 
                            account_type = ?, 
                            update_time = ? 
                        WHERE id = ?";
                $params = [
                    $mailAddress,
                    $description,
                    $mailUser,
                    $accountType,
                    $updateTime,
                    (int)$id
                ];
            }

            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute($params);

            if ($result) {
                if (function_exists('audit_log')) {
                    audit_log('update', 'send-mail-accounts', "Mail hesabı güncellendi: {$mailAddress} (ID: {$id})", $this->table, (int)$id);
                }
                return true;
            }
            return false;
        } catch (PDOException $e) {
            error_log("MailAccountModel updateAccount Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mail hesabını siler.
     */
    public function deleteAccount($id)
    {
        try {
            $account = $this->getAccountById($id);
            $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = ?");
            $result = $stmt->execute([(int)$id]);

            if ($result) {
                if (function_exists('audit_log')) {
                    $mailName = $account['mail_address'] ?? "ID: {$id}";
                    audit_log('delete', 'send-mail-accounts', "Mail hesabı silindi: {$mailName}", $this->table, (int)$id);
                }
                return true;
            }
            return false;
        } catch (PDOException $e) {
            error_log("MailAccountModel deleteAccount Error: " . $e->getMessage());
            return false;
        }
    }
}
