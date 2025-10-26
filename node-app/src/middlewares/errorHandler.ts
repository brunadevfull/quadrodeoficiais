import { Request, Response, NextFunction } from 'express';
import { logger } from '../config/logger';

export class HttpError extends Error {
  public status: number;
  public details?: unknown;

  constructor(status: number, message: string, details?: unknown) {
    super(message);
    this.status = status;
    this.details = details;
  }
}

// eslint-disable-next-line @typescript-eslint/no-unused-vars
export const errorHandler = (error: unknown, req: Request, res: Response, _next: NextFunction) => {
  const status = error instanceof HttpError ? error.status : 500;
  const message = error instanceof Error ? error.message : 'Unknown error';

  logger.error({ error, path: req.path, method: req.method }, 'Request failed');

  res.status(status).json({
    status: 'error',
    message,
    ...(error instanceof HttpError && error.details ? { details: error.details } : {})
  });
};
