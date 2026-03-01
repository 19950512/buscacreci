
# Busca CRECI

O **Busca CRECI** é uma API desenvolvida para facilitar a consulta de informações sobre corretores de imóveis registrados no CRECI (Conselho Regional de Corretores de Imóveis) em todo o Brasil. Este projeto nasceu da necessidade de centralizar e simplificar o acesso a dados sobre corretores, proporcionando uma solução rápida e eficiente para desenvolvedores e empresas do ramo imobiliário.

## Problema

No mercado imobiliário, é comum encontrar dificuldades para validar a situação de um corretor de imóveis e obter informações sobre sua atividade. A falta de uma API oficial do CRECI para consulta de corretores torna esse processo ainda mais complicado. Além disso, cada estado brasileiro possui um site diferente para consulta de corretores, o que aumenta a dispersão e a inconsistência das informações disponíveis.

## Solução

O Busca CRECI oferece uma solução abrangente, permitindo a consulta de corretores de forma simples e rápida, em um único lugar. Com uma API fácil de usar, desenvolvedores e empresas podem acessar informações atualizadas sobre corretores de imóveis em todo o Brasil.

## Estados Disponíveis

O Busca CRECI suporta **todos os 27 estados brasileiros**! A maioria é consultada via Conselho Nacional CRECI, e alguns estados possuem scrapers dedicados com fallback automático.

| DF  | SP  | TO  | MG  | RS  | RJ  | ES  | RO  | PR  | PE  | GO  | BA  | SC  | PA  |
|-----|-----|-----|-----|-----|-----|-----|-----|-----|-----|-----|-----|-----|-----|
| [X] | [X] | [X] | [X] | [X] | [X] | [X] | [X] | [X] | [X] | [X] | [X] | [X] | [X] |

| MS  | CE  | SE  | RN  | AM  | MT  | MA  | PB  | AL  | PI  | AC  | RR  | AP  |
|-----|-----|-----|-----|-----|-----|-----|-----|-----|-----|-----|-----|-----|
| [X] | [X] | [X] | [X] | [X] | [X] | [X] | [X] | [X] | [X] | [X] | [X] | [X] |

### Fontes de Consulta

- **SP**: Scraper dedicado com headless Chrome (reCAPTCHA Enterprise requer navegador real)
- **RS e ES**: Scrapers dedicados nos sites oficiais
- **22 estados**: API do Conselho Nacional CRECI (conselho.net.br)
- **MG e TO**: Sem Conselho Nacional e sem scraper dedicado (contribuições bem-vindas!)

> **Nota técnica**: O CRECI SP utiliza reCAPTCHA Enterprise do Google, que não pode ser resolvido por serviços como 2Captcha. A solução utiliza Node.js + Puppeteer para executar o captcha nativamente em um Chrome headless.

### Requisitos adicionais para SP

O scraper de SP requer:
- **Node.js** (v18+)
- **Google Chrome** ou Chromium
- **puppeteer-core**: `npm install puppeteer-core`

## Como Usar

### Base URL
```
https://api.buscacreci.com.br
```

### Etapas de Consulta

#### 1. Enviar o CRECI para Consulta
```bash
curl --request GET --url "https://api.buscacreci.com.br/?creci=SP12335F"
```

**Resposta:**
```json
{
    "codigo_solicitacao": "9a0b056f-4ae9-427d-aa80-0f262547a6f3",
    "message": "Seu CRECI foi enviado para o sistema de consulta, você pode acompanhar o status da consulta pelo código abaixo."
}
```

#### 2. Verificar Status da Solicitação
```bash
curl --request GET --url "https://api.buscacreci.com.br/status?codigo_solicitacao=9a0b056f-4ae9-427d-aa80-0f262547a6f3"
```

**Resposta em processamento:**
```json
{
    "codigoSolicitacao": "9a0b056f-4ae9-427d-aa80-0f262547a6f3",
    "status": "PROCESSANDO",
    "mensagem": "Creci já foi consultado anteriormente.",
    "creciID": "1b93a23a-224a-4ccb-a7ec-70b63a45f04d",
    "creciCompleto": "CRECI/SP 12335-F"
}
```

**Resposta finalizada:**
```json
{
    "codigoSolicitacao": "9a0b056f-4ae9-427d-aa80-0f262547a6f3",
    "status": "FINALIZADO",
    "mensagem": "Creci consultado com sucesso.",
    "creciID": "7f59bbd8-cb26-4492-b7ff-2c9fde92954f",
    "creciCompleto": "CRECI/SP 12335-F"
}
```

#### 3. Obter Detalhes do CRECI
```bash
curl --request GET --url "https://api.buscacreci.com.br/creci?id=7f59bbd8-cb26-4492-b7ff-2c9fde92954f"
```

**Resposta:**
```json
{
    "codigo": "7f59bbd8-cb26-4492-b7ff-2c9fde92954f",
    "creciCompleto": "CRECI/SP 12335-F",
    "nomeCompleto": "Carlos Augusto Moltocaro",
    "situacao": "Inativo",
    "cidade": "Sp",
    "estado": "SP",
    "momento": "2025-05-06 23:38:16+00"
}
```

## Como Instalar

Depois de clonar o projeto:

```bash
# Copiar o arquivo de configuração e preencher com seus valores
cp .env.example .env

# Instalar dependências PHP
composer install

# Instalar dependências Node.js (necessário para scraper SP)
npm install

# Subir os containers
docker compose up -d
```

### Endpoints disponíveis

| Endpoint | Descrição |
|----------|-----------|
| `GET /?creci=RS12345F` | Envia CRECI para consulta |
| `GET /status?codigo_solicitacao=UUID` | Verifica status da consulta |
| `GET /creci?id=UUID` | Obtém detalhes do CRECI |
| `GET /estados` | Lista todos os estados e disponibilidade |
| `GET /ultimoscrecis` | Lista os últimos CRECIs consultados |

Acesse a API localmente em: `http://localhost:8053`

## Site
https://github.com/19950512/buscacrecisite

## Discord
https://discord.gg/B4pXbCd22b

## Contribua

O Busca CRECI é um projeto open-source. Para colaborar:

1. Faça um fork do projeto.
2. Crie uma branch com sua feature.
3. Commit suas mudanças.
4. Push para sua branch.
5. Abra um Pull Request.
6. Aguarde revisão.

Sua contribuição é muito bem-vinda para tornar o acesso à informação mais acessível no mercado imobiliário brasileiro.
