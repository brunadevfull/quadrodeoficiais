import { RequestHandler } from 'express';
import {
  getSessionUserId,
  getSessionUsername,
  isSessionAdmin
} from '../utils/session';

export const ensureAuthenticated: RequestHandler = (req, res, next) => {
  const userId = getSessionUserId(req.session);

  if (userId !== null) {
    next();
    return;
  }

  res.status(401).json({ success: false, error: 'Usuário não autenticado.' });
};

export const requireAdmin: RequestHandler = (req, res, next) => {
  const userId = getSessionUserId(req.session);

  if (userId !== null && isSessionAdmin(req.session)) {
    next();
    return;
  }

  res.status(403).json({ success: false, error: 'Acesso não autorizado.' });
};

export const requireDutyOfficerManager: RequestHandler = (req, res, next) => {
  const userId = getSessionUserId(req.session);
  const username = getSessionUsername(req.session);
  const isAdmin = isSessionAdmin(req.session);

  if (userId !== null && (isAdmin || username?.toLowerCase() === 'eor')) {
    next();
    return;
  }

  res.status(403).json({ success: false, error: 'Acesso não autorizado.' });
};

