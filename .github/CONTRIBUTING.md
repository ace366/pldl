# 26yorii 開発ルール

## 基本
1. Issueを作成する
2. Issueごとにブランチを切る
3. Pull Requestでmainにマージ

## ブランチ命名

feature/{issue番号}-{内容}
fix/{issue番号}-{内容}

例

feature/120-questionnaire
fix/140-login-error

## コミット

feat: 機能追加  
fix: バグ修正  
refactor: リファクタ  
chore: その他  

## PR

PRには必ず関連Issueを書く

Fixes #123
