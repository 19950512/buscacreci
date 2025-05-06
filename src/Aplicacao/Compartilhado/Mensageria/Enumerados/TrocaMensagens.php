<?php

declare(strict_types=1);

namespace App\Aplicacao\Compartilhado\Mensageria\Enumerados;

enum TrocaMensagens: string
{
    case EMISSAO_EMAIL_EXCHANGE = 'emissao_email_exchange';
    case EMISSAO_EMAIL_DLX_EXCHANGE = 'emissao_email_dlq_exchange';

    case CONSULTA_CRECI_EXCHANGE = 'consulta_creci_exchange';
    case CONSULTA_CRECI_DLX_EXCHANGE = 'consulta_creci_dlq_exchange';

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


            // CONSULTA CRECI
            [
                'exchange' => self::CONSULTA_CRECI_EXCHANGE,
                'type'=> 'direct',
            ],
            [
                'exchange' => self::CONSULTA_CRECI_DLX_EXCHANGE,
                'type'=> 'fanout',
            ],
        ];
    }
}