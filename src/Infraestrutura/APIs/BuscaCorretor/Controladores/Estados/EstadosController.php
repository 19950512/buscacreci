<?php

declare(strict_types=1);

namespace App\Infraestrutura\APIs\BuscaCorretor\Controladores\Estados;

use DI\Container;
use App\Dominio\ObjetoValor\Endereco\Estado;
use App\Dominio\Entidades\ConselhoNacionalCRECI\ConselhoNacionalCRECI;
use App\Aplicacao\CasosDeUso\Enums\CreciImplementado;
use App\Infraestrutura\APIs\BuscaCorretor\Controladores\Middlewares\Controller;

final class EstadosController extends Controller
{
    public function __construct(
        private Container $container
    ){}

    public function index()
    {
        $estados = Estado::getEstados();
        $conselho = new ConselhoNacionalCRECI();
        
        $resultado = [];
        
        foreach ($estados as $uf => $nomeCompleto) {
            if ($uf === 'NN') continue;
            
            $fonteConsulta = 'Não disponível';
            $disponivel = false;
            
            // Verifica se tem scraper dedicado
            $creciImplementado = CreciImplementado::tryFrom($uf);
            if ($creciImplementado !== null) {
                $fonteConsulta = 'Scraper dedicado + Conselho Nacional (fallback)';
                $disponivel = true;
            } elseif ($conselho->estadoPossuiMembroAtivo($uf)) {
                $fonteConsulta = 'Conselho Nacional CRECI';
                $disponivel = true;
            }
            
            $resultado[] = [
                'uf' => $uf,
                'estado' => $nomeCompleto,
                'disponivel' => $disponivel,
                'fonte' => $fonteConsulta,
            ];
        }
        
        $this->response([
            'statusCode' => 200,
            'statusMessage' => 'OK',
            'totalEstados' => count(array_filter($resultado, fn($e) => $e['disponivel'])),
            'data' => $resultado,
        ]);
    }
}
