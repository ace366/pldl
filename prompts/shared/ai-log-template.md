# AI Log Template

以下を `docs/ai-logs/` に保存して使う。

````md
# Date

YYYY-MM-DD

# Issue

- Issue URL:
- Issue Number:

# Title

作業タイトルを記載

# Branch

- branch name:

# 目的

- 今回の作業で達成したいこと

# 背景

- なぜ必要か
- 既存運用や既存仕様との関係

# Claude分析

- routes:
- controllers:
- blade:
- middleware / roles:
- database relations:
- リスク:
- 技術負債:

# Codex実装指示

- 実装方針
- 最小変更方針
- 変更対象
- migration 要否

# 変更ファイル

- changed files:
- new files:

# DB変更

- あり / なし
- migration 名:
- 影響テーブル:

# ローカル実行コマンド

```bash
# 実行したコマンドを書く
```

# サーバー実行コマンド

```bash
# 本番反映時のコマンドを書く
```

# 手動確認

1. 
2. 
3. 

# 補足

- 引き継ぎ事項
- 保留事項
- rollback の注意点

# チェックリスト

- [ ] 影響範囲を確認した
- [ ] Git checkpoint を作成した
- [ ] changed files / new files を整理した
- [ ] migration 要否を明記した
- [ ] ローカル確認手順を記載した
- [ ] サーバー反映手順を記載した
- [ ] rollback 手順を確認した
- [ ] GitHub に残せる内容になっている
````
