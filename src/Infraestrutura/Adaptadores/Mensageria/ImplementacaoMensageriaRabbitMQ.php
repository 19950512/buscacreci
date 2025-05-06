<?php

declare(strict_types=1);

namespace App\Infraestrutura\Adaptadores\Mensageria;

use Exception;
use PhpAmqpLib\Wire\AMQPTable;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Exception\AMQPIOException;
use App\Aplicacao\Compartilhado\Envrionment;
use PhpAmqpLib\Exception\AMQPRuntimeException;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use App\Aplicacao\Compartilhado\Mensageria\Mensageria;
use App\Aplicacao\Compartilhado\Mensageria\Enumerados\Fila;
use App\Aplicacao\Compartilhado\Mensageria\Enumerados\Evento;
use App\Aplicacao\Compartilhado\Mensageria\Enumerados\TrocaMensagens;

readonly class ImplementacaoMensageriaRabbitMQ implements Mensageria
{

    private AMQPStreamConnection $connection;
    private AMQPChannel $channel;

    public function __construct(
        private Envrionment $ambiente
    ){
        
        $host = $this->ambiente->get('EVENT_BUS_HOST');
        $port = $this->ambiente->get('EVENT_BUS_PORT');
        $user = $this->ambiente->get('EVENT_BUS_USER');
        $password = $this->ambiente->get('EVENT_BUS_PASSWORD');
        $max_retry_connections = (int) $this->ambiente->get('EVENT_BUS_MAX_RETRY_CONNECTIONS');
        $retry_delay_seconds = (int) $this->ambiente->get('EVENT_BUS_RETRY_CONNECTIONS_DELAY_SECONDS');

        $attempts = 0;
        while ($attempts < $max_retry_connections) {
            try {
                $this->connection = new AMQPStreamConnection(
                    $host,
                    $port,
                    $user,
                    $password
                );
                break;
            } catch (AMQPIOException $e) {
                echo "Erro de E/S: " . $e->getMessage() . "\n";
            } catch (AMQPRuntimeException $e) {
                echo "Erro de tempo de execução: " . $e->getMessage() . "\n";
            } catch (Exception $e) {
                echo "Erro desconhecido: " . $e->getMessage() . "\n";
            }

            $attempts++;
            if ($attempts < $max_retry_connections) {
               // echo "Tentando novamente em $retry_delay_seconds segundos...\n";
                sleep($retry_delay_seconds);
            } else {
               // echo "Limite máximo de tentativas de conexão excedido\n";
                break;
            }
        }

        $this->channel = $this->connection->channel();
    }

    public function publicar(Evento $evento, string $message): void
    {

        $queue = $evento->Filas();

        $mensagem = new AMQPMessage(
            body: $message,
            properties: [
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT
            ]
        );

        // Inicia a transação
       // $this->channel->tx_select();

        try {

            $this->channel->basic_publish(
                msg: $mensagem,
                routing_key: $queue->value
            );
            // Confirma a transação
           // $this->channel->tx_commit();

        }catch(Exception $e) {

            $erro = $e->getMessage();

            // Desfaz a transação
           // $this->channel->tx_rollback();
        }
    }

    public function inscrever(Evento $evento, callable $retrochamada): void
    {

        $queue = $evento->Filas();

        $this->channel->basic_qos(
            prefetch_size: null,
            prefetch_count: 1, // Quantidade de mensagens que o consumidor pode receber por vez até que ele ambiente um ack
            a_global: null
        );

        try{

            $this->channel->basic_consume(
                queue: $queue->value,
                no_ack: false,
                callback: $retrochamada
            );

            /*
             Não usar isso, da forma que eu imagino, essa pratica não é útil.
             while ($this->channel->is_consuming()) {
                $this->channel->wait(
                    timeout: 20
                );
            }*/

        }catch(Exception $e){

            $erro = $e->getMessage();

            if(str_contains($erro, 'NOT_FOUND - no queue')){

                throw new Exception("A fila {$queue->value} não existe");
            }

            throw new Exception($erro);
        }

        while(count($this->channel->callbacks)) {
            $this->channel->wait();
        }
    }


    public function deletarFilas(): void
    {
        $queues = Fila::Filas();

        foreach($queues as $queue){
            $this->channel->queue_delete($queue['queue']->value);
        }
    }

    public function deletarTrocaMensagens(): void
    {
        $exchanges = TrocaMensagens::trocasMensagens();

        foreach($exchanges as $exchange){
            $this->channel->exchange_delete($exchange['exchange']->value);
        }
    }

    public function criarFilas(): void
    {
        // Create the exchanges
        $this->declarar_trocas_mensagens();

        // Create the queues
        $this->declarar_filas();

        // Bind the queues to the exchanges
        $this->ligar_filas();
    }

    private function declarar_trocas_mensagens(): void
    {

        foreach(TrocaMensagens::trocasMensagens() as $exchange){

            $this->channel->exchange_declare(
                exchange: $exchange['exchange']->value,
                type: $exchange['type'],
                durable: true,
                auto_delete: false
            );
        }
    }

    private function declarar_filas(): void
    {

        foreach(Fila::Filas() as $queue){

            $argumentsDLX = [];
            if(isset($queue['dlx']) and is_a($queue['dlx'], TrocaMensagens::class)){
                $argumentsDLX['x-dead-letter-exchange'] = $queue['dlx']->value;
            }

            $this->channel->queue_declare(
                queue: $queue['queue']->value,
                durable: true,
                auto_delete: false,
                arguments: new AMQPTable($argumentsDLX)
            );
        }
    }

    private function ligar_filas(): void
    {

        foreach(Fila::Ligacoes() as $bind){

            if(!is_a($bind['queue'], Fila::class) OR !is_a($bind['exchange'], TrocaMensagens::class)){
                throw new Exception("A fila {$bind['queue']->value} não pode ser vinculada a troca {$bind['exchange']->value}");
            }

            $this->channel->queue_bind(
                queue: $bind['queue']->value,
                exchange: $bind['exchange']->value,
            );
        }
    }

    public function __destruct()
    {
        $this->channel->close();
        $this->connection->close();
    }
}