<?php

declare(strict_types=1);

namespace App\Aplicacao\Compartilhado;

interface Envrionment {
    public static function get(string $key): string;
}