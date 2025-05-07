<?php

declare(strict_types=1);

namespace App\Infraestrutura\APIs\BuscaCorretor\Controladores\Status;

use Exception;
use DI\Container;
use App\Aplicacao\CasosDeUso\ConsultarCreci;
use App\Aplicacao\CasosDeUso\EntradaESaida\ErroDomain;
use App\Aplicacao\CasosDeUso\EntradaESaida\SaidaCodigoSolicitacao;
use App\Infraestrutura\APIs\BuscaCorretor\Controladores\Middlewares\Controller;

final class StatusController extends Controller
{

    public function __construct(
	    private Container $container
    ){}

    public function index()
    {

		if(!isset($_GET['codigo_solicitacao']) OR empty($_GET['codigo_solicitacao'])){
			$this->response(['message' => 'Informe o código de solicitação'], 400);
			return;
		}

		try {

			$codigoSolicitacao = htmlspecialchars(strip_tags(trim($_GET['codigo_solicitacao'])));

			$consultarCreci = $this->container->get(ConsultarCreci::class);
			$resposta = $consultarCreci->consultarCodigoSolicitacao($codigoSolicitacao);

			if($resposta instanceof ErroDomain){
				$this->response([
					'statusCode' => $resposta->codigo,
					'statusMessage' => 'Bad Request',
					'message' => $resposta->mensagem
				]);
				return;
			}

			if(is_a($resposta, SaidaCodigoSolicitacao::class)){
				$this->response([
					'statusCode' => 200,
					'statusMessage' => 'OK',
					'codigoSolicitacao' => $resposta->codigoSolicitacao,
					'status' => $resposta->status,
					'mensagem' => $resposta->mensagem,
					'creciID' => $resposta->creciID,
					'creciCompleto' => $resposta->creciCompleto
				]);
				return;
			}

			$this->response([
				'statusCode' => 422,
				'statusMessage' => 'Unprocessable Entity',
				'message' => 'Ocorreu um erro ao processar a consulta do CRECI. Tente novamente mais tarde.'
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

