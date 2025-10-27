import type { PoolClient } from 'pg';
import { pool } from '../config/database';
import { createErrorWithCause } from '../utils/errors';

export interface OfficerRecord {
  id: number;
  nome: string;
  descricao: string;
  imagem: string | null;
  status: string;
  localizacao: number;
  postoId: number;
}

export interface CreateOfficerInput {
  nome: string;
  postoId: number;
  status: string;
  localizacao: number;
}

export interface UpdateOfficerInput extends CreateOfficerInput {
  id: number;
}

const sanitizeText = (value: unknown): string => String(value ?? '').trim();

const normalizeOfficerPayload = <T extends CreateOfficerInput | UpdateOfficerInput>(
  payload: T
): T & CreateOfficerInput => {
  const nome = sanitizeText(payload.nome);
  const status = sanitizeText(payload.status);
  const postoId = Number(payload.postoId);
  const localizacao = Number(payload.localizacao);

  if (!nome || !status || Number.isNaN(postoId) || postoId <= 0 || Number.isNaN(localizacao) || localizacao < 0) {
    throw new Error('Dados inválidos fornecidos.');
  }

  return {
    ...payload,
    nome,
    status,
    postoId,
    localizacao
  };
};

const mapOfficerRow = (row: Record<string, unknown>): OfficerRecord => ({
  id: Number(row.id),
  nome: String(row.nome ?? ''),
  descricao: String(row.descricao ?? ''),
  imagem: row.imagem === null || row.imagem === undefined ? null : String(row.imagem),
  status: String(row.status ?? ''),
  localizacao: Number(row.localizacao ?? 0),
  postoId: Number(row.posto_id ?? row.postoId ?? 0)
});

export const getAllOfficers = async (): Promise<OfficerRecord[]> => {
  const query = `
    SELECT o.id,
           o.nome,
           p.descricao,
           p.imagem,
           o.status,
           o.localizacao,
           o.posto_id
      FROM oficiais o
      JOIN postos p ON o.posto_id = p.id
  ORDER BY o.localizacao
  `;

  const { rows } = await pool.query<Record<string, unknown>>(query);
  return rows.map((row) => mapOfficerRow(row));
};

const beginTransaction = async (): Promise<PoolClient> => {
  const client = await pool.connect();
  await client.query('BEGIN');
  return client;
};

const commitTransaction = async (client: PoolClient): Promise<void> => {
  try {
    await client.query('COMMIT');
  } finally {
    client.release();
  }
};

const rollbackTransaction = async (client: PoolClient): Promise<void> => {
  try {
    await client.query('ROLLBACK');
  } finally {
    client.release();
  }
};

export const addOfficer = async (payload: CreateOfficerInput): Promise<void> => {
  const normalized = normalizeOfficerPayload(payload);

  const client = await beginTransaction();

  try {
    await client.query('UPDATE oficiais SET localizacao = localizacao + 1 WHERE localizacao >= $1', [
      normalized.localizacao
    ]);

    await client.query(
      'INSERT INTO oficiais (nome, posto_id, status, localizacao) VALUES ($1, $2, $3, $4)',
      [normalized.nome, normalized.postoId, normalized.status, normalized.localizacao]
    );

    await commitTransaction(client);
  } catch (error) {
    await rollbackTransaction(client);
    throw createErrorWithCause('Falha ao adicionar oficial.', error);
  }
};

export const updateOfficer = async (payload: UpdateOfficerInput): Promise<void> => {
  const normalized = normalizeOfficerPayload(payload);

  const client = await beginTransaction();

  try {
    await client.query(
      'UPDATE oficiais SET nome = $1, posto_id = $2, status = $3, localizacao = $4 WHERE id = $5',
      [
        normalized.nome,
        normalized.postoId,
        normalized.status,
        normalized.localizacao,
        payload.id
      ]
    );

    await commitTransaction(client);
  } catch (error) {
    await rollbackTransaction(client);
    throw createErrorWithCause('Falha ao editar oficial.', error);
  }
};

export const removeOfficer = async (id: number): Promise<void> => {
  const client = await beginTransaction();

  try {
    const { rows } = await client.query<Record<string, unknown>>(
      'SELECT localizacao FROM oficiais WHERE id = $1',
      [id]
    );
    const localizacao = rows[0]?.localizacao;

    await client.query('DELETE FROM oficiais WHERE id = $1', [id]);

    if (localizacao !== undefined) {
      await client.query('UPDATE oficiais SET localizacao = localizacao - 1 WHERE localizacao > $1', [
        localizacao
      ]);
    }

    await commitTransaction(client);
  } catch (error) {
    await rollbackTransaction(client);
    throw createErrorWithCause('Falha ao remover oficial.', error);
  }
};
