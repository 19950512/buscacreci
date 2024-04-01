# Busca Creci
API para consultar CRECI, Corretores e imobiliárias de todos os CRECI's do Brasil.

## Problema

Um dos problemas do mercado imobiliário é que o CRECI (Conselho Regional de Corretores de Imóveis) não possui uma API para consulta de corretores.

Isso dificulta a validação de corretores e a busca de informações sobre eles.

Cada estado possui um site diferente para consulta de corretores, o que dificulta a busca de informações. Vezes o site está fora do ar, vezes o site não possui a informação que você precisa, etc.

## Solução

Com o Busca Creci, você pode consultar um CRECI de forma simples, rápida e em um único lugar.

**O Busca CRECI foi criada pensando em facilitar a vida de Desenvolvedores e Empresas do ramo imobiliário.**

## Estados disponíveis
- [x] RS - done
- [x] RJ - done
- [ ] SP - in progress
- [ ] MG - in progress
- [ ] PR - in progress
- [ ] SC - in progress
- [ ] ES - in progress
- [ ] BA - in progress
- [ ] CE - in progress
- [ ] PE - in progress
- [ ] RN - in progress
- [ ] PB - in progress
- [ ] SE - in progress
- [ ] AL - in progress
- [ ] PI - in progress
- [ ] MA - in progress
- [ ] PA - in progress
- [ ] AP - in progress
- [ ] TO - in progress
- [ ] RR - in progress
- [ ] AM - in progress
- [ ] AC - in progress
- [ ] DF - in progress
- [ ] GO - in progress
- [ ] MT - in progress
- [ ] MS - in progress
- [ ] RO - in progress

## Como usar

### BaseURL: api.buscacreci.com.br


### REQUEST
```bash
curl --request GET \
  --url 'https://api.buscacreci.com.br/?creci=RJ1234J'
```
### RESPONSE
```json
{
    "codigo": "ea844881-4582-4150-9776-7b5ebd95b30f",
    "creciCompleto": "CRECI\/RJ 1234-J",
    "nomeCompleto": "Regal Imoveis Ltda",
    "situacao": "Ativo",
    "cidade": "Rio de Janeiro",
    "estado": "RJ"
}
```

Busca CRECI é um projeto open-source, você pode contribuir com o projeto. Faça seu RP.

## Contribua
- Faça um fork do projeto
- Crie uma branch com a sua feature
- Faça um commit das suas mudanças
- Faça um push para a sua branch
- Abra um Pull Request
- Aguarde aprovação
