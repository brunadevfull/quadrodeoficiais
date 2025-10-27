import { RequestHandler } from 'express';
import { logger } from '../config/logger';
import { getAllPosts } from '../repositories/postsRepository';

export const listPosts: RequestHandler = async (_req, res) => {
  try {
    const posts = await getAllPosts();
    res.json({ success: true, posts });
  } catch (error) {
    logger.error({ err: error }, 'Falha ao listar postos');
    res.status(500).json({ success: false, error: 'Falha ao listar postos.' });
  }
};
