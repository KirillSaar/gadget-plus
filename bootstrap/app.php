<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
      ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureUserHasRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request, Throwable $e) => $request->is('api/*') || $request->expectsJson()
        );

        // 422 — ошибки валидации
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'Ошибка валидации',
                    'errors' => $e->errors(),
                ], 422);
            }
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'Требуется авторизация'], 401);
            }
        });

        $exceptions->render(function (AccessDeniedHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'Доступ запрещён'], 403);
            }
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                $message = 'Маршрут не найден';
                $previous = $e->getPrevious();

                if ($previous instanceof ModelNotFoundException) {
                    $messages = [
                        'Product' => 'Товар не найден',
                        'Category' => 'Категория не найдена',
                        'Brand' => 'Бренд не найден',
                        'Characteristic' => 'Характеристика не найдена',
                        'Order' => 'Заказ не найден',
                        'OrderItem' => 'Позиция заказа не найдена',
                        'User' => 'Пользователь не найден',
                    ];
                    $message = $messages[class_basename($previous->getModel())] ?? 'Запись не найдена';
                }

                return response()->json(['message' => $message], 404);
            }
        });

        $exceptions->render(function (MethodNotAllowedHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'Метод не поддерживается для этого адреса'], 405);
            }
        });

        $exceptions->render(function (QueryException $e, Request $request) {
            if ($request->is('api/*')) {
                $code = $e->errorInfo[1] ?? null;

                if ($code === 1451) {
                    return response()->json([
                        'message' => 'Нельзя удалить запись: на неё ссылаются другие записи',
                    ], 409);
                }
                if ($code === 1062) {
                    return response()->json(['message' => 'Запись с такими данными уже существует'], 422);
                }
                if ($code === 1452) {
                    return response()->json(['message' => 'Указана несуществующая связанная запись'], 422);
                }

                return response()->json(['message' => 'Внутренняя ошибка сервера'], 500);
            }
        });

        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            if ($request->is('api/*')) {
                $status = $e->getStatusCode();
                $message = $status === 429
                    ? 'Слишком много запросов'
                    : ($e->getMessage() ?: 'Ошибка запроса');

                return response()->json(['message' => $message], $status);
            }
        });

        $exceptions->render(function (Throwable $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'Внутренняя ошибка сервера'], 500);
            }
        });
    })->create();