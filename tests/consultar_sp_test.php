#!/usr/bin/env php
<?php

/**
 * Script de teste direto para consultar CRECI SP 123546-F
 * Executa o scraper SP diretamente, sem depender de RabbitMQ/Redis/Discord.
 * Salva o resultado no banco PostgreSQL local.
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Aplicacao\Compartilhado\Discord\Discord;
use App\Aplicacao\Compartilhado\Discord\Enums\CanalTexto;
use App\Aplicacao\Compartilhado\Envrionment;
use App\Dominio\Entidades\CreciEntidade;
use App\Dominio\ObjetoValor\IdentificacaoUnica;
use App\Dominio\Repositorios\EntradaESaida\SaidaInformacoesCreci;
use App\Dominio\Repositorios\EntradaESaida\EntradaSalvarCreciConsultado;
use App\Infraestrutura\Adaptadores\EnvrionmentImplementacao;
use App\Infraestrutura\Adaptadores\PlataformasCreci\SP\CreciSPPlataformaImplementacao;

// ── Discord stub (sem webhook configurado, só loga no terminal) ──
class DiscordTerminal implements Discord
{
    public function enviarMensagem(CanalTexto $canalTexto, string $mensagem): void
    {
        $canal = $canalTexto->value;
        echo "[Discord/{$canal}] {$mensagem}\n";
    }
}

// ── Main ──
echo "=== Teste CRECI SP - Consulta 123546-F ===\n\n";

// 1. Configurar dependências
$env = new EnvrionmentImplementacao();
$discord = new DiscordTerminal();

// 2. Instanciar o scraper SP (usa headless Chrome, não precisa de 2Captcha)
$scraperSP = new CreciSPPlataformaImplementacao(
    discord: $discord,
);

// 3. Executar a consulta
$creci = '123546';
$tipoCreci = 'F';

echo "\n[Scraper] Iniciando consulta CRECI {$creci}-{$tipoCreci} no CRECI SP...\n";
echo "[Scraper] Isso pode demorar 30-120s (resolução de captcha)...\n\n";

$startTime = time();

try {
    $resultado = $scraperSP->consultarCreci(creci: $creci, tipoCreci: $tipoCreci);
    $elapsed = time() - $startTime;

    echo "\n[✅ SUCESSO] Consulta concluída em {$elapsed}s!\n";
    echo str_repeat('─', 50) . "\n";
    echo "  Inscrição:     {$resultado->inscricao}\n";
    echo "  Nome Completo: {$resultado->nomeCompleto}\n";
    echo "  Fantasia:      {$resultado->fantasia}\n";
    echo "  Situação:      {$resultado->situacao}\n";
    echo "  Cidade:        {$resultado->cidade}\n";
    echo "  Estado:        {$resultado->estado}\n";
    echo "  Documento:     {$resultado->numeroDocumento}\n";
    echo "  Data:          {$resultado->data}\n";
    echo str_repeat('─', 50) . "\n";

    // 4. Salvar no banco PostgreSQL
    echo "\n[DB] Salvando no banco de dados...\n";

    $dbHost = $env::get('DB_HOST');
    // Se o host é 'postgres' (Docker), usar localhost
    if ($dbHost === 'postgres') {
        $dbHost = 'localhost';
    }
    $dbPort = $env::get('DB_PORT');
    $dbName = $env::get('DB_DATABASE');
    $dbUser = $env::get('DB_USERNAME');
    $dbPass = $env::get('DB_PASSWORD');

    $dsn = "pgsql:host={$dbHost};dbname={$dbName};port={$dbPort}";
    $pdo = new PDO($dsn, (string) $dbUser, (string) $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "[DB] Conexão estabelecida com sucesso.\n";

    // Construir a entidade CRECI
    $creciCodigo = (new IdentificacaoUnica())->get();
    $creciCompleto = "CRECI/SP {$creci}-{$tipoCreci}";

    $paramsBuildCreciEntidade = new SaidaInformacoesCreci(
        creciCodigo: $creciCodigo,
        creciCompleto: $creciCompleto,
        creciEstado: $resultado->estado,
        nomeCompleto: $resultado->nomeCompleto,
        atualizadoEm: date('Y-m-d H:i:s'),
        situacao: $resultado->situacao,
        cidade: $resultado->cidade,
        estado: $resultado->estado,
        numeroDocumento: $resultado->numeroDocumento,
        data: $resultado->data,
    );

    $creciEntity = CreciEntidade::build($paramsBuildCreciEntidade);

    // Salvar no banco
    $stmt = $pdo->prepare('INSERT INTO creci (
        creci_id,
        creci_completo,
        creci_estado,
        nome_completo,
        cidade,
        estado,
        numero_documento,
        atualizado_em,
        situacao
    ) VALUES (
        :creci_id,
        :creci_completo,
        :creci_estado,
        :nome_completo,
        :cidade,
        :estado,
        :numero_documento,
        :atualizado_em,
        :situacao
    )');

    $stmt->execute([
        ':creci_id' => $creciEntity->codigo->get(),
        ':creci_completo' => $creciEntity->creci->get(),
        ':creci_estado' => $creciEntity->estado->get(),
        ':nome_completo' => $creciEntity->nomeCompleto->get(),
        ':cidade' => $creciEntity->cidade->get(),
        ':estado' => $creciEntity->estado->get(),
        ':numero_documento' => $creciEntity->numeroDocumento->get(),
        ':atualizado_em' => $creciEntity->atualizadoEm->format('Y-m-d H:i:s'),
        ':situacao' => $creciEntity->situacao->get() ? 'Ativo' : 'Inativo',
    ]);

    echo "[DB] ✅ CRECI salvo com sucesso! ID: {$creciCodigo}\n";

    // Verificar o que foi salvo
    $verify = $pdo->query("SELECT * FROM creci WHERE creci_id = '{$creciCodigo}'")->fetch(PDO::FETCH_ASSOC);
    echo "\n[DB] Verificação do registro salvo:\n";
    foreach ($verify as $key => $value) {
        echo "  {$key}: {$value}\n";
    }

} catch (Exception $e) {
    $elapsed = time() - $startTime;
    echo "\n[❌ ERRO] Falha após {$elapsed}s\n";
    echo "  Classe:    " . get_class($e) . "\n";
    echo "  Mensagem:  " . $e->getMessage() . "\n";
    echo "  Arquivo:   " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "\n  Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n=== Teste concluído com sucesso! ===\n";
