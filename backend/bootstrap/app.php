<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [\App\Http\Middleware\ForceJsonResponse::class]);
        $middleware->prependToPriorityList(AuthenticatesRequests::class, \App\Http\Middleware\EnsureActiveToken::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
        $exceptions->respond(function (Response $response): Response {
            if (! request()->is('api/*')) {
                return $response;
            }

            $payload = json_decode((string) $response->getContent(), true);
            $status = $response->getStatusCode();
            $message = is_array($payload) && is_string($payload['message'] ?? null)
                ? $payload['message']
                : ($status >= 500 && ! config('app.debug') ? 'Server Error' : (Response::$statusTexts[$status] ?? 'Request failed.'));
            $body = ['message' => $message];

            if (is_array($payload['errors'] ?? null)) {
                $body['errors'] = $payload['errors'];
            }

            $body['code'] = match ($status) {
                401 => 'unauthenticated',
                403 => 'forbidden',
                404 => 'not_found',
                409 => 'conflict',
                419 => 'csrf_token_mismatch',
                422 => 'validation_error',
                429 => 'too_many_requests',
                default => $status >= 500 ? 'server_error' : 'http_error',
            };

            $headers = $response->headers->all();
            unset($headers['content-type'], $headers['content-length']);

            return response()->json($body, $status, $headers);
        });
    })->create();
