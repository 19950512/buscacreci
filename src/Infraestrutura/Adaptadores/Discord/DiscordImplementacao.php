<?php

declare(strict_types=1);

namespace App\Infraestrutura\Adaptadores\Discord;

use Override;
use App\Aplicacao\Compartilhado\Envrionment;
use App\Aplicacao\Compartilhado\Discord\Discord;
use App\Aplicacao\Compartilhado\Discord\Enums\CanalTexto;

readonly final class DiscordImplementacao implements Discord
{
    
    public function __construct(
        private Envrionment $env
    ){}
    
    #[Override] public function enviarMensagem(CanalTexto $canalTexto, string $mensagem): void
    {

        $body = [
            'content' => mb_substr($mensagem, 0, 2000),
            'username' => 'Busca Creci - Notificador'.($this->env->get('APP_DEBUG') ? ' - DEV' : ' - PRODUÇÃO'),
            //'avatar_url' => $this->_imobiliaria->logo->getUrl(),
        ];

        $headers = [
            'Content-Type: application/json',
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $canalTexto->obterWebhook());
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_exec($ch);
        curl_close($ch);
    }

}