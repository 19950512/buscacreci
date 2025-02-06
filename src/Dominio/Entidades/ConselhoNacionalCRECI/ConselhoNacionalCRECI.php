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
            new MembroDoConselho(
                estado: new Estado('MG'),
                ativo: false
            ),
            new MembroDoConselho(
                estado: new Estado('PE'),
                ativo: true
            ),
            new MembroDoConselho(
                estado: new Estado('GO'),
                ativo: true
            ),
            new MembroDoConselho(
                estado: new Estado('DF'),
                ativo: false
            ),
            new MembroDoConselho(
                estado: new Estado('BA'),
                ativo: true
            ),
            new MembroDoConselho(
                estado: new Estado('SC'),
                ativo: true
            ),
            new MembroDoConselho(
                estado: new Estado('PA'),
                ativo: true
            ),
            new MembroDoConselho(
                estado: new Estado('MS'),
                ativo: true
            ),
            new MembroDoConselho(
                estado: new Estado('CE'),
                ativo: true
            ),
            new MembroDoConselho(
                estado: new Estado('SE'),
                ativo: true
            ),
            new MembroDoConselho(
                estado: new Estado('RN'),
                ativo: true
            ),
            new MembroDoConselho(
                estado: new Estado('AM'),
                ativo: true
            ),
            new MembroDoConselho(
                estado: new Estado('MT'),
                ativo: true
            ),
            new MembroDoConselho(
                estado: new Estado('MA'),
                ativo: true
            ),
            new MembroDoConselho(
                estado: new Estado('PB'),
                ativo: true
            ),
            new MembroDoConselho(
                estado: new Estado('AL'),
                ativo: true
            ),
            new MembroDoConselho(
                estado: new Estado('PI'),
                ativo: true
            ),
            new MembroDoConselho(
                estado: new Estado('TO'),
                ativo: false
            ),
            new MembroDoConselho(
                estado: new Estado('AC'),
                ativo: true
            ),
            new MembroDoConselho(
                estado: new Estado('RR'),
                ativo: true
            ),
            new MembroDoConselho(
                estado: new Estado('AP'),
                ativo: true
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