# Configuração de Banco de Dados - SOLUÇÃO DO PROBLEMA

## 🔴 PROBLEMA REAL IDENTIFICADO

O sistema **não consegue conectar ao banco de dados** onde as tabelas `duty_assignments` e `military_personnel` existem.

### Situação Atual

- ✅ As tabelas **JÁ EXISTEM** em outro banco de dados (usado por outros sistemas)
- ❌ O código está tentando conectar em `localhost` onde o PostgreSQL **não está rodando**
- ❌ A variável `DATABASE_URL` **não está configurada**

### Por que está dando erro?

O código em `includes/DutyAssignmentsRepository.php` e `includes/MilitaryPersonnelRepository.php` tenta:

```php
$databaseUrl = getenv('DATABASE_URL') ?: 'postgresql://postgres:postgres123@localhost:5432/marinha_papem';
```

Quando `DATABASE_URL` não está definida, usa o padrão `localhost`, que não tem PostgreSQL rodando.

---

## ✅ SOLUÇÃO: Configurar a DATABASE_URL Correta

### Opção 1: Variável de Ambiente (Recomendado)

Configure a variável de ambiente `DATABASE_URL` apontando para o servidor correto:

```bash
# Substituir pelos valores corretos
export DATABASE_URL="postgresql://usuario:senha@IP_DO_SERVIDOR:5432/nome_do_banco"
```

**Exemplo real:**
```bash
export DATABASE_URL="postgresql://postgres:postgres123@10.1.129.46:5432/sistema_militar"
```

#### Tornar permanente (adicione ao arquivo de configuração do sistema):

**Para Apache:**
```bash
# Editar /etc/apache2/envvars
sudo nano /etc/apache2/envvars

# Adicionar linha:
export DATABASE_URL="postgresql://usuario:senha@host:porta/banco"

# Reiniciar Apache
sudo systemctl restart apache2
```

**Para Nginx com PHP-FPM:**
```bash
# Editar pool do PHP-FPM
sudo nano /etc/php/7.x/fpm/pool.d/www.conf

# Adicionar:
env[DATABASE_URL] = "postgresql://usuario:senha@host:porta/banco"

# Reiniciar PHP-FPM
sudo systemctl restart php7.x-fpm
```

**Para ambiente de usuário (~/.bashrc ou ~/.bash_profile):**
```bash
echo 'export DATABASE_URL="postgresql://usuario:senha@host:porta/banco"' >> ~/.bashrc
source ~/.bashrc
```

---

### Opção 2: Arquivo .env (Se implementado)

Se o sistema suportar arquivo `.env`:

```bash
# 1. Copiar o exemplo
cp .env.example .env

# 2. Editar o arquivo .env
nano .env

# 3. Configurar a DATABASE_URL
DATABASE_URL=postgresql://usuario:senha@host:porta/banco
```

---

### Opção 3: Hardcode (NÃO RECOMENDADO - apenas para teste)

Editar diretamente os arquivos:

**`includes/DutyAssignmentsRepository.php` linha 105:**
```php
// ANTES:
$databaseUrl = getenv('DATABASE_URL') ?: 'postgresql://postgres:postgres123@localhost:5432/marinha_papem';

// DEPOIS (substitua pelos valores corretos):
$databaseUrl = getenv('DATABASE_URL') ?: 'postgresql://SEU_USUARIO:SUA_SENHA@SEU_HOST:5432/SEU_BANCO';
```

**`includes/MilitaryPersonnelRepository.php` linha 85:**
```php
// ANTES:
$databaseUrl = getenv('DATABASE_URL') ?: 'postgresql://postgres:postgres123@localhost:5432/marinha_papem';

// DEPOIS (substitua pelos valores corretos):
$databaseUrl = getenv('DATABASE_URL') ?: 'postgresql://SEU_USUARIO:SUA_SENHA@SEU_HOST:5432/SEU_BANCO';
```

---

## 🔍 DESCOBRIR AS INFORMAÇÕES CORRETAS

### 1. Qual é o servidor do banco de dados?

Verifique onde o outro sistema que usa essas tabelas está conectando:

```bash
# Procurar por configurações em outros sistemas
grep -r "duty_assignments\|military_personnel" /caminho/do/outro/sistema/

# Procurar por DATABASE_URL em configurações
grep -r "DATABASE_URL" /var/www/
```

### 2. Informações necessárias:

- **Host/IP**: Endereço do servidor PostgreSQL (ex: `10.1.129.46` ou `db.exemplo.com`)
- **Porta**: Geralmente `5432`
- **Usuário**: Usuário do banco (ex: `postgres`, `app_user`)
- **Senha**: Senha do usuário
- **Banco**: Nome do banco de dados (ex: `marinha_papem`, `sistema_militar`)

### 3. Verificar se o banco está acessível:

```bash
# Testar conexão manual
psql -h IP_DO_SERVIDOR -p 5432 -U usuario -d nome_do_banco

# Se conectar, listar tabelas
\dt

# Procurar as tabelas necessárias
\dt *duty*
\dt *military*
```

---

## 🧪 TESTAR A CONFIGURAÇÃO

Depois de configurar a `DATABASE_URL`, execute o script de teste:

```bash
cd /home/user/quadrodeoficiais
./test_database.sh
```

**Resultado esperado:**
```
✓ Conexão estabelecida com sucesso!
✓ duty_assignments - EXISTE (registros: X)
✓ military_personnel - EXISTE (registros: X)
```

---

## 📋 CHECKLIST DE DIAGNÓSTICO

Execute estes comandos para obter informações:

```bash
# 1. Verificar se DATABASE_URL está configurada
echo $DATABASE_URL

# 2. Verificar processos PostgreSQL rodando
ps aux | grep postgres

# 3. Verificar configuração do PHP
php -r "echo getenv('DATABASE_URL');"

# 4. Se estiver usando Apache, verificar variáveis
apache2ctl -t -D DUMP_RUN_CFG | grep -i env

# 5. Testar conexão com o script fornecido
./test_database.sh
```

---

## 🆘 TROUBLESHOOTING

### Erro: "Não foi possível conectar ao banco"

**Possíveis causas:**
1. IP/host incorreto
2. Porta bloqueada por firewall
3. Credenciais incorretas
4. Banco de dados não existe
5. PostgreSQL não aceita conexões remotas

**Verificar:**
```bash
# Testar conectividade de rede
ping IP_DO_SERVIDOR

# Testar porta
telnet IP_DO_SERVIDOR 5432
# ou
nc -zv IP_DO_SERVIDOR 5432
```

### Erro: "relation 'duty_assignments' does not exist"

Mesmo após configurar DATABASE_URL corretamente:

1. Verifique se está conectando ao banco certo:
   ```bash
   psql -h HOST -U USER -d BANCO -c "\dt"
   ```

2. Verifique o schema:
   ```sql
   SELECT table_schema, table_name
   FROM information_schema.tables
   WHERE table_name IN ('duty_assignments', 'military_personnel');
   ```

3. Tabelas podem estar em schema diferente de 'public'

### PostgreSQL não aceita conexões remotas

Se o PostgreSQL estiver configurado para aceitar apenas conexões locais:

**Editar `pg_hba.conf`:**
```bash
# Adicionar linha permitindo conexão do IP do servidor web
host    all             all             IP_DO_SERVIDOR/32       md5
```

**Editar `postgresql.conf`:**
```bash
listen_addresses = '*'  # ou IPs específicos
```

**Reiniciar PostgreSQL:**
```bash
sudo systemctl restart postgresql
```

---

## 📞 PRÓXIMOS PASSOS

1. **Descobrir** qual é o servidor de banco de dados correto
2. **Obter** as credenciais de acesso
3. **Configurar** a `DATABASE_URL`
4. **Testar** com `./test_database.sh`
5. **Validar** no navegador: "Gerenciar Oficiais de Serviço"

---

## 💡 DICA IMPORTANTE

Se você **não sabe** qual é o banco de dados correto:

1. Pergunte ao administrador do sistema
2. Verifique documentação do outro sistema que usa essas tabelas
3. Procure em arquivos de configuração de outros sistemas:
   ```bash
   find /var/www -name "*.php" -o -name "*.conf" | xargs grep -l "duty_assignments"
   ```

---

## 📝 EXEMPLO COMPLETO DE CONFIGURAÇÃO

```bash
# 1. Descobrir informações (exemplo)
# Host: 10.1.129.46
# Porta: 5432
# Usuário: postgres
# Senha: minha_senha_segura
# Banco: sistema_marinha

# 2. Configurar DATABASE_URL
export DATABASE_URL="postgresql://postgres:minha_senha_segura@10.1.129.46:5432/sistema_marinha"

# 3. Testar
./test_database.sh

# 4. Se funcionar, tornar permanente (adicionar ao Apache/Nginx)
```

---

**Criado em**: 2025-10-23
**Status**: ✅ Diagnóstico completo - Aguardando configuração da DATABASE_URL
