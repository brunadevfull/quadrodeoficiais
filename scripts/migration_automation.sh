#!/bin/bash
set -euo pipefail

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DEFAULT_CONFIG_FILE="$PROJECT_ROOT/config/migration.env"
CONFIG_FILE="$DEFAULT_CONFIG_FILE"
SKIP_BACKUP=0
SKIP_RESTORE=0
SKIP_TESTS=0
BACKUP_FILE=""

usage() {
  cat <<'USAGE'
Uso: scripts/migration_automation.sh [opções]

Opções:
  -c, --config <arquivo>    Caminho para o arquivo de configuração (padrão: config/migration.env)
      --backup-file <arq>   Caminho de um backup já existente para reutilizar no restore
      --skip-backup         Não gerar novo backup
      --skip-restore        Não restaurar o backup no staging
      --skip-tests          Pular a execução dos testes/lints PHP e Node
  -h, --help                Exibe esta ajuda
USAGE
}

log_step() {
  printf '\n🔹 %s\n' "$1"
}

warn() {
  printf '⚠️  %s\n' "$1"
}

error() {
  printf '❌ %s\n' "$1" >&2
  exit 1
}

ensure_command() {
  if ! command -v "$1" >/dev/null 2>&1; then
    error "Comando '$1' não encontrado no PATH."
  fi
}

load_config() {
  if [[ -n "$CONFIG_FILE" && ! "$CONFIG_FILE" = /* ]]; then
    CONFIG_FILE="$PROJECT_ROOT/$CONFIG_FILE"
  fi

  if [[ -f "$CONFIG_FILE" ]]; then
    # shellcheck disable=SC1090
    set -a
    source "$CONFIG_FILE"
    set +a
  else
    warn "Arquivo de configuração '$CONFIG_FILE' não encontrado. Usando valores padrão/variáveis de ambiente."
  fi
}

create_backup() {
  ensure_command pg_dump
  local db_name="${DB_NAME:-paginadeoficiais}"
  local db_user="${DB_USER:-pagoficial_rw}"
  local db_password="${DB_PASSWORD:-Papem_RW@2024}"
  local db_host="${DB_HOST:-localhost}"
  local backup_dir="${BACKUP_DIR:-/backup/db}"

  [[ -n "$db_password" ]] || error "Variável DB_PASSWORD não definida."
  mkdir -p "$backup_dir"

  local timestamp="$(date +%Y%m%d_%H%M%S)"
  local backup_path="$backup_dir/backup_${db_name}_${timestamp}.sql"

  log_step "Gerando backup do banco '${db_name}'"
  PGPASSWORD="$db_password" pg_dump -U "$db_user" -h "$db_host" "$db_name" > "$backup_path"
  printf '✅ Backup salvo em %s\n' "$backup_path"
  BACKUP_FILE="$backup_path"
}

restore_backup() {
  ensure_command psql
  local staging_db_name="${STAGING_DB_NAME:-${DB_NAME:-paginadeoficiais}}"
  local staging_db_user="${STAGING_DB_USER:-${DB_USER:-pagoficial_rw}}"
  local staging_db_password="${STAGING_DB_PASSWORD:-${DB_PASSWORD:-Papem_RW@2024}}"
  local staging_db_host="${STAGING_DB_HOST:-${DB_HOST:-localhost}}"

  [[ -n "$BACKUP_FILE" ]] || error "Nenhum arquivo de backup informado."
  [[ -f "$BACKUP_FILE" ]] || error "Arquivo de backup '$BACKUP_FILE' não existe."
  [[ -n "$staging_db_password" ]] || error "Variável STAGING_DB_PASSWORD não definida."

  log_step "Restaurando backup em '${staging_db_name}'"
  PGPASSWORD="$staging_db_password" psql -U "$staging_db_user" -h "$staging_db_host" -d "$staging_db_name" < "$BACKUP_FILE"
  printf '✅ Restore concluído (%s)\n' "$BACKUP_FILE"
}

php_tasks() {
  ensure_command php

  if [[ -f "$PROJECT_ROOT/reset.php" ]]; then
    log_step "Executando reset.php --no-cache"
    php "$PROJECT_ROOT/reset.php" --no-cache
  fi

  log_step "Rodando lint PHP"
  find "$PROJECT_ROOT/controllers" "$PROJECT_ROOT/includes" "$PROJECT_ROOT/models" -name '*.php' -print0 | \
    xargs -0 -n1 php -l >/dev/null
  printf '✅ Lint PHP concluído\n'

  if [[ -f "$PROJECT_ROOT/test_connection.php" ]]; then
    log_step "Validando conexão PHP com o banco"
    php "$PROJECT_ROOT/test_connection.php"
  fi
}

node_tasks() {
  ensure_command npm
  local node_dir="$PROJECT_ROOT/node-app"

  log_step "Instalando dependências Node"
  if [[ -f "$node_dir/package-lock.json" ]]; then
    npm --prefix "$node_dir" ci --no-audit --no-fund
  else
    npm --prefix "$node_dir" install --no-audit --no-fund
  fi

  log_step "Compilando servidor Node (tsc)"
  npm --prefix "$node_dir" run build:server

  log_step "Gerando build completa (Vite)"
  npm --prefix "$node_dir" run build

  log_step "Executando lint Node"
  npm --prefix "$node_dir" run lint
}

while [[ $# -gt 0 ]]; do
  case "$1" in
    -c|--config)
      CONFIG_FILE="$2"
      shift 2
      ;;
    --backup-file)
      BACKUP_FILE="$2"
      shift 2
      ;;
    --skip-backup)
      SKIP_BACKUP=1
      shift
      ;;
    --skip-restore)
      SKIP_RESTORE=1
      shift
      ;;
    --skip-tests)
      SKIP_TESTS=1
      shift
      ;;
    -h|--help)
      usage
      exit 0
      ;;
    *)
      usage >&2
      exit 1
      ;;
  esac
done

load_config

if [[ "$SKIP_BACKUP" -eq 0 ]]; then
  create_backup
elif [[ -z "$BACKUP_FILE" ]]; then
  warn "--skip-backup informado sem --backup-file; nenhum restore poderá ser executado."
fi

if [[ "$SKIP_RESTORE" -eq 0 ]]; then
  restore_backup
fi

if [[ "$SKIP_TESTS" -eq 0 ]]; then
  php_tasks
  node_tasks
fi

log_step "Processo concluído"
printf 'Backup utilizado: %s\n' "${BACKUP_FILE:-N/A}"
