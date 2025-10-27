# Plano de Migração PHP → Node

Este documento descreve os passos necessários para preparar o ambiente de staging, realizar o cut-over controlado entre as aplicações PHP e Node.js e atualizar os artefatos operacionais antes da virada definitiva.

## 1. Staging com banco clonado e testes automatizados

### 1.1 Provisionamento do banco de staging
1. Gere o dump do banco de produção executando `backup_pg.sh` no host de produção. O script cria um arquivo `backup_paginadeoficiais_<timestamp>.sql` em `/backup/db`. Copie-o para o host de staging.
2. No host de staging, atualize `restore_pg.sh` apontando `ARQUIVO_BACKUP` para o dump recém-copiado e execute o script.
3. Após a restauração, verifique o acesso com `php test_connection.php` para confirmar que o PHP enxerga o banco clonado via `DATABASE_URL`.
4. Repita o teste para a API Node carregando as mesmas variáveis (`cp .env node-app/.env && npm --prefix node-app run build:server`), garantindo que ambos os serviços consultam o mesmo banco clonado.

### 1.2 Sincronização de fixtures e assets
- Rode `php reset.php --no-cache` para reconstruir caches locais utilizados pelo PHP (status JSON, temperatura, etc.).
- Execute `npm --prefix node-app run build` para gerar os bundles estáticos consumidos pela versão Node.
- Publique assets compartilhados em um bucket ou pasta NFS acessível pelas duas pilhas (garantir permissão de leitura).

### 1.3 Testes automatizados paralelos
1. Configure uma matriz de CI (GitHub Actions, GitLab CI ou Jenkins) com dois jobs paralelos: `php-tests` e `node-contracts`.
2. `php-tests` deve instalar dependências PHP (Composer se aplicável) e executar `vendor/bin/phpunit` ou, na ausência de suites, ao menos `find controllers includes models -name '*.php' -print0 | xargs -0 -n1 php -l` para lint e smoke tests.
3. `node-contracts` deve executar `npm ci` dentro de `node-app` e rodar `npm run lint`. Para contratos de API, utilize uma coleção Postman/Newman (`newman run contracts/postman_collection.json`) ou um pacote pact (`npm run test:contracts`) validando respostas da API Node contra a documentação atual do PHP.
4. Publique relatórios (JUnit/HTML) como artefatos de pipeline para comparação e alimentar dashboards de regressão.

### 1.4 Script de automação ponta-a-ponta
- Gere `config/migration.env` a partir do template `config/migration.env.sample`, preenchendo hosts, usuários e senhas tanto da base de produção quanto da base de staging.
- Execute `scripts/migration_automation.sh` para realizar, em sequência:
  1. Backup do banco de produção (`pg_dump`) usando `backup_pg.sh` como base.
  2. Restore no banco de staging (`psql`), reaproveitando o arquivo de backup gerado ou informado via `--backup-file`.
  3. Rotinas de preparação e verificação: `php reset.php --no-cache`, lint PHP, `test_connection.php`, `npm --prefix node-app install`, `npm run build:server`, `npm run build` e `npm run lint`.
- Flags úteis do script:
  - `--skip-backup` / `--backup-file`: pular um novo dump e usar um arquivo existente.
  - `--skip-restore`: apenas executar as verificações/lints.
  - `--skip-tests`: encerrar após o restore (útil para validações rápidas).
- Registre logs do script (stdout/stderr) como artefatos da pipeline para histórico de migração.

## 2. Estratégia de cut-over com feature flag

### 2.1 Topologia
- Coloque NGINX na frente das duas aplicações: PHP continua atendendo `fastcgi_pass` e a API Node roda atrás de `proxy_pass` HTTP.
- Introduza uma feature flag (`ENABLE_NODE_API`) controlada via `env` ou ConfigMap. Enquanto desativada, todo tráfego continua indo para o PHP.

### 2.2 Regras de roteamento graduais
1. Inicialmente mantenha `ENABLE_NODE_API=0` e direcione 100% dos endpoints para o PHP.
2. Habilite o Node para um subconjunto de rotas (ex.: `/api/duty-officers`) ajustando o `map` do NGINX:
   ```nginx
   map $http_x_canary_user $use_node {
       default           0;
       "true"            1;
   }
   ```
3. Utilize headers de canário ou lista de IPs para liberar o Node gradualmente. Quando `use_node` for `1`, encaminhe para `proxy_pass http://node_service;`.
4. Registre métricas separadas (NGINX `access_log` com `upstream` diferente) para comparar latência/erro entre PHP e Node.
5. Após validar, altere a feature flag para `1` em produção, migrando rota a rota até desligar os endpoints PHP equivalentes.

### 2.3 Desligamento dos endpoints PHP
- Documente cada rota migrada em uma checklist (`docs/migration-checklist.csv`) com campos `rota`, `flag`, `status`, `rollback`.
- Quando um endpoint estiver estável no Node por 48h, atualize o checklist para `desativar` e configure respostas `410 Gone` no PHP para evidenciar o desligamento.

## 3. Documentação operacional, monitoramento e rollback

### 3.1 Documentação
- Atualize runbooks em `DOCUMENTACAO_ROTAS.md` adicionando as equivalências PHP → Node e os procedimentos de fallback.
- Crie um `docs/staging-runbook.md` contendo credenciais rotacionadas, comandos de `backup_pg.sh`/`restore_pg.sh` e como reiniciar serviços.

### 3.2 Monitoramento
- Amplie painéis (Grafana/DataDog) com métricas do Node: uso de CPU, memória, erros 5xx, latência p95/p99, contagem de sessões.
- Configure alertas específicos para divergência entre respostas PHP e Node (ex.: job horário que consome ambas APIs e compara hash do payload).

### 3.3 Plano de rollback
1. Mantenha snapshots do banco de staging antes de cada cut-over parcial (`pg_dump -Fc`), guardando por 7 dias.
2. Scripts de rollback:
   - `rollback_node.sh`: ajusta feature flag para `0`, recarrega NGINX (`nginx -s reload`) e reinicia PHP-FPM.
   - `rollback_db.sh`: restaura último dump consistente se a API Node corromper dados.
3. Documente no runbook os contatos de plantão, SLAs e critérios para abortar a migração (p. ex. >2% erros 5xx por 5 minutos).

## 4. Cronograma sugerido

| Semana | Atividade principal |
|--------|--------------------|
| 1      | Clonar banco para staging, validar smoke tests PHP/Node |
| 2      | Preparar matriz CI, automatizar contratos de API |
| 3      | Implantar NGINX com feature flag e iniciar canário |
| 4      | Migrar rotas restantes, desativar PHP, validar monitoramento e runbooks |

> **Observação:** mantenha auditorias de acesso aos scripts (`backup_pg.sh`, `restore_pg.sh`) e rotacione senhas (`DB_PASSWORD`) antes da virada final.
