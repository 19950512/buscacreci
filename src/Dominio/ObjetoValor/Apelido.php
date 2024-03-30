<?php

declare(strict_types=1);

namespace App\Dominio\ObjetoValor;

use Exception;

final class Apelido
{

    function __construct(
        public string $nome
    ){

        $this->nome = mb_convert_case($this->nome, MB_CASE_LOWER, 'UTF-8');

        $this->nome = ucwords($this->nome);

        $mustache = [
            ' Da ' => ' da ',
            ' De ' => ' de ',
            ' Di ' => ' di ',
            ' Do ' => ' do ',
            ' Du ' => ' du ',
        ];

        $this->nome = str_replace(array_keys($mustache), array_values($mustache), $this->nome);

        if(!self::validation($this->nome)){
            throw new Exception("Apelido informado está inválido. ({$this->nome})");
        }
    }

    static function validation(string $nome): bool
    {
        $nome = str_replace('  ', ' ', $nome);
        $padrao = "/^[a-zA-Z0-9\s]+$/";
        return !!preg_match($padrao, $nome);
    }

    public function get(): string
    {
        return $this->nome;
    }
}