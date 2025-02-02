<?php

declare(strict_types=1);

namespace App\Infraestrutura\Adaptadores;

use Exception;
use Override;
use App\Aplicacao\Compartilhado\Envrionment;

class EnvrionmentImplementacao implements Envrionment
{

    private static string $pathForENV = __DIR__.'/../../../.env';

    private static array $env = [];

    #[Override] public static function get(string $key): string | bool | int
    {

        self::load();

        if(!array_key_exists($key, self::$env)){
            throw new Exception('A Chave "'.$key.'" não encontrada no arquivo .env.');
        }

        $valor = match(self::$env[$key]){
            'true', 'True' => true,
            'false', 'False' => false,
            default => self::$env[$key]
        };

        if(is_numeric($valor)){
            return (int) $valor;
        }

        return $valor;
    }

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
}