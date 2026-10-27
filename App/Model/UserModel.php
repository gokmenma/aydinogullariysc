<?php

declare(strict_types=1);

namespace App\Model;

use PDO;

class UserModel extends BaseModel
{
    public function __construct()
    {
        parent::__construct('users');
    }

    public function findByUsernameOrEmail(string $identity): ?object
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE (LOWER(email) = LOWER(?) OR LOWER(username) = LOWER(?)) AND statu = 1 LIMIT 1');
        $stmt->execute([$identity, $identity]);
        $row = $stmt->fetch(PDO::FETCH_OBJ);
        return $row ?: null;
    }

    public function verifyPassword(object $user, string $plainPassword): bool
    {
        $storedHash = (string) ($user->password ?? '');
        $isLegacy = (bool) preg_match('/^[a-f0-9]{32}$/i', $storedHash);
        if ($isLegacy) {
            return hash_equals(strtolower($storedHash), md5(md5(md5($plainPassword))));
        }
        return password_verify($plainPassword, $storedHash);
    }

    public function rehashIfNeeded(object $user, string $plainPassword): void
    {
        $storedHash = (string) ($user->password ?? '');
        $isLegacy = (bool) preg_match('/^[a-f0-9]{32}$/i', $storedHash);
        if ($isLegacy || password_needs_rehash($storedHash, PASSWORD_DEFAULT)) {
            $newHash = password_hash($plainPassword, PASSWORD_DEFAULT);
            $stmt = $this->db->prepare('UPDATE users SET password = ? WHERE id = ?');
            $stmt->execute([$newHash, $user->id]);
        }
    }

    public function resetPassword(int $userId, string $newPasswordHash): bool
    {
        $stmt = $this->db->prepare('UPDATE users SET password = ? WHERE id = ?');
        return $stmt->execute([$newPasswordHash, $userId]);
    }

    /**
     * Ekip / Personel KPI istatistiklerini hesaplar.
     */
    public function getTeamKpis(): array
    {
        $stmt = $this->db->query("
            SELECT 
                COUNT(*) as total_count,
                SUM(CASE WHEN statu = 1 THEN 1 ELSE 0 END) as active_count,
                SUM(CASE WHEN statu = 0 THEN 1 ELSE 0 END) as passive_count,
                SUM(CASE WHEN permission IN (1, 13) THEN 1 ELSE 0 END) as admin_count
            FROM users
        ");
        $res = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : [];
        $total = (int) ($res['total_count'] ?? 0);
        $active = (int) ($res['active_count'] ?? 0);
        $passive = (int) ($res['passive_count'] ?? 0);
        $admin = (int) ($res['admin_count'] ?? 0);
        $rate = $total > 0 ? round(($active / $total) * 100, 1) : 0.0;

        return [
            'total_count' => $total,
            'active_count' => $active,
            'passive_count' => $passive,
            'admin_count' => $admin,
            'active_rate' => $rate,
        ];
    }

    /**
     * Sistemdeki aktif pozisyon / rol tanımlarını getirir.
     */
    public function getUserRoles(): array
    {
        $stmt = $this->db->query("SELECT id, roleName, roleDescription, isActive FROM userroles ORDER BY id ASC");
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    /**
     * Ekip listesini filtre ve arama kriterlerine göre döner.
     */
    public function getTeamList(array $filters = []): array
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $status = (string) ($filters['status'] ?? 'all');
        $roleId = isset($filters['role_id']) ? (int) $filters['role_id'] : 0;

        $sql = "
            SELECT 
                u.id, 
                u.username, 
                u.Unvan, 
                u.email, 
                u.gsm, 
                u.meslek, 
                u.yetkinlikno, 
                u.ekipnetno, 
                u.odasicilno, 
                u.regdate, 
                u.giristarihi, 
                u.dogumtarihi, 
                u.cikistarihi, 
                u.permission, 
                u.statu, 
                u.avatar_link, 
                ur.roleName, 
                ur.roleDescription
            FROM users u
            LEFT JOIN userroles ur ON ur.id = u.permission
            WHERE 1=1
        ";
        $params = [];

        if ($status === 'active' || $status === '1') {
            $sql .= " AND u.statu = 1";
        } elseif ($status === 'passive' || $status === '0') {
            $sql .= " AND u.statu = 0";
        } elseif ($status === 'admin') {
            $sql .= " AND u.permission IN (1, 13)";
        }

        if ($roleId > 0) {
            $sql .= " AND u.permission = ?";
            $params[] = $roleId;
        }

        if ($search !== '') {
            $sql .= " AND (
                LOWER(u.username) LIKE ? 
                OR LOWER(u.email) LIKE ? 
                OR LOWER(u.Unvan) LIKE ? 
                OR LOWER(u.meslek) LIKE ? 
                OR u.gsm LIKE ? 
                OR LOWER(ur.roleName) LIKE ? 
                OR u.yetkinlikno LIKE ? 
                OR u.ekipnetno LIKE ?
            )";
            $like = '%' . mb_strtolower($search, 'UTF-8') . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = '%' . $search . '%';
            $params[] = $like;
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }

        $sql .= " ORDER BY u.statu DESC, u.id ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Tekil ekip üyesi detayını getirir.
     */
    public function getTeamMember(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT 
                u.id, 
                u.username, 
                u.Unvan, 
                u.email, 
                u.gsm, 
                u.meslek, 
                u.yetkinlikno, 
                u.ekipnetno, 
                u.odasicilno, 
                u.regdate, 
                u.giristarihi, 
                u.dogumtarihi, 
                u.cikistarihi, 
                u.permission, 
                u.statu, 
                u.avatar_link, 
                ur.roleName, 
                ur.roleDescription
            FROM users u
            LEFT JOIN userroles ur ON ur.id = u.permission
            WHERE u.id = ?
            LIMIT 1
        ");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Yeni ekip üyesi oluşturur.
     */
    public function createTeamMember(array $data, int $actorId = 0): int
    {
        $username = trim((string) ($data['username'] ?? ''));
        $email = trim((string) ($data['email'] ?? ''));
        $password = (string) ($data['password'] ?? '');

        if ($username === '') {
            throw new \InvalidArgumentException('Ekip üyesi adı ve soyadı zorunludur.');
        }
        if ($email === '') {
            throw new \InvalidArgumentException('E-posta adresi zorunludur.');
        }
        if ($password === '') {
            throw new \InvalidArgumentException('Şifre alanı zorunludur.');
        }

        // E-posta mükerrerlik kontrolü
        $check = $this->db->prepare("SELECT id FROM users WHERE LOWER(email) = LOWER(?) LIMIT 1");
        $check->execute([$email]);
        if ($check->fetch()) {
            throw new \RuntimeException('Bu e-posta adresiyle kayıtlı başka bir ekip üyesi zaten mevcut.');
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $unvan = trim((string) ($data['unvan'] ?? ''));
        $gsm = trim((string) ($data['gsm'] ?? ''));
        $permission = (int) ($data['permission'] ?? 10);
        $meslek = trim((string) ($data['meslek'] ?? ''));
        $odasicilno = trim((string) ($data['odasicilno'] ?? ''));
        $yetkinlikno = trim((string) ($data['yetkinlikno'] ?? ''));
        $ekipnetno = trim((string) ($data['ekipnetno'] ?? ''));
        $giristarihi = trim((string) ($data['giristarihi'] ?? ''));
        $dogumtarihi = trim((string) ($data['dogumtarihi'] ?? ''));
        $statu = isset($data['statu']) ? (int) $data['statu'] : 1;
        $today = date('Y-m-d');

        $stmt = $this->db->prepare("
            INSERT INTO users (
                username, password, Unvan, email, gsm, perm, permission, 
                regdate, creativer, meslek, odasicilno, yetkinlikno, ekipnetno, 
                giristarihi, dogumtarihi, statu
            ) VALUES (
                ?, ?, ?, ?, ?, 1, ?, 
                ?, ?, ?, ?, ?, ?, 
                ?, ?, ?
            )
        ");
        $stmt->execute([
            $username,
            $passwordHash,
            $unvan,
            $email,
            $gsm,
            $permission,
            $today,
            $actorId,
            $meslek,
            $odasicilno,
            $yetkinlikno,
            $ekipnetno,
            $giristarihi,
            $dogumtarihi,
            $statu
        ]);

        $newId = (int) $this->db->lastInsertId();

        try {
            if (function_exists('getLogger')) {
                \getLogger('Kullanıcılar')->info("Yeni ekip üyesi oluşturuldu: {$username} (#{$newId})", [
                    'actor_id' => $actorId,
                    'user_id' => $newId,
                    'email' => $email,
                ]);
            }
        } catch (\Throwable) {}

        return $newId;
    }

    /**
     * Ekip üyesi bilgilerini günceller.
     */
    public function updateTeamMember(int $id, array $data, int $actorId = 0): bool
    {
        if ($id <= 0) {
            throw new \InvalidArgumentException('Geçersiz kullanıcı ID.');
        }

        $existing = $this->getTeamMember($id);
        if (!$existing) {
            throw new \RuntimeException('Ekip üyesi bulunamadı.');
        }

        $username = trim((string) ($data['username'] ?? $existing['username']));
        $email = trim((string) ($data['email'] ?? $existing['email']));
        if ($username === '') {
            throw new \InvalidArgumentException('Ekip üyesi adı ve soyadı zorunludur.');
        }
        if ($email === '') {
            throw new \InvalidArgumentException('E-posta adresi zorunludur.');
        }

        // E-posta mükerrerlik kontrolü (kendi ID'si hariç)
        $check = $this->db->prepare("SELECT id FROM users WHERE LOWER(email) = LOWER(?) AND id != ? LIMIT 1");
        $check->execute([$email, $id]);
        if ($check->fetch()) {
            throw new \RuntimeException('Bu e-posta adresi başka bir ekip üyesi tarafından kullanılıyor.');
        }

        $unvan = trim((string) ($data['unvan'] ?? $existing['Unvan']));
        $gsm = trim((string) ($data['gsm'] ?? $existing['gsm']));
        $permission = isset($data['permission']) ? (int) $data['permission'] : (int) $existing['permission'];
        $meslek = trim((string) ($data['meslek'] ?? $existing['meslek']));
        $odasicilno = trim((string) ($data['odasicilno'] ?? $existing['odasicilno']));
        $yetkinlikno = trim((string) ($data['yetkinlikno'] ?? $existing['yetkinlikno']));
        $ekipnetno = trim((string) ($data['ekipnetno'] ?? $existing['ekipnetno']));
        $giristarihi = trim((string) ($data['giristarihi'] ?? $existing['giristarihi']));
        $dogumtarihi = trim((string) ($data['dogumtarihi'] ?? $existing['dogumtarihi']));
        $cikistarihi = trim((string) ($data['cikistarihi'] ?? $existing['cikistarihi']));
        $statu = isset($data['statu']) ? (int) $data['statu'] : (int) $existing['statu'];

        $password = trim((string) ($data['password'] ?? ''));
        if ($password !== '' && $password !== '******') {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $this->db->prepare("
                UPDATE users SET 
                    username = ?, 
                    password = ?, 
                    Unvan = ?, 
                    email = ?, 
                    gsm = ?, 
                    permission = ?, 
                    meslek = ?, 
                    odasicilno = ?, 
                    yetkinlikno = ?, 
                    ekipnetno = ?, 
                    giristarihi = ?, 
                    dogumtarihi = ?, 
                    cikistarihi = ?, 
                    statu = ?
                WHERE id = ?
            ");
            $ok = $stmt->execute([
                $username,
                $passwordHash,
                $unvan,
                $email,
                $gsm,
                $permission,
                $meslek,
                $odasicilno,
                $yetkinlikno,
                $ekipnetno,
                $giristarihi,
                $dogumtarihi,
                $cikistarihi,
                $statu,
                $id
            ]);
        } else {
            $stmt = $this->db->prepare("
                UPDATE users SET 
                    username = ?, 
                    Unvan = ?, 
                    email = ?, 
                    gsm = ?, 
                    permission = ?, 
                    meslek = ?, 
                    odasicilno = ?, 
                    yetkinlikno = ?, 
                    ekipnetno = ?, 
                    giristarihi = ?, 
                    dogumtarihi = ?, 
                    cikistarihi = ?, 
                    statu = ?
                WHERE id = ?
            ");
            $ok = $stmt->execute([
                $username,
                $unvan,
                $email,
                $gsm,
                $permission,
                $meslek,
                $odasicilno,
                $yetkinlikno,
                $ekipnetno,
                $giristarihi,
                $dogumtarihi,
                $cikistarihi,
                $statu,
                $id
            ]);
        }

        try {
            if (function_exists('getLogger')) {
                \getLogger('Kullanıcılar')->info("Ekip üyesi güncellendi: {$username} (#{$id})", [
                    'actor_id' => $actorId,
                    'user_id' => $id,
                ]);
            }
        } catch (\Throwable) {}

        return (bool) $ok;
    }

    /**
     * Ekip üyesi durumunu (aktif/pasif) günceller.
     */
    public function updateTeamMemberStatus(int $id, int $newStatus, int $actorId = 0): bool
    {
        if ($id <= 0) {
            throw new \InvalidArgumentException('Geçersiz kullanıcı ID.');
        }
        if ($id === 1) {
            throw new \RuntimeException('Ana yönetici hesabının durumu değiştirilemez.');
        }
        if ($actorId > 0 && $id === $actorId) {
            throw new \RuntimeException('Kendi hesabınızın durumunu değiştiremezsiniz.');
        }

        $stmt = $this->db->prepare("UPDATE users SET statu = ? WHERE id = ?");
        $ok = $stmt->execute([$newStatus === 1 ? 1 : 0, $id]);

        try {
            if (function_exists('getLogger')) {
                $statusText = $newStatus === 1 ? 'Aktif yapıldı' : 'Pasife alındı';
                \getLogger('Kullanıcılar')->info("Ekip üyesi durumu güncellendi: #{$id} - {$statusText}", [
                    'actor_id' => $actorId,
                    'user_id' => $id,
                    'new_status' => $newStatus,
                ]);
            }
        } catch (\Throwable) {}

        return (bool) $ok;
    }

    /**
     * Ekip üyesini sistemden kaldırır.
     */
    public function deleteTeamMember(int $id, int $actorId = 0): bool
    {
        if ($id <= 0) {
            throw new \InvalidArgumentException('Geçersiz kullanıcı ID.');
        }
        if ($id === 1) {
            throw new \RuntimeException('Ana yönetici hesabı silinemez.');
        }
        if ($actorId > 0 && $id === $actorId) {
            throw new \RuntimeException('Kendi hesabınızı silemezsiniz.');
        }

        $user = $this->getTeamMember($id);
        if (!$user) {
            throw new \RuntimeException('Silinecek ekip üyesi bulunamadı.');
        }

        // Bağlı kayıt kontrolü: Eğer foreign key engeli varsa pasife almasını isteyelim
        $checkFk = $this->db->prepare("SELECT COUNT(*) FROM kesifler WHERE kayit_yapan = ? OR silen_kullanici = ? OR guncelleyen_kullanici = ?");
        $checkFk->execute([$id, $id, $id]);
        $hasKesif = (int) $checkFk->fetchColumn();

        if ($hasKesif > 0) {
            // Pasife alalım
            $this->updateTeamMemberStatus($id, 0, $actorId);
            throw new \RuntimeException('Bu personele ait keşif kayıtları bulunduğundan fiziksel olarak silinemez; hesap güvenle PASİFE alındı.');
        }

        $stmt = $this->db->prepare("DELETE FROM users WHERE id = ?");
        $ok = $stmt->execute([$id]);

        try {
            if (function_exists('getLogger')) {
                \getLogger('Kullanıcılar')->info("Ekip üyesi silindi: {$user['username']} (#{$id})", [
                    'actor_id' => $actorId,
                    'deleted_user_id' => $id,
                ]);
            }
        } catch (\Throwable) {}

        return (bool) $ok;
    }
}
