<?php

declare(strict_types=1);

namespace App\Dominio\ObjetoValor;

use Exception;

final readonly class CNJ
{
    public string $nome;

	private string $value;
    public function __construct(
        private string $data = ''
    ){

        if(!$this->validarNumeroUnicoProcesso($this->data)){
            throw new Exception('O número Conselho Nacional de Justiça (CNJ) está inválido. - ' . $this->data);
        }
        $this->value = (new Mascara($this->data, '#######-##.####.#.##.####'))->get();
	    $this->nome = "Conselho Nacional de Justiça";
    }

    private function validarNumeroUnicoProcesso($numero): bool
    {
        $bcmod = function($x, $y) {
            $take = 5;
            $mod = '';
    
            do {
                $a = intval($mod . substr($x, 0, $take));
                $x = substr($x, $take);
                $mod = $a % $y;
            } while (strlen($x) > 0);
    
            return $mod;
        };
    
        // remove todos os pontos e traços
        $numeroProcesso = preg_replace('/[.-]/', '', $numero);
    
        if (strlen($numeroProcesso) < 14 || !is_numeric($numeroProcesso)) {
            return false;
        }
    
        $digitoVerificadorExtraido = intval(substr($numeroProcesso, -13, 2));
    
        $vara = substr($numeroProcesso, -4, 4);  // (4) vara originária do processo
        $tribunal = substr($numeroProcesso, -6, 2);  // (2) tribunal
        $ramo = substr($numeroProcesso, -7, 1);  // (1) ramo da justiça
        $anoInicio = substr($numeroProcesso, -11, 4);  // (4) ano de inicio do processo
        $tamanho = strlen($numeroProcesso) - 13;
        $numeroSequencial = str_pad(substr($numeroProcesso, 0, $tamanho), 7, '0', STR_PAD_LEFT);  // (7) numero sequencial dado pela vara ou juizo de origem
    
        $digitoVerificadorCalculado = 98 - $bcmod($numeroSequencial . $anoInicio . $ramo . $tribunal . $vara . '00', '97');
    
        return $digitoVerificadorExtraido === $digitoVerificadorCalculado;
    }

    public function get(): string
    {
        return $this->value;
    }
}