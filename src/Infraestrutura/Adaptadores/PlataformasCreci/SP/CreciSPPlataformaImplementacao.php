<?php

declare(strict_types=1);

namespace App\Infraestrutura\Adaptadores\PlataformasCreci\SP;

use DOMXPath;
use Override;
use Exception;
use DOMDocument;
use GuzzleHttp\Cookie\CookieJar;
use App\Aplicacao\CasosDeUso\PlataformaCreci;
use App\Aplicacao\Compartilhado\Captcha\Captcha;
use App\Aplicacao\Compartilhado\Discord\Discord;
use App\Infraestrutura\Adaptadores\PlataformasCreci\Robots;
use App\Aplicacao\CasosDeUso\EntradaESaida\SaidaConsultarCreciPlataforma;
use App\Aplicacao\Compartilhado\Discord\Enums\CanalTexto;

class CreciSPPlataformaImplementacao implements PlataformaCreci
{
	private \GuzzleHttp\Client $clientHttp;

    private CookieJar $cookieJar;

	public function __construct(
        private Captcha $captcha,
        private Discord $discord
    ){

		$this->clientHttp = new \GuzzleHttp\Client([
		    'base_uri' => 'https://www.creci-rs.gov.br',
		    'timeout'  => 9999.0,
			'origin' => 'www.creci-rs.gov.br'
		]);

        $this->cookieJar = new CookieJar(true);
	}

	#[Override] public function consultarCreci(string $creci, string $tipoCreci): SaidaConsultarCreciPlataforma
    {

        $pageUrl = 'https://www.crecisp.gov.br/cidadao/buscaporcorretores';

        if(!Robots::isAllowedByRobotsTxt($pageUrl)){
            $this->discord->enviarMensagem(
                canalTexto: CanalTexto::WORKERS,
                mensagem: 'Acesso negado pelo robots.txt - URL: '.$pageUrl,
            );
            throw new Exception('Acesso negado pelo robots.txt');
        }

        // 2. Acessa a página e captura o sitekey do reCAPTCHA
        $response = $this->clientHttp->get($pageUrl, ['cookies' => $this->cookieJar]);
        $html = (string) $response->getBody();

        preg_match('/data-sitekey="([^"]+)"/', $html, $matches);
        $siteKey = $matches[1];
        
        // 3. Resolve o reCAPTCHA
        $captchaResponse = $this->captcha->resolver($siteKey, $pageUrl);

        $captchaResponse = $captchaResponse->get();

        // 4. Submete o formulário com os dados do corretor
        $searchUrl = 'https://www.crecisp.gov.br/cidadao/buscaporcorretores';

        if(!Robots::isAllowedByRobotsTxt($searchUrl)){
            $this->discord->enviarMensagem(
                canalTexto: CanalTexto::WORKERS,
                mensagem: 'Acesso negado pelo robots.txt - URL: '.$searchUrl,
            );
            throw new Exception('Acesso negado pelo robots.txt');
        }

        $response = $this->clientHttp->post($searchUrl, [
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
                'Accept-Language' => 'pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
                'Referer' => $pageUrl,
                'Origin' => 'https://www.crecisp.gov.br',
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Upgrade-Insecure-Requests' => '1',
                'Sec-Fetch-Site' => 'same-origin',
                'Sec-Fetch-Mode' => 'navigate',
                'Sec-Fetch-User' => '?1',
                'Sec-Fetch-Dest' => 'document',
                // 'Cookie' → o Guzzle com CookieJar já cuida disso!
            ],
            'cookies' => $this->cookieJar,
            'form_params' => [
                'IsFinding' => 'True',
                'RegisterNumber' => $creci,
                'CPF' => '',
                'Name' => '',
                'Area' => '',
                'City' => '',
                'Language' => '',
                'Avaliador' => '',
                'g-recaptcha-response' => $captchaResponse,
            ],
        ]);

        // 5. Extrai o link da lista de corretores
        $listPage = (string) $response->getBody();

        if(!preg_match('/corretordetalhes\?registerNumber=([^"]+)/', $listPage, $detalhes)){
            throw new Exception('Não foi possível encontrar o corretor.');
        }

        $registerNumber = $detalhes[1];

        // 6. Acessa a página de detalhes
        $detalhesUrl = "https://www.crecisp.gov.br/cidadao/corretordetalhes?registerNumber={$registerNumber}";

        if(!Robots::isAllowedByRobotsTxt($detalhesUrl)){
            $this->discord->enviarMensagem(
                canalTexto: CanalTexto::WORKERS,
                mensagem: 'Acesso negado pelo robots.txt - URL: '.$detalhesUrl,
            );
            throw new Exception('Acesso negado pelo robots.txt');
        }

        $response = $this->clientHttp->post($detalhesUrl, [
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
                'Accept-Language' => 'pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
                'Referer' => 'https://www.crecisp.gov.br/cidadao/listadecorretores',
                'Origin' => 'https://www.crecisp.gov.br',
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Upgrade-Insecure-Requests' => '1',
                'Sec-Fetch-Site' => 'same-origin',
                'Sec-Fetch-Mode' => 'navigate',
                'Sec-Fetch-User' => '?1',
                'Sec-Fetch-Dest' => 'document',
            ],
            'cookies' => $this->cookieJar,
            'body' => '' // obrigatório pois o método é POST, mas sem payload
        ]);

        libxml_use_internal_errors(true); // evitar warnings por HTML malformado

        $dom = new DOMDocument();
        $dom->loadHTML((string) $response->getBody());
        $xpath = new DOMXPath($dom);

        // Localiza o escopo principal da div
        $mainDiv = $xpath->query("//div[contains(@class, 'main-container') and contains(@class, 'cidadao') and contains(@class, 'corretordetalhes')]")->item(0);

        if (!$mainDiv) {
            throw new Exception("Não foi possível localizar a div principal com os dados do corretor.");
        }

        // Criar novo DOM apenas com o conteúdo da div principal
        $newDom = new DOMDocument();
        $newDom->appendChild($newDom->importNode($mainDiv, true));
        $newXpath = new DOMXPath($newDom);

        // Pegar a imagem de perfil dentro da div principal
        $imgTag = $newXpath->query("//img")->item(0);
        $fotoPerfil = $imgTag ? $imgTag->getAttribute('src') : null;

        if ($fotoPerfil && str_starts_with($fotoPerfil, '//')) {
            $fotoPerfil = 'https:' . $fotoPerfil;
        }

        $fotoPerfil = explode('?', $fotoPerfil)[0]; // remove parâmetros da URL

        // se no html tiver um id btnShowPhones, então o corretor tem telefone
        $hasPhones = $newXpath->query("//button[@id='btnShowPhones']")->length > 0;
        $phones = [];
        if($hasPhones){
            try {

                $phones = $this->getDetalhes($registerNumber, 'phones');
            }catch (Exception $e) {
                $phones = [];
            }
        }

        // se no html tiver um id secondaryEmail, então o corretor tem email
        $hasEmail = $newXpath->query("//span[@id='secondaryEmail']")->length > 0;
        $email = [];
        if($hasEmail){
            try {
                $email = $this->getDetalhes($registerNumber, 'secondaryEmail');
            }catch (Exception $e) {
                $email = [];
            }
        }

        try {
            $enderecos = $this->getDetalhes($registerNumber, 'addresses');
        }catch (Exception $e) {
            $enderecos = [];
        }

        // Unificação e limpeza
        $phonesTemp = $phones['values'] ?? [];
        if (!empty($phones['value'])) $phonesTemp[] = $phones['value'];

        $emailsTemp = $email['values'] ?? [];
        if (!empty($email['value'])) $emailsTemp[] = $email['value'];

        $enderecosTemp = $enderecos['values'] ?? [];
        if (!empty($enderecos['value'])) $enderecosTemp[] = $enderecos['value'];

        $dados = [
            'creci' => $registerNumber,
            'nome' => $this->extractText($newXpath, "//h3[1]"),
            'dataInscricao' => $this->extractText($newXpath, "//label[@for='CurrentCategoryDate']/parent::strong/following-sibling::span"),
            'situacao' => $this->extractText($newXpath, "//label[@for='RegistrationStatus']/parent::strong/following-sibling::span"),
            'foto' => $fotoPerfil,
            'telefones' => array_unique($phonesTemp),
            'emails' => array_unique($emailsTemp),
            'enderecos' => array_unique($enderecosTemp),
        ];

        return new SaidaConsultarCreciPlataforma(
            inscricao: $dados['creci'],
            nomeCompleto: $dados['nome'],
            fantasia: $dados['nome'],
            situacao: $dados['situacao'],
            cidade: 'SP',
            estado: 'SP'
        );
    }

    // Função auxiliar para extrair textos
    private function extractText($xpath, $query) {
        $node = $xpath->query($query)->item(0);
        return $node ? trim($node->nodeValue) : null;
    }

    private function getDetalhes($creci, $infoType): array
    {
    
        $siteKey = '6LdeSBITAAAAAMq-ckp15zFfmVs0ZXMNwnCPxkob';
        $detalhesUrl = 'https://www.crecisp.gov.br/cidadao/corretordetalhes?registerNumber=' . $creci;

        if(!Robots::isAllowedByRobotsTxt($detalhesUrl)){
            $this->discord->enviarMensagem(
                canalTexto: CanalTexto::WORKERS,
                mensagem: 'Acesso negado pelo robots.txt - URL: '.$detalhesUrl,
            );
            throw new Exception('Acesso negado pelo robots.txt');
        }
        
        $captchaResponse = $this->captcha->resolver($siteKey, $detalhesUrl);

        $captchaResponse = $captchaResponse->get();
        
        $curl = curl_init();
        
        $headers = [
            'Content-Type: application/json',
            'Origin: https://www.crecisp.gov.br',
            "Referer: $detalhesUrl",
            'User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36',
        ];

        $baseURL = "https://www.crecisp.gov.br/api/details/broker";

        if(!Robots::isAllowedByRobotsTxt($baseURL)){
            $this->discord->enviarMensagem(
                canalTexto: CanalTexto::WORKERS,
                mensagem: 'Acesso negado pelo robots.txt - URL: '.$baseURL,
            );
            throw new Exception('Acesso negado pelo robots.txt');
        }
        
        curl_setopt_array($curl, [
          CURLOPT_URL => $baseURL,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => 'POST',
          CURLOPT_POSTFIELDS => "{\n\t\"infoType\":\"$infoType\",\n\t\"creci\":\"$creci\",\n\t \"captchaResponse\":\"$captchaResponse\"\n}",
          CURLOPT_HTTPHEADER => $headers,
        ]);
        
        $response = curl_exec($curl);
        $err = curl_error($curl);
        
        curl_close($curl);
        
        if ($err) {
            throw new Exception("cURL Error #:" . $err);
        } else {
            $json = json_decode($response, true);
            return $json;
        }
    }
}