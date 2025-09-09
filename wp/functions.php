<?php

/**
 * テーマの機能とヘルパー関数
 *
 * Viteによるアセット管理ヘルパーを読み込みます
 */

// =====================================
// 開発環境の検出と定義
// - ローカルホストでアクセスされた場合に DEV_ENV を有効化
// =====================================
if (!defined('DEV_ENV')) {
  $is_localhost = in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', 'localhost:8888', '127.0.0.1', '::1']);
  // manifest に依存せず、ローカルホスト判定のみ
  $has_dev_manifest = false;

  if ($is_localhost || $has_dev_manifest) {
    define('DEV_ENV', true);
  }
}

// =====================================
// 開発環境での最適化
// - ブラウザキャッシュ抑止
// - スクリプト連結オフ
// - アセットURLにタイムスタンプ付与（Vite配信は除外）
// =====================================
if (defined('DEV_ENV') && DEV_ENV) {
  // ブラウザキャッシュを無効化
  add_action('send_headers', function () {
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
  });

  // WordPressのスクリプト連結を無効化（個別ファイルの変更検知を改善）
  if (!defined('CONCATENATE_SCRIPTS')) {
    define('CONCATENATE_SCRIPTS', false);
  }

  // アセットのバージョンを常に最新に
  add_filter('style_loader_src', 'remove_version_query_var', 15, 1);
  add_filter('script_loader_src', 'remove_version_query_var', 15, 1);

  function remove_version_query_var($src) {
    // Vite開発サーバーのURL(517x)はそのまま
    if (strpos($src, 'localhost:517') !== false || strpos($src, '127.0.0.1:517') !== false) {
      return $src;
    }
    // その他のアセットはタイムスタンプでキャッシュ無効化
    $parts = explode('?', $src);
    return $parts[0] . '?t=' . time();
  }
}

// =====================================
// Viteヘルパーの読み込み
// - 開発/本番のアセット読込を一元化
// =====================================
require_once get_template_directory() . '/lib/viteHelper.php';

// =====================================
// wp_head の不要出力や絵文字等を一括で削除
// =====================================
foreach (
  [
    ['wp_head', 'wp_generator'],
    ['wp_head', 'index_rel_link'],
    ['wp_head', 'rsd_link'],
    ['wp_head', 'wlwmanifest_link'],
    ['wp_head', 'rest_output_link_wp_head'],
    ['wp_head', 'feed_links', 2],
    ['wp_head', 'feed_links_extra', 3],
    ['wp_head', 'print_emoji_detection_script', 7],
    ['wp_head', 'adjacent_posts_rel_link_wp_head', 10],
    ['wp_head', 'wp_shortlink_wp_head', 10],
    ['admin_print_styles', 'print_emoji_styles'],
    ['admin_print_scripts', 'print_emoji_detection_script'],
    ['wp_print_styles', 'print_emoji_styles'],
  ] as $r
) {
  remove_action($r[0], $r[1], $r[2] ?? 10);
}

// 不要なフィルターの除去
foreach (
  [
    ['term_description', 'wpautop'],
    ['the_content_feed', 'wp_staticize_emoji'],
    ['comment_text_rss', 'wp_staticize_emoji'],
    ['wp_mail', 'wp_staticize_emoji_for_email'],
  ] as $f
) {
  remove_filter($f[0], $f[1]);
}

// TinyMCE から絵文字プラグインを外す
function disable_emojis_tinymce($plugins) {
  if (is_array($plugins)) {
    return array_diff($plugins, array('wpemoji'));
  }
  return $plugins;
}

// 基本フィルター
add_filter('tiny_mce_plugins', 'disable_emojis_tinymce');
add_filter('show_admin_bar', '__return_false');

// dns-prefetch の削除（外部先プリフェッチを抑止）
add_filter('wp_resource_hints', function ($hints, $relation_type) {
  if ($relation_type === 'dns-prefetch') return [];
  return $hints;
}, 10, 2);

// Recent Comments ウィジェットのインラインスタイルを抑止
add_action('widgets_init', function () {
  global $wp_widget_factory;
  remove_action('wp_head', array($wp_widget_factory->widgets['WP_Widget_Recent_Comments'], 'recent_comments_style'));
});

// 不要な oEmbed スクリプトの削除
add_action('wp_footer', function () {
  wp_deregister_script('wp-embed');
});

// jQuery Migrate をフロントから除外
add_action('wp_default_scripts', function ($scripts) {
  if (!is_admin() && isset($scripts->registered['jquery'])) {
    $script = $scripts->registered['jquery'];
    if ($script->deps) {
      $script->deps = array_diff($script->deps, array('jquery-migrate'));
    }
  }
});

// デフォルトの「投稿」メニューを非表示
add_action('admin_menu', function () {
  global $menu;
  remove_menu_page('edit.php');
});

// テーマ機能
// - アイキャッチ、HTML5 マークアップ、レスポンシブ埋め込み
add_theme_support('post-thumbnails');
add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script']);
add_theme_support('responsive-embeds');

// タイトル出力（必要に応じて編集）
function switch_title() {
  $pipe = ' | ';
  $get_title = '***';
  if (is_home()):
    $title = $get_title . $pipe . '***';
  elseif (is_single() || is_page()):
    $title = get_the_title() . $pipe . $get_title;
  elseif (is_404()):
    $title = '404' . $pipe . $get_title;
  else:
    $title = $get_title;
  endif;
  return $title;
}

// descriptionの設定
// ディスクリプション出力（必要に応じて編集）
function switch_description() {
  $get_description = '***';
  if (is_home()):
    $description = $get_description;
  elseif (is_single() || is_page()):
    $description = get_the_excerpt();
  elseif (is_404()):
    $description = '404';
  else:
    $description = $get_description;
  endif;
  return $description;
}

// OGP の基本メタ出力（必要に応じて編集/拡張）
function add_ogp() {
  $get_title = '***';
  $get_description = '***';
  $get_url = home_url('/');
  $get_ogp = '***';
  if (is_home()):
    $title = $get_title;
    $description = $get_description;
    $url = $get_url;
    $ogp = $get_ogp;
  elseif (is_single() || is_page()):
    $title = get_the_title();
    $description = get_the_excerpt();
    $url = get_permalink();
    $ogp = get_the_post_thumbnail_url();
  else:
    $title = $get_title;
    $description = $get_description;
    $url = $get_url;
    $ogp = $get_ogp;
  endif; ?>
  <meta property="og:title" content="<?php echo esc_html($title); ?>">
  <meta property="og:site_name" content="<?php echo esc_html($title); ?>">
  <meta property="og:description" content="<?php echo esc_html($description); ?>">
  <meta property="og:url" content="<?php echo esc_url($url); ?>">
  <meta property="og:image" content="<?php echo esc_url($ogp); ?>">
  <meta property="og:image:width" content="1200">
  <meta property="og:image:height" content="630">
  <meta property="og:type" content="website">
  <meta property="og:locale" content="ja_JP">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:site" content="">
<?php
}
add_action('wp_head', 'add_ogp');

/**
 * カスタム投稿タイプの登録
 */

