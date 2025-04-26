<?php

declare(strict_types=1);

namespace App\Infraestrutura\Adaptadores\PlataformasCreci\Conselho;

use App\Aplicacao\CasosDeUso\EntradaESaida\SaidaConsultarCreciPlataforma;
use App\Aplicacao\CasosDeUso\PlataformaCreci;
use GuzzleHttp\Client;
use Exception;
use Override;

class CreciConselhoPlataformaImplementacao implements PlataformaCreci
{
    private Client $clientHttp;

    public function __construct(
        private string $uf,
    )
    {
        $this->clientHttp = new Client([
            "base_uri" => "https://www.creci".mb_strtolower($this->uf).".conselho.net.br",
            "timeout" => 99
        ]);
    }

    private function consultarApiCreci($uri, $body)
    {
        try {
            $response = $this->clientHttp->post($uri, [
                'headers' => [
					'Content-Type' => 'application/x-www-form-urlencoded',
					'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.3'
				],
                "form_params" => $body
            ]);
            
        } catch(Exception $e){
            throw new Exception("Erro ao consultar a API do creci PR". $e->getMessage());
        }

        if($response->getStatusCode() !== 200){
            throw new Exception("Creci invalido!");
        }

        return $response;
    }

    #[Override] public function consultarCreci(string $creci, string $tipoCreci): SaidaConsultarCreciPlataforma
    {
        $creciConsultado = $this->consultarApiCreci('/form_pesquisa_cadastro_geral_site.php', ["inscricao" => $creci]);
        $creciResponse = json_decode($creciConsultado->getBody()->getContents(), true);
        if(empty($creciResponse['cadastros'])){
            throw new Exception("CRECI Inexistente no estado informado");
        }


        $situacao = match($creciResponse['cadastros'][0]['regular']){
            false => "Inativo",
            true => "Ativo",
            default => throw new Exception("Erro ao consultar a situacao do creci")
        };
        
        $body = [
            "inscricao" => strval($creciResponse['cadastros'][0]['creci']),
            "nomeCompleto" => $creciResponse['cadastros'][0]['nome'],
            "cidade" => '',
            "estado" => $this->uf,
            "documento" => $creciResponse['cadastros'][0]['cpf'],
            "fantasia" => "",
            "telefone" => $creciResponse['cadastros'][0]['telefones'][0] ?? '',
        ];

        return new SaidaConsultarCreciPlataforma(
            inscricao: $body['inscricao'],
            nomeCompleto: $body['nomeCompleto'],
            fantasia: $body['fantasia'],
            situacao: $situacao,
            cidade: $body['cidade'],
            estado: $body['estado'],
            numeroDocumento: $body['documento'],
            telefone: $body['telefone'],
        );
    }
}
