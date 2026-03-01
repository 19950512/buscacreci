<?php

use App\Dominio\Entidades\ConselhoNacionalCRECI\ConselhoNacionalCRECI;
use App\Dominio\ObjetoValor\Endereco\Estado;

test("Todos os 27 estados do Brasil devem estar cadastrados no Conselho Nacional", function(){
    $conselho = new ConselhoNacionalCRECI();
    $estados = Estado::getEstados();
    
    // Todos os 27 estados devem estar presentes (mesmo que inativos)
    $estadosCadastrados = [];
    foreach ($conselho->participantes as $participante) {
        $estadosCadastrados[] = $participante->estado->getUF();
    }

    expect(count($estadosCadastrados))->toBe(27);
})
->group("ConselhoNacional");

test("O ConselhoNacional deve ter 22 estados ativos (5 sem DNS)", function(){
    $conselho = new ConselhoNacionalCRECI();

    $estadosAtivos = [];
    foreach ($conselho->participantes as $participante) {
        if ($participante->ativo) {
            $estadosAtivos[] = $participante->estado->getUF();
        }
    }

    // 5 estados não possuem DNS no conselho.net.br: ES, MG, RS, SP, TO
    // ES e RS possuem scrapers dedicados, SP usa headless Chrome
    expect(count($estadosAtivos))->toBe(22);
})
->group("ConselhoNacional");

test("O ConselhoNacional deve suportar os estados com DNS ativo", function(){
    $conselho = new ConselhoNacionalCRECI();

    // Estados com DNS funcionando em conselho.net.br
    $estadosAtivos = [
        'AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'GO',
        'MA', 'MT', 'MS', 'PA', 'PB', 'PR', 'PE', 'PI',
        'RJ', 'RN', 'RO', 'RR', 'SC', 'SE',
    ];

    foreach ($estadosAtivos as $uf) {
        expect($conselho->estadoPossuiMembroAtivo($uf))
            ->toBeTrue("Estado {$uf} deveria estar ativo no Conselho Nacional");
    }
})
->group("ConselhoNacional");

test("Estados sem DNS no Conselho Nacional devem estar inativos", function(){
    $conselho = new ConselhoNacionalCRECI();

    // Estes estados não possuem DNS em conselho.net.br
    // ES e RS possuem scrapers dedicados, SP usa headless Chrome
    $estadosSemDNS = ['ES', 'MG', 'RS', 'SP', 'TO'];

    foreach ($estadosSemDNS as $uf) {
        expect($conselho->estadoPossuiMembroAtivo($uf))
            ->toBeFalse("Estado {$uf} não deveria estar ativo (DNS inexistente)");
    }
})
->group("ConselhoNacional");
