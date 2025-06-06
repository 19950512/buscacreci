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

    #[Override] public function resolverV3(string $siteKey, string $pageUrl): CaptchaResolvido
    {

        $clientKey = $this->env->get('CAPTCHA_TOKEN_2CAPTCHA');
        $minScore = 0.9;
        $pageAction = 'submit_broker_search';

        $createTaskPayload = [
            'clientKey' => $clientKey,
            'task' => [
                'type' => 'RecaptchaV3TaskProxyless',
                'websiteURL' => $pageUrl,
                'websiteKey' => $siteKey,
                'minScore' => $minScore,
                'pageAction' => $pageAction,
                'isEnterprise' => false,
                'apiDomain' => 'www.recaptcha.net'
            ]
        ];

        $createTaskResponse = $this->postJson('https://api.2captcha.com/createTask', $createTaskPayload);
        d('Create Task Response: ' . json_encode($createTaskResponse));

        if (!isset($createTaskResponse['errorId']) || $createTaskResponse['errorId'] !== 0) {
            throw new Exception('Erro ao criar tarefa de reCAPTCHA v3: ' . json_encode($createTaskResponse));
        }

        $taskId = $createTaskResponse['taskId'];

        // Aguarda o resultado
        $resultUrl = 'https://api.2captcha.com/getTaskResult';
        $startTime = time();

        while (true) {
            sleep(5);

            $getResultPayload = [
                'clientKey' => $clientKey,
                'taskId' => $taskId
            ];

            $resultResponse = $this->postJson($resultUrl, $getResultPayload);
            d('Result Response: ' . json_encode($resultResponse));

            if (isset($resultResponse['status']) && $resultResponse['status'] === 'ready') {
                return new CaptchaResolvido($resultResponse['solution']['gRecaptchaResponse']);
            }

            if (time() - $startTime > 120) {
                throw new Exception('Tempo limite excedido para resolver o reCAPTCHA v3.');
            }
        }

        throw new Exception('Falha ao resolver o reCAPTCHA v3 com 2Captcha.');
    }
    
    #[Override] public function resolverV2(string $siteKey, string $pageUrl): CaptchaResolvido
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

        d('SiteKey: ' . $siteKey);
        d('PageUrl: ' . $pageUrl);
        d('Response: ' . $response);
        d('ResponseData: ' . json_encode($responseData));

        if(isset($responseData['status']) and $responseData['status'] == 1) {
            // Captura o ID da solicitação para resolver o reCAPTCHA
            $captchaId = $responseData['request'];

            // Aguardar até que o reCAPTCHA seja resolvido (sem limite de tentativas)
            while (true) {
                sleep(5); // Espera 5 segundos antes de verificar novamente

                // Verifica se o reCAPTCHA foi resolvido
                $result = file_get_contents('http://2captcha.com/res.php?key=' . $apiKey . '&action=get&id=' . $captchaId . '&json=1');

                d($result);
                $resultData = json_decode($result, true);

                if ($resultData['status'] == 1) {
                    return new CaptchaResolvido($resultData['request']); // Retorna o token do reCAPTCHA
                }
            }
        }

        throw new Exception('Falha ao resolver o reCAPTCHA com 2Captcha.');
    }

    private function postJson(string $url, array $payload): array
    {
        $options = [
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-Type: application/json\r\n",
                'content' => json_encode($payload),
                'timeout' => 60
            ]
        ];

        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);

        if ($result === false) {
            throw new Exception('Erro na requisição HTTP para ' . $url);
        }

        return json_decode($result, true);
    }
}