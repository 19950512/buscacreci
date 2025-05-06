#!/usr/bin/php
<?php

declare(strict_types=1);

$pathVendor = __DIR__ . '/../../../../vendor/autoload.php';
if (!is_file($pathVendor)) {
    echo "O autoload do composer não foi encontrado. Verifique o caminho.\n";
    exit(1);
}
require_once $pathVendor;

$pathContainer = __DIR__ . '/../../../Aplicacao/Compartilhado/Container.php';
if (!is_file($pathContainer)) {
    echo "O container não foi encontrado. Verifique o caminho.\n";
    exit(1);
}
require_once $pathContainer;

use App\Aplicacao\Compartilhado\Mensageria\Enumerados\Evento;
use App\Infraestrutura\Workers\Workers;
use DI\Container;
use PhpAmqpLib\Message\AMQPMessage;

$worker = new Workers(
    evento: Evento::EnviarEmail,
    maximoDeTentativasDeProcessamento: 10,
    lidarComMensagem: function(Container $container, AMQPMessage $mensagem){
        echo "mensagem: ".$mensagem->getBody()."\n O e-mail SMTP ainda não foi implementado.\n";
    }
);

$worker->start();