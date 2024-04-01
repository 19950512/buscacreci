<?php

declare(strict_types=1);

namespace App\Infraestrutura\APIs\BuscaCorretor\Controladores\Index;

use App\Aplicacao\CasosDeUso\ConsultarCreci;
use App\Infraestrutura\APIs\BuscaCorretor\Controladores\Middlewares\Controller;
use DI\Container;
use Exception;

final class IndexController extends Controller
{

    public function __construct(
	    private Container $container
    ){}

    public function index()
    {

		if(!isset($_GET['creci']) OR empty($_GET['creci'])){
			$this->response(['message' => 'Informe o parametro query CRECI'], 400);
			return;
		}

		try {

			$creci = trim($_GET['creci']);

			$consultarCreci = $this->container->get(ConsultarCreci::class);
			$saidaCreci = $consultarCreci->consultarCreci($creci);

			$this->response([
				'codigo' => $saidaCreci->creciID,
				'creciCompleto' => $saidaCreci->creciCompleto,
				'nomeCompleto' => $saidaCreci->nomeCompleto,
				'situacao' => $saidaCreci->situacao,
				'cidade' => $saidaCreci->cidade,
				'estado' => $saidaCreci->estado,
			]);

		}catch (Exception $e){

			$statusCode = 400;
			$statusMessage = 'Bad Request';
			if(str_contains($e->getMessage(), 'não foi encontrado no CRECI')){
				$statusCode = 404;
				$statusMessage = 'Creci não encontrado';
			}

			$this->response([
				'statusCode' => $statusCode,
				'statusMessage' => $statusMessage,
				'message' => $e->getMessage()
			]);
		}
    }
}

