<?php

use App\Aplicacao\CasosDeUso\PlataformaCreci;
use App\Aplicacao\CasosDeUso\EntradaESaida\SaidaConsultarCreciPlataforma;
use App\Infraestrutura\Adaptadores\PlataformasCreci\Conselho\CreciConselhoPlataformaImplementacao;

test("CreciConselhoPlataformaImplementacao deve implementar a interface PlataformaCreci", function(){
    $conselho = new CreciConselhoPlataformaImplementacao("PR");
    expect($conselho)->toBeInstanceOf(CreciConselhoPlataformaImplementacao::class)->toBeInstanceOf(PlataformaCreci::class);
});

test("Devera retornar o CRECI de um corretor de estado de RO", function(){
    $conselho = new CreciConselhoPlataformaImplementacao("RO");
    $numeroInscricao = "2097";
    $tipoCreci = "F";

    $resposta = $conselho->consultarCreci($numeroInscricao, $tipoCreci);

    expect($resposta)->toBeInstanceOf(SaidaConsultarCreciPlataforma::class);
    
    expect($resposta->nomeCompleto)->toBe("IMOVEIS LVF LTDA ME");
    expect($resposta->situacao)->toBe("Ativo");
    expect($resposta->estado)->toBe("RO");
    expect($resposta->numeroDocumento)->toBe("23458090000164");
    expect($resposta->telefone)->toBe("69993104021");
})
->group("PlataformaCreci", "Conselho");

test("Devera lançar uma exception caso o CRECI não exista na região informada.", function(){
    $conselho = new CreciConselhoPlataformaImplementacao("RO");
    $numeroInscricao = "123456";
    $tipoCreci = "F";

    $resposta = $conselho->consultarCreci($numeroInscricao, $tipoCreci);

    expect($resposta)->toBeInstanceOf(SaidaConsultarCreciPlataforma::class);
})
->throws('CRECI Inexistente no estado informado')
->group("PlataformaCreci", "Conselho");
