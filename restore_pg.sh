#!/bin/bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
DEFAULT_CONFIG_FILE="$SCRIPT_DIR/config/migration.env"
CONFIG_FILE="${CONFIG_FILE:-$DEFAULT_CONFIG_FILE}"

if [[ -f "$CONFIG_FILE" ]]; then
  # shellcheck disable=SC1090
  set -a
  source "$CONFIG_FILE"
  set +a
fi

BACKUP_FILE="${1:-${ARQUIVO_BACKUP:-}}"

if [[ -z "$BACKUP_FILE" ]]; then
  echo "Uso: $0 <caminho_backup.sql>" >&2
  echo "Ou defina a variável de ambiente ARQUIVO_BACKUP." >&2
  exit 1
fi

if [[ ! -f "$BACKUP_FILE" ]]; then
  echo "❌ Arquivo de backup '$BACKUP_FILE' não encontrado." >&2
  exit 1
fi

STAGING_DB_NAME="${STAGING_DB_NAME:-${DB_NAME:-paginadeoficiais}}"
STAGING_DB_USER="${STAGING_DB_USER:-${DB_USER:-pagoficial_rw}}"
STAGING_DB_PASSWORD="${STAGING_DB_PASSWORD:-${DB_PASSWORD:-Papem_RW@2024}}"
STAGING_DB_HOST="${STAGING_DB_HOST:-${DB_HOST:-localhost}}"

if [[ -z "$STAGING_DB_PASSWORD" ]]; then
  echo "❌ Variável STAGING_DB_PASSWORD não definida." >&2
  exit 1
fi

echo "➡️  Restaurando backup '$BACKUP_FILE' no banco '$STAGING_DB_NAME'..."
PGPASSWORD="$STAGING_DB_PASSWORD" psql -U "$STAGING_DB_USER" -h "$STAGING_DB_HOST" -d "$STAGING_DB_NAME" < "$BACKUP_FILE"

echo "✅ Restore concluído com sucesso!"
