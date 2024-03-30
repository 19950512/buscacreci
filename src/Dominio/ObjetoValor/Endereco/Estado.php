<?php

declare(strict_types=1);

namespace App\Dominio\ObjetoValor\Endereco;

use Exception;

final class Estado
{
    public function __construct(
        private string $state
    )
    {
        /* if(!self::validation($this->state)){
            throw new Exception('State is not valid');
        } */

        if(empty($this->state)){
            throw new Exception("Estado precisa ser informado");
        }

        if(strlen($this->state) == 2){

            if(!self::validationUF($this->state)){
                throw new Exception("Estado informado não é válido. (".$this->state.")");
            }

            if(isset(self::$states[strtoupper($this->state)])){
                $this->state = self::$states[$this->state];
                return;
            }
            
            throw new Exception("Estado informado não existe. (".$this->state.")");
        }


        if(strlen($this->state) > 2){

            $state = array_search($this->state, self::$states);

            if($state){
                $this->state = self::$states[$state];
                return;
            }

            throw new Exception("Estado informado não existe. (".$this->state.")");
        }

        throw new Exception("Estado informado não é válido. (".$this->state.")");
    }

    private static $states = [
        'AC' => 'Acre',
        'AL' => 'Alagoas',
        'AP' => 'Amapá',
        'AM' => 'Amazonas',
        'BA' => 'Bahia',
        'CE' => 'Ceará',
        'DF' => 'Distrito Federal',
        'ES' => 'Espírito Santo',
        'GO' => 'Goiás',
        'MA' => 'Maranhão',
        'MT' => 'Mato Grosso',
        'MS' => 'Mato Grosso do Sul',
        'MG' => 'Minas Gerais',
        'PA' => 'Pará',
        'PB' => 'Paraíba',
        'PR' => 'Paraná',
        'PE' => 'Pernambuco',
        'PI' => 'Piauí',
        'RJ' => 'Rio de Janeiro',
        'RN' => 'Rio Grande do Norte',
        'RS' => 'Rio Grande do Sul',
        'RO' => 'Rondônia',
        'RR' => 'Roraima',
        'SC' => 'Santa Catarina',
        'SP' => 'São Paulo',
        'SE' => 'Sergipe',
        'TO' => 'Tocantins',
	    'NN' => 'Não informado'
    ];

    public function getUF(): string
    {
        return array_search($this->state, self::$states);
    }

    public function getFull(): string
    {
        return $this->state;
    }

    public function get(): string
    {
        return $this->getUF();
    }

	public static function getEstados(): array
	{
		return self::$states;
	}

    public static function validationUF(string $uf): bool
    {
        return !!preg_match('/^[A-Z]{2,2}$/', $uf);
    }
}