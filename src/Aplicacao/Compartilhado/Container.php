<?php

declare(strict_types=1);

namespace App\Aplicacao\Compartilhado;

$pathVendor = __DIR__.'/../../../vendor/autoload.php';

if (!file_exists($pathVendor)) {
	throw new Exception('Execute o comando composer install');
}

require $pathVendor;

use App\Aplicacao\CasosDeUso\ConsultarCreci;
use App\Aplicacao\CasosDeUso\ConsultarCreciImplementacao;
use App\Aplicacao\CasosDeUso\PlataformaCreci;
use App\Infraestrutura\Adaptadores\PlataformasCreci\CreciRSPlataformaImplementacao;
use DI\Container;
use DI\ContainerBuilder;
use Exception;

$container = new ContainerBuilder();

$container->addDefinitions([
	PlataformaCreci::class => function(Container $container)
	{
		return new CreciRSPlataformaImplementacao();
	},
	ConsultarCreci::class => function(Container $container)
	{
		return new ConsultarCreciImplementacao();
	}

]);



return $container->build();


