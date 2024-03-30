<?php

declare(strict_types=1);

namespace App\Dominio\ObjetoValor\Endereco;

use App\Dominio\ObjetoValor\TextoSimples;
use App\Dominio\ObjetoValor\Endereco\CEP;
use App\Dominio\ObjetoValor\Endereco\Estado;
use App\Dominio\ObjetoValor\Endereco\Pais;
use App\Dominio\ObjetoValor\Endereco\Localizacao\Latitude;
use App\Dominio\ObjetoValor\Endereco\Localizacao\Longitude;
use App\Dominio\ObjetoValor\Endereco\Localizacao\Localizacao;

final class Endereco
{
    public function __construct(
        public ?TextoSimples $rua = null,
        public ?TextoSimples $numero = null,
        public ?TextoSimples $bairro = null,
        public ?TextoSimples $cidade = null,
        public ?Estado $estado = null,
        public ?Pais $pais = null,
        public ?CEP $cep = null,
        public ?TextoSimples $complemento = null,
        public ?TextoSimples $referencia = null,
        public ?Localizacao $localizacao = null,
    ){}

    public function setParams(array $params): void
    {
        $numero = null;
        $rua = null;
        $cep = null;
        $complemento = null;
        $bairro = null;
        $cidade = null;
        $estado = null;
        $pais = new Pais('Brazil');
        $referencia = null;
        $localizacao = null;

        if(isset($params['numero']) and !empty($params['numero'])){
            $numero = new TextoSimples($params['numero']);
        }
        if(isset($params['rua']) and !empty($params['rua'])){
            $rua = new TextoSimples($params['rua']);
        }
        if(isset($params['cep']) and !empty($params['cep'])){
            $cep = new CEP($params['cep']);
        }
        if(isset($params['complemento']) and !empty($params['complemento'])){
            $complemento = new TextoSimples($params['complemento']);
        }
        if(isset($params['bairro']) and !empty($params['bairro'])){
            $bairro = new TextoSimples($params['bairro']);
        }
        if(isset($params['cidade']) and !empty($params['cidade'])){
            $cidade = new TextoSimples($params['cidade']);
        }
        if(isset($params['estado']) and !empty($params['estado'])){
            $estado = new Estado($params['estado']);
        }
        if(isset($params['pais']) and !empty($params['pais'])){
            $pais = new Pais($params['pais']);
        }
        if(isset($params['referencia']) and !empty($params['referencia'])){
            $referencia = new TextoSimples($params['referencia']);
        }
        if(isset($params['latitude'], $params['longitude']) and !empty($params['latitude']) and !empty($params['longitude'])){
            $localizacao = new Localizacao(
                latitude: new Latitude((float) $params['latitude']),
                longitude: new Longitude((float) $params['longitude'])
            );
        }

        $this->numero = $numero;
        $this->rua = $rua;
        $this->cep = $cep;
        $this->complemento = $complemento;
        $this->bairro = $bairro;
        $this->cidade = $cidade;
        $this->estado = $estado;
        $this->pais = $pais;
        $this->referencia = $referencia;
        $this->localizacao = $localizacao;
    }

	public function enderecoCompleto(): string
	{
		$informacoes = [];
		if(is_a($this->rua, TextoSimples::class)){
			$informacoes[] = $this->rua->get();
		}
		if(is_a($this->numero, TextoSimples::class)){
			$informacoes[] = $this->numero->get();
		}
		if(is_a($this->bairro, TextoSimples::class)){
			$informacoes[] = $this->bairro->get();
		}
		if(is_a($this->cidade, TextoSimples::class)){
			$informacoes[] = $this->cidade->get();
		}
		if(is_a($this->estado, Estado::class)){
			$informacoes[] = $this->estado->getFull();
		}
		if(is_a($this->pais, Pais::class)){
			$informacoes[] = $this->pais->getFull();
		}
		if(is_a($this->cep, CEP::class)){
			$informacoes[] = $this->cep->get();
		}
		return implode(', ', $informacoes);
	}

    public function get(): array
    {
        return [
            'rua' => is_a($this->rua, TextoSimples::class) ? $this->rua->get() : '',
            'numero' => is_a($this->rua, TextoSimples::class) ? $this->numero->get() : '',
            'bairro' => is_a($this->numero, TextoSimples::class) ? $this->bairro->get() : '',
            'cidade' => is_a($this->bairro, TextoSimples::class) ? $this->cidade->get() : '',
            'estado' => is_a($this->estado, Estado::class) ? $this->estado->get() : '',
            'pais' => is_a($this->pais, Pais::class) ? $this->pais->get() : '',
            'cep' => is_a($this->cep, CEP::class) ? $this->cep->get() : '',
            'complemento' => is_a($this->complemento, TextoSimples::class) ? $this->complemento->get() : '',
            'referencia' => is_a($this->referencia, TextoSimples::class) ? $this->referencia->get() : '',
            'localizacao' => is_a($this->localizacao, Localizacao::class) ? $this->localizacao->get() : '',
        ];
    }
}