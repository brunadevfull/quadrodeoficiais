import { Pool, PoolConfig } from 'pg';
import { env } from './env';

const DEFAULT_EXTERNAL_DATABASE_URL =
  'postgresql://postgres:suasenha123@localhost:5432/marinha_papem';

const isPostgresProtocol = (protocol: string): boolean => {
  const normalized = protocol.replace(':', '').toLowerCase();
  return normalized === 'postgresql' || normalized === 'postgres';
};

const validateDatabaseUrl = (rawUrl: string | undefined): string => {
  const candidate = rawUrl && rawUrl.trim() !== '' ? rawUrl.trim() : DEFAULT_EXTERNAL_DATABASE_URL;

  let parsed: URL;

  try {
    parsed = new URL(candidate);
  } catch (error) {
    throw new Error('DATABASE_URL inválida ou ausente.', { cause: error });
  }

  if (!isPostgresProtocol(parsed.protocol)) {
    throw new Error('DATABASE_URL deve utilizar o driver PostgreSQL.');
  }

  const database = parsed.pathname.replace(/^\//, '');

  if (!database) {
    throw new Error('DATABASE_URL não contém o nome do banco de dados.');
  }

  return candidate;
};

const buildExternalPoolConfig = (): PoolConfig => {
  const connectionString = validateDatabaseUrl(env.databaseUrl);

  return {
    connectionString,
    ssl: env.nodeEnv === 'production' ? { rejectUnauthorized: true } : false
  };
};

let externalPool: Pool | null = null;

export const getExternalPool = (): Pool => {
  if (!externalPool) {
    externalPool = new Pool(buildExternalPoolConfig());
  }

  return externalPool;
};

export const getExternalConnection = () => getExternalPool().connect();

export const getExternalDatabaseUrl = () => validateDatabaseUrl(env.databaseUrl);
