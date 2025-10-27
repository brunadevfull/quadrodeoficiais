import { Router } from 'express';
import { getSunset } from '../controllers/sunsetController';

const sunsetRouter = Router();

sunsetRouter.get('/', getSunset);

export { sunsetRouter };

