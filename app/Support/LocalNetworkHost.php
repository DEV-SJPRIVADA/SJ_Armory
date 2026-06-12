<?php

namespace App\Support;

use Illuminate\Http\Request;

class LocalNetworkHost
{
    public static function isFlexibleLocalHost(string $host): bool
    {
        if (in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
            return true;
        }

        if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return ! filter_var(
                $host,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
            );
        }

        return (bool) preg_match('/^[a-zA-Z0-9.-]+$/', $host);
    }

    /**
     * @return list<string>
     */
    public static function sanctumHostVariants(Request $request): array
    {
        $host = $request->getHost();
        $port = $request->getPort();
        $variants = [$host];

        if (! in_array($port, [80, 443], true)) {
            $variants[] = "{$host}:{$port}";
        } else {
            $variants[] = "{$host}:{$port}";
            $variants[] = "{$host}:80";
            $variants[] = "{$host}:443";
        }

        return array_values(array_unique($variants));
    }
}
