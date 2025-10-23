# Migrations - Gerenciamento de Oficiais de Serviço

## Problema Identificado

O sistema de **Gerenciamento de Oficiais de Serviço** estava apresentando erros porque as tabelas necessárias não existiam no banco de dados `marinha_papem`.

### Tabelas Faltantes

1. **duty_assignments** - Armazena as atribuições de oficiais de serviço (Oficial de Serviço e Contramestre)
2. **military_personnel** - Cadastro de militares disponíveis para seleção

## Solução

Execute o script SQL fornecido para criar as tabelas necessárias.

### Opção 1: Executar via psql (Recomendado)

```bash
# Conectar ao banco de dados marinha_papem e executar o script
psql -U postgres -d marinha_papem -f migrations/create_duty_management_tables.sql
```

### Opção 2: Executar via cliente PostgreSQL

1. Conecte-se ao banco de dados `marinha_papem`
2. Execute o conteúdo do arquivo `create_duty_management_tables.sql`

### Opção 3: Usar o script helper

```bash
# Torna o script executável
chmod +x migrations/run_migration.sh

# Executa a migration
./migrations/run_migration.sh
```

## Verificação

Após executar a migration, você pode verificar se as tabelas foram criadas corretamente:

```sql
-- Conectar ao banco marinha_papem
psql -U postgres -d marinha_papem

-- Verificar as tabelas
\dt

-- Verificar a estrutura da tabela duty_assignments
\d duty_assignments

-- Verificar a estrutura da tabela military_personnel
\d military_personnel

-- Verificar os dados de exemplo inseridos
SELECT * FROM military_personnel;
```

## Estrutura das Tabelas

### duty_assignments

| Coluna | Tipo | Descrição |
|--------|------|-----------|
| id | SERIAL | Chave primária |
| officer_name | VARCHAR(255) | Nome do oficial de serviço |
| officer_rank | VARCHAR(100) | Posto/graduação do oficial |
| master_name | VARCHAR(255) | Nome do contramestre |
| master_rank | VARCHAR(100) | Posto/graduação do contramestre |
| valid_from | TIMESTAMP | Data/hora de início da validade |
| updated_at | TIMESTAMP | Data/hora da última atualização |

**Constraint**: Pelo menos um dos campos (officer_name ou master_name) deve ser preenchido.

### military_personnel

| Coluna | Tipo | Descrição |
|--------|------|-----------|
| id | SERIAL | Chave primária |
| name | VARCHAR(255) | Nome completo do militar |
| rank | VARCHAR(100) | Posto/graduação |
| type | VARCHAR(50) | Tipo: 'officer' ou 'master' |
| specialty | VARCHAR(100) | Especialidade (IM, T, AA, etc) |
| status | VARCHAR(50) | Status (active, inactive) |
| created_at | TIMESTAMP | Data de criação |
| updated_at | TIMESTAMP | Data da última atualização |

## Dados de Exemplo

O script inclui dados de exemplo baseados nos oficiais já cadastrados no sistema. Estes dados podem ser removidos em produção, se necessário.

## Próximos Passos

Após executar a migration:

1. ✅ Acesse o sistema e tente gerenciar os oficiais de serviço
2. ✅ Verifique se a lista de oficiais aparece nos dropdowns
3. ✅ Teste a atualização dos oficiais de serviço
4. ✅ Verifique se os dados são salvos corretamente

## Troubleshooting

### Erro: "database marinha_papem does not exist"

Se o banco de dados não existir, crie-o primeiro:

```sql
-- Como usuário postgres
createdb -U postgres marinha_papem

-- Ou via SQL
CREATE DATABASE marinha_papem WITH ENCODING 'UTF8';
```

### Erro: "permission denied"

Certifique-se de estar executando com um usuário que tem permissões adequadas:

```bash
# Se necessário, execute como superusuário
sudo -u postgres psql -d marinha_papem -f migrations/create_duty_management_tables.sql
```

### Erro de conexão

Verifique a variável de ambiente `DATABASE_URL`:

```bash
# Verificar a variável
echo $DATABASE_URL

# Se não estiver definida, defina-a
export DATABASE_URL="postgresql://postgres:senha@localhost:5432/marinha_papem"
```

## Configuração de Ambiente

O sistema usa a variável de ambiente `DATABASE_URL` para conectar ao banco de dados. Se não estiver definida, usa o padrão:

```
postgresql://postgres:postgres123@localhost:5432/marinha_papem
```

Para configurar permanentemente:

```bash
# No .bashrc ou .bash_profile
export DATABASE_URL="postgresql://usuario:senha@host:porta/marinha_papem"
```

## Contato

Para dúvidas ou problemas, consulte o repositório do projeto ou entre em contato com a equipe de desenvolvimento.
