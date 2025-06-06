<?php

declare(strict_types=1);

namespace App\Aplicacao\Compartilhado\Captcha;

use App\Aplicacao\Compartilhado\Captcha\CaptchaResolvido;

interface Captcha
{
    public function resolverV2(string $siteKey, string $pageUrl): CaptchaResolvido;
    public function resolverV3(string $siteKey, string $pageUrl): CaptchaResolvido;
}