import type { Session } from 'express-session';

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

const getSessionUserId = (session: Session | undefined): number | null => {
  if (!session) {
    return null;
  }

  return parseNumericId(session.userId ?? session.user_id ?? null);
};

const getSessionUsername = (session: Session | undefined): string | null => {
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

const isSessionAdmin = (session: Session | undefined): boolean => {
  if (!session) {
    return false;
  }

  return parseBoolean(session.isAdmin ?? session.is_admin ?? false);
};

export { getSessionUserId, getSessionUsername, isSessionAdmin };

