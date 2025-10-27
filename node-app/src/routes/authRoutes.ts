import { Router } from 'express';
import {
  changePasswordController,
  loginController,
  logoutController
} from '../controllers/authController';
import { ensureAuthenticated, requireAdmin } from '../middlewares/auth';

const authRouter = Router();

authRouter.post('/login', loginController);
authRouter.post('/logout', logoutController);
authRouter.post('/password', ensureAuthenticated, requireAdmin, changePasswordController);

export { authRouter };
