<?php

namespace App\Routing;

use App\Helper\Security;
use InvalidArgumentException;

final class PilotRouter
{
    /** @var array<string, array{page:string, permissions:array<int, string>, encrypted_id?:bool}> */
    private const ROUTES = [
        'teklifler' => [
            'page' => 'offers/list',
            'permissions' => ['offerview'],
        ],
        'satin-almalar' => [
            'page' => 'purchases',
            // Mevcut satin alma listesi tum oturum acmis kullanicilara acik.
            'permissions' => [],
        ],
        'teklif-duzenle' => [
            'page' => 'offers/offer-manage',
            'permissions' => ['offeredit'],
            'encrypted_id' => true,
        ],
    ];

    /**
     * Temiz URL'yi mevcut sayfa parametrelerine cevirir.
     *
     * @return array{path:string, page:string, permissions:array<int, string>}|null
     */
    public static function resolve(string $requestUri): ?array
    {
        $path = trim((string) parse_url($requestUri, PHP_URL_PATH), '/');
        $routeName = basename($path);
        $route = self::ROUTES[$routeName] ?? null;

        if ($route === null) {
            return null;
        }

        if (!empty($route['encrypted_id'])) {
            $encryptedId = trim((string) ($_GET['id'] ?? ''));
            $decryptedId = $encryptedId !== '' ? Security::decrypt($encryptedId) : false;

            if ($decryptedId === false || !ctype_digit((string) $decryptedId) || (int) $decryptedId < 1) {
                throw new InvalidArgumentException('Geçersiz teklif bağlantısı.');
            }

            $_GET['id'] = (int) $decryptedId;
        }

        $_GET['p'] = $route['page'];

        return [
            'path' => $routeName,
            'page' => $route['page'],
            'permissions' => $route['permissions'],
        ];
    }

    /** @param array<int, string> $permissions */
    public static function isAuthorized(array $permissions): bool
    {
        if ($permissions === []) {
            return true;
        }

        $userId = (int) ($_SESSION['lid'] ?? 0);
        $roleId = (int) ($_SESSION['perm'] ?? 0);
        if (in_array($userId, [1, 12], true) || in_array($roleId, [1, 13], true)) {
            return true;
        }

        foreach ($permissions as $permission) {
            if (permtrue($permission)) {
                return true;
            }
        }

        return false;
    }
}
