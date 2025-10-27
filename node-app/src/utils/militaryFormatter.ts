const normalizeString = (value: unknown): string => {
  if (value === null || value === undefined) {
    return '';
  }

  const trimmed = String(value).trim();

  if (trimmed === '') {
    return '';
  }

  try {
    return trimmed.toLocaleUpperCase('pt-BR');
  } catch {
    return trimmed.toUpperCase();
  }
};

const stripOuterParentheses = (value: string): string => {
  let stripped = value;

  while (stripped.startsWith('(') && stripped.endsWith(')')) {
    stripped = stripped.slice(1, -1).trim();
  }

  return stripped;
};

export const formatRank = (rank: unknown): string => normalizeString(rank);

export const formatSpecialty = (specialty: unknown): string => {
  if (specialty === null || specialty === undefined) {
    return '';
  }

  const trimmed = String(specialty).trim();

  if (trimmed === '') {
    return '';
  }

  const withoutParentheses = stripOuterParentheses(trimmed);

  return normalizeString(withoutParentheses);
};

export const formatName = (name: unknown): string => normalizeString(name);

export const buildRankWithSpecialty = (
  rank: unknown,
  specialty?: unknown
): string => {
  const formattedRank = formatRank(rank);
  const formattedSpecialty = formatSpecialty(specialty);

  if (formattedRank === '' && formattedSpecialty === '') {
    return '';
  }

  if (formattedRank === '') {
    return `(${formattedSpecialty})`;
  }

  if (formattedSpecialty === '') {
    return formattedRank;
  }

  if (formattedRank.toLocaleUpperCase().includes(formattedSpecialty)) {
    return formattedRank;
  }

  return `${formattedRank} (${formattedSpecialty})`;
};

export const buildDisplayName = (
  rank: unknown,
  name: unknown,
  specialty?: unknown
): string => {
  const rankWithSpecialty = buildRankWithSpecialty(rank, specialty);
  const formattedName = formatName(name);

  const parts: string[] = [];

  if (rankWithSpecialty !== '') {
    parts.push(rankWithSpecialty);
  }

  if (formattedName !== '') {
    parts.push(formattedName);
  }

  return parts.join(' ').trim();
};

export const militaryFormatter = {
  formatRank,
  formatSpecialty,
  formatName,
  buildRankWithSpecialty,
  buildDisplayName
};

export type MilitaryFormatter = typeof militaryFormatter;
