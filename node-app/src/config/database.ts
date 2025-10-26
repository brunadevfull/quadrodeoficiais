import { Pool, PoolConfig } from 'pg';
import { env } from './env';

const buildPoolConfig = (): PoolConfig => {
  if ('connectionString' in env.database) {
    return {
      connectionString: env.database.connectionString,
      ssl: env.nodeEnv === 'production' ? { rejectUnauthorized: true } : false
    };
  }

  return {
    host: env.database.host,
    port: env.database.port,
    database: env.database.database,
    user: env.database.user,
    password: env.database.password
  };
};

export const pool = new Pool(buildPoolConfig());

export const getConnection = () => pool.connect();
