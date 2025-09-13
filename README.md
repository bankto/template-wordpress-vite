# Template (WordPress + Vite)

## 要件

- Node.js 20.x（LTS 推奨）
- Docker Desktop（@wordpress/env 用）

## 開発

```bash
# WP-ENV 起動 + プラグイン同期 + Vite 起動
npm run dev
```

- テーマ配下 `wp/` はそのまま本番へデプロイ可能

> アクセス先（ブラウザ）
>
> - WordPress: `http://localhost:8888/` ← 自動で開きます
> - Vite HMR: `http://localhost:5175/`（開発サーバ・HMR 用、直接アクセス不要）

## ビルド

```bash
npm run vite:build
```

- 出力先: `wp/`

## デプロイ

- `wp/` ディレクトリのみをサーバーにアップロード

## 画像・アセット方針

- 画像は `wp/images/` に統一（PHP/SCSS/JS から同一パスで参照）
- favicon などは PHP から:

```php
<link rel="icon" href="<?php echo esc_url( get_theme_file_uri('images/favicon.png') ); ?>">
```

## データベース操作（WP-ENV）

```bash
# 一括リフレッシュ（リセット + インポート + URL置換 + キャッシュ削除 + 再起動）
npm run db:refresh

# バックアップ
npm run db:backup

# 状態確認
npm run db:status
```

SQL ファイル名や URL は `tools/sql.config.json` を編集してください。

`tools/sql.config.json` の主なキー:

- `sql`: インポートする SQL の相対パス（例: `./sql/dump.sql`）
- `sourceUrl`: 本番（元サイト）の URL
- `localUrl`: ローカルの URL（通常 `http://localhost:8888`）

`npm run db:refresh` は以下を順に実行します: リセット → インポート → URL 置換 → キャッシュ削除 → 再起動

## プラグイン同期（WP-CLI）

```bash
# 必要プラグインのインストール/有効化
npm run plugins:sync
```

- 定義ファイル: `tools/plugins.config.json`
- 例: `hello-dolly` をバージョン固定で導入します

設定例（複数タイプに対応）

```json
{
  "plugins": [
    { "type": "repo", "slug": "hello-dolly", "version": "1.7.3", "activate": true },
    { "type": "local", "slug": "advanced-custom-fields-pro", "activate": true },
    { "type": "zip", "url": "${PRIVATE_ZIP_URL}", "activate": true }
  ]
}
```

備考:

- type=repo: WordPress 公式プラグイン。`slug` と `version` を指定
- type=local: リポジトリ同梱（例: `plugins/advanced-custom-fields-pro`）。有効化のみ
- type=zip: 外部 ZIP URL からインストール。URL は環境変数などで安全に供給

環境変数の設定方法:

- ルートに `.env`（または `tools/.env`）を作成し、以下のように記述
  ```ini
  PRIVATE_ZIP_URL=https://example.com/paid-plugin.zip
  ```

## スクリプト一覧

```bash
# 開発（WP 起動 → プラグイン同期 → Vite 起動）
npm run dev

# Vite 単体
npm run vite:dev
npm run vite:build

# WP-ENV 操作
npm run wp:start
npm run wp:stop

# DB 操作
npm run db:refresh
npm run db:backup
npm run db:status

# プラグイン同期
npm run plugins:sync
```

## トラブルシューティング

- wp-env が不安定/起動しない: Node 22 系では動作が不安定な場合があります。Node 20.x LTS を使用してください。
- 8888 にアクセスできない: Docker Desktop を起動し、`npm run wp:stop && npm run wp:start` を再実行
