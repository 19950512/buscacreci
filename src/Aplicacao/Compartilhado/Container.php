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
use App\Dominio\Repositorios\CreciRepositorio;
use App\Infraestrutura\Adaptadores\Cache\RedisCacheImplementacao;
use App\Infraestrutura\Adaptadores\EnvrionmentImplementacao;
use App\Infraestrutura\Adaptadores\PlataformasCreci\CreciRSPlataformaImplementacao;
use App\Infraestrutura\Repositorios\CreciRepositorioImplementacao;
use DI\Container;
use DI\ContainerBuilder;
use Exception;
use PDO;
use PDOException;

$container = new ContainerBuilder();

$container->addDefinitions([
	Envrionment::class => function(Container $container)
	{
		return new EnvrionmentImplementacao();
	},
	Cache::class => function(Container $container)
	{
		return new RedisCacheImplementacao(
			env: $container->get(Envrionment::class)
		);
	},
	PDO::class => function(Container $container)
	{
		$env = $container->get(Envrionment::class);
        try {

            $linkConexao = "pgsql:host={$env->get('DB_HOST')};dbname={$env::get('DB_DATABASE')};user={$env::get('DB_USERNAME')};password={$env::get('DB_PASSWORD')};port={$env->get('DB_PORT')}";

            $PDO = new PDO($linkConexao);
            $PDO->setAttribute(PDO::ATTR_EMULATE_PREPARES, 1);
            $PDO->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $PDO;
        }catch (PDOException $erro){

            $message = $erro->getMessage();

            if($message == 'could not find driver'){
                die('Não foi encontrado o Driver do PDO.');
            }

            header("HTTP/1.0 500 Connection");
            echo file_get_contents(__DIR__.'/sem_conexao.html');
            exit;
        }
	},
	CreciRepositorio::class => function(Container $container)
	{
		return new CreciRepositorioImplementacao(
			conexao: $container->get(PDO::class)
		);
	},
	ConsultarCreci::class => function(Container $container)
	{
		return new ConsultarCreciImplementacao(
			creciRepositorio: $container->get(CreciRepositorio::class),
			cache: $container->get(Cache::class)
		);
	}
]);

return $container->build();