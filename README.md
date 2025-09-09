# bankto (WordPress + Vite)

## 開発

```bash
# WP-ENV 起動 + Vite 起動
npm run dev
```

- テーマ配下 `wp/` はそのまま本番へデプロイ可能

> アクセス先（ブラウザ）
>
> - WordPress: `http://localhost:8888/` ← ここを開く
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

SQL ファイル名や URL は `sql-tools/sql.config.json` を編集してください。
