# CLAUDE.md — tasklog

**Field Trial 11** — NENE2 v1.4 JWT 認証フロー実地検証プロジェクト。

このリポジトリは `composer require hideyukimori/nene2:^1.4` を起点とし、
JWT Bearer 認証付きタスク管理 API を 0 から構築する。

---

## ミッション

> JWT Bearer 認証フロー（ユーザー登録・ログイン・認証保護エンドポイント）は、
> NENE2 のドキュメントだけを見た Claude が迷わず実装できるか。

詰まった箇所・ドキュメントのギャップ・設計で迷った判断を **すべて記録する**。
記録は摩擦点（F-N）として `docs/field-trial-report.md` に蓄積すること。

---

## 実装するもの

### ドメイン

| ドメイン | 概要 |
|---|---|
| User | 登録・ログイン・JWT 発行 |
| Task | ユーザー所有タスクの CRUD + ページネーション |

### エンドポイント

| Method | Path | 認証 | 説明 |
|---|---|---|---|
| POST | /auth/register | 不要 | ユーザー登録 |
| POST | /auth/login | 不要 | ログイン → JWT 返却 |
| GET | /tasks | Bearer | 自分のタスク一覧（ページネーション） |
| POST | /tasks | Bearer | タスク作成 |
| GET | /tasks/{id} | Bearer | タスク取得（他人のは 403） |
| PUT | /tasks/{id} | Bearer | タスク更新（他人のは 403） |
| DELETE | /tasks/{id} | Bearer | タスク削除（他人のは 403） |

---

## 開発コマンド

```bash
# 依存インストール（Docker 経由）
docker compose run --rm app composer install

# 全チェック
docker compose run --rm app composer check

# 個別
docker compose run --rm app composer test
docker compose run --rm app composer analyse
docker compose run --rm app composer cs:fix

# DB 初期化
docker compose run --rm app php database/init.php

# 開発サーバー
docker compose up app
```

---

## NENE2 参照先

```
../NENE2/                        ← NENE2 フレームワーク本体
../NENE2/docs/howto/             ← How-to ガイド（必読）
../NENE2/docs/adr/               ← 設計決定記録
../NENE2/src/Example/Note/       ← Note 実装（パターン参照）
../NENE2/src/Example/Tag/        ← Tag 実装（パターン参照）
../NENE2/src/Http/               ← PaginationQueryParser 等
../NENE2/src/Middleware/         ← BearerAuthMiddleware 等
```

---

## 摩擦記録のルール

詰まったこと・迷ったことは **その場で** `docs/field-trial-report.md` に追記する。

```markdown
### F-N: タイトル

**状況**: 何をしようとしていたか
**問題**: 何が分からなかったか / 何で詰まったか
**解決**: どう解決したか（またはどこを読んで解決したか）
**提案**: NENE2 側でこうなっていれば詰まらなかった
```

深刻度タグ: `[高]` `[中]` `[低]`

---

## 完了条件

- [ ] `composer check` 全通過（PHPUnit・PHPStan level 8・PHP-CS-Fixer）
- [ ] 認証なし → 401、他ユーザーリソース → 403 の動作確認
- [ ] `docs/field-trial-report.md` に摩擦記録あり
- [ ] GitHub PR を作成する（宛先: `hideyukiMORI/tasklog` `main` ブランチ）

---

## コーディング規約

NENE2 の PHP コーディング規約に従う（`../NENE2/CLAUDE.md` セクション 3 参照）。

- `declare(strict_types=1)` 必須
- PSR-12 準拠
- UseCase / Domain は HTTP・DB から独立
- エラーは RFC 9457 Problem Details
- `ValidationException` → 422 自動マッピング
