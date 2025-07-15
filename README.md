# API de Logs & Auditoria

Sistema de registro e auditoria de logs com garantia de imutabilidade e rastreabilidade.

## Requisitos

- Docker
- Docker Compose
- Git

## Instalação

1. Clone o repositório:
```bash
git clone <repository-url>
cd logs-api
```

2. Configure as variáveis de ambiente:
```bash
cp .env.example .env
```

3. Inicie os containers:
```bash
docker-compose up -d
```

4. Instale as dependências:
```bash
docker-compose exec app composer install
```

5. Gere a chave da aplicação:
```bash
docker-compose exec app php artisan key:generate
```

6. Configure as permissões:
```bash
docker-compose exec app chown -R www-data:www-data storage bootstrap/cache
```

## Endpoints da API

### Registrar Log
```http
POST /api/v1/logs
Content-Type: application/json
X-API-Token: seu-token-aqui

{
    "user_id": "123",
    "system": "auth",
    "action_type": "login",
    "resource": "user",
    "result": "success",
    "ip": "192.168.1.1",
    "payload": {
        "browser": "Chrome",
        "platform": "Windows"
    },
    "is_sensitive": false
}
```

### Buscar Logs
```http
GET /api/v1/logs?user_id=123&system=auth&date_start=2024-01-01
X-API-Token: seu-token-aqui
```

### Exportar Logs
```http
GET /api/v1/logs/export?format=csv&date_start=2024-01-01&date_end=2024-01-31
X-API-Token: seu-token-aqui
```

### Verificar Integridade
```http
GET /api/v1/logs/{id}/verify
X-API-Token: seu-token-aqui
```

## Características

- Registro imutável de logs
- Cache com Redis
- Processamento assíncrono via filas
- Exportação em CSV e JSON
- Verificação de integridade via hash SHA-256
- Autenticação via token
- Rate limiting
- Documentação OpenAPI/Swagger

## Segurança

- Todos os logs são assinados digitalmente
- Armazenamento WORM (Write Once, Read Many)
- Controle de acesso granular
- Proteção contra edição/exclusão
- Rate limiting para prevenção de abuso
- Logs sensíveis com acesso restrito

## Cache

O sistema utiliza Redis para cache com as seguintes configurações:
- TTL padrão: 5 minutos
- Cache de consultas frequentes
- Invalidação automática em alterações

## Monitoramento

- Logs de sistema via Laravel Log
- Monitoramento de filas
- Métricas de performance
- Alertas de falhas

## Testes

Execute os testes com:
```bash
docker-compose exec app php artisan test
```

## Contribuição

1. Fork o projeto
2. Crie sua branch de feature (`git checkout -b feature/nova-feature`)
3. Commit suas mudanças (`git commit -am 'Adiciona nova feature'`)
4. Push para a branch (`git push origin feature/nova-feature`)
5. Crie um Pull Request

## Licença

Este projeto está sob a licença MIT. Veja o arquivo [LICENSE](LICENSE) para mais detalhes.
