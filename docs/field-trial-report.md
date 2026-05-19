# Field Trial 11 — tasklog: JWT 認証フロー実地検証

## Date

2026-05-19

## Baseline

- NENE2 v1.4.0（`hideyukimori/nene2: ^1.4`）
- PHP 8.4（Docker: `php:8.4-cli` ベースイメージ）
- プロジェクト: **tasklog** — タスク管理 JSON API
- エンティティ: `User` / `Task`（2ドメイン、7エンドポイント）
- テスト: PHPUnit・PHPStan level 8・PHP-CS-Fixer
- DB: SQLite（ローカル）

## Goal

JWT Bearer 認証フロー（ユーザー登録・ログイン・認証保護エンドポイント）が、
NENE2 のドキュメントだけを参照した Claude が迷わず実装できるかを検証する。

---

## Steps Taken

1. **NENE2 ソース & docs を読み込み**  
   `docs/adr/0008-jwt-authentication.md`、`src/Auth/`、`src/Example/Tag/` を参照し、設計パターンを把握。

2. **Auth ドメイン実装**  
   `User`, `UserRepositoryInterface`, `PdoUserRepository`, `TokenIssuerInterface`, `LocalJwtIssuer` を作成。  
   登録・ログインの UseCase/Handler/RouteRegistrar/ServiceProvider を NENE2 の Tag 例に倣って実装。

3. **Task ドメイン実装**  
   `Task`, `TaskRepositoryInterface`, `PdoTaskRepository`、7 エンドポイント分の UseCase/Handler/RouteRegistrar/ServiceProvider を実装。  
   `GET/PUT/DELETE /tasks/{id}` で `nene2.auth.claims['sub']` と `task.userId` を突き合わせて 403 を返す設計を採用。

4. **カスタムミドルウェア `TaskBearerAuthMiddleware` を新設**  
   `BearerTokenMiddleware` が exact-path マッチングのみをサポートするため（F-1 参照）、`/tasks` プレフィックスマッチ対応のミドルウェアを自前で実装。

5. **アプリケーションブートストラップ**  
   `RuntimeApplicationFactory` を使わず、`AppServiceProvider` / `AppContainerFactory` を独自実装。  
   NENE2 の個別ミドルウェアコンポーネントを組み合わせてパイプラインを構築（F-2 参照）。

6. **`composer check` 全通過後、動作確認**  
   DB 初期化 → サーバー起動 → 全 7 エンドポイント手動テストで動作を確認。

---

## Findings

### F-1: `BearerTokenMiddleware` の protected パスがダイナミックルートに非対応 [高]

**状況**: `/tasks/{id}` のような動的パスを `BearerTokenMiddleware` で保護しようとした。

**問題**: `BearerTokenMiddleware` の `$protectedPaths` は exact-path マッチング（`in_array`）のみ。  
`/tasks/1`, `/tasks/2` などを列挙することは不可能。一方で `$protectedPaths = []`（空配列 = 全パス保護）では `/auth/register` や `/auth/login` も保護されてしまう。

**解決**: `BearerTokenMiddleware` を композせず、`str_starts_with($path, '/tasks')` でプレフィックスマッチを行う独自ミドルウェア `TaskBearerAuthMiddleware` を実装した。

**提案**: `BearerTokenMiddleware` に `$excludedPaths` （ブラックリスト方式）、または `$pathPrefixes` （プレフィックスマッチ）オプションを追加する。  
あるいは how-to ドキュメントで「カスタムミドルウェアを作成して prefix マッチを行う」パターンを明示する。

---

### F-2: JWT 認証付きアプリでの `RuntimeApplicationFactory` 使用不可 [中]

**状況**: `RuntimeApplicationFactory` を使ってミドルウェアパイプラインを組み立てようとした。

**問題**: `RuntimeApplicationFactory` のコンストラクタが受け付けるのは `?BearerTokenMiddleware` のみ（型指定）。  
F-1 で必要になったカスタムミドルウェア（`MiddlewareInterface` 実装）を渡せない。`BearerTokenMiddleware` は `final` クラスなので継承も不可。

**解決**: `RuntimeApplicationFactory` を使用せず、NENE2 の個別ミドルウェアコンポーネント（`RequestIdMiddleware` 等）を直接組み合わせて `AppServiceProvider` にフルパイプラインを実装した。  
参考: `RuntimeApplicationFactory::create()` の内部実装をテンプレートとして流用。

**提案**: `RuntimeApplicationFactory` のコンストラクタ引数を `?MiddlewareInterface $authMiddleware` に緩める（後方互換あり）。  
または how-to に「認証ミドルウェアをカスタマイズする場合は RuntimeApplicationFactory の代わりに MiddlewareDispatcher を直接使う」ガイドを追加する。

---

### F-3: JWT 発行 API が安定インターフェースとして未公開 [中]

**状況**: ログイン UseCase で JWT を発行する必要があった。

**問題**: `TokenVerifierInterface`（検証用）は公開 API として安定保証されているが、JWT 発行メソッドは `LocalBearerTokenVerifier::issue()` として `@internal` クラスに存在するのみ。  
ADR 0008 には "Ship no concrete JWT verifier in the framework core" とあるが、アプリ側で発行する公開インターフェースも提供されていない。

**解決**: アプリ内で `TokenIssuerInterface` を独自定義し、`LocalJwtIssuer` が `LocalBearerTokenVerifier::issue()` をラップする構成を採用。`@internal` クラスへの依存を隔離した。

**提案**: `TokenIssuerInterface`（または `TokenVerifierInterface` に `issue()` を追加した `TokenServiceInterface`）を公開 API に追加する。  
または how-to に `LocalBearerTokenVerifier::issue()` の使用方法と、本番向けの差し替え方針を記載する。

---

### F-4: インストール済みパッケージと開発ソースの乖離 [低]

**状況**: `../NENE2/src/` を参照して `PaginationResponse`、`ErrorHandlerMiddleware` の `$debug` 引数を使ったコードを書いた。

**問題**: インストール済み v1.4.0 には `PaginationResponse` が存在せず、`ErrorHandlerMiddleware` は 2 引数のみ。  
開発ソース（`../NENE2/`）はリリース前の HEAD のため、公開パッケージと差異がある。PHPStan レベル 8 を実行するまで気づかなかった。

**解決**: PHPStan のエラーを見てインストール済みパッケージの実際の API に合わせてコードを修正。

**提案**: CLAUDE.md の「NENE2 参照先」セクションに「`../NENE2/` は開発版。実際にインストールされる API は `vendor/` を確認すること」の注記を追加する。

---

## Test Results

```
composer check 全通過

PHPUnit:         12/12 tests passed
PHPStan level 8: No errors (61 files)
PHP-CS-Fixer:    0 of 63 files need fixing
```

---

## Friction Summary

| # | 内容 | 深刻度 | 種別 |
|---|---|---|---|
| F-1 | `BearerTokenMiddleware` がダイナミックルートに非対応（exact-path のみ） | 高 | ミドルウェア設計 |
| F-2 | `RuntimeApplicationFactory` にカスタムミドルウェアを渡せない | 中 | 拡張性 |
| F-3 | JWT 発行 API が安定インターフェース未定義 | 中 | API 設計 |
| F-4 | 開発ソースと公開パッケージの API 乖離 | 低 | ドキュメント |

---

## Recommendations

1. **`BearerTokenMiddleware` にプレフィックスマッチ or 除外パスオプションを追加**（F-1 対応、最優先）
2. **`RuntimeApplicationFactory` の auth 引数型を `MiddlewareInterface` に緩める**（F-2 対応）
3. **`TokenIssuerInterface` を公開 API に追加**し、how-to に JWT 発行フローを文書化（F-3 対応）
4. **how-to に JWT 認証フロー実装ガイドを追加**：登録・ログイン・保護エンドポイントの最小構成例

---

## Overall Impression

NENE2 の基本設計（PSR-15 パイプライン、ServiceProvider、UseCase/Handler 分離、ValidationException → 422 自動マッピング）は一貫していて、Tag/Note のサンプルコードを参照すれば Task ドメインの実装は迷わず進められた。

JWT 認証フロー固有の課題は主に「`BearerTokenMiddleware` のパスマッチング方式」と「RuntimeApplicationFactory の柔軟性」の2点。どちらも深刻ではなく回避策があるが、how-to がなければ初見の実装者は同じところで躓く可能性が高い。`add-jwt-authentication.md` how-to の追加が最も効果的な改善策と考える。
