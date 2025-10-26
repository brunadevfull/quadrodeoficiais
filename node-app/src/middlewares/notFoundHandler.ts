import { Request, Response, NextFunction } from 'express';
import { HttpError } from './errorHandler';

export const notFoundHandler = (req: Request, _res: Response, next: NextFunction) => {
  next(new HttpError(404, `Route ${req.method} ${req.originalUrl} was not found`));
};
