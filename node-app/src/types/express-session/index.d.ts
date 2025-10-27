import 'express-session';

declare module 'express-session' {
  interface SessionData {
    userId?: number | string | null;
    user_id?: number | string | null;
    username?: string | null;
    isAdmin?: boolean | number | string | null;
    is_admin?: boolean | number | string | null;
  }
}

