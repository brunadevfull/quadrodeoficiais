#!/bin/bash

# Script para testar conexão com banco de dados PostgreSQL
# e verificar se as tabelas necessárias existem

echo "=========================================="
echo "  TESTE DE CONEXÃO - BANCO DE DADOS"
echo "=========================================="
echo ""

# Configurações padrão
DB_USER="${DB_USER:-postgres}"
DB_HOST="${DB_HOST:-localhost}"
DB_PORT="${DB_PORT:-5432}"
DB_NAME="${DB_NAME:-marinha_papem}"

echo "Configurações:"
echo "  Host: $DB_HOST"
echo "  Port: $DB_PORT"
echo "  User: $DB_USER"
echo "  Database: $DB_NAME"
echo ""

# Verificar se DATABASE_URL está definida
if [ ! -z "$DATABASE_URL" ]; then
    echo "✓ DATABASE_URL encontrada: $DATABASE_URL"
    echo ""
fi

# Testar conexão
echo "1. Testando conexão com o banco..."
if psql -U "$DB_USER" -h "$DB_HOST" -p "$DB_PORT" -d "$DB_NAME" -c "SELECT version();" >/dev/null 2>&1; then
    echo "   ✓ Conexão estabelecida com sucesso!"
    echo ""
else
    echo "   ✗ ERRO: Não foi possível conectar ao banco '$DB_NAME'"
    echo ""
    echo "Tentando listar bancos disponíveis..."
    psql -U "$DB_USER" -h "$DB_HOST" -p "$DB_PORT" -l 2>/dev/null | grep -E "Name|------|marinha" || echo "   Não foi possível listar bancos"
    echo ""
    echo "SOLUÇÕES:"
    echo "1. Verifique se o PostgreSQL está rodando"
    echo "2. Verifique as credenciais (usuário/senha)"
    echo "3. Configure as variáveis de ambiente:"
    echo "   export DB_NAME='nome_do_banco_correto'"
    echo "   export DB_USER='usuario'"
    echo "   export DB_HOST='host'"
    exit 1
fi

# Listar tabelas
echo "2. Listando tabelas no banco '$DB_NAME'..."
TABLES=$(psql -U "$DB_USER" -h "$DB_HOST" -p "$DB_PORT" -d "$DB_NAME" -t -c "SELECT tablename FROM pg_tables WHERE schemaname = 'public' ORDER BY tablename;" 2>/dev/null)

if [ -z "$TABLES" ]; then
    echo "   ✗ Nenhuma tabela encontrada"
    echo ""
else
    echo "   Tabelas encontradas:"
    echo "$TABLES" | while read table; do
        if [ ! -z "$table" ]; then
            COUNT=$(psql -U "$DB_USER" -h "$DB_HOST" -p "$DB_PORT" -d "$DB_NAME" -t -c "SELECT COUNT(*) FROM $table;" 2>/dev/null | tr -d ' ')
            echo "      - $table (registros: $COUNT)"
        fi
    done
    echo ""
fi

# Verificar tabelas específicas
echo "3. Verificando tabelas necessárias..."
for table in "duty_assignments" "military_personnel"; do
    EXISTS=$(psql -U "$DB_USER" -h "$DB_HOST" -p "$DB_PORT" -d "$DB_NAME" -t -c "SELECT EXISTS (SELECT FROM information_schema.tables WHERE table_schema = 'public' AND table_name = '$table');" 2>/dev/null | tr -d ' ')

    if [ "$EXISTS" = "t" ]; then
        COUNT=$(psql -U "$DB_USER" -h "$DB_HOST" -p "$DB_PORT" -d "$DB_NAME" -t -c "SELECT COUNT(*) FROM $table;" 2>/dev/null | tr -d ' ')
        echo "   ✓ $table - EXISTE (registros: $COUNT)"
    else
        echo "   ✗ $table - NÃO EXISTE"
    fi
done

echo ""
echo "=========================================="
echo "  FIM DO TESTE"
echo "=========================================="
