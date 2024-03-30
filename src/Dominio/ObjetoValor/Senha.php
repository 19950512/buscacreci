<?php

declare(strict_types=1);

namespace App\Dominio\ObjetoValor;

use Exception;

final readonly class Senha
{
	public int $limiteMaximoCaracteresSenha;
	public int $limiteMinimoCaracteresSenha;

	public function __construct(
		private string $senha
	){
		$this->limiteMaximoCaracteresSenha = 50;
		$this->limiteMinimoCaracteresSenha = 8;

		if(strlen($this->senha) < $this->limiteMinimoCaracteresSenha){
			throw new Exception("A senha precisa ter no mínimo {$this->limiteMinimoCaracteresSenha} caracteres.");
		}

		if(strlen($this->senha) > $this->limiteMaximoCaracteresSenha){
			throw new Exception("A senha atingiu o limite máximo de {$this->limiteMaximoCaracteresSenha} caracteres.");
		}

		if(!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[$@$!%*?&])[A-Za-z\d$@$!%*?&]{8,50}$/', $this->senha)){
			throw new Exception("A senha precisa ter no mínimo 1 letra maiúscula, 1 letra minúscula, 1 número e 1 caractere especial.");
		}

		if(preg_match('/(.)\1{2,}/', $this->senha)){
			throw new Exception("A senha não pode ter sequências de caracteres repetidos.");
		}

		if(in_array($this->senha, $this->getListaSenhaComuns())){
			throw new Exception("A senha informada é muito comum. Por favor, escolha uma senha mais segura.");
		}
	}

	public function get(): string
	{
		return $this->senha;
	}

	private function getListaSenhaComuns(): array
	{
		return [
			'password',
			'123456',
			'123456789',
			'qwerty',
			'password',
			'1234567',
			'12345678',
			'12345',
			'iloveyou',
			'111111',
			'123123',
			'abc123',
			'qwerty123',
			'1q2w3e4r',
			'admin',
			'qwertyuiop',
			'654321',
			'555555',
			'lovely',
			'7777777',
			'welcome',
			'888888',
			'princess',
			'dragon',
			'password1',
			'123qwe',
			'666666',
			'1qaz2wsx',
			'121212',
			'flower',
			'password123',
			'1234',
			'letmein',
			'mustang',
			'000000',
			'1234567890',
			'123456a',
			'654321',
			'123321',
			'123qwe',
			'123abc',
			'qwe123',
			'qweasd',
			'qweasdzxc',
			'qazwsx',
			'qazwsx123',
			'qazxsw',
			'qweasdzxc',
			'qweasd',
			'qwe123',
			'qweasd123',
			'qweasdzxc',
			'qweasdzxc123',
			'qazwsx',
			'qazwsx123',
			'qazxsw',
			'qazxsw123',
			'qazxswedc',
			'qazxswedc123',
			'qazwsxedc',
			'qazwsxedc123',
			'qazwsxedcrfv',
			'qazwsxedcrfv123',
			'qazwsxedcrfvtgb',
			'qazwsxedcrfvtgb123',
		];
	}
}
