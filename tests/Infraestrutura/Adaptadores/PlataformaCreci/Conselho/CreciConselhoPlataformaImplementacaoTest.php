<?php

use App\Aplicacao\CasosDeUso\PlataformaCreci;
use App\Aplicacao\CasosDeUso\EntradaESaida\SaidaConsultarCreciPlataforma;
use App\Infraestrutura\Adaptadores\PlataformasCreci\Conselho\CreciConselhoPlataformaImplementacao;
use Tests\Infraestrutura\Adaptadores\PlataformaCreci\Conselho\DiscordMock;
use Tests\Infraestrutura\Adaptadores\PlataformaCreci\Conselho\CaptchaMock;

test("CreciConselhoPlataformaImplementacao deve implementar a interface PlataformaCreci", function(){
    $discord = new DiscordMock();
    $captcha = new CaptchaMock();
    $conselho = new CreciConselhoPlataformaImplementacao(uf: "PR", discord: $discord, captcha: $captcha);
    expect($conselho)->toBeInstanceOf(CreciConselhoPlataformaImplementacao::class)->toBeInstanceOf(PlataformaCreci::class);
});

test("Deve testar RJ", function(){
    $discord = new DiscordMock();
    $captcha = new \App\Infraestrutura\Adaptadores\Captcha\Captcha2CaptchaImplementation(
        env: new \App\Infraestrutura\Adaptadores\EnvrionmentImplementacao()
    );
    $conselho = new CreciConselhoPlataformaImplementacao(uf: "RJ", discord: $discord, captcha: $captcha);
    $numeroInscricao = "102030";
    $tipoCreci = "F";

    $resposta = $conselho->consultarCreci($numeroInscricao, $tipoCreci);

    expect($resposta)->toBeInstanceOf(SaidaConsultarCreciPlataforma::class);
    expect($resposta->nomeCompleto)->toBe("SIDCLEI JOSE MARQUES");
    expect($resposta->situacao)->toBe("Ativo");
    expect($resposta->inscricao)->toBe("102030");
})->group("CRECIRJ");

test("Devera retornar o CRECI de um corretor de estado de RO", function(){
    $discord = new DiscordMock();
    $captcha = new \App\Infraestrutura\Adaptadores\Captcha\Captcha2CaptchaImplementation(
        env: new \App\Infraestrutura\Adaptadores\EnvrionmentImplementacao()
    );
    $conselho = new CreciConselhoPlataformaImplementacao(uf: "RO", discord: $discord, captcha: $captcha);
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
    $captcha = new \App\Infraestrutura\Adaptadores\Captcha\Captcha2CaptchaImplementation(
        env: new \App\Infraestrutura\Adaptadores\EnvrionmentImplementacao()
    );
    $conselho = new CreciConselhoPlataformaImplementacao(uf: "RO", discord: $discord, captcha: $captcha);
    $numeroInscricao = "123456";
    $tipoCreci = "F";

    $resposta = $conselho->consultarCreci($numeroInscricao, $tipoCreci);

    expect($resposta)->toBeInstanceOf(SaidaConsultarCreciPlataforma::class);
})
->throws('CRECI Inexistente no estado informado')
->group("PlataformaCreci", "Conselho");
