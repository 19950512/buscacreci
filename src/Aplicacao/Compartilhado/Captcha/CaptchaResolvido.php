<?php

declare(strict_types=1);

namespace App\Aplicacao\Compartilhado\Captcha;

final class CaptchaResolvido
{
    public function __construct(
        public string $token,
    ){}

    public function get(): string
    {
        return $this->token;
    }
}