<?php

declare(strict_types=1);

namespace App\Aplicacao\Compartilhado\Mensageria\Enumerados;

enum Fila: string
{
    case EMISSAO_EMAIL_QUEUE = 'emissao_email_queue';
    case EMISSAO_EMAIL_QUEUE_DLQ_QUEUE = 'emissao_email_queue_dlq_queue';

    static public function Ligacoes(): array
    {
        return [

            // EMAIL
            [
                'queue' => self::EMISSAO_EMAIL_QUEUE,
                'exchange' => TrocaMensagens::EMISSAO_EMAIL_EXCHANGE,
            ],
            [
                'queue' => self::EMISSAO_EMAIL_QUEUE_DLQ_QUEUE,
                'exchange' => TrocaMensagens::EMISSAO_EMAIL_DLX_EXCHANGE,
            ],
        ];
    }

    static public function Filas(): array
    {
        return [

            // EMAIL
            [
                'queue' => Fila::EMISSAO_EMAIL_QUEUE,
                'dlx' => TrocaMensagens::EMISSAO_EMAIL_DLX_EXCHANGE,
            ],
            [
                'queue' => Fila::EMISSAO_EMAIL_QUEUE_DLQ_QUEUE,
                'dlx' => TrocaMensagens::EMISSAO_EMAIL_DLX_EXCHANGE,
            ],
        ];
    }
}