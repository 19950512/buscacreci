<?php

declare(strict_types=1);

namespace App\Aplicacao\Compartilhado\Mensageria\Enumerados;

enum TrocaMensagens: string
{
    case EMISSAO_EMAIL_EXCHANGE = 'emissao_email_exchange';
    case EMISSAO_EMAIL_DLX_EXCHANGE = 'emissao_email_dlq_exchange';

    static public function trocasMensagens(): array
    {
        return [
            
            // EMAIL
            [
                'exchange' => self::EMISSAO_EMAIL_EXCHANGE,
                'type'=> 'direct',
            ],
            [
                'exchange' => self::EMISSAO_EMAIL_DLX_EXCHANGE,
                'type'=> 'fanout',
            ],
        ];
    }
}