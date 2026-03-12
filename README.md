# Rolê Agroecológico

## Deploy Kubernetes (Ambiente SME)

Para o deploy no kubernetes é utilizando o helm, onde os arquivos estão no diretorio: `k8s/helm`

### Criação de secrets e configMaps
PHP
  - Criar secrets wp-secrets
  - Criar configMap wp-configs

Apache
  - Criar secrets apache-secrets

### Criação de PVC
Criar PVC com o nome de acordo com o ambiente: `roleagroecologico-qa`, `roleagroecologico-hom` ou `roleagroecologico-prod`

### Variaveis de ambiente
O arquivo [values.yaml](https://git.sme.prefeitura.sp.gov.br/wordpress/role-agroecologico/-/blob/homolog/k8s/helm/values.yaml) contém envs dinamicas e estaticas.

#### Estaticas
São os valores padrão para qualquer ambiente (hom e prod).

#### Dinamicas
São os valores que mudam conforme o ambiente, desta forma são substituidas durante a esteira.

Por exemplo, no trecho do ingress, o valor `${INGRESS_HOST}` da chave `host` representa um valor dinamico:

```yaml
ingress:
  className: nginx
  port: 80
  rules:
    - host: ${INGRESS_HOST}
      paths:
        - path: /
          pathType: Prefix
          port: 80
```

Onde durante o deploy, o arquivo `values-qa.yaml`, `values-hom.yaml` ou `values-prod.yaml` é gerado com seus devidos valores para cada ambiente.

#### Fluxo:

![fluxo](https://git.sme.prefeitura.sp.gov.br/wordpress/role-agroecologico/-/raw/homolog/img/wp-fluxo.png?ref_type=heads)

## Deploy local

### Dependencias:

- docker
- docker compose
- conexão com o banco de dados (caso seja externo)

Instalação Docker:
```bash
curl -fsSL https://get.docker.com | bash
```

### Setup:

- Copiar `.env.example` para `.env`
- Ajustar valores das variaveis 📋
- Executar docker compose ▶️

Para conectar a um banco de dados local
```bash
docker compose -f docker-compose-local.yaml up -d
```

Para conectar a um banco de dados remoto
```bash
docker compose -f docker-compose-remoto.yaml up -d
```

- Limpeza 🧹

```bash
docker compose -f docker-compose-local.yaml down -v

rm -rf nfs/
```