<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Session\TokenMismatchException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * レポートしない例外（必要に応じて追加）
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * フラッシュしない入力（セキュリティ）
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * 例外処理の登録
     */
    public function register(): void
    {
        // 419（CSRF不一致）は専用ページで案内
        $this->renderable(function (TokenMismatchException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'セッションが切れました。画面を再読み込みしてからもう一度お試しください。',
                ], 419);
            }

            return response()->view('errors.419', [], 419);
        });
    }
}
