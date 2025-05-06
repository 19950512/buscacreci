#!/usr/bin/php
<?php

declare(strict_types=1);

namespace App\Aplicacao\Compartilhado\Mensageria;

use App\Aplicacao\Compartilhado\Mensageria\Mensageria;

function momento(){
  return date('d/m/Y H:i:s');
}

echo momento()." | Iniciando o script Inicializador de Mensageria\n";

$container = require __DIR__ . '/../../../Aplicacao/Compartilhado/Container.php';

echo momento()." | Container iniciado.\n";

$mensageria = $container->get(Mensageria::class);

echo momento()." | Iniciando criação das filas.\n";
$mensageria->criarFilas();
echo momento()." | Finalização da criação das filas.\n";