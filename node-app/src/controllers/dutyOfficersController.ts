import { RequestHandler } from 'express';
import { logger } from '../config/logger';
import {
  createAssignment,
  DutyAssignmentInput,
  DutyAssignmentRecord,
  getCurrentAssignment
} from '../repositories/dutyAssignmentsRepository';

const buildEmptyAssignment = (): DutyAssignmentRecord => ({
  id: null,
  officerName: null,
  officerRank: null,
  officerDisplayName: null,
  masterName: null,
  masterRank: null,
  masterDisplayName: null,
  validFrom: null,
  updatedAt: null
});

const sanitizeNullableString = (value: unknown): string | null => {
  if (value === null || value === undefined) {
    return null;
  }

  const text = String(value).trim();
  return text === '' ? null : text;
};

const normalizePayload = (body: unknown): DutyAssignmentInput | null => {
  if (typeof body !== 'object' || body === null) {
    return null;
  }

  const rawOfficerName = sanitizeNullableString((body as Record<string, unknown>).officerName);
  const rawMasterName = sanitizeNullableString((body as Record<string, unknown>).masterName);

  if (rawOfficerName === null && rawMasterName === null) {
    return null;
  }

  const officerRank = sanitizeNullableString((body as Record<string, unknown>).officerRank);
  const masterRank = sanitizeNullableString((body as Record<string, unknown>).masterRank);

  const validFromRaw = (body as Record<string, unknown>).validFrom;
  const validFrom =
    validFromRaw instanceof Date
      ? validFromRaw
      : typeof validFromRaw === 'string' && validFromRaw.trim() !== ''
        ? validFromRaw.trim()
        : null;

  return {
    officerName: rawOfficerName,
    officerRank,
    masterName: rawMasterName,
    masterRank,
    validFrom
  };
};

const mapAssignmentError = (error: unknown): { status: number; message: string } => {
  if (error instanceof Error) {
    if (error.message === 'Data/hora inválida informada.') {
      return { status: 400, message: error.message };
    }

    if (error.message === 'Não foi possível recuperar o registro salvo.') {
      return { status: 500, message: error.message };
    }

    if (error.message === 'Falha ao salvar oficiais de serviço.') {
      return { status: 500, message: error.message };
    }
  }

  return {
    status: 500,
    message: 'Banco de dados de oficiais de serviço não disponível. Verifique a configuração.'
  };
};

export const getDutyOfficers: RequestHandler = async (_req, res) => {
  try {
    const assignment = await getCurrentAssignment();
    res.json({ success: true, officers: assignment ?? buildEmptyAssignment() });
  } catch (error) {
    logger.error({ err: error }, 'Falha ao buscar oficiais de serviço');
    res.json({ success: true, officers: buildEmptyAssignment() });
  }
};

export const updateDutyOfficers: RequestHandler = async (req, res) => {
  const payload = normalizePayload(req.body);

  if (payload === null) {
    res.status(400).json({ success: false, error: 'Selecione pelo menos um oficial de serviço.' });
    return;
  }

  try {
    const assignment = await createAssignment(payload);
    res.json({ success: true, officers: assignment });
  } catch (error) {
    logger.error({ err: error, body: req.body }, 'Falha ao atualizar oficiais de serviço');
    const mapped = mapAssignmentError(error);
    res.status(mapped.status).json({ success: false, error: mapped.message });
  }
};

