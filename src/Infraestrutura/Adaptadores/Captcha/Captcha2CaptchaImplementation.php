<?php

declare(strict_types=1);

namespace App\Infraestrutura\Adaptadores\Captcha;

use Override;
use Exception;
use App\Aplicacao\Compartilhado\Envrionment;
use App\Aplicacao\Compartilhado\Captcha\Captcha;
use App\Aplicacao\Compartilhado\Captcha\CaptchaResolvido;

class Captcha2CaptchaImplementation implements Captcha
{

    public function __construct(
        private Envrionment $env
    ){}
    
    #[Override] public function resolver(string $siteKey, string $pageUrl): CaptchaResolvido
    {
        $apiKey = $this->env->get('CAPTCHA_TOKEN_2CAPTCHA');

        // Envia a solicitação para 2Captcha resolver o reCAPTCHA
        $request = [
            'key' => $apiKey,
            'method' => 'userrecaptcha',
            'googlekey' => $siteKey,
            'pageurl' => $pageUrl,
            'json' => 1
        ];

        $response = file_get_contents('http://2captcha.com/in.php?' . http_build_query($request));
        $responseData = json_decode($response, true);

        if(isset($responseData['status']) and $responseData['status'] == 1) {
            // Captura o ID da solicitação para resolver o reCAPTCHA
            $captchaId = $responseData['request'];

            // Aguardar até que o reCAPTCHA seja resolvido (sem limite de tentativas)
            while (true) {
                sleep(5); // Espera 5 segundos antes de verificar novamente

                // Verifica se o reCAPTCHA foi resolvido
                $result = file_get_contents('http://2captcha.com/res.php?key=' . $apiKey . '&action=get&id=' . $captchaId . '&json=1');
                $resultData = json_decode($result, true);

                if ($resultData['status'] == 1) {
                    return new CaptchaResolvido($resultData['request']); // Retorna o token do reCAPTCHA
                }
            }
        }

        throw new Exception('Falha ao resolver o reCAPTCHA com 2Captcha.');
    }
}