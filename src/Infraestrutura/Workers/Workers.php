<?php

declare(strict_types=1);

namespace App\Infraestrutura\Workers;

use DateTime;
use Exception;
use DateTimeZone;
use \Di\Container;
use PhpAmqpLib\Message\AMQPMessage;
use App\Aplicacao\Compartilhado\Discord\Discord;
use App\Aplicacao\Compartilhado\Mensageria\Mensageria;
use App\Aplicacao\Compartilhado\Discord\Enums\CanalTexto;
use App\Aplicacao\Compartilhado\Mensageria\Enumerados\Evento;

class Workers
{
    private Container $container;

    public function __construct(
        private Evento $evento,
        private int $maximoDeTentativasDeProcessamento,
        private $lidarComMensagem,
        private array $mensagensQueNaoDevemSerProcessadas = []
    ){
        $this->container = require __DIR__ . '/../../Aplicacao/Compartilhado/Container.php';

        echo $this->momento()." | Container iniciado.\n";

        $this->maximoDeTentativasDeProcessamento = max($this->maximoDeTentativasDeProcessamento, 0);
    }

    private function momento(): string
    {
        return date('d/m/Y H:i:s');
    }

    private function processarComRetry(AMQPMessage $mensagem): void
    {
        $tentativas = 0;
        $maxTentativas = $this->maximoDeTentativasDeProcessamento;

        if($maxTentativas === -1){
            $maxTentativas = PHP_INT_MAX;
        }

        echo str_repeat('-', 50) . "\n";

        echo $this->momento() . " | 📩 mensagem recebida.\n";

        $propriedades = $mensagem->get_properties();

        if(isset($propriedades['application_headers'])){
            // Acessando o cabeçalho x-death
            $headers = $mensagem->get('application_headers');
            
            if ($headers && $headers->getNativeData()) {
                $xDeathHeader = $headers->getNativeData()['x-death'] ?? [];
            } else {
                $xDeathHeader = [];
            }
    
            // Verificando o histórico de falhas
            if (!empty($xDeathHeader)) {
                foreach ($xDeathHeader as $death) {
                    echo "\n";
    
                    echo "Mensagem foi rejeitada da fila: {$death['queue']}\n";
                    echo "Motivo: {$death['reason']}\n";
                    echo "Tentativas anteriores: {$death['count']}\n";
    
                    if(is_a(($death['time'] ?? ''), DateTime::class)){
                        // Criar objeto DateTime em UTC
                        $death['time']->setTimezone(new DateTimeZone('UTC'));
    
                        // Converter para America/Sao_Paulo
                        $death['time']->setTimezone(new DateTimeZone('America/Sao_Paulo'));
    
                        echo "Hora: " . $death['time']->format('d/m/Y H:i:s') . "\n";
                    }
    
                    echo "\n";
                }
    
                if(isset($xDeathHeader[0], $xDeathHeader[0]['count'])){
                    $tentativas = $xDeathHeader[0]['count'];
                }
    
                // Lógica para maxRetry, exemplo: interromper após 3 tentativas
                if ($xDeathHeader[0]['count'] >= $maxTentativas) {
                    echo "Mensagem atingiu o número máximo de tentativas. Não será processada.\n";
                    $mensagem->ack(); // Ack para remover da fila
            
                    echo str_repeat('_', 50) . "\n";

                    $mensagemMaximoInformacoes = [];
                    $mensagemMaximoInformacoes['detalhes'] = $xDeathHeader;
                    $mensagemMaximoInformacoes['headers'] = $mensagem->get_properties();
                    $mensagemMaximoInformacoes['mensagem'] = $mensagem->getBody();

                    /*
                    $this->container->get(Mensageria::class)->publicar(
                        evento: Evento::MensagensComProblema,
                        message: json_encode($mensagemMaximoInformacoes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
                    );
                    */
    
                    return; // Ou pode mover para uma fila de erro
                }
            }
        }

        try {

            if($maxTentativas === PHP_INT_MAX){
                echo $this->momento() . " | Tentativa " . ($tentativas + 1) . " de " . "infinitas" . "\n";
            }else{
                echo $this->momento() . " | Tentativa " . ($tentativas + 1) . " de " . ($maxTentativas + 1) . "\n";
            }

            ($this->lidarComMensagem)($this->container, $mensagem);

            echo $this->momento() . " | Processamento concluído com sucesso.\n";

            $mensagem->ack();

        } catch (Exception $erro) {

            foreach($this->mensagensQueNaoDevemSerProcessadas as $mensagemDeErro){
                if(str_contains($erro->getMessage(), $mensagemDeErro)){
                    echo $this->momento() . " | Erro ao processar a message: " . $erro->getMessage() . "\n";
                    echo $this->momento() . " | Mensagem não será reenviada.\n";
                    $mensagem->ack(); // Ack para remover da fila
                    return;
                }
            }

            echo $this->momento() . " | Erro ao processar a message: " . $erro->getMessage() . "\n";

            if($maxTentativas === PHP_INT_MAX){
                echo $this->momento() . " | Tentando novamente.\n";
            }

            // Rejeitar a mensagem original sem reencaminhá-la automaticamente
            $mensagem->nack(false, false); // Rejeita a mensagem e a envia para o DLQ
        
        }finally{
            echo str_repeat('_', 50) . "\n";
        }
    }

    public function start(): void
    {
        while(true){

            try {
                echo $this->momento()." | Estamos prontos para receber mensagens da fila ".$this->evento->value."\n";
                echo $this->momento()." | Configurada com $this->maximoDeTentativasDeProcessamento tentativas máxima de processamento.\n";
                echo $this->momento()." | 👷 Worker rodando... Pressione CTRL+C para sair\n";

                $this->container->get(Discord::class)->enviarMensagem(
                    canalTexto: CanalTexto::WORKERS,
                    mensagem: "O Worker {$this->evento->value} está pronto para receber mensagens da fila.\n"
                );

                $this->container->get(Mensageria::class)->inscrever(
                    evento: $this->evento,
                    retrochamada: function(AMQPMessage $mensagem) {
                        echo $this->momento()." | Recebemos uma mensagem\n";
                        $this->processarComRetry($mensagem); // Chama o método de processamento com retry
                    }
                );

            } catch (Exception $erro) {
                echo $this->momento()." | Ops, a fila caiu\n";
                echo $erro->getMessage()."\n";

                if($erro->getMessage() === 'Channel connection is closed.'){
                    echo $this->momento()." | É o fim...\n";
                    break;
                }

                $this->container->get(Discord::class)->enviarMensagem(
                    canalTexto: CanalTexto::WORKERS,
                    mensagem: 'Ops, a fila caiu '.$this->evento->value. PHP_EOL.$erro->getMessage()
                );
            }
        }
    }
}
