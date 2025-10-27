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

DB_NAME="${DB_NAME:-paginadeoficiais}"
DB_USER="${DB_USER:-pagoficial_rw}"
DB_PASSWORD="${DB_PASSWORD:-Papem_RW@2024}"
DB_HOST="${DB_HOST:-localhost}"
BACKUP_DIR="${BACKUP_DIR:-/backup/db}"

if [[ -z "${DB_PASSWORD}" ]]; then
  echo "❌ Variável DB_PASSWORD não definida." >&2
  exit 1
fi

mkdir -p "$BACKUP_DIR"

TIMESTAMP="$(date +%Y%m%d_%H%M%S)"
BACKUP_FILE="$BACKUP_DIR/backup_${DB_NAME}_${TIMESTAMP}.sql"

echo "➡️  Gerando backup do banco '$DB_NAME' em '$BACKUP_FILE'..."
PGPASSWORD="$DB_PASSWORD" pg_dump -U "$DB_USER" -h "$DB_HOST" "$DB_NAME" > "$BACKUP_FILE"

echo "✅ Backup realizado com sucesso: $BACKUP_FILE"
echo "$BACKUP_FILE"
