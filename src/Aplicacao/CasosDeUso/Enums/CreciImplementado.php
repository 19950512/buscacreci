<?php

declare(strict_types=1);

namespace App\Aplicacao\CasosDeUso\Enums;

enum CreciImplementado: string
{
	case RS = 'RS';
	case ES = 'ES';
	// SP removido temporariamente - CRECI SP usa reCAPTCHA Enterprise
	// cuja validação server-side está rejeitando 100% dos tokens
	// (inclusive tokens gerados por Chrome real). Reativar quando resolver.
	// case SP = 'SP';
}