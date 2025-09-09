// Vite 基本設定（WordPress テーマで利用）
import { defineConfig } from 'vite'
import path from 'path'
import liveReload from 'vite-plugin-live-reload'
import { spawn } from 'node:child_process'

export default defineConfig(({ command }) => {
  // 環境フラグ
  const isDev = command === 'serve'
  // 入力と出力のルート
  const rootDir = path.resolve(__dirname, 'src')
  const outDir = path.resolve(__dirname, 'wp')
  // WordPress ローカルURL（wp-env の規定ポート）
  const wpUrl = 'http://localhost:8888/'

  return {
    // 本番ビルド時は相対パスで参照（テーマ直下出力のため）
    base: isDev ? '/' : './',
    root: rootDir,

    // PHP テンプレート更新時にブラウザをリロード + 起動案内を出力
    plugins: [
      liveReload(['wp/**/*.php'], { root: __dirname, alwaysReload: true }),
      {
        name: 'auto-open-wp-url',
        configureServer(server) {
          server.httpServer?.once('listening', () => {
            // Vite 起動時に WP サイトを自動で開く
            try {
              const url = wpUrl
              if (process.platform === 'win32') {
                // 空タイトル("")を挟むのが start のお作法
                spawn('cmd', ['/c', 'start', '', url], {
                  stdio: 'ignore',
                  shell: false,
                  windowsHide: true,
                })
              } else if (process.platform === 'darwin') {
                spawn('open', [url], { stdio: 'ignore', detached: true })
              } else {
                spawn('xdg-open', [url], { stdio: 'ignore', detached: true })
              }
            } catch {
              // 失敗しても起動は継続（ログのみ）
              // eslint-disable-next-line no-console
              console.warn('[vite] auto-open failed.')
            }
          })
        },
      },
    ],

    // 依存最適化キャッシュの保存先
    optimizeDeps: {
      cacheDir: '.vite_opt_cache',
    },

    build: {
      outDir,
      emptyOutDir: false, // テーマ直下を掃除しない（WP ファイルを維持）
      manifest: true, // 本番は manifest.json を PHP から参照
      cssCodeSplit: true,
      rollupOptions: {
        // エントリポイント（JS側から SCSS を取り込む）
        input: {
          script: path.resolve(rootDir, 'assets/scripts/app.js'),
        },
        // テーマの出力構成に合わせたファイル名
        output: {
          entryFileNames: ({ name }) =>
            name === 'script' ? 'scripts/script.js' : 'scripts/[name].js',
          chunkFileNames: 'scripts/[name].js',
          assetFileNames: ({ name }) => {
            const ext = name?.split('.').pop() || ''
            if (ext === 'css') return 'stylesheets/style.css'
            if (/woff2?|ttf|otf|eot/i.test(ext)) return 'fonts/[name][extname]'
            return `${ext}/[name][extname]`
          },
        },
      },
      chunkSizeWarningLimit: 1000,
      sourcemap: isDev ? 'inline' : false,
    },

    // 開発サーバー（WP は 8888、フロント閲覧は常に 8888 を使用）
    server: {
      host: 'localhost',
      port: 5175,
      strictPort: true,
      hmr: { protocol: 'ws', host: 'localhost', port: 5176 },
      // Vite 自身は開かず（WP 側 URL を開く）
      open: false,
    },

    // SCSS の事前設定
    css: {
      devSourcemap: isDev,
      preprocessorOptions: {
        scss: {
          charset: false,
          additionalData: `@use "sass:math";`,
        },
      },
    },

    // インポートのエイリアス
    resolve: {
      alias: {
        '@': path.resolve(rootDir, 'assets'),
        '@scripts': path.resolve(rootDir, 'assets/scripts'),
        '@styles': path.resolve(rootDir, 'assets/stylesheets'),
      },
      extensions: ['.js', '.jsx', '.json', '.scss', '.css'],
    },
  }
})
