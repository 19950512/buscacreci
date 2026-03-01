<?php

declare(strict_types=1);

namespace App\Infraestrutura\APIs\BuscaCorretor\Controladores\Ultimoscrecis;

use PDO;
use Exception;
use DI\Container;
use App\Infraestrutura\APIs\BuscaCorretor\Controladores\Middlewares\Controller;

final class UltimoscrecisController extends Controller
{

    public function __construct(
	    private Container $container
    ){}

    public function index()
    {
		try {
			$pdo = $this->container->get(PDO::class);
			$consulta = $pdo->prepare('SELECT
				creci_id as codigo,
				creci_completo as "creciCompleto",
				nome_completo as "nomeCompleto",
				situacao,
				cidade,
				estado,
				atualizado_em as momento
			FROM creci
			ORDER BY atualizado_em DESC
			LIMIT 10');
			$consulta->execute();
			$resultados = $consulta->fetchAll(PDO::FETCH_ASSOC);

			$this->response([
				'statusCode' => 200,
				'statusMessage' => 'OK',
				'data' => $resultados
			]);
		} catch (Exception $e) {
			$this->response([
				'statusCode' => 500,
				'statusMessage' => 'Internal Server Error',
				'message' => 'Erro ao buscar os últimos CRECIs consultados.'
			]);
		}
    }
}

