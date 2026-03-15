<!doctype html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>セッション期限切れ</title>
    <style>
        body { margin: 0; font-family: "Hiragino Kaku Gothic ProN", "Yu Gothic", Meiryo, sans-serif; background: #f8fafc; color: #0f172a; }
        .wrap { min-height: 100vh; display: grid; place-items: center; padding: 24px; }
        .card { width: 100%; max-width: 720px; background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 28px; box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08); }
        .code { display: inline-block; font-size: 12px; font-weight: 700; color: #0369a1; background: #e0f2fe; border-radius: 999px; padding: 6px 10px; }
        h1 { margin: 12px 0 10px; font-size: 24px; }
        p { margin: 0 0 12px; line-height: 1.7; color: #334155; }
        ul { margin: 10px 0 18px 20px; color: #334155; }
        li { margin-bottom: 6px; }
        .actions { display: flex; flex-wrap: wrap; gap: 10px; }
        .btn { border: 1px solid #cbd5e1; background: #fff; color: #0f172a; border-radius: 10px; padding: 10px 14px; font-size: 14px; cursor: pointer; text-decoration: none; }
        .btn.primary { background: #0f172a; border-color: #0f172a; color: #fff; }
    </style>
</head>
<body>
<main class="wrap">
    <section class="card">
        <span class="code">HTTP 419</span>
        <h1>操作の有効期限が切れました</h1>
        <p>しばらく操作がなかった場合や、複数タブ操作などで安全確認情報が一致しないと、この画面が表示されます。</p>
        <ul>
            <li>まず「再読み込み」を押してから、もう一度操作してください。</li>
            <li>改善しない場合は、前の画面に戻って再入力してください。</li>
            <li>それでも解消しない場合は、一度ログインし直してください。</li>
        </ul>
        <div class="actions">
            <button class="btn primary" type="button" onclick="location.reload()">再読み込み</button>
            <button class="btn" type="button" onclick="history.back()">前の画面へ戻る</button>
            <a class="btn" href="{{ route('login', [], false) }}">ログインへ</a>
            <a class="btn" href="{{ url('/') }}">トップへ</a>
        </div>
    </section>
</main>
</body>
</html>
