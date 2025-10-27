import { pool } from '../config/database';

export interface LoginAuditInput {
  userId: number;
  username: string;
  clientIp: string | null;
}

export const recordLoginAudit = async ({
  userId,
  username,
  clientIp
}: LoginAuditInput): Promise<void> => {
  await pool.query(
    'INSERT INTO login_audit (user_id, username, ip_cliente) VALUES ($1, $2, $3)',
    [userId, username, clientIp]
  );
};
