import { Router } from 'express';
import { getDutyOfficers, updateDutyOfficers } from '../controllers/dutyOfficersController';
import { ensureAuthenticated, requireDutyOfficerManager } from '../middlewares/auth';

const dutyOfficersRouter = Router();

dutyOfficersRouter.get('/', getDutyOfficers);
dutyOfficersRouter.put('/', ensureAuthenticated, requireDutyOfficerManager, updateDutyOfficers);

export { dutyOfficersRouter };

