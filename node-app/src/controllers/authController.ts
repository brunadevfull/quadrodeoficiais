import { RequestHandler } from 'express';
import bcrypt from 'bcrypt';
import { sessionCookieName } from '../config/session';
import { logger } from '../config/logger';
import { recordLoginAudit } from '../repositories/loginAuditRepository';
import {
  findUserByUsername,
  getAdminById,
  updateAdminPassword
} from '../repositories/usersRepository';
import { getSessionUserId } from '../utils/session';

const normalizeString = (value: unknown): string | null => {
  if (typeof value !== 'string') {
    return null;
  }

  const trimmed = value.trim();
  return trimmed === '' ? null : trimmed;
};

const extractClientIp = (req: Parameters<RequestHandler>[0]): string | null => {
  const forwarded = req.headers['x-forwarded-for'];

  if (Array.isArray(forwarded)) {
    return forwarded.length > 0 ? forwarded[0] : null;
  }

  if (typeof forwarded === 'string' && forwarded.trim() !== '') {
    return forwarded.split(',')[0].trim();
  }

  return req.ip ?? req.socket.remoteAddress ?? null;
};

const regenerateSession = (req: Parameters<RequestHandler>[0]): Promise<void> => {
  return new Promise((resolve, reject) => {
    req.session.regenerate((error) => {
      if (error) {
        reject(error);
        return;
      }

      resolve();
    });
  });
};

const saveSession = (req: Parameters<RequestHandler>[0]): Promise<void> => {
  return new Promise((resolve, reject) => {
    req.session.save((error) => {
      if (error) {
        reject(error);
        return;
      }

      resolve();
    });
  });
};

export const loginController: RequestHandler = async (req, res) => {
  const username = normalizeString(req.body?.username);
  const password = normalizeString(req.body?.password);

  if (!username || !password) {
    res
      .status(400)
      .json({ success: false, error: 'Usuário e senha são obrigatórios.' });
    return;
  }

  try {
    const user = await findUserByUsername(username);

    if (!user) {
      res.status(401).json({ success: false, error: 'Credenciais inválidas.' });
      return;
    }

    const isPasswordValid = await bcrypt.compare(password, user.password);

    if (!isPasswordValid) {
      res.status(401).json({ success: false, error: 'Credenciais inválidas.' });
      return;
    }

    await regenerateSession(req);

    req.session.userId = user.id;
    req.session.user_id = user.id;
    req.session.username = user.username;
    req.session.isAdmin = user.isAdmin;
    req.session.is_admin = user.isAdmin;

    const clientIp = extractClientIp(req);
    await recordLoginAudit({ userId: user.id, username: user.username, clientIp });

    await saveSession(req);

    res.json({
      success: true,
      user: {
        id: user.id,
        username: user.username,
        isAdmin: user.isAdmin
      }
    });
  } catch (error) {
    logger.error({ err: error }, 'Falha ao realizar login');
    res.status(500).json({ success: false, error: 'Falha ao realizar login.' });
  }
};

export const logoutController: RequestHandler = (req, res) => {
  if (!req.session) {
    res.json({ success: true });
    return;
  }

  req.session.destroy((error) => {
    if (error) {
      logger.error({ err: error }, 'Falha ao encerrar sessão');
      res.status(500).json({ success: false, error: 'Falha ao encerrar sessão.' });
      return;
    }

    res.clearCookie(sessionCookieName);
    res.json({ success: true });
  });
};

export const changePasswordController: RequestHandler = async (req, res) => {
  const currentPassword = normalizeString(req.body?.currentPassword ?? req.body?.current_password);
  const newPassword = normalizeString(req.body?.newPassword ?? req.body?.new_password);
  const confirmPassword = normalizeString(req.body?.confirmPassword ?? req.body?.confirm_password);

  if (!currentPassword || !newPassword || !confirmPassword) {
    res.status(400).json({ success: false, error: 'Todos os campos são obrigatórios.' });
    return;
  }

  if (newPassword !== confirmPassword) {
    res
      .status(400)
      .json({ success: false, error: 'A nova senha e a confirmação não coincidem.' });
    return;
  }

  const userId = getSessionUserId(req.session);

  if (userId === null) {
    res.status(401).json({ success: false, error: 'Usuário não autenticado.' });
    return;
  }

  try {
    const admin = await getAdminById(userId);

    if (!admin) {
      res.status(403).json({ success: false, error: 'Apenas administradores podem alterar a senha.' });
      return;
    }

    const isCurrentPasswordValid = await bcrypt.compare(currentPassword, admin.password);

    if (!isCurrentPasswordValid) {
      res.status(400).json({ success: false, error: 'Senha atual incorreta.' });
      return;
    }

    const hashedPassword = await bcrypt.hash(newPassword, 10);
    const updated = await updateAdminPassword(admin.id, hashedPassword);

    if (!updated) {
      res.status(500).json({ success: false, error: 'Não foi possível atualizar a senha.' });
      return;
    }

    res.json({ success: true, message: 'Senha atualizada com sucesso.' });
  } catch (error) {
    logger.error({ err: error }, 'Falha ao alterar senha');
    res.status(500).json({ success: false, error: 'Falha ao alterar senha.' });
  }
};
