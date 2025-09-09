<?php
// Vite アセット読込ヘルパー（WP テーマ用）

// キャッシュ（本番 manifest.json 読み込みの結果を保持）
static $vite_manifest_cache = null;

// エントリポイント関数：環境によって読込先を切替
function vite_assets() {
  $theme_path = get_template_directory();
  $is_dev = defined('DEV_ENV');

  if ($is_dev) {
    // 開発: dev サーバーから module で直読み（HMR 有効）
    vite_load_dev_assets($theme_path);
    return;
  }

  // 本番: ビルド済みアセット（manifest.json）を読み込み
  vite_load_prod_assets($theme_path);
}

// 開発: Vite dev サーバーから読み込み
function vite_load_dev_assets($theme_path) {
  // manifest は使用せず、Vite 開発サーバーを直指定
  $origin = 'http://localhost:5175';
  echo "<script src='{$origin}/@vite/client' type='module'></script>\n";
  echo "<script src='{$origin}/assets/scripts/app.js' type='module'></script>\n";
}

// 本番: manifest.json から出力パスを取得して読み込み
function vite_load_prod_assets($theme_path) {
  global $vite_manifest_cache;

  // キャッシュがあれば使用
  if ($vite_manifest_cache !== null) {
    $data = $vite_manifest_cache;
  } else {
    $manifest_path_candidates = [
      "{$theme_path}/.vite/manifest.json",
      "{$theme_path}/manifest.json",
    ];

    $manifest_path = null;
    foreach ($manifest_path_candidates as $candidate) {
      if (file_exists($candidate)) {
        $manifest_path = $candidate;
        break;
      }
    }

    if (!$manifest_path) {
      return;
    }

    $manifest = @file_get_contents($manifest_path);
    if ($manifest === false) {
      error_log('[viteHelper.php] Failed to read manifest.json: ' . $manifest_path);
      return;
    }

    $data = json_decode($manifest, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
      error_log('[viteHelper.php] JSON decode error in manifest.json: ' . json_last_error_msg());
      return;
    }

    // キャッシュに保存
    $vite_manifest_cache = $data;
  }

  // JS の出力パス（エントリ）
  $js_entry_key_candidates = [
    'assets/scripts/app.js',
  ];

  $js_path = null;
  foreach ($js_entry_key_candidates as $candidate) {
    if (isset($data[$candidate]['file'])) {
      $js_path = $data[$candidate]['file'];
      break;
    }
  }

  // CSS の出力パス群（エントリに紐づく css[] を参照）
  $css_paths = [];

  // JS エントリ側の css[] に格納されているスタイルを取得
  foreach ($js_entry_key_candidates as $candidate) {
    if (isset($data[$candidate]['css']) && is_array($data[$candidate]['css'])) {
      $css_paths = array_merge($css_paths, $data[$candidate]['css']);
    }
  }

  // 重複を排除
  $css_paths = array_unique($css_paths);

  // 実際に存在するファイルだけを残す
  $css_paths = array_values(array_filter($css_paths, function ($path) use ($theme_path) {
    return file_exists("{$theme_path}/" . ltrim($path, '/'));
  }));

  if ($js_path) {
    $js_abs_path = "{$theme_path}/" . ltrim($js_path, '/');
    $js_version = file_exists($js_abs_path) ? filemtime($js_abs_path) : null;
    wp_enqueue_script(
      'main-js',
      get_template_directory_uri() . '/' . ltrim($js_path, '/'),
      [],
      $js_version,
      true
    );

    // JS を module タイプとして読み込む（重複登録を防止）
    static $module_filter_added = false;
    if (!$module_filter_added) {
      add_filter('script_loader_tag', function ($tag, $handle, $src) {
        if ($handle === 'main-js') {
          return '<script type="module" src="' . esc_url($src) . '"></script>' . "\n";
        }
        return $tag;
      }, 10, 3);
      $module_filter_added = true;
    }
  }

  if ($css_paths) {
    foreach ($css_paths as $index => $css_path) {
      $handle = $index === 0 ? 'main-css' : 'main-css-' . $index;
      $css_abs_path = "{$theme_path}/" . ltrim($css_path, '/');
      $css_version = file_exists($css_abs_path) ? filemtime($css_abs_path) : null;
      wp_enqueue_style(
        $handle,
        get_template_directory_uri() . '/' . ltrim($css_path, '/'),
        [],
        $css_version
      );
    }
  }
}

// WP のスクリプト読み込みに接続（早期に実行）
add_action('wp_enqueue_scripts', 'vite_assets', 1);

// 画像は get_theme_file_uri('images/...') を利用（専用ヘルパーは不要）

