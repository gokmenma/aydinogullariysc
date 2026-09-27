<?php

namespace App\Routing;

use App\Helper\Security;
use InvalidArgumentException;

final class Router
{
    /** @var array<string, array{page:string, permissions:array<int, string>, encrypted_id?:bool, query?:array<string, string>}>|null */
    private static ?array $routes = null;

    /** @return array<string, array{page:string, permissions:array<int, string>, encrypted_id?:bool, query?:array<string, string>}> */
    private static function routes(): array
    {
        if (self::$routes !== null) {
            return self::$routes;
        }

        $groups = require __DIR__ . '/routes.php';
        $routes = [];
        foreach ($groups as $groupRoutes) {
            foreach ($groupRoutes as $path => $route) {
                if (array_key_exists($path, $routes)) {
                    throw new \LogicException('Mükerrer rota tanımı: ' . $path);
                }
                $routes[$path] = $route;
            }
        }

        self::$routes = $routes;
        return self::$routes;
    }

    /**
     * Temiz URL'yi mevcut sayfa parametrelerine cevirir.
     *
     * @return array{path:string, page:string, permissions:array<int, string>}|null
     */
    public static function resolve(string $requestUri, string $scriptName = '/index.php'): ?array
    {
        $requestPath = trim((string) parse_url($requestUri, PHP_URL_PATH), '/');
        $basePath = trim(str_replace('\\', '/', dirname($scriptName)), '/.');
        if ($requestPath === $basePath) {
            $routeName = '';
        } elseif ($basePath !== '' && str_starts_with($requestPath, $basePath . '/')) {
            $routeName = substr($requestPath, strlen($basePath) + 1);
        } else {
            $routeName = $requestPath;
        }

        $route = self::routes()[$routeName] ?? null;

        if ($route === null) {
            return null;
        }

        $_GET['p'] = $route['page'];
        foreach (($route['query'] ?? []) as $key => $value) {
            $_GET[$key] = $value;
        }

        return [
            'path' => $routeName,
            'page' => $route['page'],
            'permissions' => $route['permissions'],
        ];
    }

    public static function pathForPage(string $page, array $query = []): ?string
    {
        $fallback = null;
        foreach (self::routes() as $path => $route) {
            if ($route['page'] !== $page) {
                continue;
            }
            if (($route['query'] ?? []) === $query && $path !== '') {
                return $path;
            }
            if ($fallback === null && $path !== '' && empty($route['query'])) {
                $fallback = $path;
            }
        }
        return $fallback;
    }

    public static function pageForPath(string $path): ?string
    {
        return self::routes()[trim($path, '/')]['page'] ?? null;
    }

    public static function rewriteHtml(string $html): string
    {
        return (string)preg_replace_callback(
            '~index\.php\?p=[^"\'\s<>]+~',
            static function (array $match): string {
                $legacyUrl = html_entity_decode($match[0], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $fragment = '';
                if (str_contains($legacyUrl, '#')) {
                    [$legacyUrl, $fragmentValue] = explode('#', $legacyUrl, 2);
                    $fragment = '#' . $fragmentValue;
                }

                $queryString = (string)(parse_url($legacyUrl, PHP_URL_QUERY) ?? '');
                parse_str($queryString, $params);
                $page = (string)($params['p'] ?? '');
                unset($params['p']);

                $encryptedEditRoutes = [
                    'offers/offer-manage' => 'teklif-duzenle',
                    'customers/manage' => 'firma-duzenle',
                    'products/manage' => 'urun-hizmet-duzenle',
                    'service/manage' => 'servis-duzenle',
                    'purchases/manage' => 'siparis-duzenle',
                    'purchases/price-request-manage' => 'fiyat-talebi-duzenle',
                ];
                if (isset($params['id'], $encryptedEditRoutes[$page])) {
                    $rawId = (string)$params['id'];
                    if (ctype_digit($rawId) && (int)$rawId > 0) {
                        $params['id'] = Security::encrypt($rawId);
                    } else {
                        $decryptedId = @Security::decrypt($rawId);
                        if ($decryptedId === false || !ctype_digit((string)$decryptedId) || (int)$decryptedId < 1) {
                            return $match[0];
                        }
                    }

                    $path = $encryptedEditRoutes[$page];
                    $query = '?' . http_build_query(array_map('strval', $params));
                    return $path . $query . $fragment;
                }

                $stringParams = array_map('strval', $params);
                $path = self::pathForPage($page, $stringParams);
                if ($path === null) {
                    return $match[0];
                }

                $route = self::routes()[$path] ?? [];
                foreach (($route['query'] ?? []) as $key => $value) {
                    if ((string)($stringParams[$key] ?? '') === (string)$value) {
                        unset($stringParams[$key]);
                    }
                }

                $query = $stringParams === [] ? '' : '?' . http_build_query($stringParams);
                return $path . $query . $fragment;
            },
            $html
        );
    }

    public static function isPathActive(string $path, string $currentPage, array $currentQuery): bool
    {
        $route = self::routes()[trim($path, '/')] ?? null;
        if ($route === null || $route['page'] !== $currentPage) {
            return false;
        }

        $routeQuery = $route['query'] ?? [];
        foreach ($routeQuery as $key => $value) {
            if ((string)($currentQuery[$key] ?? '') !== (string)$value) {
                return false;
            }
        }

        // Aynı sayfanın filtreli ve filtresiz rotaları birbirini aktif etmemeli.
        if ($routeQuery === []) {
            foreach (self::routes() as $candidate) {
                if ($candidate['page'] !== $currentPage) {
                    continue;
                }
                foreach (($candidate['query'] ?? []) as $key => $value) {
                    if ((string)($currentQuery[$key] ?? '') === (string)$value) {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    /** @param array{path:string, page:string, permissions:array<int, string>} $resolvedRoute */
    public static function resolveParameters(array $resolvedRoute): void
    {
        $route = self::routes()[$resolvedRoute['path']] ?? null;
        if (empty($route['encrypted_id'])) {
            return;
        }

        $encryptedId = trim((string) ($_GET['id'] ?? ''));
        $decryptedId = $encryptedId !== '' ? Security::decrypt($encryptedId) : false;

        if ($decryptedId === false || !ctype_digit((string) $decryptedId) || (int) $decryptedId < 1) {
            throw new InvalidArgumentException('Geçersiz düzenleme bağlantısı.');
        }

        $_GET['id'] = (int) $decryptedId;
    }

    /** @param array<int, string> $permissions */
    public static function isAuthorized(array $permissions): bool
    {
        if ($permissions === []) {
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
