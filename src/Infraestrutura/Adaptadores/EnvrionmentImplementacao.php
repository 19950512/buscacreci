<?php

declare(strict_types=1);

namespace App\Infraestrutura\Adaptadores;
use App\Aplicacao\Compartilhado\Envrionment;
use Exception;

class EnvrionmentImplementacao implements Envrionment
{

    private static string $pathForLOG = 'Shared -> Envoriment';

    private static string $pathForENV = __DIR__.'/../../../.env';

    private static array $env = [];

    private static function load(): void
    {

        if(!empty(self::$env)){
            return;
        }

        if(!file_exists(self::$pathForENV)){
            throw new Exception("Arquivo .env não encontrado.");
        }

        $env = file_get_contents(self::$pathForENV);

        if(empty($env)){
            throw new Exception('Arquivo .env está vazio.');
        }

        $env = explode(PHP_EOL, $env);

        foreach($env as $key => $value){
            $value = explode('=', $value);
            if(isset($value[1]) and !empty($value[1])){
                self::$env[$value[0]] = $value[1];
            }
        }
    }

    public static function get(string $key): string
    {

        self::load();

        if(!array_key_exists($key, self::$env)){
            throw new Exception('A Chave "'.$key.'" não encontrada no arquivo .env.');
        }

        return self::$env[$key];
    }
}