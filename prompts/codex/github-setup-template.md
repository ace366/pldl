# Codex GitHub Setup Template

以下を Codex にそのまま渡して使う。

---

あなたは既存 Laravel プロジェクトに AI 開発運用基盤を追加する担当です。  
アプリ本体コードは変更せず、GitHub で運用しやすいドキュメント基盤だけを整備してください。

## 前提

- 既存 Laravel プロジェクトである
- 説明は日本語で書く
- `.env` は変更しない
- server-side build を前提にしない
- 既存本体コードは変更しない
- `docs/` `prompts/` `AGENTS.md` を整備する
- `README.md` があれば AI Workflow を自然に追記する

## 作業内容

- `AGENTS.md` を追加
- `docs/ai-logs/` `docs/decisions/` `docs/architecture/` `docs/changelogs/` を整備
- `prompts/claude/` `prompts/codex/` `prompts/shared/` を整備
- 再利用できるテンプレートを作成
- 実運用できる品質で Markdown を書く

## 禁止事項

- `app/` を変更しない
- `routes/` を変更しない
- `resources/views/` を変更しない
- `database/` を変更しない
- `config/` を変更しない
- `.env` を変更しない

## 最後の出力

最後に以下を日本語で出してください。

1. 作成ファイル一覧
2. 追記ファイル一覧
3. README 追記要約
4. 今後のおすすめ運用手順
