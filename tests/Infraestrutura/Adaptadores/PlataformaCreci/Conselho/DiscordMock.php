<?php

declare(strict_types=1);

namespace Tests\Infraestrutura\Adaptadores\PlataformaCreci\Conselho;

use App\Aplicacao\Compartilhado\Discord\Discord;
use App\Aplicacao\Compartilhado\Discord\Enums\CanalTexto;

class DiscordMock implements Discord
{
    public array $mensagens = [];

    public function enviarMensagem(CanalTexto $canalTexto, string $mensagem): void
    {
        $this->mensagens[] = [
            'canal' => $canalTexto->value,
            'mensagem' => $mensagem,
        ];
    }
}
