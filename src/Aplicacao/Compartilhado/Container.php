<?php

declare(strict_types=1);

namespace App\Aplicacao\Compartilhado;

use PDO;
use Exception;

$pathVendor = __DIR__.'/../../../vendor/autoload.php';

if (!file_exists($pathVendor)) {
	throw new Exception('Execute o comando composer install');
}

require $pathVendor;

use DI\Container;
use PDOException;
use DI\ContainerBuilder;
use App\Aplicacao\CasosDeUso\ConsultarCreci;
use App\Dominio\Repositorios\CreciRepositorio;
use App\Aplicacao\Compartilhado\Captcha\Captcha;
use App\Aplicacao\Compartilhado\Discord\Discord;
use App\Aplicacao\Compartilhado\Mensageria\Mensageria;
use App\Aplicacao\CasosDeUso\ConsultarCreciImplementacao;
use App\Infraestrutura\Adaptadores\EnvrionmentImplementacao;
use App\Infraestrutura\Adaptadores\Discord\DiscordImplementacao;
use App\Infraestrutura\Adaptadores\Cache\RedisCacheImplementacao;
use App\Infraestrutura\Repositorios\CreciRepositorioImplementacao;
use App\Infraestrutura\Adaptadores\Captcha\Captcha2CaptchaImplementation;
use App\Infraestrutura\Adaptadores\Mensageria\ImplementacaoMensageriaRabbitMQ;

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
	Mensageria::class => function(Container $container)
	{
		return new ImplementacaoMensageriaRabbitMQ(
			ambiente: $container->get(Envrionment::class)
		);
	},
	PDO::class => function(Container $container)
	{
		$env = $container->get(Envrionment::class);
        try {

            $linkConexao = "pgsql:host={$env::get('DB_HOST')};dbname={$env::get('DB_DATABASE')};user={$env::get('DB_USERNAME')};password={$env::get('DB_PASSWORD')};port={$env::get('DB_PORT')}";

            $PDO = new PDO($linkConexao);
            $PDO->setAttribute(PDO::ATTR_EMULATE_PREPARES, 0);
            $PDO->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $PDO;
        }catch (PDOException $erro){

            error_log('Database connection error: ' . $erro->getMessage());

            if (php_sapi_name() !== 'cli') {
                header('Content-Type: application/json');
                http_response_code(503);
                echo json_encode(['error' => 'Serviço temporariamente indisponível.']);
                exit;
            }

            throw $erro;
        }
	},
	CreciRepositorio::class => function(Container $container)
	{
		return new CreciRepositorioImplementacao(
			conexao: $container->get(PDO::class)
		);
	},
	Discord::class => function(Container $container)
	{
		return new DiscordImplementacao(
			env: $container->get(Envrionment::class)
		);
	},
	Captcha::class => function(Container $container)
	{
		return new Captcha2CaptchaImplementation(
			env: $container->get(Envrionment::class)
		);
	},
	ConsultarCreci::class => function(Container $container)
	{
		return new ConsultarCreciImplementacao(
			creciRepositorio: $container->get(CreciRepositorio::class),
			discord: $container->get(Discord::class),
			cache: $container->get(Cache::class),
			captcha: $container->get(Captcha::class),
			mensageria: $container->get(Mensageria::class)
		);
	}
]);

return $container->build();