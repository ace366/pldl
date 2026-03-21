# Date

2026-03-21

# Issue

- Issue URL:
- Issue Number:

# Title

管理者向け源泉税テーブル取込の 2026 `.xls` 固定対応と範囲異常値対策

# Branch

- branch name: `main`

# 目的

- 2026年の NTA 公式 `01-07.xls` を URL/ファイル指定のどちらでも取り込めるようにする
- 既存の `csv/xlsx` フローを維持する
- `max_amount` に異常値が入る再発を防ぐ

# 背景

- 既存 UI は `.xls` を選べるが、Controller 側では未対応でエラー化していた
- `xlsx` のマッピング変換も、ヘッダ行や注記行を税額表行として誤解釈し、`max_amount` に異常値が混入する余地があった
- ワークツリーに未コミット差分が多数あったため、今回の checkpoint commit は既存作業を巻き込むリスクがあり未実施

# Claude分析

- routes: `admin/payroll/withholding/import*` の admin 専用経路で閉じている
- controllers: `WithholdingTaxImportController` が file/url/auto の入口を担当
- blade: `withholding_import.blade.php` が URL/ファイル/auto とマッピング UI を持つ
- middleware / roles: `EnsureAdmin` により admin のみ
- database relations: `withholding_tax_tables` は lookup 用テーブルで relation なし。`PayrollPayment` は計算結果保存のみ
- リスク: `.xls` はバイナリ Excel のため汎用パーサなしでは直接解釈できない
- 技術負債: UI と実装の `.xls` 対応状況が不一致、変換ロジックがヒューリスティック依存

# Codex実装指示

- 実装方針: 2026年の公式 `01-07.xls` はハッシュで同一性確認し、正規化済み CSV fixture に変換して取り込む
- 最小変更方針: 既存 `csv/xlsx` 経路は保持しつつ、`.xls` は固定対応のみ追加
- 変更対象: Controller、SpreadsheetConverter、TaxImporter、取込画面文言、テスト
- migration 要否: なし

# 変更ファイル

- changed files:
  - `app/Http/Controllers/Admin/WithholdingTaxImportController.php`
  - `app/Services/Payroll/WithholdingSpreadsheetConverter.php`
  - `app/Services/Payroll/WithholdingTaxImporter.php`
  - `resources/views/admin/payroll/withholding_import.blade.php`
- new files:
  - `resources/withholding/official_2026_01-07.csv`
  - `tests/Feature/WithholdingSpreadsheetConverterTest.php`
  - `tests/Fixtures/withholding/official_2026_01-07.xls`
  - `tests/Fixtures/withholding/official_2026_01-07.xlsx`

# DB変更

- なし
- migration 名:
- 影響テーブル: `withholding_tax_tables`

# ローカル実行コマンド

```bash
php artisan test tests/Feature/WithholdingSpreadsheetConverterTest.php
php artisan test tests/Feature/WithholdingTaxImportCommandTest.php
php artisan test tests/Unit/WithholdingTaxCalculatorTest.php
```

# サーバー実行コマンド

```bash
php artisan optimize:clear
php artisan test tests/Feature/WithholdingSpreadsheetConverterTest.php
```

# 手動確認

1. 管理者で `/admin/payroll/withholding/import?year=2026` を開く
2. URL欄に `https://www.nta.go.jp/publication/pamph/gensen/zeigakuhyo2026/data/01-07.xls` を入れて取り込む
3. 2026年データが成功表示になり、エラーなく取り込まれることを確認する
4. 同画面で `csv/xlsx` 取込も従来どおり動くことを確認する

# 補足

- 2026 `.xls` は公式配布物のハッシュ一致時のみ固定対応する
- `max_amount` の上限を一律 740000 で切る処理は入れていない
- rollback は対象ファイルを戻し、追加 fixture と AI ログを削除する

# チェックリスト

- [x] 影響範囲を確認した
- [ ] Git checkpoint を作成した
- [x] changed files / new files を整理した
- [x] migration 要否を明記した
- [x] ローカル確認手順を記載した
- [x] サーバー反映手順を記載した
- [x] rollback 手順を確認した
- [x] GitHub に残せる内容になっている
