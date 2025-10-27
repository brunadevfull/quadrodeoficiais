import { getExternalPool } from '../config/externalDatabase';
import {
  buildDisplayName,
  buildRankWithSpecialty,
  formatName,
  formatRank,
  formatSpecialty
} from '../utils/militaryFormatter';

export interface PersonnelOption {
  id: number | null;
  value: string;
  name: string;
  rank: string;
  shortRank: string;
  type: string;
  specialty: string;
  display: string;
}

const sanitizeType = (type: string): string => type.trim();

const normalizePersonnel = (
  row: Record<string, unknown>,
  requestedType: string
): {
  id: number | null;
  name: string;
  rank: string;
  rankDisplay: string;
  type: string;
  specialty: string;
  display: string;
} => {
  const name =
    (row.name as string | undefined) ??
    (row.nome as string | undefined) ??
    (row.fullName as string | undefined) ??
    (row.full_name as string | undefined) ??
    '';

  const rank =
    (row.rank as string | undefined) ??
    (row.posto as string | undefined) ??
    (row.descricao as string | undefined) ??
    (row.patente as string | undefined) ??
    '';

  const type = (row.type as string | undefined) ?? requestedType;
  const specialty =
    (row.specialty as string | undefined) ??
    (row.especialidade as string | undefined) ??
    '';

  const identifier = row.id ?? null;

  const formattedName = formatName(name);
  const formattedRank = formatRank(rank);
  const formattedSpecialty = formatSpecialty(specialty);
  const rankDisplay = buildRankWithSpecialty(formattedRank, formattedSpecialty);
  const display = buildDisplayName(formattedRank, formattedName, formattedSpecialty);

  return {
    id: identifier === null || identifier === undefined ? null : Number(identifier),
    name: formattedName,
    rank: formattedRank,
    rankDisplay,
    type: sanitizeType(String(type ?? '')),
    specialty: formattedSpecialty,
    display
  };
};

const buildOptionKey = (normalized: {
  id: number | null;
  name: string;
  rankDisplay: string;
  type: string;
}): string => {
  const parts = [
    normalized.id === null ? '' : String(normalized.id),
    normalized.name,
    normalized.rankDisplay,
    normalized.type
  ];

  return parts
    .map((part) => part.toLocaleLowerCase('pt-BR'))
    .join('|');
};

export const getPersonnelOptions = async (
  type: string
): Promise<PersonnelOption[]> => {
  const trimmedType = sanitizeType(type);

  if (trimmedType === '') {
    throw new Error('Tipo de militar inválido informado.');
  }

  const pool = getExternalPool();

  let rows: Record<string, unknown>[];

  try {
    const result = await pool.query('SELECT * FROM military_personnel WHERE type = $1', [
      trimmedType
    ]);
    rows = result.rows;
  } catch (error) {
    throw new Error('Falha ao consultar militares no banco de dados.', { cause: error });
  }

  const optionsMap = new Map<string, PersonnelOption>();

  for (const row of rows) {
    const normalized = normalizePersonnel(row, trimmedType);

    if (normalized.display === '') {
      continue;
    }

    const key = buildOptionKey(normalized);

    if (optionsMap.has(key)) {
      continue;
    }

    optionsMap.set(key, {
      id: normalized.id,
      value: normalized.name,
      name: normalized.name,
      rank: normalized.rankDisplay,
      shortRank: normalized.rank,
      type: normalized.type,
      specialty: normalized.specialty,
      display: normalized.display
    });
  }

  return Array.from(optionsMap.values()).sort((left, right) =>
    left.display.localeCompare(right.display, 'pt-BR')
  );
};
