import { Router } from 'express';
import { authRouter } from './authRoutes';
import { healthController } from '../controllers/healthController';
import { dutyOfficersRouter } from './dutyOfficersRoutes';
import { officersRouter } from './officersRoutes';
import { sunsetRouter } from './sunsetRoutes';

const router = Router();

router.get('/health', healthController);
router.use(authRouter);
router.use('/oficiais', officersRouter);
router.use('/duty-officers', dutyOfficersRouter);
router.use('/sunset', sunsetRouter);

export { router };
