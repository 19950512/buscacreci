<?php

declare(strict_types=1);

namespace App\Dominio\Entidades\ConselhoNacionalCRECI;

use App\Dominio\ObjetoValor\Endereco\Estado;


/**
* @see https://github.com/19950512/buscacreci/issues/2#issuecomment-2033495252
*/
class ConselhoNacionalCRECI
{
    public array $participantes = [];

    public function __construct(){
        $this->participantes = [
            new MembroDoConselho(
                estado: new Estado('RO'),
                ativo: true
            ),
            new MembroDoConselho(
                estado: new Estado('RJ'),
                ativo: true
            ),
            new MembroDoConselho(
                estado: new Estado('PR'),
                ativo: true
            ),
            new MembroDoConselho(
                estado: new Estado('SP'),
                ativo: false
            ),
            new MembroDoConselho(
                estado: new Estado('ES'),
                ativo: false
            ),
        ];
    }

    public function estadoPossuiMembroAtivo(string $uf): bool
    {
        foreach($this->participantes as $participante){
            if($participante->estado->getUF() == $uf && $participante->ativo){
                return true;
            }
        }

        return false;
    }
}