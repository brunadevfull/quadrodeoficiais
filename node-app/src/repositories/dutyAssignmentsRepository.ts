import { getExternalPool } from '../config/externalDatabase';
import {
  buildDisplayName,
  formatName,
  formatRank
} from '../utils/militaryFormatter';

type NullableString = string | null;

export interface DutyAssignmentRecord {
  id: number | null;
  officerName: NullableString;
  officerRank: NullableString;
  officerDisplayName: NullableString;
  masterName: NullableString;
  masterRank: NullableString;
  masterDisplayName: NullableString;
  validFrom: NullableString;
  updatedAt: NullableString;
}

export interface DutyAssignmentInput {
  officerName?: unknown;
  officerRank?: unknown;
  masterName?: unknown;
  masterRank?: unknown;
  validFrom?: unknown;
}

const sanitizeNullableString = (value: unknown): NullableString => {
  if (value === null || value === undefined) {
    return null;
  }

  const trimmed = String(value).trim();

  return trimmed === '' ? null : trimmed;
};

const parseDateInput = (value: unknown): Date => {
  if (value instanceof Date && !Number.isNaN(value.getTime())) {
    return new Date(value.getTime());
  }

  if (typeof value === 'string' && value.trim() !== '') {
    const normalized = value.trim().replace(' ', 'T');
    const candidate = /[zZ]|[+-]\d{2}:?\d{2}$/.test(normalized)
      ? normalized
      : `${normalized}Z`;
    const parsed = new Date(candidate);

    if (!Number.isNaN(parsed.getTime())) {
      return parsed;
    }
  }

  throw new Error('Data/hora inválida informada.');
};

const formatDateTimeForDatabase = (date: Date): string => {
  const year = date.getUTCFullYear();
  const month = `${date.getUTCMonth() + 1}`.padStart(2, '0');
  const day = `${date.getUTCDate()}`.padStart(2, '0');
  const hours = `${date.getUTCHours()}`.padStart(2, '0');
  const minutes = `${date.getUTCMinutes()}`.padStart(2, '0');
  const seconds = `${date.getUTCSeconds()}`.padStart(2, '0');

  return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
};

const formatDateTimeFromDatabase = (value: unknown): NullableString => {
  if (value === null || value === undefined) {
    return null;
  }

  const raw = String(value).trim();

  if (raw === '') {
    return null;
  }

  try {
    const normalized = raw.replace(' ', 'T');
    const candidate = /[zZ]|[+-]\d{2}:?\d{2}$/.test(normalized)
      ? normalized
      : `${normalized}Z`;
    const parsed = new Date(candidate);

    if (Number.isNaN(parsed.getTime())) {
      return null;
    }

    return parsed.toISOString();
  } catch {
    return null;
  }
};

const normalizeAssignment = (row: Record<string, unknown>): DutyAssignmentRecord => {
  const officerName = sanitizeNullableString(row.officer_name ?? row.officerName);
  const officerRank = sanitizeNullableString(row.officer_rank ?? row.officerRank);
  const masterName = sanitizeNullableString(row.master_name ?? row.masterName);
  const masterRank = sanitizeNullableString(row.master_rank ?? row.masterRank);

  const formattedOfficerName = officerName ? formatName(officerName) : null;
  const formattedOfficerRank = officerRank ? formatRank(officerRank) : null;
  const formattedMasterName = masterName ? formatName(masterName) : null;
  const formattedMasterRank = masterRank ? formatRank(masterRank) : null;

  return {
    id: row.id === null || row.id === undefined ? null : Number(row.id),
    officerName: formattedOfficerName,
    officerRank: formattedOfficerRank,
    officerDisplayName:
      formattedOfficerName !== null || formattedOfficerRank !== null
        ? buildDisplayName(formattedOfficerRank, formattedOfficerName)
        : null,
    masterName: formattedMasterName,
    masterRank: formattedMasterRank,
    masterDisplayName:
      formattedMasterName !== null || formattedMasterRank !== null
        ? buildDisplayName(formattedMasterRank, formattedMasterName)
        : null,
    validFrom: formatDateTimeFromDatabase(row.valid_from ?? row.validFrom),
    updatedAt: formatDateTimeFromDatabase(row.updated_at ?? row.updatedAt)
  };
};

export const getCurrentAssignment = async (): Promise<DutyAssignmentRecord | null> => {
  const pool = getExternalPool();

  try {
    const { rows } = await pool.query(
      `
        SELECT id,
               officer_name,
               officer_rank,
               master_name,
               master_rank,
               valid_from,
               updated_at
          FROM duty_assignments
      ORDER BY valid_from DESC, updated_at DESC
         LIMIT 1
      `
    );

    if (rows.length === 0) {
      return null;
    }

    return normalizeAssignment(rows[0]);
  } catch (error) {
    throw new Error('Falha ao consultar oficiais de serviço.', { cause: error });
  }
};

export const createAssignment = async (
  payload: DutyAssignmentInput
): Promise<DutyAssignmentRecord> => {
  const pool = getExternalPool();

  const officerName = sanitizeNullableString(payload.officerName);
  const officerRank = sanitizeNullableString(payload.officerRank);
  const masterName = sanitizeNullableString(payload.masterName);
  const masterRank = sanitizeNullableString(payload.masterRank);

  const formattedOfficerName = officerName ? formatName(officerName) : null;
  const formattedOfficerRank = officerRank ? formatRank(officerRank) : null;
  const normalizedOfficerRank = formattedOfficerRank === '' ? null : formattedOfficerRank;

  const formattedMasterName = masterName ? formatName(masterName) : null;
  const formattedMasterRank = masterRank ? formatRank(masterRank) : null;
  const normalizedMasterRank = formattedMasterRank === '' ? null : formattedMasterRank;

  let validFromString: string;

  if (payload.validFrom === null || payload.validFrom === undefined || payload.validFrom === '') {
    const now = new Date();
    validFromString = formatDateTimeForDatabase(now);
  } else {
    const parsed = parseDateInput(payload.validFrom);
    validFromString = formatDateTimeForDatabase(parsed);
  }

  try {
    const { rows } = await pool.query(
      `
        INSERT INTO duty_assignments (officer_name, officer_rank, master_name, master_rank, valid_from)
        VALUES ($1, $2, $3, $4, $5)
        RETURNING id,
                  officer_name,
                  officer_rank,
                  master_name,
                  master_rank,
                  valid_from,
                  updated_at
      `,
      [
        formattedOfficerName,
        normalizedOfficerRank,
        formattedMasterName,
        normalizedMasterRank,
        validFromString
      ]
    );

    const row = rows[0];

    if (!row) {
      throw new Error('Não foi possível recuperar o registro salvo.');
    }

    return normalizeAssignment(row);
  } catch (error) {
    if (error instanceof Error && error.message === 'Não foi possível recuperar o registro salvo.') {
      throw error;
    }

    throw new Error('Falha ao salvar oficiais de serviço.', { cause: error });
  }
};
