<?php

use App\Http\Middleware\JwtMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'jwt.auth' => JwtMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (Throwable $e, Request $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                $status = get_status_code($e);
                $message = config('app.debug') ? $e->getMessage() : get_error_message($e, $status);

                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], $status);
            }

            return null;
        });
    })->create();

function get_status_code(Throwable $e): int
{
    if ($e instanceof NotFoundHttpException) {
        return Response::HTTP_NOT_FOUND;
    }

    if (method_exists($e, 'getStatusCode')) {
        return $e->getStatusCode();
    }

    return Response::HTTP_INTERNAL_SERVER_ERROR;
}

function get_error_message(Throwable $e, int $status): string
{
    if ($status === Response::HTTP_NOT_FOUND) {
        return 'Resource not found';
    }

    if ($status === Response::HTTP_UNAUTHORIZED) {
        return 'Unauthorized';
    }

    if ($status === Response::HTTP_FORBIDDEN) {
        return 'Forbidden';
    }

    if ($status === Response::HTTP_UNPROCESSABLE_ENTITY) {
        return 'Validation failed';
    }

    return 'Server error';
}
