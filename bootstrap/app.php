<?php

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
        //
        $middleware->alias([
            'super_admin'  => \App\Http\Middleware\SuperAdminMiddleware::class,
            'school.active' => \App\Http\Middleware\SchoolActiveMiddleware::class,
            'admin_or_super_with_context' => \App\Http\Middleware\AdminOrSuperWithContextMiddleware::class,
            'auth' => \App\Http\Middleware\Authenticate::class,
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'teacher' => \App\Http\Middleware\TeacherMiddleware::class,
            'student' => \App\Http\Middleware\StudentMiddleware::class,
            'parent' => \App\Http\Middleware\ParentMiddleware::class,
            'role' => \App\Http\Middleware\RoleMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
