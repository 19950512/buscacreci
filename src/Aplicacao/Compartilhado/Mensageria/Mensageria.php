<?php

declare(strict_types=1);

namespace App\Aplicacao\Compartilhado\Mensageria;

use App\Aplicacao\Compartilhado\Mensageria\Enumerados\Evento;

interface Mensageria
{
    public function publicar(Evento $evento, string $message): void;
    public function inscrever(Evento $evento, callable $retrochamada): void;
    public function criarFilas(): void;
    public function deletarFilas(): void;
    public function deletarTrocaMensagens(): void;
}