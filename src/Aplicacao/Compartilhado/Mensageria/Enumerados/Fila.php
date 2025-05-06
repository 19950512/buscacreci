<?php

declare(strict_types=1);

namespace App\Aplicacao\Compartilhado\Mensageria\Enumerados;

enum Fila: string
{
    case EMISSAO_EMAIL_QUEUE = 'emissao_email_queue';
    case EMISSAO_EMAIL_QUEUE_DLQ = 'emissao_email_queue_dlq_queue';

    case CONSULTA_CRECI_QUEUE = 'consulta_creci_queue';
    case CONSULTA_CRECI_QUEUE_DLQ = 'consulta_creci_queue_dlq_queue';

    static public function Ligacoes(): array
    {
        return [

            // EMAIL
            [
                'main_queue' => self::EMISSAO_EMAIL_QUEUE,
                'dead_letter_exchange' => TrocaMensagens::EMISSAO_EMAIL_DLX,
                'dead_letter_queue' => self::EMISSAO_EMAIL_QUEUE_DLQ,
            ],

            // CONSULTA CRECI
            [
                'main_queue' => self::CONSULTA_CRECI_QUEUE,
                'dead_letter_exchange' => TrocaMensagens::CONSULTA_CRECI_DLX,
                'dead_letter_queue' => self::CONSULTA_CRECI_QUEUE_DLQ,
            ],
            
        ];
    }

    static public function Filas(): array
    {
        return [

            // EMAIL
            [
                'main_queue' => self::EMISSAO_EMAIL_QUEUE,
                'dead_letter_exchange' => TrocaMensagens::EMISSAO_EMAIL_DLX,
                'dead_letter_queue' => self::EMISSAO_EMAIL_QUEUE_DLQ,
            ],

            // CONSULTA CRECI
            [
                'main_queue' => self::CONSULTA_CRECI_QUEUE,
                'dead_letter_exchange' => TrocaMensagens::CONSULTA_CRECI_DLX,
                'dead_letter_queue' => self::CONSULTA_CRECI_QUEUE_DLQ,
            ],
        ];
    }
}