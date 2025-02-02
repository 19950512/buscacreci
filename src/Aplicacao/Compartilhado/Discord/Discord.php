<?php

declare(strict_types=1);

namespace App\Aplicacao\Compartilhado\Discord;

use App\Aplicacao\Compartilhado\Discord\Enums\CanalTexto;

interface Discord
{
    public function enviarMensagem(CanalTexto $canalTexto, string $mensagem): void;
}