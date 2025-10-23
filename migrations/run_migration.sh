#!/bin/bash

# Script para executar a migration de criação das tabelas de gerenciamento de oficiais de serviço
# Autor: Claude Code
# Data: 2025-10-23

set -e  # Parar em caso de erro

# Cores para output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}  Migration: Duty Management Tables${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""

# Verificar se o arquivo SQL existe
MIGRATION_FILE="$(dirname "$0")/create_duty_management_tables.sql"
if [ ! -f "$MIGRATION_FILE" ]; then
    echo -e "${RED}Erro: Arquivo de migration não encontrado: $MIGRATION_FILE${NC}"
    exit 1
fi

# Configurações padrão
DB_USER="${DB_USER:-postgres}"
DB_NAME="${DB_NAME:-marinha_papem}"
DB_HOST="${DB_HOST:-localhost}"
DB_PORT="${DB_PORT:-5432}"

echo -e "${YELLOW}Configurações:${NC}"
echo "  Usuário: $DB_USER"
echo "  Banco: $DB_NAME"
echo "  Host: $DB_HOST"
echo "  Port: $DB_PORT"
echo ""

# Perguntar confirmação
read -p "Deseja continuar com a migration? (s/N) " -n 1 -r
echo ""
if [[ ! $REPLY =~ ^[Ss]$ ]]; then
    echo -e "${YELLOW}Migration cancelada.${NC}"
    exit 0
fi

# Verificar se o banco de dados existe
echo -e "${YELLOW}Verificando se o banco de dados existe...${NC}"
DB_EXISTS=$(psql -U "$DB_USER" -h "$DB_HOST" -p "$DB_PORT" -tAc "SELECT 1 FROM pg_database WHERE datname='$DB_NAME'" postgres 2>/dev/null || echo "")

if [ -z "$DB_EXISTS" ]; then
    echo -e "${YELLOW}Banco de dados '$DB_NAME' não encontrado.${NC}"
    read -p "Deseja criar o banco de dados? (s/N) " -n 1 -r
    echo ""
    if [[ $REPLY =~ ^[Ss]$ ]]; then
        echo -e "${YELLOW}Criando banco de dados '$DB_NAME'...${NC}"
        createdb -U "$DB_USER" -h "$DB_HOST" -p "$DB_PORT" "$DB_NAME"
        echo -e "${GREEN}✓ Banco de dados criado com sucesso!${NC}"
    else
        echo -e "${RED}Migration cancelada. Banco de dados não existe.${NC}"
        exit 1
    fi
else
    echo -e "${GREEN}✓ Banco de dados encontrado${NC}"
fi

# Executar a migration
echo ""
echo -e "${YELLOW}Executando migration...${NC}"
psql -U "$DB_USER" -h "$DB_HOST" -p "$DB_PORT" -d "$DB_NAME" -f "$MIGRATION_FILE"

if [ $? -eq 0 ]; then
    echo ""
    echo -e "${GREEN}========================================${NC}"
    echo -e "${GREEN}  ✓ Migration executada com sucesso!${NC}"
    echo -e "${GREEN}========================================${NC}"
    echo ""
    echo -e "${YELLOW}Próximos passos:${NC}"
    echo "  1. Acesse o sistema de gerenciamento de oficiais"
    echo "  2. Teste a funcionalidade de atribuição de oficiais de serviço"
    echo "  3. Verifique se os dados são salvos corretamente"
    echo ""
else
    echo ""
    echo -e "${RED}========================================${NC}"
    echo -e "${RED}  ✗ Erro ao executar migration${NC}"
    echo -e "${RED}========================================${NC}"
    echo ""
    echo -e "${YELLOW}Verifique:${NC}"
    echo "  1. As credenciais do banco de dados"
    echo "  2. Se o usuário tem permissões adequadas"
    echo "  3. Os logs de erro acima"
    exit 1
fi
