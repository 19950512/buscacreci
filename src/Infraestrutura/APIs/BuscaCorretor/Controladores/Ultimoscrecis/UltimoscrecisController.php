<?php

declare(strict_types=1);

namespace App\Infraestrutura\APIs\BuscaCorretor\Controladores\Ultimoscrecis;

use App\Dominio\ObjetoValor\IdentificacaoUnica;
use DI\Container;
use App\Infraestrutura\APIs\BuscaCorretor\Controladores\Middlewares\Controller;

final class UltimoscrecisController extends Controller
{

    public function __construct(
	    private Container $container
    ){}

    public function index()
    {
		$this->response([
			'statusCode' => 200,
			'statusMessage' => 'OK',
			'data' => [
				[
					'codigo' => (new IdentificacaoUnica())->get(),
					'creciCompleto' => 'RS12345F',
					'nomeCompleto' => 'João da Silva Mock',
					'situacao' => 'Ativo',
					'cidade' => 'Porto Alegre',
					'estado' => 'RS',
					'momento' => '2023-10-10T10:00:00Z',
				],
				[
					'codigo' => (new IdentificacaoUnica())->get(),
					'creciCompleto' => 'RS67890F',
					'nomeCompleto' => 'Maria Oliveira Mock',
					'situacao' => 'Inativo',
					'cidade' => 'Caxias do Sul',
					'estado' => 'RS',
					'momento' => '2023-10-11T11:00:00Z',
				],
				[
					'codigo' => (new IdentificacaoUnica())->get(),
					'creciCompleto' => 'RS54321F',
					'nomeCompleto' => 'Carlos Pereira Mock',
					'situacao' => 'Ativo',
					'cidade' => 'Bento Gonçalves',
					'estado' => 'RS',
					'momento' => '2023-10-12T12:00:00Z',
				],
				[
					'codigo' => (new IdentificacaoUnica())->get(),
					'creciCompleto' => 'RS98765F',
					'nomeCompleto' => 'Ana Costa Mock',
					'situacao' => 'Inativo',
					'cidade' => 'Gramado',
					'estado' => 'RS',
					'momento' => '2023-10-13T13:00:00Z',
				],
				[
					'codigo' => (new IdentificacaoUnica())->get(),
					'creciCompleto' => 'RS13579F',
					'nomeCompleto' => 'Lucas Almeida Mock',
					'situacao' => 'Ativo',
					'cidade' => 'Novo Hamburgo',
					'estado' => 'RS',
					'momento' => '2023-10-14T14:00:00Z',
				],
				[
					'codigo' => (new IdentificacaoUnica())->get(),
					'creciCompleto' => 'RS24680F',
					'nomeCompleto' => 'Fernanda Lima Mock',
					'situacao' => 'Inativo',
					'cidade' => 'Pelotas',
					'estado' => 'RS',
					'momento' => '2023-10-15T15:00:00Z',
				],
				[
					'codigo' => (new IdentificacaoUnica())->get(),
					'creciCompleto' => 'RS86420F',
					'nomeCompleto' => 'Ricardo Santos Mock',
					'situacao' => 'Ativo',
					'cidade' => 'Santa Maria',
					'estado' => 'RS',
					'momento' => '2023-10-16T16:00:00Z',
				],
				[
					'codigo' => (new IdentificacaoUnica())->get(),
					'creciCompleto' => 'RS75319F',
					'nomeCompleto' => 'Patrícia Rocha Mock',
					'situacao' => 'Inativo',
					'cidade' => 'Uruguaiana',
					'estado' => 'RS',
					'momento' => '2023-10-17T17:00:00Z',
				],
				[
					'codigo' => (new IdentificacaoUnica())->get(),
					'creciCompleto' => 'RS15973F',
					'nomeCompleto' => 'Gabriel Martins Mock',
					'situacao' => 'Ativo',
					'cidade' => 'Rio Grande',
					'estado' => 'RS',
					'momento' => '2023-10-18T18:00:00Z',
				],
				[
					'codigo' => (new IdentificacaoUnica())->get(),
					'creciCompleto' => 'RS35791F',
					'nomeCompleto' => 'Juliana Ferreira Mock',
					'situacao' => 'Inativo',
					'cidade' => 'Ijuí',
					'estado' => 'RS',
					'momento' => '2023-10-19T19:00:00Z',
				],

			]
		]);
    }
}

