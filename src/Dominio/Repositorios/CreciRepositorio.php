<?php

declare(strict_types=1);

namespace App\Dominio\Repositorios;

use App\Dominio\Repositorios\EntradaESaida\EntradaSalvarCreciConsultado;
use App\Dominio\Repositorios\EntradaESaida\SaidaInformacoesCreci;

interface CreciRepositorio
{
	public function creciJaFoiConsultadoAntes(string $creci): bool;
	public function buscarInformacoesCreci(string $creci): SaidaInformacoesCreci;
	public function salvarCreciConsultado(EntradaSalvarCreciConsultado $parametros): void;
}