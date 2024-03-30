<?php

declare(strict_types=1);

namespace App\Dominio\ObjetoValor;

use App\Dominio\ObjetoValor\DocumentoIdentificacao;
use Exception;

final readonly class CNPJ implements DocumentoIdentificacao
{

    private string $numero;
    function __construct(
        private string $numeroDocumento
    ){

        if(!self::valido($this->numeroDocumento)){
            throw new Exception('O CNPJ informado não é válido. '.$this->numeroDocumento);
        }

        $this->numero = (new Mascara($this->numeroDocumento, '##.###.###/####-##'))->get();
    }

    function get(): string
    {
        return $this->numero;
    }

    static function valido(string $numeroDocumento): bool
    {

        $cnpj = $numeroDocumento;

        $cnpj = preg_replace('/[^0-9]/', '', (string) $cnpj);
        // Valida tamanho
        if (strlen($cnpj) != 14)
            return false;
        // Valida primeiro dígito verificador
        for ($i = 0, $j = 5, $soma = 0; $i < 12; $i++)
        {
            $soma += $cnpj[$i] * $j;
            $j = ($j == 2) ? 9 : $j - 1;
        }
        $resto = $soma % 11;
        if ($cnpj[12] != ($resto < 2 ? 0 : 11 - $resto))
            return false;
        // Valida segundo dígito verificador
        for ($i = 0, $j = 6, $soma = 0; $i < 13; $i++)
        {
            $soma += $cnpj[$i] * $j;
            $j = ($j == 2) ? 9 : $j - 1;
        }
        $resto = $soma % 11;
        return $cnpj[13] == ($resto < 2 ? 0 : 11 - $resto);
    }
}