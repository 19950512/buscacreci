<?php

declare(strict_types=1);

namespace App\Dominio\Entidades;

use App\Dominio\ObjetoValor\Apelido;
use App\Dominio\ObjetoValor\Ativo;
use App\Dominio\ObjetoValor\CNPJ;
use App\Dominio\ObjetoValor\CPF;
use App\Dominio\ObjetoValor\Creci;
use App\Dominio\ObjetoValor\DocumentoIdentificacao;
use App\Dominio\ObjetoValor\DocumentoNaoInformado;
use App\Dominio\ObjetoValor\Endereco\Estado;
use App\Dominio\ObjetoValor\IdentificacaoUnica;
use App\Dominio\ObjetoValor\NomeCompleto;
use App\Dominio\Repositorios\EntradaESaida\SaidaInformacoesCreci;
use DateTime;

class CreciEntidade
{
	public function __construct(
		readonly public IdentificacaoUnica $codigo,
		public Creci $creci,
		public NomeCompleto $nomeCompleto,
		public DocumentoIdentificacao $numeroDocumento,
		public Ativo $situacao,
		public Apelido $cidade,
		public Estado $estado,
		public DateTime $atualizadoEm
	){}

	public static function build(SaidaInformacoesCreci $parametros): CreciEntidade
	{

		$codigo = new IdentificacaoUnica($parametros->creciCodigo);
		$creci = new Creci($parametros->creciCompleto);
		$nomeCompleto = new NomeCompleto($parametros->nomeCompleto);
		$situacao = new Ativo($parametros->situacao == 'Ativo');
		$cidade = new Apelido($parametros->cidade);
		$estado = new Estado($parametros->estado);
		$atualizadoEm = new DateTime($parametros->atualizadoEm);

		$numeroDocumento = new DocumentoNaoInformado();
		if(CPF::valido($parametros->numeroDocumento)) {
			$numeroDocumento = new CPF($parametros->numeroDocumento);
		}else if (CNPJ::valido($parametros->numeroDocumento)) {
			$numeroDocumento = new CNPJ($parametros->numeroDocumento);
		}

		return new CreciEntidade(
			codigo: $codigo,
			creci: $creci,
			nomeCompleto: $nomeCompleto,
			numeroDocumento: $numeroDocumento,
			situacao: $situacao,
			cidade: $cidade,
			estado: $estado,
			atualizadoEm: $atualizadoEm
		);
	}
}
