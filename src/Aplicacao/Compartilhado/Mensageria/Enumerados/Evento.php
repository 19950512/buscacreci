<?php

declare(strict_types=1);

namespace App\Aplicacao\Compartilhado\Mensageria\Enumerados;

enum Evento: string
{
    case EnviarEmail = 'Enviar e-mail';
    case ConsultaCreci = 'Consulta CRECI';

    public function Filas(): Fila
    {
        return match ($this) {
            self::EnviarEmail => Fila::EMISSAO_EMAIL_QUEUE,
            self::ConsultaCreci => Fila::CONSULTA_CRECI_QUEUE,
        };
    }
}