import { Router } from 'express';
import {
  createOfficer,
  deleteOfficer,
  editOfficer,
  listOfficers
} from '../controllers/officersController';
import { ensureAuthenticated, requireAdmin } from '../middlewares/auth';

const officersRouter = Router();

officersRouter.get('/', listOfficers);
officersRouter.post('/', ensureAuthenticated, requireAdmin, createOfficer);
officersRouter.put('/', ensureAuthenticated, requireAdmin, editOfficer);
officersRouter.delete('/', ensureAuthenticated, requireAdmin, deleteOfficer);

export { officersRouter };

