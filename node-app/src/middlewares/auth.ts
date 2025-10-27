import { RequestHandler } from 'express';

const parseNumericId = (value: unknown): number | null => {
  if (typeof value === 'number') {
    return Number.isInteger(value) && value > 0 ? value : null;
  }

  if (typeof value === 'string') {
    const trimmed = value.trim();

    if (trimmed === '') {
      return null;
    }

    const parsed = Number.parseInt(trimmed, 10);
    return Number.isNaN(parsed) || parsed <= 0 ? null : parsed;
  }

  return null;
};

const parseBoolean = (value: unknown): boolean => {
  if (typeof value === 'boolean') {
    return value;
  }

  if (typeof value === 'number') {
    return value !== 0;
  }

  if (typeof value === 'string') {
    const normalized = value.trim().toLowerCase();
    return normalized === 'true' || normalized === '1';
  }

  return false;
};

const getSessionUserId = (session: Express.Session | undefined): number | null => {
  if (!session) {
    return null;
  }

  return parseNumericId(session.userId ?? session.user_id ?? null);
};

const getSessionUsername = (session: Express.Session | undefined): string | null => {
  if (!session) {
    return null;
  }

  const raw = session.username;

  if (typeof raw !== 'string') {
    return null;
  }

  const trimmed = raw.trim();
  return trimmed === '' ? null : trimmed;
};

const isSessionAdmin = (session: Express.Session | undefined): boolean => {
  if (!session) {
    return false;
  }

  return parseBoolean(session.isAdmin ?? session.is_admin ?? false);
};

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

