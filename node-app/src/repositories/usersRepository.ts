import { pool } from '../config/database';

export interface UserRecord {
  id: number;
  username: string;
  password: string;
  isAdmin: boolean;
}

const mapUserRow = (row: Record<string, unknown>): UserRecord => ({
  id: Number(row.id),
  username: String(row.username ?? ''),
  password: String(row.password ?? ''),
  isAdmin: Boolean(row.is_admin ?? row.isAdmin ?? false)
});

export const getAdminById = async (id: number): Promise<UserRecord | null> => {
  const { rows } = await pool.query('SELECT * FROM users WHERE id = $1 AND is_admin = TRUE', [id]);

  if (rows.length === 0) {
    return null;
  }

  return mapUserRow(rows[0]);
};

export const updateAdminPassword = async (id: number, password: string): Promise<boolean> => {
  const { rowCount } = await pool.query(
    'UPDATE users SET password = $1 WHERE id = $2 AND is_admin = TRUE',
    [password, id]
  );

  return rowCount > 0;
};

export const updateUserPassword = async (id: number, password: string): Promise<boolean> => {
  const { rowCount } = await pool.query('UPDATE users SET password = $1 WHERE id = $2', [
    password,
    id
  ]);

  return rowCount > 0;
};
