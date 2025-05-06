<?php

declare(strict_types=1);

namespace App\Aplicacao\Compartilhado\Mensageria\Enumerados;

enum TrocaMensagens: string
{
    case EMISSAO_EMAIL_DLX = 'emissao_email_dlq_exchange';

    case CONSULTA_CRECI_DLX = 'consulta_creci_dlq_exchange';

    static public function trocasMensagens(): array
    {
        return [
            
            // EMAIL
            [
                'exchange' => self::EMISSAO_EMAIL_DLX,
                'type' => 'direct',
            ],


            // CONSULTA CRECI
            [
                'exchange' => self::CONSULTA_CRECI_DLX,
                'type' => 'direct',
            ],
        ];
    }
}