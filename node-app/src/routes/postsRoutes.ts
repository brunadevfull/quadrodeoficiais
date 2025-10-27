import { Router } from 'express';
import { listPosts } from '../controllers/postsController';

const postsRouter = Router();

postsRouter.get('/', listPosts);

export { postsRouter };
