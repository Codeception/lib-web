<?php

declare(strict_types=1);

namespace Codeception\Util;

use InvalidArgumentException;

use function dirname;
use function ltrim;
use function parse_url;
use function preg_match;
use function rtrim;

class Uri
{
    /**
     * Merges the passed $add argument onto $base.
     *
     * If a relative URL is passed as the 'path' part of the $add url
     * array, the relative URL is mapped using the base 'path' part as
     * its base.
     *
     * @param string $baseUri the base URL
     * @param string $uri the URL to merge
     * @return string the merged array
     */
    public static function mergeUrls(string $baseUri, string $uri): string
    {
        $base = self::parseUrl($baseUri);
        $parts = parse_url($uri);

        //If the relative URL does not parse, attempt to parse the entire URL.
        //PHP Known bug ( https://bugs.php.net/bug.php?id=70942 )
        if ($parts === false) {
            $parts = parse_url($baseUri . $uri);
        }

        if ($parts === false) {
            throw new InvalidArgumentException("Invalid URI {$uri}");
        }

        if (isset($parts['host']) && isset($parts['scheme'])) {
            // if this is an absolute url, replace with it
            return $uri;
        }

        if (isset($parts['host'])) {
            $base['host'] = $parts['host'];
            $base['path'] = '';
            $base['query'] = '';
            $base['fragment'] = '';
        }
        if (isset($parts['path'])) {
            $path = $parts['path'];
            $basePath = $base['path'] ?? '';
            if ((!str_starts_with($path, '/')) && !empty($path)) {
                if ($basePath !== '') {
                    // if it ends with a slash, relative paths are below it
                    if (preg_match('#/$#', $basePath)) {
                        $path = $basePath . $path;
                    } else {
                        // remove double slashes
                        $dir = rtrim(dirname($basePath), '\\/');
                        $path = $dir . '/' . $path;
                    }
                } else {
                    $path = '/' . ltrim($path, '/');
                }
            }
            $base['path'] = $path;
            $base['query'] = '';
            $base['fragment'] = '';
        }
        if (isset($parts['query'])) {
            $base['query'] = $parts['query'];
            $base['fragment'] = '';
        }
        if (isset($parts['fragment'])) {
            $base['fragment'] = $parts['fragment'];
        }

        return self::phpUrlPartsToString($base);
    }

    /**
     * Retrieve /path?query#fragment part of URL
     */
    public static function retrieveUri(string $url): string
    {
        $urlParts = self::parseUrl($url);

        return self::phpUrlPartsToString([
            'path' => $urlParts['path'],
            'query' => $urlParts['query'] ?? '',
            'fragment' => $urlParts['fragment'] ?? '',
        ]);
    }

    public static function retrieveHost(string $url): string
    {
        $urlParts = self::parseUrl($url);
        if (!isset($urlParts['host']) || !isset($urlParts['scheme'])) {
            throw new InvalidArgumentException("Wrong URL passes, host and scheme not set");
        }
        $host = $urlParts['scheme'] . '://' . $urlParts['host'];
        if (isset($urlParts['port'])) {
            $host .= ':' . $urlParts['port'];
        }
        return $host;
    }

    public static function appendPath(string $url, string $path): string
    {
        $cutUrl = parse_url($url);
        unset(
            $cutUrl['query'],
            $cutUrl['fragment'],
        );
        $cutUrl = self::phpUrlPartsToString($cutUrl);

        if ($path === '' || $path[0] === '#') {
            return $cutUrl . $path;
        }

        return rtrim($cutUrl, '/') . '/' . ltrim($path, '/');
    }

    /**
     * @return array{
     *      scheme?: string,
     *      host?: string,
     *      port?: int,
     *      user?: string,
     *      pass?: string,
     *      query?: string,
     *      path?: string,
     *      fragment?: string,
     *  }
     */
    public static function parseUrl(string $uri): array
    {
        $parts = parse_url($uri);

        if ($parts === false) {
            throw new InvalidArgumentException("Invalid URI {$uri}");
        }

        return $parts;
    }

    /**
     * @param array{
     *     scheme?: string,
     *     host?: string,
     *     port?: int,
     *     user?: string,
     *     pass?: string,
     *     query?: string,
     *     path?: string,
     *     fragment?: string,
     * } $urlParts
     */
    public static function phpUrlPartsToString(array $urlParts): string
    {
        $uri = '';
        $scheme = $urlParts['scheme'] ?? '';
        $host = $urlParts['host'] ?? '';
        $path = $urlParts['path'] ?? '';

        if ($scheme !== '') {
            $uri .= $scheme . ':';
        }

        if ($host !== '' || $scheme === 'file') {
            $uri .= '//' . $host;

            if (($urlParts['port'] ?? '') !== '') {
                $uri .= ':' . $urlParts['port'];
            }
        }

        if ($host !== '' && $path !== '' && $path[0] !== '/') {
            $path = '/' . $path;
        }

        $uri .= $path;

        if (($urlParts['query'] ?? '') !== '') {
            $uri .= '?' . $urlParts['query'];
        }

        if (($urlParts['fragment'] ?? '') !== '') {
            $uri .= '#' . $urlParts['fragment'];
        }

        return $uri;
    }
}
