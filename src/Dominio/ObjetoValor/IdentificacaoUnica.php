<?php

declare(strict_types=1);

namespace App\Dominio\ObjetoValor;

use Exception;

final readonly class IdentificacaoUnica {

	private string $value;
    function __construct(
        private string $data = ''
    ){

        if(strlen($this->data) == 36){
            $this->value = $this->data;
            return;
        }

		$data = $this->data;

		if(empty($data)){
			$data = random_bytes(16);
		}

		if(strlen($data) < 8){
			throw new Exception('O código informado está inválido. (' . $data . ')');
		}

        assert(strlen($data) == 16);
    
        // Set version to 0100
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        // Set bits 6-7 to 10
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        // Output the 36 character UUID.
        $data = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
		$this->value = $data;
    }

    function get(): string
    {
        return $this->value;
    }
}