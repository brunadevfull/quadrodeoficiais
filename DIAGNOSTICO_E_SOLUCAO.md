# Diagnóstico e Solução - Gerenciamento de Oficiais de Serviço

**Data**: 2025-10-23
**Status**: ✅ PROBLEMA IDENTIFICADO - Aguardando configuração

---

## 🔴 PROBLEMA REAL

O sistema de **Gerenciamento de Oficiais de Serviço** está retornando erros.

### Situação Identificada

- ✅ As tabelas `duty_assignments` e `military_personnel` **JÁ EXISTEM** em outro banco de dados
- ✅ Essas tabelas são **consumidas por outros sistemas**
- ❌ O sistema **não consegue conectar** ao banco correto
- ❌ A variável `DATABASE_URL` **não está configurada**

---

## 🔍 ANÁLISE TÉCNICA

### Comportamento do Código

O código em `includes/DutyAssignmentsRepository.php` (linha 105) e `includes/MilitaryPersonnelRepository.php` (linha 85) tenta conectar assim:

```php
$databaseUrl = getenv('DATABASE_URL') ?: 'postgresql://postgres:postgres123@localhost:5432/marinha_papem';
```

**O que acontece:**

1. Tenta ler a variável de ambiente `DATABASE_URL`
2. Como ela **não está definida**, usa o padrão: `localhost`
3. Tenta conectar em `localhost:5432`
4. ❌ PostgreSQL não está rodando localmente
5. ❌ Conexão falha
6. ❌ Sistema retorna erro

### Evidência

```bash
$ ps aux | grep postgres
PostgreSQL process not found

$ ./test_database.sh
✗ ERRO: Não foi possível conectar ao banco 'marinha_papem'
```

---

## ✅ SOLUÇÃO

Configure a `DATABASE_URL` para apontar ao servidor correto onde as tabelas existem.

### Método 1: Script Interativo (MAIS FÁCIL)

Execute o script de configuração interativo:

```bash
cd /home/user/quadrodeoficiais
./configure_database.sh
```

O script vai:
1. Perguntar as informações de conexão
2. Testar a conexão
3. Verificar se as tabelas existem
4. Salvar a configuração automaticamente

### Método 2: Configuração Manual

```bash
# Descobra as informações do banco:
# - Host/IP do servidor PostgreSQL
# - Porta (geralmente 5432)
# - Nome do banco de dados
# - Usuário
# - Senha

# Configure a variável (exemplo):
export DATABASE_URL="postgresql://usuario:senha@10.1.129.46:5432/nome_do_banco"

# Teste a conexão:
./test_database.sh
```

### Método 3: Tornar Permanente

**Para Apache:**
```bash
sudo nano /etc/apache2/envvars

# Adicionar:
export DATABASE_URL="postgresql://usuario:senha@host:porta/banco"

# Reiniciar:
sudo systemctl restart apache2
```

**Para Nginx com PHP-FPM:**
```bash
sudo nano /etc/php/7.x/fpm/pool.d/www.conf

# Adicionar:
env[DATABASE_URL] = "postgresql://usuario:senha@host:porta/banco"

# Reiniciar:
sudo systemctl restart php7.x-fpm
```

---

## 📋 INFORMAÇÕES NECESSÁRIAS

Para configurar corretamente, você precisa saber:

| Informação | Onde encontrar |
|------------|----------------|
| **Host/IP** | Perguntar ao admin do banco ou verificar no outro sistema que usa essas tabelas |
| **Porta** | Geralmente `5432` (padrão do PostgreSQL) |
| **Banco** | Nome do banco onde estão as tabelas `duty_assignments` e `military_personnel` |
| **Usuário** | Usuário com permissão de SELECT/INSERT/UPDATE nessas tabelas |
| **Senha** | Senha do usuário |

### Como descobrir?

1. **Pergunte ao administrador do sistema**
2. **Verifique outros sistemas** que usam essas mesmas tabelas:
   ```bash
   grep -r "duty_assignments" /var/www/
   grep -r "DATABASE_URL" /var/www/
   ```
3. **Verifique documentação** do sistema que criou essas tabelas

---

## 🧪 TESTANDO A CONFIGURAÇÃO

Depois de configurar, execute:

```bash
# 1. Testar conexão e verificar tabelas
./test_database.sh
```

**Resultado esperado:**
```
✓ Conexão estabelecida com sucesso!
✓ duty_assignments - EXISTE (registros: X)
✓ military_personnel - EXISTE (registros: X)
```

```bash
# 2. Testar no navegador
# - Acesse o sistema PAPEM
# - Clique em "Gerenciar Oficiais de Serviço"
# - Verifique se os dropdowns são populados
# - Selecione oficiais e salve
```

---

## 🛠️ ARQUIVOS CRIADOS

Para ajudar na resolução, foram criados:

| Arquivo | Descrição |
|---------|-----------|
| `configure_database.sh` | 🌟 Script interativo para configurar DATABASE_URL |
| `test_database.sh` | Script para testar conexão e verificar tabelas |
| `test_database_connection.php` | Teste de conexão via PHP |
| `.env.example` | Exemplo de arquivo .env |
| `CONFIGURACAO_BANCO_DE_DADOS.md` | 📖 Documentação completa |
| `DIAGNOSTICO_E_SOLUCAO.md` | Este arquivo |

---

## ❌ O QUE NÃO FAZER

1. ❌ **NÃO execute** `migrations/create_duty_management_tables.sql`
   - As tabelas JÁ EXISTEM em outro banco
   - Executar este script criaria tabelas duplicadas no lugar errado

2. ❌ **NÃO modifique** o código PHP diretamente
   - Use variáveis de ambiente
   - Mantém o código portável e seguro

---

## ✅ CHECKLIST DE RESOLUÇÃO

- [ ] Descobrir qual é o servidor de banco de dados correto
- [ ] Obter as credenciais de acesso (usuário e senha)
- [ ] Descobrir o nome do banco de dados
- [ ] Executar `./configure_database.sh` OU configurar DATABASE_URL manualmente
- [ ] Testar com `./test_database.sh`
- [ ] Verificar que ambas as tabelas existem e têm dados
- [ ] Reiniciar servidor web (se configurou no Apache/Nginx)
- [ ] Testar no navegador: "Gerenciar Oficiais de Serviço"
- [ ] Verificar que dropdowns são populados
- [ ] Testar atualização de oficiais
- [ ] Confirmar que dados são salvos

---

## 🆘 TROUBLESHOOTING

### Problema: "Não sei qual é o banco correto"

**Solução:**
- Pergunte ao administrador do sistema
- Verifique documentação do outro sistema que usa essas tabelas
- Procure em arquivos de configuração:
  ```bash
  find /var/www -name "*.conf" -o -name "config.php" | xargs grep -l "duty_assignments"
  ```

### Problema: "Não tenho as credenciais"

**Solução:**
- Entre em contato com o DBA (Database Administrator)
- Verifique se há documentação de deploy
- Peça ao responsável pelo outro sistema que usa essas tabelas

### Problema: "Firewall bloqueia a conexão"

**Solução:**
```bash
# Testar conectividade
telnet IP_DO_SERVIDOR 5432

# Se bloqueado, pedir ao admin para liberar
```

### Problema: "Tabelas existem mas estão vazias"

**Solução:**
- Verifique se está conectando ao banco correto
- Pode ser que precise popular as tabelas primeiro
- Consulte o sistema que deveria alimentar essas tabelas

---

## 📞 PRÓXIMOS PASSOS

1. **URGENTE**: Descobrir as informações de conexão corretas
2. Executar `./configure_database.sh` com as informações corretas
3. Testar a conexão
4. Validar no navegador

---

## 💡 EXEMPLO COMPLETO

```bash
# Supondo que as informações são:
# - Servidor: 10.1.129.46
# - Porta: 5432
# - Banco: sistema_marinha
# - Usuário: app_user
# - Senha: senha_segura

# 1. Configurar
export DATABASE_URL="postgresql://app_user:senha_segura@10.1.129.46:5432/sistema_marinha"

# 2. Testar
./test_database.sh

# 3. Se funcionar, tornar permanente
sudo nano /etc/apache2/envvars
# Adicionar a linha do export acima
sudo systemctl restart apache2

# 4. Testar no navegador
# Acessar: Gerenciar Oficiais de Serviço
```

---

**Status**: ✅ Diagnóstico completo
**Ação necessária**: Configurar DATABASE_URL com informações corretas do servidor de banco de dados
**Documentação completa**: `CONFIGURACAO_BANCO_DE_DADOS.md`
