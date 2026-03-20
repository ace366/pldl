# AI作業ログ: 管理画面お知らせ編集の本番URL不具合修正

## 日付

- 2026-03-20

## checkpoint

- Git tag: `ai-checkpoint-20260320-notice-fix`
- Base commit: `d79b728`

## 依頼概要

- 本番環境で管理者向けお知らせ編集画面 `/admin/notices/edit` の保存処理が `Not Found` になる
- 画像アップロード時点でも失敗する
- ローカルでは再現しない

## 原因見立て

- お知らせ編集画面では、保存先と画像アップロード先に `route(..., false)` を使っていた
- この形式はルート相対URLになるため、サブディレクトリ配備時にベースパスが落ちる
- 本番では `/admin/notices` に送られて 404 になり、`/pldl/public` などの実際の配備パスを含められていない可能性が高い

## 今回の対応

- 保存フォーム action を、現在のリクエストルートを基準にしたURLへ変更
- 画像アップロード先URLも、現在のリクエストルートを基準にしたURLへ変更
- 画像アップロード成功時に返すURLも、現在のリクエストルート基準で返すように変更

## 変更ファイル

- `resources/views/admin/notices/edit.blade.php`
- `app/Http/Controllers/Admin/NoticeController.php`

## 確認観点

- 本番で保存時に `/admin/notices` ではなく正しい配備パス付きURLへ送信されるか
- 本番で画像アップロードが成功するか
- 返却された画像URLで、管理画面プレビューと公開側表示が崩れないか
- ローカル環境でも従来どおり保存・画像登録ができるか
