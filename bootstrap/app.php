<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*')) {
                $code = 500;
                $data = null;
                $message = $e->getMessage() ?: 'Internal Server Error';

                if ($e instanceof \Illuminate\Validation\ValidationException) {
                    $code = 422;
                    $message = 'The given data was invalid.';
                    $data = $e->errors();
                } elseif ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                    $code = 404;
                    $message = 'Data not found.';
                } elseif ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                    $code = $e->getStatusCode();
                }

                return response()->json([
                    'success' => false,
                    'message' => $message,
                    'code' => $code,
                    'data' => $data,
                ], $code);
            }
        });
    })->create();
