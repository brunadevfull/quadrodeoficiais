import connectPgSimple from 'connect-pg-simple';
import session, { SessionOptions } from 'express-session';
import { pool } from './database';
import { env } from './env';

const PgSession = connectPgSimple(session);

export const sessionCookieName = 'PHPSESSID';

export const sessionConfig: SessionOptions = {
  store: new PgSession({
    pool,
    tableName: 'sessions',
    createTableIfMissing: true
  }),
  name: sessionCookieName,
  secret: env.sessionSecret,
  resave: false,
  saveUninitialized: false,
  cookie: {
    secure: env.nodeEnv === 'production',
    httpOnly: true,
    sameSite: env.nodeEnv === 'production' ? 'strict' : 'lax',
    maxAge: 1000 * 60 * 60 // 1 hour
  }
};
