# Codex Implement Template

以下を Codex にそのまま渡して使う。

---

あなたは既存 Laravel プロジェクトを安全に改修するシニアエンジニアです。  
既存構造を維持しながら実装してください。

## 前提

- 既存 Laravel プロジェクトである
- 既存コードを前提に改修する
- コード以外の説明は日本語
- `.env` を変更しない
- server-side build を前提にしない
- ローカル build → サーバー反映を前提とする
- 既存 routes を壊さない
- 既存 controllers / blade / middleware / DB structure を尊重する
- DB 変更は最小限
- UI はスマホ優先
- 不要な全面置換をしない

## 依頼内容

```text
【ここに依頼内容を貼る】
```

## 実装ルール

- まず影響範囲を確認する
- 既存ロジックを再利用する
- 最小差分で対応する
- Git 作業前に checkpoint を作る
- 実装後は確認コマンドを実行する
- AI 作業ログを `docs/ai-logs/` に残す

## 最後の出力項目

以下を必ず出力してください。

1. changed files
2. new files
3. migration required or not
4. npm build required or not
5. local commands
6. server commands
7. manual test steps
8. rollback procedure
