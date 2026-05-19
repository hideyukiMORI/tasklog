# AGENTS.md — tasklog (Field Trial 11)

AI エージェントがこのリポジトリで作業を始めるためのエントリーポイント。

## このリポジトリの目的

NENE2 Field Trial 11 — JWT 認証フローの AI 実装可能性検証。

`composer require hideyukimori/nene2:^1.4` から JWT 認証付きタスク管理 API を構築し、
詰まった箇所をすべて摩擦点として記録する。

## まず読むべきもの

1. `CLAUDE.md` — ミッション・エンドポイント仕様・摩擦記録ルール
2. `../NENE2/docs/howto/add-second-entity.md` — ドメイン追加の手順
3. `../NENE2/docs/howto/add-database-endpoint.md` — DB 接続パターン
4. `../NENE2/src/Example/Note/` — 実装パターンの参照

## 作業の進め方

1. `composer install` で依存を解決する
2. User ドメインから実装を開始する（JWT 発行が先決）
3. Task ドメインを追加し、Bearer ミドルウェアで保護する
4. 詰まったら必ず `docs/field-trial-report.md` に記録してから進む
5. `composer check` 全通過を確認して PR を作成する

## ゴール

`composer check` 全通過 + 摩擦記録 + PR 作成
