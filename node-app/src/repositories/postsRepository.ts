import { pool } from '../config/database';

export interface PostRecord {
  id: number;
  descricao: string;
  imagem: string | null;
}

export const getAllPosts = async (): Promise<PostRecord[]> => {
  const { rows } = await pool.query<Record<string, unknown>>(
    'SELECT id, descricao, imagem FROM postos ORDER BY descricao'
  );

  return rows.map((row: Record<string, unknown>) => ({
    id: Number(row.id),
    descricao: String(row.descricao ?? ''),
    imagem: row.imagem === null || row.imagem === undefined ? null : String(row.imagem)
  }));
};
