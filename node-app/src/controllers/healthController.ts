import { Request, Response } from 'express';
import { checkDatabaseConnection } from '../repositories/healthRepository';

export const healthController = async (_req: Request, res: Response) => {
  const database = await checkDatabaseConnection();

  if (!database) {
    res.status(503).json({ status: 'error', database: false, message: 'Database unavailable' });
    return;
  }

  res.json({ status: 'ok', database: true });
};
