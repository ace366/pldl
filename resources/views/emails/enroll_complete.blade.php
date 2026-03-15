<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <title>登録完了</title>
</head>
<body>
    <p>{{ $guardian_name }} 様</p>
    <p>登録完了</p>
    <p>下記よりLINE登録をお願いします</p>
    <p>
        <a href="{{ $line_url }}">{{ $line_url }}</a>
    </p>
    @if(!empty($line_img))
        <p><img src="{{ $line_img }}" alt="友だち追加" height="36"></p>
    @endif

    <p>Login ID</p>
    <p>{{ $child_code ?? '---' }}</p>
    <p>上記IDがサイトのログインIDになります。大切に保管してください。</p>

    <p>QRコードについて</p>
    <p>QRコードは出席の時に使用します。お子様が携帯電話をお持ちの場合は、本人もログインできるようにすることをおすすめします（任意）。</p>
    <p>まだ携帯電話をお持ちでないお子様には、QRコード付きのカードを配布しますので、会場の管理者へお伝えください。</p>

    <p>何かお困りごとがあれば、LINEからお気軽にご連絡ください。</p>

    <p>
        お知らせへ進む：
        <a href="{{ rtrim(config('app.url'), '/') }}/family/login">{{ rtrim(config('app.url'), '/') }}/family/notices</a>
    </p>
</body>
</html>
