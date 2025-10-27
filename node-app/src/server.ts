import path from 'path';
import express from 'express';
import helmet from 'helmet';
import session from 'express-session';
import type { NextFunction, Request, Response } from 'express';
import { env } from './config/env';
import { logger } from './config/logger';
import { sessionConfig } from './config/session';
import { router } from './routes';
import { errorHandler } from './middlewares/errorHandler';
import { notFoundHandler } from './middlewares/notFoundHandler';
import { requestLogger } from './middlewares/requestLogger';

const app = express();

app.disable('x-powered-by');
app.use(helmet());
app.use(express.json());
app.use(requestLogger);
app.use(session(sessionConfig));

const staticDir = path.join(__dirname, '..', 'public');

app.use(express.static(staticDir));

app.use('/api', router);

app.get('/*', (req: Request, res: Response, next: NextFunction) => {
  if (req.method !== 'GET' || req.path.startsWith('/api')) {
    next();
    return;
  }

  res.sendFile(path.join(staticDir, 'index.html'));
});

app.use(notFoundHandler);
app.use(errorHandler);

const start = () => {
  app.listen(env.port, () => {
    logger.info({ port: env.port }, 'HTTP server started');
  });
};

if (require.main === module) {
  start();
}

export { app, start };
