import session from 'express-session';
import { env } from './env';

export const sessionConfig: session.SessionOptions = {
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
