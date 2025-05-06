<?php

declare(strict_types=1);

namespace App\Infraestrutura\APIs\BuscaCorretor\Controladores\Index;

use Exception;
use DI\Container;
use App\Aplicacao\CasosDeUso\ConsultarCreci;
use App\Aplicacao\CasosDeUso\EntradaESaida\ErroDomain;
use App\Dominio\ObjetoValor\IdentificacaoUnica;
use App\Infraestrutura\APIs\BuscaCorretor\Controladores\Middlewares\Controller;

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

			$creci = htmlspecialchars(strip_tags(trim($_GET['creci'])));

			$consultarCreci = $this->container->get(ConsultarCreci::class);
			$resposta = $consultarCreci->consultarCreci($creci);

			if($resposta instanceof ErroDomain){
				$this->response([
					'statusCode' => $resposta->codigo,
					'statusMessage' => 'Bad Request',
					'message' => $resposta->mensagem
				]);
				return;
			}

			if(is_a($resposta, IdentificacaoUnica::class)){
				$this->response([
					'statusCode' => 200,
					'statusMessage' => 'OK',
					'message' => 'Seu CRECI foi enviado para o sistema de consulta, você pode acompanhar o status da consulta pelo código abaixo.',
					'codigo_solicitacao' => $resposta->get()
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

