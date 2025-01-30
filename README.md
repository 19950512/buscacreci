# Busca CRECI
O Busca CRECI é uma API desenvolvida para facilitar a consulta de informações sobre corretores de imóveis registrados no CRECI (Conselho Regional de Corretores de Imóveis) em todo o Brasil. Este projeto nasceu da necessidade de centralizar e simplificar o acesso a dados sobre corretores, proporcionando uma solução rápida e eficiente para desenvolvedores e empresas do ramo imobiliário.

## Problema
No mercado imobiliário, é comum encontrar dificuldades para validar a situação de um corretor de imóveis e obter informações sobre sua atividade. A falta de uma API oficial do CRECI para consulta de corretores torna esse processo ainda mais complicado. Além disso, cada estado brasileiro possui um site diferente para consulta de corretores, o que aumenta a dispersão e a inconsistência das informações disponíveis.

## Solução
O Busca CRECI oferece uma solução abrangente, permitindo a consulta de corretores de forma simples e rápida, em um único lugar. Com uma API fácil de usar, desenvolvedores e empresas podem acessar informações atualizadas sobre corretores de imóveis em todo o Brasil.

# Estados Disponíveis
Atualmente, o Busca CRECI oferece suporte aos estados do Rio Grande do Sul (RS), Espirito Santo (ES) e Rio de Janeiro (RJ). Estamos trabalhando para expandir nossa cobertura e incluir mais estados em breve.
- [x] RS
- [x] RJ
- [x] ES

## Como Usar

#### BaseURL `api.buscacreci.com.br`

### Exemplo de Requisição
```bash
curl --request GET \
  --url 'https://api.buscacreci.com.br/?creci=RJ1234J'
```

### Exemplo de Resposta
```json
{
    "codigo": "ea844881-4582-4150-9776-7b5ebd95b30f",
    "creciCompleto": "CRECI/RJ 1234-J",
    "nomeCompleto": "Regal Imoveis Ltda",
    "situacao": "Ativo",
    "cidade": "Rio de Janeiro",
    "estado": "RJ"
}
```

## Contribua
O Busca CRECI é um projeto open-source e você pode contribuir para o seu desenvolvimento. Siga os passos abaixo para colaborar:

- Faça um fork do projeto.
- Crie uma branch com a sua feature.
- Faça um commit das suas mudanças.
- Faça um push para a sua branch.
- Abra um Pull Request.
- Aguarde aprovação.

Sua contribuição é fundamental para a melhoria contínua do Busca CRECI e para oferecer uma ferramenta cada vez mais útil para a comunidade do mercado imobiliário.

Junte-se a nós e ajude a tornar a consulta de corretores de imóveis no Brasil mais acessível e eficiente! 🏠🔍

## Como Instalar

Depois de clonar o projeto

```bash
composer install
```

```bash
docker compose up -d
```

Quando todos os containers estiverem **Started**, estará disponível os links:

- Site -> http://localhost:8052
- API -> http://localhost:8053




