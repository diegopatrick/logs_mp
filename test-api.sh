#!/bin/bash

# Configurações
API_URL="http://localhost:8080/api/v1"
API_TOKEN="logs-api-token-123"

# Cores para output
GREEN='\033[0;32m'
RED='\033[0;31m'
NC='\033[0m'

# Função para fazer requisições
make_request() {
    local method=$1
    local endpoint=$2
    local data=$3
    
    echo -e "\n${GREEN}Testando $method $endpoint${NC}"
    
    if [ -n "$data" ]; then
        curl -X $method "$API_URL$endpoint" \
            -H "X-API-Token: $API_TOKEN" \
            -H "Content-Type: application/json" \
            -d "$data" \
            -w "\nStatus: %{http_code}\n"
    else
        curl -X $method "$API_URL$endpoint" \
            -H "X-API-Token: $API_TOKEN" \
            -H "Content-Type: application/json" \
            -w "\nStatus: %{http_code}\n"
    fi
}

echo -e "${GREEN}Iniciando testes da API${NC}"

# Teste 1: GET /logs (Listar logs)
make_request "GET" "/logs"

# Teste 2: POST /logs (Criar novo log)
make_request "POST" "/logs" '{
    "user_id": "123",
    "system": "auth",
    "action_type": "login",
    "resource": "user",
    "result": "success",
    "ip": "127.0.0.1",
    "payload": {
        "browser": "Chrome",
        "platform": "Windows"
    },
    "is_sensitive": false
}'

# Teste 3: GET /logs/export (Exportar logs)
make_request "GET" "/logs/export"

# Teste 4: GET /logs/{id}/verify (Verificar integridade)
# Primeiro precisamos pegar um ID válido da listagem
make_request "GET" "/logs/123/verify"

echo -e "\n${GREEN}Testes concluídos${NC}" 