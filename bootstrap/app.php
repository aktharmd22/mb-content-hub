<?php

use App\Http\Middleware\EnsureUserHasRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => EnsureUserHasRole::class,
        ]);

        // Trust Hostinger's edge proxy so Laravel correctly detects HTTPS via X-Forwarded-Proto.
        // Without this, secure cookies break the login round-trip behind the proxy.
        $middleware->trustProxies(at: '*', headers:
            \Illuminate\Http\Request::HEADER_X_FORWARDED_FOR |
            \Illuminate\Http\Request::HEADER_X_FORWARDED_HOST |
            \Illuminate\Http\Request::HEADER_X_FORWARDED_PORT |
            \Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO |
            \Illuminate\Http\Request::HEADER_X_FORWARDED_AWS_ELB
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Never show the bare "419 Session expired" page. Instead, silently send the user
        // back to where they came from (or login) so they can try again with a fresh token.
        $exceptions->render(function (\Illuminate\Session\TokenMismatchException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Session expired. Please refresh and try again.'], 419);
            }

            // If user is logged in, bounce them to wherever they were (gets fresh CSRF on reload).
            // Otherwise, send to login fresh.
            $target = auth()->check()
                ? ($request->headers->get('referer') ?: route('dashboard'))
                : route('login');

            return redirect()->to($target)->with('error', 'Your session timed out. Please try again.');
        });

        // A file/upload bigger than the server's post_max_size makes PHP discard the whole
        // request, which otherwise surfaces as a bare 413 or a misleading "field is required".
        // Show a clear, friendly message instead.
        $exceptions->render(function (\Illuminate\Http\Exceptions\PostTooLargeException $e, $request) {
            $msg = 'That file is too large to upload. Please choose a smaller file (the server limit is '
                . (ini_get('upload_max_filesize') ?: 'a few MB') . ').';

            if ($request->expectsJson()) {
                return response()->json(['message' => $msg], 413);
            }

            $target = $request->headers->get('referer') ?: url()->previous();
            return redirect()->to($target)->with('error', $msg);
        });
    })->create();
