<?php

declare(strict_types=1);

namespace App\Dominio\ObjetoValor;

use Exception;

final readonly class NomeCompleto {

	private string $value;
    function __construct(
        private string $nome
    ){

		$nome = $this->nome;
        $nome = mb_convert_case($nome, MB_CASE_LOWER, 'UTF-8');

        $nome = ucwords($nome);

        $mustache = [
            ' Da ' => ' da ',
            ' De ' => ' de ',
            ' Di ' => ' di ',
            ' Do ' => ' do ',
            ' Du ' => ' du ',
        ];

        $nome = str_replace(array_keys($mustache), array_values($mustache), $nome);

        if(!self::validation($nome)){
            throw new Exception("Nome completo informado está inválido. ({$nome})");
        }

		$this->value = $nome;
    }

    static function validation(string $name): bool
    {

        $name = str_replace('  ', ' ', $name);

        $contain_words_only = preg_match("/^[A-ZÀ-Ÿ][A-zÀ-ÿ']+\s([A-zÀ-ÿ']\s?)*[A-ZÀ-Ÿ][A-zÀ-ÿ']+$/", trim($name));
        return !!$contain_words_only;
    }

    public function get(): string
    {
        return $this->value;
    }
}