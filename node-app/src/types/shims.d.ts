declare var process: {
  env: Record<string, string | undefined>;
};

declare var __dirname: string;

declare var require: {
  main: unknown;
};

declare var module: unknown;

declare module 'path' {
  export function join(...parts: string[]): string;
}

declare module 'dotenv' {
  export interface DotenvConfigOptions {
    path?: string;
    override?: boolean;
  }

  export function config(options?: DotenvConfigOptions): void;
}

declare module 'bcrypt' {
  export function compare(data: string, encrypted: string): Promise<boolean>;
  export function hash(data: string, rounds: number): Promise<string>;
}

declare module 'pino' {
  export interface LoggerOptions {
    level?: string;
    base?: Record<string, unknown>;
  }

  export interface Logger {
    info: (...args: unknown[]) => void;
    error: (...args: unknown[]) => void;
    warn: (...args: unknown[]) => void;
    child: (bindings: Record<string, unknown>) => Logger;
  }

  export default function pino(options?: LoggerOptions): Logger;
}

declare module 'helmet' {
  import type { RequestHandler } from 'express';

  export default function helmet(): RequestHandler;
}

declare module 'pg' {
  export interface PoolConfig {
    connectionString?: string;
    host?: string;
    port?: number;
    database?: string;
    user?: string;
    password?: string;
    ssl?: boolean | { rejectUnauthorized: boolean };
  }

  export interface QueryResult<T = unknown> {
    rows: T[];
    rowCount: number;
  }

  export interface PoolClient {
    query<T = unknown>(text: string, params?: unknown[]): Promise<QueryResult<T>>;
    release(): void;
  }

  export class Pool {
    constructor(config?: PoolConfig);
    query<T = unknown>(text: string, params?: unknown[]): Promise<QueryResult<T>>;
    connect(): Promise<PoolClient>;
  }
}

declare module 'express-session' {
  import type { RequestHandler } from 'express';

  export interface SessionData {
    [key: string]: unknown;
  }

  export interface Session extends SessionData {
    id: string;
    regenerate(callback: (err?: unknown) => void): void;
    destroy(callback: (err?: unknown) => void): void;
    save(callback: (err?: unknown) => void): void;
    cookie: {
      secure?: boolean;
      httpOnly?: boolean;
      sameSite?: boolean | 'lax' | 'strict' | 'none';
      maxAge?: number | null;
      [key: string]: unknown;
    };
    [key: string]: unknown;
  }

  export interface SessionOptions {
    secret: string | string[];
    name?: string;
    resave?: boolean;
    saveUninitialized?: boolean;
    store?: unknown;
    cookie?: {
      secure?: boolean;
      httpOnly?: boolean;
      sameSite?: boolean | 'lax' | 'strict' | 'none';
      maxAge?: number;
    };
  }

  export interface Store {
    get: (sid: string, callback: (err: unknown, session?: Session | null) => void) => void;
    set: (sid: string, session: SessionData, callback?: (err?: unknown) => void) => void;
    destroy: (sid: string, callback?: (err?: unknown) => void) => void;
  }

  interface SessionMiddleware {
    (options: SessionOptions): RequestHandler;
  }

  const session: SessionMiddleware;

  export default session;
  export { SessionData };
}

declare module 'connect-pg-simple' {
  import type session from 'express-session';
  import type { Pool } from 'pg';

  interface ConnectPgSimpleOptions {
    pool?: Pool;
    tableName?: string;
    createTableIfMissing?: boolean;
  }

  type ConnectPgSimpleFactory = (sess: typeof session) => {
    new (options?: ConnectPgSimpleOptions): unknown;
  };

  const connectPgSimple: ConnectPgSimpleFactory;
  export default connectPgSimple;
}

declare module 'express' {
  import type { Session } from 'express-session';

  export interface Request {
    method: string;
    path: string;
    originalUrl: string;
    headers: Record<string, string | string[] | undefined>;
    ip?: string;
    socket?: { remoteAddress?: string };
    session?: Session;
    body: any;
    query: any;
    params: any;
    [key: string]: unknown;
  }

  export interface Response {
    status: (code: number) => Response;
    json: (body: unknown) => Response;
    clearCookie: (name: string) => Response;
    sendFile: (path: string) => void;
    on: (event: string, listener: (...args: unknown[]) => void) => Response;
    statusCode: number;
    locals: Record<string, unknown>;
    [key: string]: unknown;
  }

  export type NextFunction = (error?: unknown) => void;

  export type RequestHandler = (req: Request, res: Response, next: NextFunction) => unknown;

  export interface Router {
    use: (...handlers: unknown[]) => Router;
    get: (...handlers: unknown[]) => Router;
    post: (...handlers: unknown[]) => Router;
    put: (...handlers: unknown[]) => Router;
    delete: (...handlers: unknown[]) => Router;
  }

  export interface Express extends Router {
    listen: (port: number, callback?: () => void) => void;
    disable: (setting: string) => void;
    use: (...handlers: unknown[]) => Express;
    get: (...handlers: unknown[]) => Express;
    post: (...handlers: unknown[]) => Express;
  }

  interface ExpressModule {
    (): Express;
    Router: () => Router;
    json: () => RequestHandler;
    static: (path: string) => RequestHandler;
  }

  const express: ExpressModule;
  export default express;
  export const Router: () => Router;
}
