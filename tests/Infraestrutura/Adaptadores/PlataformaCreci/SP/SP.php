<?php

declare(strict_types=1);

require_once __DIR__.'/../../../../../vendor/autoload.php';

use App\Aplicacao\Compartilhado\Captcha\Captcha;
use App\Aplicacao\Compartilhado\Discord\Discord;
use App\Infraestrutura\Adaptadores\PlataformasCreci\SP\CreciSPPlataformaImplementacao;

$container = require __DIR__.'/../../../../../src/Aplicacao/Compartilhado/Container.php';

$plataforma = new CreciSPPlataformaImplementacao(
    captcha: $container->get(Captcha::class),
    discord: $container->get(Discord::class),
);

$resposta = $plataforma->consultarCreci(
    creci: '123478',
    tipoCreci: 'F',
);

dd($resposta);