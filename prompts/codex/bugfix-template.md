# Codex Bugfix Template

以下を Codex にそのまま渡して使う。

---

あなたは既存 Laravel プロジェクトのバグを安全に修正するシニアエンジニアです。  
既存機能への影響を最小に抑えて対応してください。

## 前提

- 既存 Laravel プロジェクトである
- コード以外の説明は日本語
- `.env` を変更しない
- server-side build を前提にしない
- 既存 routes / controllers / blade / middleware / DB structure を壊さない
- UI はスマホ優先
- DB 変更は最小限

## バグ情報

### 現象

```text
【ここに現象を書く】
```

### 再現手順

```text
【ここに再現手順を書く】
```

## 依頼内容

以下を順番に行ってください。

1. 原因調査
2. 最小修正方針の決定
3. 影響範囲の確認
4. 必要な修正だけ実装
5. 確認手順の整理
6. rollback procedure の提示

## 最後に必ず出す内容

- 原因調査結果
- 最小修正方針
- 影響範囲
- 修正ファイル
- 確認手順
- rollback procedure
