<?php

declare(strict_types=1);

namespace App\Infraestrutura\APIs\BuscaCorretor\Controladores\Creci;

use Exception;
use DI\Container;
use App\Aplicacao\CasosDeUso\ConsultarCreci;
use App\Aplicacao\CasosDeUso\EntradaESaida\ErroDomain;
use App\Aplicacao\CasosDeUso\EntradaESaida\SaidaCreci;
use App\Aplicacao\CasosDeUso\EntradaESaida\SaidaCodigoSolicitacao;
use App\Infraestrutura\APIs\BuscaCorretor\Controladores\Middlewares\Controller;

final class CreciController extends Controller
{

    public function __construct(
	    private Container $container
    ){}

    public function index()
    {

		if(!isset($_GET['id']) OR empty($_GET['id'])){
			$this->response(['message' => 'Informe o código do Creci'], 400);
			return;
		}

		try {

			$codigoCreci = htmlspecialchars(strip_tags(trim($_GET['id'])));

			$consultarCreci = $this->container->get(ConsultarCreci::class);
			$resposta = $consultarCreci->consultarCreciCodigo($codigoCreci);

			if($resposta instanceof ErroDomain){
				$this->response([
					'statusCode' => $resposta->codigo,
					'statusMessage' => 'Bad Request',
					'message' => $resposta->mensagem
				]);
				return;
			}

			if(is_a($resposta, SaidaCreci::class)){
				$this->response([
					'statusCode' => 200,
					'statusMessage' => 'OK',
					'codigo' => $resposta->creciID,
					'creciCompleto' => $resposta->creciCompleto,
					'nomeCompleto' => $resposta->nomeCompleto,
					'situacao' => $resposta->situacao,
					'cidade' => $resposta->cidade,
					'estado' => $resposta->estado,
					'momento' => $resposta->data,
				]);
				return;
			}

			$this->response([
				'statusCode' => 422,
				'statusMessage' => 'Unprocessable Entity',
				'message' => 'Ocorreu um erro ao processar a consulta do CRECI. Tente novamente mais tarde.'
			]);

		}catch (Exception $e){

			error_log('CreciController error: ' . $e->getMessage());

			$statusCode = 400;
			$statusMessage = 'Bad Request';
			$userMessage = 'Ocorreu um erro ao consultar os dados do CRECI.';
			if(str_contains($e->getMessage(), 'não foi encontrado no CRECI')){
				$statusCode = 404;
				$statusMessage = 'Creci não encontrado';
				$userMessage = 'O CRECI informado não foi encontrado.';
			}

			$this->response([
				'statusCode' => $statusCode,
				'statusMessage' => $statusMessage,
				'message' => $userMessage
			]);
		}
    }
}

