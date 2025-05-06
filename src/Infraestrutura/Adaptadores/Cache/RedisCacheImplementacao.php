<?php

declare(strict_types=1);

namespace App\Infraestrutura\Adaptadores\Cache;

use App\Aplicacao\Compartilhado\Cache;
use App\Aplicacao\Compartilhado\Envrionment;
use App\Dominio\ObjetoValor\IPHost;
use Predis\Autoloader;
use Predis\Client;
use Override;

final readonly class RedisCacheImplementacao implements Cache
{

    private Client $redis;

    public function __construct(
        readonly private Envrionment $env
    ){

        Autoloader::register();

        $configuracao = [
            "scheme" => $this->env->get('REDIS_SCHEME'),
            "host" => $this->env->get('REDIS_HOST'),
            "port" => $this->env->get('REDIS_PORT'),
            "password" => $this->env->get('REDIS_PASSWORD'),
        ];

        $this->redis = new Client($configuracao);
    }

    #[Override] public function get(string $key): string | bool
    {
        if($this->redis->exists($key)){
            return $this->redis->get($key);
        }
        return false;
    }

    #[Override] public function set(string $key, string $value): void
    {
        $this->redis->set($key, $value);
    }

    #[Override] public function delete(string $key): void
    {
        $this->redis->del($key);
    }

    #[Override] public function expire(string $key, int $seconds): void
    {
        $this->redis->expire($key, $seconds);
    }
}
