<?php

declare(strict_types=1);

namespace App\Infraestrutura\Adaptadores\PlataformasCreci;

use Exception;

class Robots
{
    public static function isAllowedByRobotsTxt(string $url, string $userAgent = '*'): bool
    {
        $parsedUrl = parse_url($url);
        if (!isset($parsedUrl['scheme'], $parsedUrl['host'])) {
            throw new Exception("URL inválida: {$url}");
        }

        $robotsUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'] . '/robots.txt';
        $robotsTxt = file_get_contents($robotsUrl);

        // Se não conseguir acessar o robots.txt, assume que é permitido (comum e aceito)
        if ($robotsTxt === false) {
            return true;
        }

        $lines = explode("\n", $robotsTxt);
        $isRelevant = false;
        $disallowedPaths = [];

        foreach ($lines as $line) {
            $line = trim($line);

            // Ignora comentários
            if (strpos($line, '#') === 0) {
                continue;
            }

            if (stripos($line, 'User-agent:') === 0) {
                $agent = trim(substr($line, strlen('User-agent:')));
                $isRelevant = ($agent === '*' || stripos($agent, $userAgent) !== false);
            }

            if ($isRelevant && stripos($line, 'Disallow:') === 0) {
                $disallowed = trim(substr($line, strlen('Disallow:')));
                $disallowedPaths[] = $disallowed;
            }
        }

        $pathToCheck = $parsedUrl['path'] ?? '/';

        foreach ($disallowedPaths as $disallowedPath) {
            if ($disallowedPath === '') {
                continue; // Disallow vazio = tudo permitido
            }

            // Se a URL começar com o caminho disallow, está bloqueada
            if (strpos($pathToCheck, $disallowedPath) === 0) {
                return false;
            }
        }

        return true;
    }
}
