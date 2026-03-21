# AI作業ログ: 保護者カレンダー一括更新の誤操作防止

## 日付

- 2026-03-21

## checkpoint

- Git tag: `ai-checkpoint-20260321-family-availability-bulk-safety`
- Base commit: `976c1b7`

## 依頼概要

- 保護者側の送迎 / 参加予定カレンダー入力画面で、個別に日付を選んだあとに誤って一括更新を押してしまう問題を減らす
- 一括操作は残しつつ、スマホで誤爆しにくい UI にする
- 一括更新前に確認を挟み、危険操作として一段深くする

## 実装方針

- 一括操作パネルをスマホでは初期状態で閉じる
- 一括操作は折りたたみ式にし、使うときだけ開く形にする
- 現在の一括設定をサマリー表示し、更新前に内容を認識しやすくする
- `更新` ボタンは即実行せず、確認ダイアログを経由して実行する
- `family.availability.bulk_on` route に family 認証 middleware を明示する

## 変更ファイル

- `resources/js/pages/family/AvailabilityPage.jsx`
- `routes/web.php`

## 確認コマンド

- `php artisan route:list --name=family.availability`
- `npm run build`

## 確認メモ

- 個別日付トグルと本日の解除理由モーダルは既存のまま維持
- 一括操作は削除せず、スマホでは「開く」操作を1回増やして誤爆を減らす
- 一括更新の対象範囲と操作内容は確認ダイアログに表示する
