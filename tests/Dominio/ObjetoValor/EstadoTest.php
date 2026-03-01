<?php

declare(strict_types=1);

use App\Dominio\ObjetoValor\Endereco\Estado;

test("Deve criar um Estado válido a partir da UF", function(){
    $estado = new Estado('RS');
    expect($estado->getUF())->toBe('RS');
    expect($estado->getFull())->toBe('Rio Grande do Sul');
});

test("Deve retornar todos os 28 estados (27 + NN)", function(){
    $estados = Estado::getEstados();
    expect(count($estados))->toBe(28);
});

test("Deve lançar exceção para UF inválida", function(){
    new Estado('XX');
})->throws(Exception::class);

test("Deve aceitar todas as UFs brasileiras", function(){
    $ufs = ['AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO',
            'MA', 'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI',
            'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'];
    foreach ($ufs as $uf) {
        $estado = new Estado($uf);
        expect($estado->getUF())->toBe($uf);
    }
});
