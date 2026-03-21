# AI作業ログ: スマホ下メニューのシフト登録導線追加

## 日付

- 2026-03-20

## checkpoint

- Git tag: `ai-checkpoint-20260320-shift-react-nav`
- Base commit: `976c1b7`

## 依頼概要

- スマホ下の共通メニューからシフト登録へ直接入れるようにする
- `public/images/icons8.png` を使ったシフト登録アイコンを追加する
- 遷移先は既存 Blade とは別に、React ベースのシフト作成画面を新設する
- 一括登録まわりはスマホで分かりやすく、説明文を減らしたUIに寄せる

## 実装方針

- 共通メニューは role 固定ではなく `shift_day:create` 権限ベースで導線を表示する
- 既存 `admin/shifts/create` は残しつつ、新規に `admin/shifts/create/react` を追加する
- 保存先は既存 `admin.shifts.store` を再利用し、既存の単日登録・一括登録・重複確認ロジックに載せる
- React mount 用 Blade と `resources/js/app.jsx` の既存パターンを踏襲する

## 変更ファイル

- `routes/web.php`
- `app/Http/Controllers/Admin/ShiftController.php`
- `resources/views/layouts/navigation.blade.php`
- `resources/views/admin/shifts/react.blade.php`
- `resources/js/app.jsx`
- `resources/js/pages/admin/ShiftCreatePage.jsx`

## 確認コマンド

- `php -l app/Http/Controllers/Admin/ShiftController.php`
- `php artisan route:list --name=admin.shifts`
- `php artisan view:cache`
- `npm run build`

## 確認メモ

- 新規 route `admin.shifts.create.react` が追加されている
- スマホ下メニューでは family を除く非 family 画面に対して、権限がある場合のみシフト登録アイコンを表示する
- Vite build 実行時に Node 18 系への警告は出たが、build 自体は成功した
