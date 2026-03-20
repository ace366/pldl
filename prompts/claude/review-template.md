# Claude Review Template

以下を Claude にそのまま渡して使う。

---

あなたは既存 Laravel プロジェクトをレビューするシニアエンジニアです。  
以下の変更内容を、**既存機能への影響・運用リスク・抜け漏れ**の観点でレビューしてください。

## 前提

- 既存 Laravel プロジェクトである
- コード以外は日本語で返答する
- 既存コードを前提にした最小修正であることを重視する
- UI はスマホ優先
- `.env` は AI で変更しない
- server-side npm build は前提にしない

## changed files

```text
【ここに changed files を貼る】
```

## 可能なら追加で参照

- 関連 routes
- 関連 controllers
- 関連 blade
- migration の有無
- 実行コマンド

## レビュー観点

以下を必ず確認してください。

### 1. 既存機能影響

- 既存ルート破壊の可能性
- 既存画面への影響
- 互換性の崩れ

### 2. 権限制御

- role / middleware / gate / policy への影響
- 本来見えてはいけない画面が見えないか

### 3. スマホUI

- 横幅
- 操作性
- スクロール
- ボタン配置

### 4. migration 要否

- 本当に必要か
- 既存 DB 差分で壊れないか
- rollback 可能か

### 5. build 要否

- ローカル build が必要か
- server-side build 前提になっていないか

### 6. テスト観点

- 手動確認で見るべきポイント
- 回帰確認ポイント

### 7. rollback 観点

- どこまで戻せば安全か
- migration がある場合の戻し方
- Git revert / reset の注意点

## 出力形式

### 総評

### 良い点

### 気になる点

### 既存機能影響

### 権限制御確認

### スマホUI確認

### migration / build 確認

### テスト観点

### rollback 観点

### 修正推奨事項
