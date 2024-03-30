<?php

declare(strict_types=1);

namespace App\Dominio\ObjetoValor;


use App\Infraestrutura\Adaptadores\EnvrionmentImplementacao;

final class IPHost
{
    private string $ip;
    public function __construct(){

        $env = new EnvrionmentImplementacao();

        if($env->get('SEM_INTERNET') == 'true'){
            $this->ip = 'localhost';
        }else{
            $hostIP = gethostbyname('host.docker.internal');
            if($hostIP != 'host.docker.internal'){
                $this->ip =  $hostIP;
            }else {
                $respostaShell = shell_exec('ip addr show');

                if(strlen($respostaShell ?? '') < 10){

                    $this->ip = $env->get('IP_HOST');

                }else {
                    $interfaces = array_values(array_filter(explode(PHP_EOL, $respostaShell)));
                    foreach ($interfaces as $interface) {
                        if (strpos($interface, 'inet ') !== false && strpos($interface, '127.0.0.1') === false) {
                            preg_match('/inet (\d+\.\d+\.\d+\.\d+)/', $interface, $matches);
                            if (isset($matches[1])) {
                                $this->ip = $matches[1];
                                break;
                            }
                        }
                    }
                }
            }
        }
    }

    public function getIp(): string
    {
        return $this->ip;
    }
}