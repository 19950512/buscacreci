<?php

declare(strict_types=1);

namespace App\Aplicacao\Compartilhado\Discord\Enums;

enum CanalTexto: string {
    case CONSULTAS = 'consultas';
    case DEPLOY = 'deploy';

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
        };
    }
}