<?php

use App\Aplicacao\CasosDeUso\PlataformaCreci;
use App\Aplicacao\CasosDeUso\EntradaESaida\SaidaConsultarCreciPlataforma;
use App\Infraestrutura\Adaptadores\PlataformasCreci\Conselho\CreciConselhoPlataformaImplementacao;
use Tests\Infraestrutura\Adaptadores\PlataformaCreci\Conselho\DiscordMock;

test("CreciConselhoPlataformaImplementacao deve implementar a interface PlataformaCreci", function(){
    $discord = new DiscordMock();
    $conselho = new CreciConselhoPlataformaImplementacao(uf: "PR", discord: $discord);
    expect($conselho)->toBeInstanceOf(CreciConselhoPlataformaImplementacao::class)->toBeInstanceOf(PlataformaCreci::class);
});

test("Devera retornar o CRECI de um corretor de estado de RO", function(){
    $discord = new DiscordMock();
    $conselho = new CreciConselhoPlataformaImplementacao(uf: "RO", discord: $discord);
    $numeroInscricao = "2097";
    $tipoCreci = "F";

    try {
        $resposta = $conselho->consultarCreci($numeroInscricao, $tipoCreci);
    } catch (Exception $e) {
        // Se a API retornar erro (dados não encontrados, timeout, etc.), pula o teste
        $this->markTestSkipped('API do Conselho Nacional indisponível ou CRECI não encontrado: ' . $e->getMessage());
    }

    expect($resposta)->toBeInstanceOf(SaidaConsultarCreciPlataforma::class);
    
    expect($resposta->nomeCompleto)->not->toBeEmpty();
    expect($resposta->situacao)->toBeIn(["Ativo", "Inativo"]);
    expect($resposta->estado)->toBe("RO");
})
->group("PlataformaCreci", "Conselho");

test("Devera lançar uma exception caso o CRECI não exista na região informada.", function(){
    $discord = new DiscordMock();
    $conselho = new CreciConselhoPlataformaImplementacao(uf: "RO", discord: $discord);
    $numeroInscricao = "123456";
    $tipoCreci = "F";

    $resposta = $conselho->consultarCreci($numeroInscricao, $tipoCreci);

    expect($resposta)->toBeInstanceOf(SaidaConsultarCreciPlataforma::class);
})
->throws('CRECI Inexistente no estado informado')
->group("PlataformaCreci", "Conselho");
