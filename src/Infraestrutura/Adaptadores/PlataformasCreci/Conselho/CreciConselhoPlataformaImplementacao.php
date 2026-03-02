<?php

declare(strict_types=1);

namespace App\Infraestrutura\Adaptadores\PlataformasCreci\Conselho;

use Override;
use Exception;
use GuzzleHttp\Client;
use App\Aplicacao\CasosDeUso\PlataformaCreci;
use App\Aplicacao\Compartilhado\Captcha\Captcha;
use App\Aplicacao\Compartilhado\Discord\Discord;
use App\Aplicacao\Compartilhado\Discord\Enums\CanalTexto;
use App\Aplicacao\CasosDeUso\EntradaESaida\SaidaConsultarCreciPlataforma;

class CreciConselhoPlataformaImplementacao implements PlataformaCreci
{
    private Client $clientHttp;
    private string $baseURL;

    // Sitekey do Cloudflare Turnstile utilizado por todos os estados do Conselho Nacional
    private const TURNSTILE_SITEKEY = '0x4AAAAAAB5EssxvqmsTJ5Wx';

    public function __construct(
        private string $uf,
        private Discord $discord,
        private Captcha $captcha,
    ){
        $this->baseURL = 'https://www.creci'.mb_strtolower($this->uf).'.conselho.net.br';
        $this->clientHttp = new Client([
            "base_uri" => $this->baseURL,
            "timeout" => 99
        ]);
    }

    private function consultarApiCreci($uri, $body)
    {
        try {
            $response = $this->clientHttp->post($uri, [
                'headers' => [
					'Content-Type' => 'application/x-www-form-urlencoded',
					'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36'
				],
                "form_params" => $body
            ]);
            
        } catch(Exception $e){
            throw new Exception("Erro ao consultar a API do CRECI " . $this->uf . ": " . $e->getMessage());
        }

        if($response->getStatusCode() !== 200){
            throw new Exception("Creci invalido!");
        }

        return $response;
    }

    #[Override] public function consultarCreci(string $creci, string $tipoCreci): SaidaConsultarCreciPlataforma
    {
        $pageUrl = $this->baseURL . '/form_pesquisa_cadastro_geral_site.php';

        // Resolver o Cloudflare Turnstile via 2Captcha
        $this->discord->enviarMensagem(
            canalTexto: CanalTexto::CONSULTAS,
            mensagem: "Resolvendo Cloudflare Turnstile para CRECI {$this->uf}..."
        );

        $captchaResolvido = $this->captcha->resolverTurnstile(
            siteKey: self::TURNSTILE_SITEKEY,
            pageUrl: $pageUrl,
        );

        $this->discord->enviarMensagem(
            canalTexto: CanalTexto::CONSULTAS,
            mensagem: "Turnstile resolvido para CRECI {$this->uf}. Token: " . substr($captchaResolvido->get(), 0, 20) . "..."
        );

        // Enviar requisição com o token do Turnstile
        $creciConsultado = $this->consultarApiCreci('/form_pesquisa_cadastro_geral_site.php', [
            "inscricao" => $creci,
            "token" => $captchaResolvido->get(),
        ]);

        $responseBody = $creciConsultado->getBody()->getContents();

        // A API do Conselho Nacional agora retorna HTML (Vue.js/Quasar) em vez de JSON.
        // Precisamos parsear o HTML para extrair os dados da tabela de resultados.
        $dados = $this->parsearRespostaHTML($responseBody);

        if(empty($dados)){
            throw new Exception("CRECI Inexistente no estado informado");
        }

        return new SaidaConsultarCreciPlataforma(
            inscricao: $dados['inscricao'],
            nomeCompleto: $dados['nomeCompleto'],
            fantasia: '',
            situacao: $dados['situacao'],
            cidade: '',
            estado: $this->uf,
            numeroDocumento: '',
            telefone: $dados['telefone'],
        );
    }

    /**
     * Parseia a resposta HTML do Conselho Nacional para extrair dados do CRECI.
     * O HTML contém uma tabela Quasar com colunas:
     * Nome | Nº Inscrição | Situação | Certidão de Regularidade | Tel. Comercial | Certidão
     */
    private function parsearRespostaHTML(string $html): array
    {
        // Verificar se a resposta contém dados (se não tem <tbody>, não houve resultado)
        if(!str_contains($html, '<tbody>')){
            return [];
        }

        // Extrair o conteúdo do tbody
        if(!preg_match('/<tbody>(.*?)<\/tbody>/s', $html, $tbodyMatch)){
            return [];
        }

        $tbody = $tbodyMatch[1];

        // Extrair a primeira linha da tabela (primeiro resultado)
        if(!preg_match('/<tr>(.*?)<\/tr>/s', $tbody, $trMatch)){
            return [];
        }

        $tr = $trMatch[1];

        // Extrair todas as células <td>
        preg_match_all('/<td[^>]*>(.*?)<\/td>/s', $tr, $tdMatches);

        if(count($tdMatches[1]) < 6){
            return [];
        }

        // td[0] = avatar (ignorar)
        // td[1] = Nome (dentro de <div>NOME</div>)
        // td[2] = Nº Inscrição
        // td[3] = Situação (ATIVO/INATIVO)
        // td[4] = Certidão de Regularidade (REGULAR/IRREGULAR)
        // td[5] = Telefone

        // Extrair nome do segundo td
        $nomeCompleto = '';
        if(preg_match('/<div>([^<]+)<\/div>/', $tdMatches[1][1], $nomeMatch)){
            $nomeCompleto = trim($nomeMatch[1]);
        }

        // Extrair inscrição do terceiro td
        $inscricao = trim(strip_tags($tdMatches[1][2]));

        // Extrair situação do quarto td
        $situacaoTexto = trim(strip_tags($tdMatches[1][3]));
        $situacao = str_contains(mb_strtoupper($situacaoTexto), 'ATIVO') && !str_contains(mb_strtoupper($situacaoTexto), 'INATIVO')
            ? 'Ativo'
            : 'Inativo';

        // Extrair telefone do sexto td
        $telefoneTexto = trim(strip_tags($tdMatches[1][5]));
        $telefone = str_contains($telefoneTexto, 'DIVULGADO') ? '' : $telefoneTexto;

        if(empty($nomeCompleto) || empty($inscricao)){
            return [];
        }

        return [
            'inscricao' => $inscricao,
            'nomeCompleto' => $nomeCompleto,
            'situacao' => $situacao,
            'telefone' => $telefone,
        ];
    }
}
