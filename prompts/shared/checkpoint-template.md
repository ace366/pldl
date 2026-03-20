# Checkpoint Template

AI 作業前の Git 安全運用テンプレート。

## 目的

AI が既存 Laravel プロジェクトを触る前に、戻れる地点を必ず作る。  
問題発生時に慌てて履歴を追わなくて済むように、作業開始時点を commit として残す。

## 基本手順

```bash
git status
git add -A
git commit -m "checkpoint before AI modification"
git status
git diff --stat HEAD~1..HEAD
```

## 差分確認

- `git status` で未整理の差分がないか確認する
- 意図しないファイルが含まれていないか確認する
- 作業開始前の状態が checkpoint に残ったことを確認する

## rollback の基本

### 直前の AI 作業をなかったことにしたい場合

```bash
git reset --hard HEAD~1
```

使いどころ:

- ローカルだけでやり直したい
- まだ push していない
- 履歴を残さなくてよい

### 履歴を残して打ち消したい場合

```bash
git revert HEAD
```

使いどころ:

- すでに共有済み
- GitHub に push 済み
- 取り消し履歴も残したい

## revert と reset の使い分け

### `git revert`

- 共有済み履歴向け
- 打ち消し commit を積む
- 安全性が高い

### `git reset --hard`

- 未共有のローカル作業向け
- 履歴ごと巻き戻す
- 強力だが危険

## 実務上の注意

- AI が大きな作業を始める前に毎回 checkpoint を作る
- 途中で方針転換するときも checkpoint を切る
- migration を含む作業は特に checkpoint を必須にする
- rollback 手順は AI ログにも残す
