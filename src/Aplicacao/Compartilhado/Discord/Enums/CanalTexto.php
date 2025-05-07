<?php

declare(strict_types=1);

namespace App\Aplicacao\Compartilhado\Discord\Enums;

enum CanalTexto: string {
    case CONSULTAS = 'consultas';
    case DEPLOY = 'deploy';
    case WORKERS = 'workers';
    case EXCEPTIONS = 'exceptions';

    case CONSULTA_CRECI = 'consulta-creci';
    case EMAIL = 'email';

    public function obterWebhook(): string
    {

        $pathWebhooks = __DIR__.'/DiscordWebhooks.php';
        if(!is_file($pathWebhooks)){
            $webhooks = [];
        }else{
            $webhooks = require $pathWebhooks;
        }
        
        return match($this) {
            self::CONSULTAS => $webhooks[self::CONSULTAS->name],
            self::DEPLOY => $webhooks[self::DEPLOY->name],
            self::WORKERS => $webhooks[self::WORKERS->name],
            self::EXCEPTIONS => $webhooks[self::EXCEPTIONS->name],
            self::CONSULTA_CRECI => $webhooks[self::CONSULTA_CRECI->name],
            self::EMAIL => $webhooks[self::EMAIL->name],
        };
    }
}