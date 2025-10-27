export const createErrorWithCause = (message: string, cause?: unknown): Error => {
  const error = new Error(message);

  if (cause !== undefined) {
    (error as Error & { cause?: unknown }).cause = cause;
  }

  return error;
};
