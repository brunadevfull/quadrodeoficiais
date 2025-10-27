import { RequestHandler } from 'express';
import { logger } from '../config/logger';
import { getSunsetInfo } from '../repositories/chmHorariosRepository';

const sanitizeDateQuery = (value: unknown): string | undefined => {
  if (typeof value !== 'string') {
    return undefined;
  }

  const trimmed = value.trim();
  return trimmed === '' ? undefined : trimmed;
};

export const getSunset: RequestHandler = async (req, res) => {
  const date = sanitizeDateQuery(req.query.date);

  try {
    const sunset = await getSunsetInfo(date);
    res.json({ success: true, sunset });
  } catch (error) {
    logger.error({ err: error, date }, 'Falha ao buscar informações de pôr do sol');
    res.status(500).json({ success: false, error: 'Falha ao consultar informações do pôr do sol.' });
  }
};

