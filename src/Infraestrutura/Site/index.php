<?php

declare(strict_types=1);

namespace App\Infraestrutura\Site;

if(!is_file(__DIR__ . '/../../../vendor/autoload.php')){
	die('Você precisa rodar o comando composer install');
}

if(!is_file(__DIR__.'/../../../.env')){
	die('Você precisa criar o arquivo .env');
}

use App\Aplicacao\CasosDeUso\ConsultarCreci;
use Exception;

$container = require __DIR__ . '/../../Aplicacao/Compartilhado/Container.php';

echo '<h1>Buscar Creci</h1>';

$resultado = '';
$creciBuscado = '';
if(isset($_POST['creci'])){
	$creciBuscado = $_POST['creci'];
	$consultarCreci = $container->get(ConsultarCreci::class);

	try {

		$saidaCreci = $consultarCreci->consultarCreci($creciBuscado);

		$resultado = "<p>Inscrição: $saidaCreci->creciCompleto</p>";
		$resultado .= "<p>Nome Completo: $saidaCreci->nomeCompleto</p>";
		$resultado .= "<p>Situação: $saidaCreci->situacao</p>";
		$resultado .= "<p>Cidade: $saidaCreci->cidade</p>";
		$resultado .= "<p>Estado: $saidaCreci->estado</p>";
		$resultado .= "<p>Documento: $saidaCreci->numeroDocumento</p>";
	}catch (Exception $e){
		$resultado = $e->getMessage();
	}
}

echo <<<htmlsites
<form action="/" method="post">
	<input type="text" name="creci" id="creci" placeholder="Digite o Creci" value="$creciBuscado">
	<button id="consultar">Consultar</button>
</form>
<hr>
<div id="resultado">$resultado</div>

htmlsites;

