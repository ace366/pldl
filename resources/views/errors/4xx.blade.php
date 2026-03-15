<!doctype html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ページを表示できません</title>
    <style>
        body { margin: 0; font-family: "Hiragino Kaku Gothic ProN", "Yu Gothic", Meiryo, sans-serif; background: #f8fafc; color: #0f172a; }
        .wrap { min-height: 100vh; display: grid; place-items: center; padding: 24px; }
        .card { width: 100%; max-width: 720px; background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 28px; box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08); }
        .code { display: inline-block; font-size: 12px; font-weight: 700; color: #334155; background: #e2e8f0; border-radius: 999px; padding: 6px 10px; }
        h1 { margin: 12px 0 10px; font-size: 24px; }
        p { margin: 0 0 12px; line-height: 1.7; color: #334155; }
        .actions { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 14px; }
        .btn { border: 1px solid #cbd5e1; background: #fff; color: #0f172a; border-radius: 10px; padding: 10px 14px; font-size: 14px; cursor: pointer; text-decoration: none; }
        .btn.primary { background: #0f172a; border-color: #0f172a; color: #fff; }
    </style>
</head>
<body>
<main class="wrap">
    <section class="card">
        <span class="code">4xx Error</span>
        <h1>ページを表示できませんでした</h1>
        <p>アクセス方法が正しくないか、ページの有効期限が切れている可能性があります。</p>
        <p>時間をおいて再度お試しいただくか、トップページから操作をやり直してください。</p>
        <div class="actions">
            <button class="btn primary" type="button" onclick="location.reload()">再読み込み</button>
            <button class="btn" type="button" onclick="history.back()">前の画面へ戻る</button>
            <a class="btn" href="{{ url('/') }}">トップへ</a>
        </div>
    </section>
</main>
</body>
</html>
