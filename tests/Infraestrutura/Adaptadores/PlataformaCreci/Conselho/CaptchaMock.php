<?php

declare(strict_types=1);

namespace Tests\Infraestrutura\Adaptadores\PlataformaCreci\Conselho;

use App\Aplicacao\Compartilhado\Captcha\Captcha;
use App\Aplicacao\Compartilhado\Captcha\CaptchaResolvido;

class CaptchaMock implements Captcha
{
    public function resolverV2(string $siteKey, string $pageUrl): CaptchaResolvido
    {
        return new CaptchaResolvido('mock-token-v2');
    }

    public function resolverV3(string $siteKey, string $pageUrl, bool $isEnterprise = false, string $pageAction = 'submit_broker_search'): CaptchaResolvido
    {
        return new CaptchaResolvido('mock-token-v3');
    }

    public function resolverTurnstile(string $siteKey, string $pageUrl): CaptchaResolvido
    {
        return new CaptchaResolvido('mock-token-turnstile');
    }
}
