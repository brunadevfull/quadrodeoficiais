import { RequestHandler } from 'express';
import { logger } from '../config/logger';
import {
  addOfficer,
  getAllOfficers,
  removeOfficer,
  updateOfficer
} from '../repositories/officersRepository';

const isValidationError = (error: unknown): boolean => {
  return error instanceof Error && error.message === 'Dados inválidos fornecidos.';
};

const respondWithOfficerError = (
  res: Parameters<RequestHandler>[1],
  error: unknown,
  fallbackMessage: string
) => {
  if (isValidationError(error)) {
    res.status(400).json({ success: false, error: (error as Error).message });
    return;
  }

  if (error instanceof Error && error.message) {
    res.status(500).json({ success: false, error: error.message });
    return;
  }

  res.status(500).json({ success: false, error: fallbackMessage });
};

const parseIdFromBody = (value: unknown): number | null => {
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

export const listOfficers: RequestHandler = async (_req, res) => {
  try {
    const officers = await getAllOfficers();
    res.json({ success: true, officers });
  } catch (error) {
    logger.error({ err: error }, 'Falha ao listar oficiais');
    res.status(500).json({ success: false, error: 'Falha ao listar oficiais.' });
  }
};

export const createOfficer: RequestHandler = async (req, res) => {
  try {
    await addOfficer({
      nome: req.body?.nome,
      postoId: req.body?.postoId ?? req.body?.posto ?? req.body?.posto_id,
      status: req.body?.status,
      localizacao: req.body?.localizacao
    });

    res.status(201).json({ success: true, message: 'Oficial cadastrado com sucesso.' });
  } catch (error) {
    logger.error({ err: error, body: req.body }, 'Falha ao adicionar oficial');
    respondWithOfficerError(res, error, 'Falha ao adicionar oficial.');
  }
};

export const editOfficer: RequestHandler = async (req, res) => {
  const id = parseIdFromBody(req.body?.id);

  if (id === null) {
    res.status(400).json({ success: false, error: 'ID do oficial inválido.' });
    return;
  }

  try {
    await updateOfficer({
      id,
      nome: req.body?.nome,
      postoId: req.body?.postoId ?? req.body?.posto ?? req.body?.posto_id,
      status: req.body?.status,
      localizacao: req.body?.localizacao
    });

    res.json({ success: true, message: 'Oficial atualizado com sucesso.' });
  } catch (error) {
    logger.error({ err: error, body: req.body }, 'Falha ao editar oficial');
    respondWithOfficerError(res, error, 'Falha ao editar oficial.');
  }
};

export const deleteOfficer: RequestHandler = async (req, res) => {
  const id = parseIdFromBody(req.body?.id ?? req.query?.id);

  if (id === null) {
    res.status(400).json({ success: false, error: 'ID do oficial inválido.' });
    return;
  }

  try {
    await removeOfficer(id);
    res.json({ success: true, message: 'Oficial removido com sucesso.' });
  } catch (error) {
    logger.error({ err: error, body: req.body, query: req.query }, 'Falha ao remover oficial');
    respondWithOfficerError(res, error, 'Falha ao remover oficial.');
  }
};

