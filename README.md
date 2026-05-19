# tasklog

**NENE2 Field Trial 11** — JWT Bearer 認証フロー検証プロジェクト。

NENE2 v1.4 を使って JWT 認証付きタスク管理 JSON API を 0 から構築し、
認証フロー実装の摩擦点を記録する。

## エンドポイント

| Method | Path | 認証 | 説明 |
|---|---|---|---|
| POST | `/auth/register` | 不要 | ユーザー登録 |
| POST | `/auth/login` | 不要 | ログイン → JWT 返却 |
| GET | `/tasks` | Bearer | 自分のタスク一覧（ページネーション） |
| POST | `/tasks` | Bearer | タスク作成 |
| GET | `/tasks/{id}` | Bearer | タスク取得（他人のは 403） |
| PUT | `/tasks/{id}` | Bearer | タスク更新（他人のは 403） |
| DELETE | `/tasks/{id}` | Bearer | タスク削除（他人のは 403） |

## セットアップ

```bash
# 依存インストール
docker compose run --rm app composer install

# DB 初期化
docker compose run --rm app php database/init.php

# 開発サーバー起動（ポート 8080）
docker compose up app
```

## 開発コマンド

```bash
# 全チェック（PHPUnit / PHPStan level 8 / PHP-CS-Fixer）
docker compose run --rm app composer check

# 個別
docker compose run --rm app composer test
docker compose run --rm app composer analyse
docker compose run --rm app composer cs:fix
```

## 使用例

```bash
# ユーザー登録
curl -X POST http://localhost:8080/auth/register \
  -H "Content-Type: application/json" \
  -d '{"email":"alice@example.com","password":"password123"}'

# ログイン（トークン取得）
TOKEN=$(curl -s -X POST http://localhost:8080/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"alice@example.com","password":"password123"}' \
  | python3 -c "import sys,json; print(json.load(sys.stdin)['access_token'])")

# タスク作成
curl -X POST http://localhost:8080/tasks \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"title":"やること","description":"メモ"}'

# タスク一覧
curl http://localhost:8080/tasks -H "Authorization: Bearer $TOKEN"
```

## Field Trial レポート

`docs/field-trial-report.md` に実装中の摩擦点（F-1〜F-4）を記録。

主な知見:
- `BearerTokenMiddleware` は exact-path マッチングのみ対応（ダイナミックルート非対応）
- `RuntimeApplicationFactory` にカスタムミドルウェアを渡せない
- JWT 発行 API が安定インターフェースとして未定義
