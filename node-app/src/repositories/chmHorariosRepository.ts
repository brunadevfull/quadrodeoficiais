import { pool } from '../config/database';
import { logger } from '../config/logger';

const DEFAULT_SUNSET = '18:00';

const stripSeconds = (time: string): string => time.slice(0, 5);

const toNullableString = (value: unknown): string | null => {
  if (value === null || value === undefined) {
    return null;
  }

  const text = String(value).trim();

  return text === '' ? null : text;
};

const safeString = (value: unknown): string => toNullableString(value) ?? '';

export interface SunsetInfo {
  date: string;
  sunset_time: string;
  source: string;
  notes: string;
  has_passed: boolean;
  formatted_date: string;
}

export interface WeekSunsetRecord {
  data: string;
  por_do_sol: string;
  fonte: string;
  formatted_date: string;
}

const formatDate = (date: Date): string => {
  if (Number.isNaN(date.getTime())) {
    return '01/01/1970';
  }

  const day = `${date.getDate()}`.padStart(2, '0');
  const month = `${date.getMonth() + 1}`.padStart(2, '0');
  const year = date.getFullYear();
  return `${day}/${month}/${year}`;
};

const buildDateFromString = (value: string): Date => {
  const [year, month, day] = value.split('-').map((part) => Number(part));

  if ([year, month, day].some((part) => Number.isNaN(part))) {
    const parsed = new Date(value);
    return Number.isNaN(parsed.getTime()) ? new Date(0) : parsed;
  }

  return new Date(year, month - 1, day);
};

const hasSunsetPassed = (sunsetTime: string): boolean => {
  const [hours, minutes] = sunsetTime.split(':').map((part) => Number(part));
  const sunsetDate = new Date();
  sunsetDate.setHours(hours, minutes, 0, 0);

  return new Date() > sunsetDate;
};

export const getSunsetTimeForDate = async (date: string): Promise<string> => {
  try {
    const { rows } = await pool.query(
      'SELECT por_do_sol FROM chm_horarios WHERE data = $1 LIMIT 1',
      [date]
    );

    const raw = rows[0]?.por_do_sol;
    const parsed = toNullableString(raw);

    if (!parsed) {
      return DEFAULT_SUNSET;
    }

    return stripSeconds(parsed);
  } catch (error) {
    logger.error({ err: error, date }, 'Erro ao buscar horário CHM');
    return DEFAULT_SUNSET;
  }
};

export const getTodaysSunsetTime = async (): Promise<string> => {
  const today = new Date();
  const isoDate = today.toISOString().slice(0, 10);
  return getSunsetTimeForDate(isoDate);
};

export const getSunsetInfo = async (date?: string): Promise<SunsetInfo> => {
  const targetDate = date ?? new Date().toISOString().slice(0, 10);

  try {
    const { rows } = await pool.query(
      `
        SELECT data, por_do_sol, fonte, observacoes
          FROM chm_horarios
         WHERE data = $1
         LIMIT 1
      `,
      [targetDate]
    );

    const row = rows[0];

    if (!row) {
      const fallbackDate = buildDateFromString(targetDate);

      return {
        date: targetDate,
        sunset_time: DEFAULT_SUNSET,
        source: 'Fallback',
        notes: 'Horário padrão',
        has_passed: false,
        formatted_date: formatDate(fallbackDate)
      };
    }

    const sunsetTime = stripSeconds(toNullableString(row.por_do_sol) ?? DEFAULT_SUNSET);
    const formattedDate = formatDate(buildDateFromString(String(row.data)));

    return {
      date: String(row.data),
      sunset_time: sunsetTime,
      source: safeString(row.fonte),
      notes: safeString(row.observacoes),
      has_passed:
        targetDate === new Date().toISOString().slice(0, 10) ? hasSunsetPassed(sunsetTime) : false,
      formatted_date: formattedDate
    };
  } catch (error) {
    logger.error({ err: error, date: targetDate }, 'Erro ao buscar informações CHM');

    const fallbackDate = buildDateFromString(targetDate);

    return {
      date: targetDate,
      sunset_time: DEFAULT_SUNSET,
      source: 'Error',
      notes: 'Erro ao consultar banco',
      has_passed: false,
      formatted_date: formatDate(fallbackDate)
    };
  }
};

export const getWeekSunsetTimes = async (): Promise<WeekSunsetRecord[]> => {
  try {
    const { rows } = await pool.query(
      `
        SELECT data, por_do_sol, fonte
          FROM chm_horarios
         WHERE data >= CURRENT_DATE
      ORDER BY data
         LIMIT 7
      `
    );

    return rows.map((row) => ({
      data: String(row.data),
      por_do_sol: stripSeconds(toNullableString(row.por_do_sol) ?? DEFAULT_SUNSET),
      fonte: safeString(row.fonte),
      formatted_date: formatDate(buildDateFromString(String(row.data)))
    }));
  } catch (error) {
    logger.error({ err: error }, 'Erro ao buscar horários da semana');
    return [];
  }
};

export const getJavaScriptData = async (): Promise<string> => {
  try {
    const { rows } = await pool.query(
      `
        SELECT data, por_do_sol
          FROM chm_horarios
         WHERE data >= CURRENT_DATE AND data <= CURRENT_DATE + INTERVAL '30 days'
      ORDER BY data
      `
    );

    const entries = rows.map((row) => [
      String(row.data),
      stripSeconds(toNullableString(row.por_do_sol) ?? DEFAULT_SUNSET)
    ] as const);

    return JSON.stringify(Object.fromEntries(entries));
  } catch (error) {
    logger.error({ err: error }, 'Erro ao gerar dados JavaScript');
    return '{}';
  }
};

export const getDebugInfo = async (): Promise<unknown> => {
  try {
    const countResult = await pool.query('SELECT COUNT(*)::int AS total FROM chm_horarios');
    const totalRecords = Number(countResult.rows[0]?.total ?? 0);
    const todayDate = new Date().toISOString().slice(0, 10);
    const todaySunset = await getSunsetTimeForDate(todayDate);

    return {
      status: 'OK',
      total_records: totalRecords,
      today_date: todayDate,
      today_sunset: todaySunset,
      has_passed: hasSunsetPassed(todaySunset)
    };
  } catch (error) {
    logger.error({ err: error }, 'Erro no debug CHM');
    return { status: 'ERRO', message: (error as Error).message };
  }
};
