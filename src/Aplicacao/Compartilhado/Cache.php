<?php

declare(strict_types=1);

namespace App\Aplicacao\Compartilhado;

interface Cache
{
    public function get(string $key): string | bool;
    public function set(string $key, string $value): void;
    public function delete(string $key): void;
    public function expire(string $key, int $seconds): void;
}