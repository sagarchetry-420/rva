import { Request, Response, NextFunction } from 'express';

/**
 * Wraps an async route handler so unhandled rejections are forwarded to Express error middleware.
 * Usage: router.get('/path', asyncHandler(async (req, res) => { ... }));
 */
export function asyncHandler(fn: (req: Request, res: Response, next: NextFunction) => Promise<any>) {
  return (req: Request, res: Response, next: NextFunction) => {
    Promise.resolve(fn(req, res, next)).catch(next);
  };
}

/**
 * Global error handler — mount as the LAST middleware in index.ts.
 * Catches unhandled errors and returns a consistent JSON response.
 */
export function globalErrorHandler(err: any, _req: Request, res: Response, _next: NextFunction) {
  const status = err.status || err.statusCode || 500;
  const message = err.message || 'Internal server error';

  console.error(`[ERROR] ${status} — ${message}`, err.stack ? `\n${err.stack}` : '');

  res.status(status).json({
    error: message,
    ...(process.env.NODE_ENV !== 'production' && { stack: err.stack }),
  });
}
