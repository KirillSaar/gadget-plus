<?php

use App\Exceptions\BusinessException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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

        // Журналирование всех запросов к API
        $middleware->api(append: [
            \App\Http\Middleware\LogApiRequests::class,
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

        // Нарушение бизнес-правил: 409 (или код, указанный в исключении)
        $exceptions->dontReport(BusinessException::class);

        $exceptions->render(function (BusinessException $e, Request $request) {
            if ($request->is('api/*')) {
                Log::warning('Нарушено бизнес-правило', [
                    'message' => $e->getMessage(),
                    'user_id' => $request->user()?->id,
                    'method' => $request->method(),
                    'path' => $request->path(),
                ]);

                return response()->json(['message' => $e->getMessage()], $e->status());
            }
        });

        // 401 — не авторизован
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'Требуется авторизация'], 401);
            }
        });

        // 403 — нет доступа
        $exceptions->render(function (AccessDeniedHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'Доступ запрещён'], 403);
            }
        });

        // 404 — запись или маршрут не найдены
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

        // 405 — неверный HTTP-метод
        $exceptions->render(function (MethodNotAllowedHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'Метод не поддерживается для этого адреса'], 405);
            }
        });

        // Ошибки БД (MySQL и SQLite для тестов): 409, 422, иначе 500
        $exceptions->render(function (QueryException $e, Request $request) {
            if ($request->is('api/*')) {
                $code = $e->errorInfo[1] ?? null;
                $text = $e->getMessage();
                $isForeignKey = str_contains($text, 'FOREIGN KEY constraint failed');
                $isUnique = str_contains($text, 'UNIQUE constraint failed');

                if ($code === 1451 || ($isForeignKey && $request->isMethod('DELETE'))) {
                    return response()->json([
                        'message' => 'Нельзя удалить запись: на неё ссылаются другие записи',
                    ], 409);
                }
                if ($code === 1062 || $isUnique) {
                    return response()->json(['message' => 'Запись с такими данными уже существует'], 422);
                }
                if ($code === 1452 || $isForeignKey) {
                    return response()->json(['message' => 'Указана несуществующая связанная запись'], 422);
                }

                return response()->json(['message' => 'Внутренняя ошибка сервера'], 500);
            }
        });

        // Прочие HTTP-ошибки (429 и т.д.)
        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            if ($request->is('api/*')) {
                $status = $e->getStatusCode();
                $message = $status === 429
                    ? 'Слишком много запросов'
                    : ($e->getMessage() ?: 'Ошибка запроса');

                return response()->json(['message' => $message], $status);
            }
        });

        // 500 — всё непредвиденное, без стека и SQL (подробности попадают в журнал)
        $exceptions->render(function (Throwable $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'Внутренняя ошибка сервера'], 500);
            }
        });
    })->create();
