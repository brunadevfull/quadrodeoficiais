#!/bin/bash

# Script interativo para configurar a conexão com o banco de dados
# Autor: Claude Code
# Data: 2025-10-23

set -e

# Cores
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

clear
echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}  CONFIGURAÇÃO DE BANCO DE DADOS${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""
echo "Este script ajudará você a configurar a conexão"
echo "com o banco de dados PostgreSQL que contém as"
echo "tabelas 'duty_assignments' e 'military_personnel'"
echo ""

# Perguntar informações
echo -e "${YELLOW}Por favor, forneça as informações de conexão:${NC}"
echo ""

read -p "Host/IP do servidor PostgreSQL [localhost]: " DB_HOST
DB_HOST=${DB_HOST:-localhost}

read -p "Porta do PostgreSQL [5432]: " DB_PORT
DB_PORT=${DB_PORT:-5432}

read -p "Nome do banco de dados: " DB_NAME
if [ -z "$DB_NAME" ]; then
    echo -e "${RED}Erro: Nome do banco é obrigatório${NC}"
    exit 1
fi

read -p "Usuário do banco de dados [postgres]: " DB_USER
DB_USER=${DB_USER:-postgres}

read -sp "Senha do usuário: " DB_PASS
echo ""

if [ -z "$DB_PASS" ]; then
    echo -e "${RED}Erro: Senha é obrigatória${NC}"
    exit 1
fi

# Montar DATABASE_URL
DATABASE_URL="postgresql://${DB_USER}:${DB_PASS}@${DB_HOST}:${DB_PORT}/${DB_NAME}"

echo ""
echo -e "${BLUE}----------------------------------------${NC}"
echo -e "${YELLOW}Configuração:${NC}"
echo "  Host: $DB_HOST"
echo "  Port: $DB_PORT"
echo "  Database: $DB_NAME"
echo "  User: $DB_USER"
echo "  Password: ********"
echo ""
echo -e "${YELLOW}DATABASE_URL:${NC}"
echo "  postgresql://${DB_USER}:********@${DB_HOST}:${DB_PORT}/${DB_NAME}"
echo -e "${BLUE}----------------------------------------${NC}"
echo ""

# Testar conexão
echo -e "${YELLOW}Testando conexão...${NC}"
if psql "$DATABASE_URL" -c "SELECT version();" >/dev/null 2>&1; then
    echo -e "${GREEN}✓ Conexão estabelecida com sucesso!${NC}"
    echo ""
else
    echo -e "${RED}✗ ERRO: Não foi possível conectar ao banco${NC}"
    echo ""
    echo "Verifique:"
    echo "1. Se o servidor PostgreSQL está rodando"
    echo "2. Se as credenciais estão corretas"
    echo "3. Se o firewall permite conexões na porta $DB_PORT"
    echo ""
    read -p "Deseja continuar mesmo assim? (s/N) " -n 1 -r
    echo ""
    if [[ ! $REPLY =~ ^[Ss]$ ]]; then
        exit 1
    fi
fi

# Verificar tabelas
echo -e "${YELLOW}Verificando tabelas necessárias...${NC}"
for table in "duty_assignments" "military_personnel"; do
    EXISTS=$(psql "$DATABASE_URL" -t -c "SELECT EXISTS (SELECT FROM information_schema.tables WHERE table_schema = 'public' AND table_name = '$table');" 2>/dev/null | tr -d ' ')

    if [ "$EXISTS" = "t" ]; then
        COUNT=$(psql "$DATABASE_URL" -t -c "SELECT COUNT(*) FROM $table;" 2>/dev/null | tr -d ' ')
        echo -e "   ${GREEN}✓${NC} $table (registros: $COUNT)"
    else
        echo -e "   ${RED}✗${NC} $table ${RED}NÃO EXISTE${NC}"
        echo ""
        echo -e "${RED}ATENÇÃO: A tabela '$table' não foi encontrada!${NC}"
        echo "Este banco de dados não parece ser o correto."
        echo ""
        read -p "Deseja continuar mesmo assim? (s/N) " -n 1 -r
        echo ""
        if [[ ! $REPLY =~ ^[Ss]$ ]]; then
            exit 1
        fi
    fi
done

echo ""
echo -e "${BLUE}========================================${NC}"
echo -e "${GREEN}  CONFIGURAÇÃO CONCLUÍDA${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""

# Opções de salvamento
echo "Como você deseja salvar esta configuração?"
echo ""
echo "1. Criar arquivo .env (recomendado)"
echo "2. Exportar para sessão atual (temporário)"
echo "3. Adicionar ao ~/.bashrc (permanente para usuário)"
echo "4. Mostrar comando para Apache/Nginx (manual)"
echo "5. Não salvar agora"
echo ""

read -p "Escolha uma opção [1-5]: " OPTION

case $OPTION in
    1)
        echo "DATABASE_URL=\"$DATABASE_URL\"" > .env
        echo -e "${GREEN}✓ Arquivo .env criado!${NC}"
        echo ""
        echo "ATENÇÃO: O código PHP precisa carregar este arquivo .env"
        echo "Adicione no início do seu código PHP:"
        echo ""
        echo "<?php"
        echo "// Carregar variáveis do .env"
        echo "if (file_exists(__DIR__ . '/.env')) {"
        echo "    \$lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);"
        echo "    foreach (\$lines as \$line) {"
        echo "        if (strpos(\$line, '=') !== false && strpos(\$line, '#') !== 0) {"
        echo "            putenv(trim(\$line));"
        echo "        }"
        echo "    }"
        echo "}"
        ;;
    2)
        export DATABASE_URL="$DATABASE_URL"
        echo -e "${GREEN}✓ Variável exportada para sessão atual${NC}"
        echo ""
        echo "Para usar agora:"
        echo "  export DATABASE_URL=\"$DATABASE_URL\""
        ;;
    3)
        echo "export DATABASE_URL=\"$DATABASE_URL\"" >> ~/.bashrc
        echo -e "${GREEN}✓ Adicionado ao ~/.bashrc${NC}"
        echo ""
        echo "Execute para aplicar agora:"
        echo "  source ~/.bashrc"
        ;;
    4)
        echo ""
        echo -e "${YELLOW}Para Apache (adicione em /etc/apache2/envvars):${NC}"
        echo "  export DATABASE_URL=\"$DATABASE_URL\""
        echo ""
        echo "Depois reinicie o Apache:"
        echo "  sudo systemctl restart apache2"
        echo ""
        echo -e "${YELLOW}Para Nginx com PHP-FPM (adicione em /etc/php/7.x/fpm/pool.d/www.conf):${NC}"
        echo "  env[DATABASE_URL] = \"$DATABASE_URL\""
        echo ""
        echo "Depois reinicie o PHP-FPM:"
        echo "  sudo systemctl restart php7.x-fpm"
        ;;
    5)
        echo -e "${YELLOW}Configuração não salva.${NC}"
        echo ""
        echo "Sua DATABASE_URL é:"
        echo "  $DATABASE_URL"
        ;;
    *)
        echo -e "${RED}Opção inválida${NC}"
        ;;
esac

echo ""
echo -e "${YELLOW}Próximos passos:${NC}"
echo "1. Configure a DATABASE_URL conforme a opção escolhida"
echo "2. Reinicie o servidor web se necessário"
echo "3. Teste a aplicação: Gerenciar Oficiais de Serviço"
echo ""
echo -e "${GREEN}Configuração concluída!${NC}"
