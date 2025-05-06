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

use DI\Container;
use PhpAmqpLib\Message\AMQPMessage;
use App\Infraestrutura\Workers\Workers;
use App\Aplicacao\CasosDeUso\ConsultarCreci;
use App\Dominio\ObjetoValor\IdentificacaoUnica;
use App\Aplicacao\Compartilhado\Mensageria\Enumerados\Evento;

$worker = new Workers(
    evento: Evento::ConsultaCreci,
    maximoDeTentativasDeProcessamento: 10,
    mensagensQueNaoDevemSerProcessadas: [
        'Não encontramos nenhuma consulta com o código de solicitação informado',
    ],
    lidarComMensagem: function(Container $container, AMQPMessage $mensagem){

        echo "mensagem: ".$mensagem->getBody()."\n";

        $mensagem = json_decode($mensagem->getBody(), true);

        if(!isset($mensagem['codigoSolicitacao']) OR empty($mensagem['codigoSolicitacao'])){
            echo "O código de solicitação não foi informado.\n";
            return;
        }

        $container->get(ConsultarCreci::class)->consultarCreciPlataforma(
            codigoSolicitacao: new IdentificacaoUnica($mensagem['codigoSolicitacao'])
        );
    }
);

$worker->start();