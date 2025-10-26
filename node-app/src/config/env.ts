import { config } from 'dotenv';

const envFile = process.env.NODE_ENV === 'test' ? '.env.test' : '.env';
config({ path: envFile, override: true });

type DatabaseConfig =
  | { connectionString: string }
  | { host: string; port: number; database: string; user: string; password: string };

const parseNumber = (value: string | undefined, fallback: number): number => {
  if (!value) {
    return fallback;
  }

  const parsed = Number.parseInt(value, 10);
  return Number.isNaN(parsed) ? fallback : parsed;
};

const databaseUrl = process.env.DATABASE_URL;

const database: DatabaseConfig = databaseUrl
  ? { connectionString: databaseUrl }
  : {
      host: process.env.DB_HOST ?? 'localhost',
      port: parseNumber(process.env.DB_PORT, 5432),
      database: process.env.DB_NAME ?? 'postgres',
      user: process.env.DB_USER ?? 'postgres',
      password: process.env.DB_PASSWORD ?? ''
    };

export const env = {
  nodeEnv: process.env.NODE_ENV ?? 'development',
  port: parseNumber(process.env.PORT, 3000),
  sessionSecret: process.env.SESSION_SECRET ?? 'session-secret',
  logLevel: process.env.LOG_LEVEL ?? 'info',
  database,
  databaseUrl
};

export type { DatabaseConfig };
