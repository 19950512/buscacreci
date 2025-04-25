<?php

declare(strict_types=1);

namespace App\Aplicacao\Compartilhado\Captcha;

use App\Aplicacao\Compartilhado\Captcha\CaptchaResolvido;

interface Captcha
{
    public function resolver(string $siteKey, string $pageUrl): CaptchaResolvido;
}