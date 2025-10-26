import express from 'express';
import helmet from 'helmet';
import session from 'express-session';
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

app.get('/', (_req, res) => {
  res.json({ status: 'running' });
});

app.use('/api', router);

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
