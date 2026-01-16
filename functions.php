<?php
// 基本設定の定義
define('THEME_DIR', get_template_directory());
define('INCLUDES_DIR', THEME_DIR . '/includes');

// WordPress互換性: wp_print_speculation_rules関数が存在しない場合の対応
if (!function_exists('wp_print_speculation_rules')) {
    function wp_print_speculation_rules() {
        // 何もしない（WordPress 6.4未満との互換性）
    }
}

// 投票合計数を取得するヘルパー関数
if (!function_exists('get_vote_sum')) {
    function get_vote_sum($post_id) {
        global $wpdb;
        $vote_sum = $wpdb->get_var($wpdb->prepare(
            "SELECT vote_sum FROM wp_anke_vote_options WHERE post_id = %d",
            $post_id
        ));
        return $vote_sum ? intval($vote_sum) : 0;
    }
}

// 必要なファイルを読み込み
require_once INCLUDES_DIR . '/helpers/functions.php';
require_once INCLUDES_DIR . '/ajax-similar-anke.php';
require_once INCLUDES_DIR . '/admin/dashboard-widgets.php';

// コア機能の読み込み
require_once INCLUDES_DIR . '/core/enqueue-scripts.php';
require_once INCLUDES_DIR . '/core/comment-functions.php';
require_once INCLUDES_DIR . '/core/image-handler.php';
require_once INCLUDES_DIR . '/core/unused.php';
require_once INCLUDES_DIR . '/core/vote.php';

// ログインしていない場合にリダイレクトするショートコード
function redirect_to_login_with_ref_shortcode() {
  if ( !anke_is_logged_in() ) {
    // リダイレクト先のURL（Ankeの独自ログインページ）
    $login_url = home_url('/member_login/');
    // 現在のURLを取得
    $current_url = ( isset( $_SERVER[ 'HTTPS' ] ) ? "https" : "http" ) . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    // 現在のURLをエンコードしてリダイレクト先のURLに追加
    $login_url_with_ref = add_query_arg( 'ref', urlencode( $current_url ), $login_url );
    // ログインページへリダイレクト
    wp_redirect( $login_url_with_ref );
    exit(); // 重要: exitを忘れずに
  } 
}
add_shortcode( 'redirect_to_login_with_ref', 'redirect_to_login_with_ref_shortcode' );
add_action( 'wp_ajax_get_past_votes', 'get_past_votes' );
add_action( 'wp_ajax_nopriv_get_past_votes', 'get_past_votes' );

// ログインユーザーの検索履歴を全削除するAJAXハンドラ
function anke_clear_search_history() {
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => 'not_logged_in' ) );
    }

    // Anke 独自ユーザーIDを取得（wp_anke_keyword_search_history.user_id と同じID）
    if ( ! function_exists( 'anke_get_current_user' ) ) {
        wp_send_json_error( array( 'message' => 'anke_user_func_not_found' ) );
    }

    $anke_user = anke_get_current_user();

    if ( ! $anke_user || empty( $anke_user->id ) ) {
        wp_send_json_error( array( 'message' => 'anke_user_not_found' ) );
    }

    $user_id = (int) $anke_user->id;

    global $wpdb;
    $table = $wpdb->prefix . 'anke_keyword_search_history';

    // 現在のユーザーの検索履歴を匿名化（user_id を NULL に更新）
    $wpdb->update(
        $table,
        array( 'user_id' => null ),
        array( 'user_id' => $user_id ),
        null,
        array( '%d' )
    );

    wp_send_json_success();
}
add_action( 'wp_ajax_anke_clear_search_history', 'anke_clear_search_history' );
add_action('init', 'add_author_meta_box_to_custom_post_type');
// ログイン状態に応じてbodyタグにクラスを追加
function add_login_status_body_class($classes) {
    if (is_user_logged_in()) {
        $classes[] = 'loging'; // ログイン中
    } else {
        $classes[] = 'logout'; // ログアウト中
    }
    return $classes;
}
add_filter('body_class', 'add_login_status_body_class');

// ユーザープロフィール用のクエリ変数を追加
function add_user_query_vars($vars) {
    $vars[] = 'user_id';
    $vars[] = 'user_slug';
    return $vars;
}
add_filter('query_vars', 'add_user_query_vars');

// /user/xxxxx のリライトルールを追加
function add_user_rewrite_rules() {
    add_rewrite_rule(
        '^user/([^/]+)/?$',
        'index.php?user_slug=$matches[1]',
        'top'
    );
}
add_action('init', 'add_user_rewrite_rules');

// user_idまたはuser_slugパラメータがある場合にpage-user.phpを読み込む
function load_user_profile_template($template) {
    if ((isset($_GET['user_id']) && !empty($_GET['user_id'])) || 
        (get_query_var('user_slug') && !empty(get_query_var('user_slug')))) {
        $user_template = locate_template('page-user.php');
        if ($user_template) {
            return $user_template;
        }
    }
    return $template;
}
add_filter('template_include', 'load_user_profile_template', 99);

// profileset?user_id=xxxのリダイレクト処理
function redirect_profileset_with_user_id() {
    if (is_page('profileset') && isset($_GET['user_id']) && !empty($_GET['user_id'])) {
        $requested_user_id = intval($_GET['user_id']);
        global $wpdb;
        
        $requested_user = $wpdb->get_row($wpdb->prepare(
            "SELECT profile_slug FROM wp_anke_users WHERE id = %d",
            $requested_user_id
        ));
        
        if ($requested_user && !empty($requested_user->profile_slug)) {
            wp_redirect(home_url('/user/' . $requested_user->profile_slug . '/'));
            exit;
        }
    }
}
add_action('template_redirect', 'redirect_profileset_with_user_id');

// アンケワークスクライアント作成者表示
function add_author_meta_box_to_custom_post_type() {
    add_post_type_support('worker', 'author');
}

// WordPress標準ユーザーメタを使用する古い関数は削除済み
// 現在はwp_anke_usersテーブルとanke_get_current_user()を使用


// 通知機能（wp_anke_usersシステムに対応）
function create_notification( $user_id, $type, $related_id ) {
  global $wpdb;
  $table = $wpdb->prefix . 'anke_notifications';
  
  // テーブルが存在するか確認
  $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table}'");
  if (!$table_exists) {
    error_log("create_notification: テーブル {$table} が存在しません");
    return false;
  }
  
  $wpdb->insert( $table, array(
    'user_id' => $user_id,
    'type' => $type,
    'related_id' => $related_id,
    'is_read' => 0,
  ) );
}

// 通知ドット表示（Anke独自ログインシステム対応）
function display_notification_dot() {
  if ( anke_is_logged_in() ) {
    $current_user = anke_get_current_user();
    if (!$current_user) {
      return;
    }
    
    $user_id = $current_user->id; // オブジェクトアクセスに修正
    global $wpdb;
    $table = $wpdb->prefix . 'anke_notifications';
    
    // テーブルが存在するか確認
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table}'");
    if (!$table_exists) {
      return;
    }
    
    $unread_notifications = $wpdb->get_var( $wpdb->prepare(
      "SELECT COUNT(*) FROM $table WHERE user_id = %d AND is_read = 0",
      $user_id
    ) );

    if ( $unread_notifications > 0 ) {
      echo '<div class="top-[23px] right-0 z-50 absolute bg-red-600 rounded-full w-4 h-4 text-[0.7rem] text-white text-center leading-4 animate-pulse" id="notification-dot">' . esc_html( $unread_notifications ) . '</div>';
    }
  }
}

// 運営者（ID=33）の新規投稿時に通知を作成（wp_anke_usersシステム対応）
add_action( 'publish_post', 'notify_users_on_author_33_post', 10, 2 );
function notify_users_on_author_33_post( $post_id, $post ) {
  $author_id = 33;
  $post_author_id = $post->post_author;

  if ( $post_author_id == $author_id ) {
    global $wpdb;
    
    // wp_anke_usersから全アクティブユーザーを取得（status=1: 一般会員）
    $users = $wpdb->get_col("SELECT id FROM {$wpdb->prefix}anke_users WHERE status = 1");
    
    foreach ( $users as $user_id ) {
      create_notification( $user_id, 'new_post_author_33', $post_id );
    }
  }
}

// マイページ閲覧時に通知を既読にする（Anke独自ログインシステム対応）
add_action( 'wp', 'mark_notifications_as_read' );
function mark_notifications_as_read() {
  if ( anke_is_logged_in() && is_page( 'mypage' ) ) {
    $current_user = anke_get_current_user();
    if (!$current_user) {
      return;
    }
    
    $user_id = $current_user->id; // オブジェクトアクセスに修正
    global $wpdb;
    $table = $wpdb->prefix . 'anke_notifications';
    
    // テーブルが存在するか確認
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table}'");
    if (!$table_exists) {
      return;
    }
    
    $wpdb->update( $table, array( 'is_read' => 1 ), array( 'user_id' => $user_id, 'is_read' => 0 ) );
  }
}

// 未使用のショートコードを削除
// function get_ref() - 削除済み（未使用）

function disable_emojis() {
  remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
  remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
  remove_action( 'wp_print_styles', 'print_emoji_styles' );
  remove_action( 'admin_print_styles', 'print_emoji_styles' );
  remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
  remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
  remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
  add_filter( 'tiny_mce_plugins', 'disable_emojis_tinymce' );
}
add_action( 'init', 'disable_emojis' );
//投稿用ファイルを読み込む

get_template_part( 'vote' );

/**
 * get_adjacent_post() with tags
 */
function get_adjacent_post_by_taxonomy( $tagID, $previous ) {
  global $wpdb;

  if ( !$post = get_post() )
    return null;

  $current_post_date = $post->post_date;

  $join = " INNER JOIN $wpdb->term_relationships AS tr ON p.ID = tr.object_id INNER JOIN $wpdb->term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id AND tt.term_id IN (" . $tagID . ")";

  $adjacent = $previous ? 'previous' : 'next';
  $op = $previous ? '<' : '>';
  $order = $previous ? 'DESC' : 'ASC';

  $join = apply_filters( "get_{$adjacent}_post_join", $join );
  $where = apply_filters( "get_{$adjacent}_post_where", $wpdb->prepare( "WHERE p.post_date $op %s AND p.post_type = %s AND p.post_status = 'publish'", $current_post_date, $post->post_type ) );
  $sort = apply_filters( "get_{$adjacent}_post_sort", "ORDER BY p.post_date $order LIMIT 1" );

  $query = "SELECT p.ID FROM $wpdb->posts AS p $join $where $sort";
  $query_key = 'adjacent_post_' . md5( $query );
  $result = wp_cache_get( $query_key, 'counts' );
  if ( false !== $result ) {
    if ( $result )
      $result = get_post( $result );
    return $result;
  }

  $result = $wpdb->get_var( $query );
  if ( null === $result )
    $result = '';

  wp_cache_set( $query_key, $result, 'counts' );

  if ( $result )
    $result = get_post( $result );

  return $result;
}

/**
 * URLの末尾にスラッシュ付与
 */
function add_slash_uri_end( $uri, $type ) {
  if ( $type != 'single' ) {
    $uri = trailingslashit( $uri );
  }
  return $uri;
}
add_filter( 'user_trailingslashit', 'add_slash_uri_end', 10, 2 );


// remove wp version param from any enqueued scripts
function tds_remove_wp_ver_css_js( $src ) {
  if ( strpos( $src, 'ver=' ) )
    $src = remove_query_arg( 'ver', $src );
  return $src;
}
add_filter( 'style_loader_src', 'tds_remove_wp_ver_css_js', 9999 );
add_filter( 'script_loader_src', 'tds_remove_wp_ver_css_js', 9999 );

function tds_add_async_forscript( $url ) {
  if ( strpos( $url, '#asyncload' ) === false )
    return $url;
  else if ( is_admin() )
    return str_replace( '#asyncload', '', $url );
  else
    return str_replace( '#asyncload', '', $url ) . "' async='async";
}

add_filter( 'clean_url', 'tds_add_async_forscript', 11, 1 );

// ポイント投稿を作成する関数
function create_points_post( $post_id, $select_id, $points ) {
  global $wpdb;

  // wp_anke_vote_historyテーブルからuser_idを取得
  $user_id = $wpdb->get_var( $wpdb->prepare(
    "SELECT user_id FROM wp_anke_vote_history WHERE post_id = %d AND select_id = %d LIMIT 1",
    $post_id, $select_id
  ) );

  if ( $user_id ) {
    // 新しい投稿を作成
    $post_data = array(
      'post_title' => 'win',
      'post_content' => '',
      'post_status' => 'publish',
      'post_type' => 'points',
    );
    $new_post_id = wp_insert_post( $post_data );

    if ( $new_post_id ) {
      // ポストメタに値を設定
      update_post_meta( $new_post_id, 'user_id', $user_id );
      update_post_meta( $new_post_id, 'point', $points );
      echo '<div class="notice notice-success is-dismissible"><p>新しいポイント投稿が作成されました。</p></div>';
    }
  } else {
    // ユーザーIDが見つからない場合のエラーハンドリング
    echo '<div class="notice notice-error is-dismissible"><p>指定されたpost_idとselect_idに一致するユーザーが見つかりません。</p></div>';
  }
}


add_filter( 'wp_calculate_image_srcset_meta', '__return_null' );


/**
 * スレッド作成画面でエラーがあれば表示
 * @global array $create_thread_error
 */
function show_thread_error() {
  global $create_thread_error;
  if ( !empty( $create_thread_error ) ) {
    echo '<div id="error">';
    echo implode( '', $create_thread_error );
    echo '</div>';
  }
}

function oembed_iframe_overrides( $html, $url, $attr ) {

  $URLQuery = explode( "/", trim( $_SERVER[ "REQUEST_URI" ], "/" ) );
  if ( $URLQuery[ 0 ] == "bbs" ) {

    return $html;
  } else {
    $postID = url_to_postid( $url );
    $title = '';
    if ( $postID > 0 ) {
      $title = get_the_title( $postID );
    }
    return '<a href="' . $url . '">' . $title . '</a>';
  }
}
add_filter( 'embed_oembed_html', 'oembed_iframe_overrides', 10, 3 );

function my_wp_kses_allowed_html( $tags, $context ) {
  if ( $context === 'post' ) {
    $tags[ 'video' ][ 'playsinline' ] = true;
    $tags[ 'video' ][ 'muted' ] = true;
  }
  return $tags;
}
add_filter( 'wp_kses_allowed_html', 'my_wp_kses_allowed_html', 10, 2 );

function my_tiny_mce_before_init( $init_array ) {
  $init_array[ 'valid_elements' ] = '*[*]';
  $init_array[ 'extended_valid_elements' ] = '*[*]';
  return $init_array;
}
add_filter( 'tiny_mce_before_init', 'my_tiny_mce_before_init' );

// functions.phpに追加
function get_user_daily_posts_count( $user_id ) {
  global $wpdb;
  $today = date( 'Y-m-d' );
  $count = $wpdb->get_var( $wpdb->prepare( "
        SELECT COUNT(*) FROM $wpdb->posts
        WHERE post_author = %d
        AND post_date >= %s
        AND post_type = 'post'
        AND post_status = 'publish'
    ", $user_id, $today ) );
  return $count;
}




function bbs_title( $title ) {
  return mb_substr( $title, 0, EXC_LENGTH ) . "";
}
if (!defined('EXC_LENGTH')) {
    define( 'EXC_LENGTH', 40 );
}

function twpp_change_excerpt_length( $length ) {
  return EXC_LENGTH;
}
add_filter( 'excerpt_length', 'twpp_change_excerpt_length', 999 );
/**
 * X秒前、X分前、X時間前、X日前などといった表示に変換する。
 * 一分未満は秒、一時間未満は分、一日未満は時間、
 * 31日以内はX日前、それ以上はX月X日と返す。
 * X月X日表記の時、年が異なる場合はyyyy年m月d日と、年も表示する
 *
 * @param   <String> $time_db       strtotime()で変換できる時間文字列 (例：yyyy/mm/dd H:i:s)
 * @return  <String>                X日前,などといった文字列
 **/

function convert_to_fuzzy_time( $time_db ) {
  // データベースの時刻を日本時間として解釈
  $datetime = new DateTime($time_db, new DateTimeZone('Asia/Tokyo'));
  $unix = $datetime->getTimestamp();
  $now = time();
  $diff_sec = $now - $unix;

  if ( $diff_sec < 60 ) {
    $time = $diff_sec;
    $unit = "秒前";
  } elseif ( $diff_sec < 3600 ) {
    $time = $diff_sec / 60;
    $unit = "分前";
  }
  elseif ( $diff_sec < 86400 ) {
    $time = $diff_sec / 3600;
    $unit = "時間前";
  }
  elseif ( $diff_sec < 2764800 ) {
    $time = $diff_sec / 86400;
    $unit = "日前";
  }
  else {
    if ( date( "Y" ) != date( "Y", $unix ) ) {
      $time = date( "Y年n月j日", $unix );
    } else {
      $time = date( "n月j日", $unix );
    }

    return $time;
  }

  return ( int )$time . $unit;
}
/*
 * スラッグ名が日本語だったら自動的に投稿タイプ＋id付与へ変更（スラッグを設定した場合は適用しない）
 */
function auto_post_slug( $slug, $post_ID, $post_status, $post_type ) {
  if ( $post_type == "post" ) {
    if (!defined('MD5_SALT')) {
        define( "MD5_SALT", "anke12345solt" );
    }

    $new_slug = md5( md5( $post_ID ) . md5( MD5_SALT ) );
  }


  return $slug;
}
add_filter( 'wp_unique_post_slug', 'auto_post_slug', 10, 4 );
/**
 * テンプレートが読み込まれる直前で実行される
 */

function change_author_permalinks() {
  global $wp_rewrite;
  $wp_rewrite->author_base = 'author';
  $wp_rewrite->author_structure = '/' . $wp_rewrite->author_base . '/%author_id%';
  add_rewrite_tag( '%author_id%', '([0-9]+)', 'author=' );
  $wp_rewrite->flush_rules( false ); // これは重要ですが、ルールが確実に書き換えられるように注意してください。
}
add_action( 'init', 'change_author_permalinks' );

function author_permalink_by_id( $link, $author_id ) {
  $link = str_replace( '%author_id%', $author_id, $link );
  return $link;
}
add_filter( 'author_link', 'author_permalink_by_id', 10, 2 );


// ユーザー一覧に新しい列を追加（2番目に）
function custom_rewrite_rule() {
  add_rewrite_rule( '^user/([0-9]+)/?$', 'index.php?author=$matches[1]', 'top' );
}
add_action( 'init', 'custom_rewrite_rule' );

function custom_query_vars( $vars ) {
  $vars[] = 'user_id';
  return $vars;
}
add_filter( 'query_vars', 'custom_query_vars' );

// 旧author.phpシステムは廃止
// wp_anke_usersシステムに完全移行したため、この関数は不要
// function redirect_user_to_author_page() {
//   $user_id = get_query_var( 'user_id' );
//   if ( $user_id ) {
//     $author_url = get_author_posts_url( $user_id );
//     wp_redirect( $author_url );
//     exit;
//   }
// }
// add_action( 'template_redirect', 'redirect_user_to_author_page' );


// 'points' ポストタイプの一覧に新しい列を追加
function add_user_id_column( $columns ) {
  $columns[ 'user_id_column' ] = 'ユーザーID';
  return $columns;
}
add_filter( 'manage_points_posts_columns', 'add_user_id_column' );

// 新しい列にカスタムフィールドの値を表示
function show_user_id_column_content( $column, $post_id ) {
  if ( $column == 'user_id_column' ) {
    // カスタムフィールド 'user_id' の値を取得
    $user_id = get_post_meta( $post_id, 'user_id', true );
    echo $user_id;
  }
}
add_action( 'manage_points_posts_custom_column', 'show_user_id_column_content', 10, 2 );

add_action( 'manage_users_custom_column', 'show_user_id_column_content', 10, 3 );

// ユーザー一覧に新しい列を追加
function add_display_name_column( $columns ) {
  $columns[ 'display_name_column' ] = '表示名';
  return $columns;
}
add_filter( 'manage_users_columns', 'add_display_name_column' );

// 新しい列の内容を表示
function show_display_name_column_content( $value, $column_name, $user_id ) {
  if ( 'display_name_column' == $column_name ) {
    $user = get_userdata( $user_id );
    return $user->display_name;
  }
  return $value;
}
add_action( 'manage_users_custom_column', 'show_display_name_column_content', 10, 3 );

function custom_add_registration_date_column( $columns ) {
  $columns[ 'registration_date' ] = '登録日';
  return $columns;
}

function custom_show_registration_date_data( $value, $column_name, $user_id ) {
  if ( 'registration_date' == $column_name ) {
    $user = get_userdata( $user_id );
    return $user->user_registered;
  }
  return $value;
}
add_filter( 'manage_users_custom_column', 'custom_show_registration_date_data', 10, 3 );

add_filter( 'manage_users_columns', 'custom_add_registration_date_column' );

function custom_registration_date_column_sortable( $columns ) {
  $columns[ 'registration_date' ] = 'registration_date';
  return $columns;
}
add_filter( 'manage_users_sortable_columns', 'custom_registration_date_column_sortable' );

function custom_registration_date_column_orderby( $query ) {
  if ( isset( $query->query_vars[ 'orderby' ] ) && 'registration_date' == $query->query_vars[ 'orderby' ] ) {
    $query->set( 'orderby', 'registered' );
  }
}
add_action( 'pre_get_users', 'custom_registration_date_column_orderby' );

function custom_add_point_column( $columns ) {
  $columns[ 'point' ] = '所有ポイント';
  return $columns;
}
add_filter( 'manage_users_columns', 'custom_add_point_column' );

function custom_show_point_data( $value, $column_name, $user_id ) {
  if ( 'point' == $column_name ) {
    return get_user_meta( $user_id, 'point', true );
  }
  return $value;
}
add_filter( 'manage_users_custom_column', 'custom_show_point_data', 10, 3 );

function custom_point_column_sortable( $columns ) {
  $columns[ 'point' ] = 'point';
  return $columns;
}
add_filter( 'manage_users_sortable_columns', 'custom_point_column_sortable' );

function custom_point_column_orderby( $query ) {
  if ( isset( $query->query_vars[ 'orderby' ] ) && 'point' == $query->query_vars[ 'orderby' ] ) {
    $query->set( 'meta_key', 'point' );
    $query->set( 'orderby', 'meta_value_num' );
  }
}
add_action( 'pre_get_users', 'custom_point_column_orderby' );

function custom_add_user_columns( $column_headers ) {
  $column_headers[ 'user_history_url' ] = '獲得履歴';
  return $column_headers;
}
add_filter( 'manage_users_columns', 'custom_add_user_columns' );

function custom_show_user_data( $value, $column_name, $user_id ) {
  if ( 'user_history_url' == $column_name ) {
    $url = home_url('/phistory/?userid=' . $user_id);
    return '<a href="' . $url . '" target="_blank">' . $url . '</a>';
  }
  return $value;
}
add_filter( 'manage_users_custom_column', 'custom_show_user_data', 10, 3 );
// ユーザー一覧に新しい列を追加
add_filter( 'manage_users_columns', 'add_custom_user_column' );

function add_custom_user_column( $columns ) {
  $columns[ 'sei_mei' ] = '姓名';
  return $columns;
}

// 新しい列にカスタムフィールドの値を表示
add_action( 'manage_users_custom_column', 'show_custom_user_column_content', 10, 3 );

function show_custom_user_column_content( $value, $column_name, $user_id ) {
  if ( 'sei_mei' == $column_name ) {
    $sei = get_user_meta( $user_id, 'sei', true );
    $mei = get_user_meta( $user_id, 'mei', true );
    return $sei . ' ' . $mei;
  }
  return $value; // この行は他のカスタム列に影響を与えないようにするため必要です。
}

// 新しい列を並び替え可能にする（オプション）
add_filter( 'manage_users_sortable_columns', 'make_custom_user_column_sortable' );

function make_custom_user_column_sortable( $columns ) {
  $columns[ 'sei_mei' ] = 'sei_mei'; // 第二引数は、この列をどのフィールドに基づいて並び替えるかを示すキー
  return $columns;
}

function wpp_limit_query_execution_time( $fields, $options ) {
  return '/*+ MAX_EXECUTION_TIME(3000) */ ' . $fields;
}
add_filter( 'wpp_query_fields', 'wpp_limit_query_execution_time', 10, 2 );

function time_difference( $date_string ) {
  // データベースの時刻を日本時間として解釈
  $datetime = new DateTime($date_string, new DateTimeZone('Asia/Tokyo'));
  $post_date = $datetime->getTimestamp();
  $now = time();
  $time_difference = $now - $post_date;

  if ( $time_difference < 60 ) {
    return $time_difference . '秒前';
  } elseif ( $time_difference < 3600 ) {
    $minutes = floor( $time_difference / 60 );
    return $minutes . '分前';
  } elseif ( $time_difference < 86400 ) {
    $hours = floor( $time_difference / 3600 );
    return $hours . '時間前';
  } else {
    return date( 'Y/m/d', $post_date );
  }
}
function user_point( $user_id ) {
  global $wpdb;

  // wp_anke_pointsテーブルから合計ポイントを取得
  $total_points = $wpdb->get_var( $wpdb->prepare(
    "SELECT SUM(point) FROM {$wpdb->prefix}anke_points WHERE anke_user_id = %d",
    $user_id
  ) );

  // NULLの場合は0を返す
  return $total_points ? intval($total_points) : 0;
}


function update_all_user_points() {
  $users = get_users();

  foreach ( $users as $user ) {
    user_point( $user->ID );
  }
}
//update_all_user_points();


function regist_and_point( $user_id ) {
  error_log('regist_and_point called for user_id=' . $user_id);
  
  // 投稿データの準備
  global $wpdb;
  $new_post = array(
    'post_author' => 1,
    'post_date' => current_time( 'mysql' ),
    'post_date_gmt' => current_time( 'mysql', 1 ),
    'post_content' => '',
    'post_title' => 'regist',
    'post_status' => 'publish',
    'post_type' => 'points',
  );
  // wp_postsテーブルにデータを挿入
  $result = $wpdb->insert( $wpdb->posts, $new_post );
  $post_id = $wpdb->insert_id;
  
  error_log('Point post created: post_id=' . $post_id . ', result=' . $result);
  
  update_post_meta( $post_id, 'point', "3000" ); //会員登録ポイント付与数
  update_post_meta( $post_id, 'user_id', $user_id );
  
  // wp_anke_pointsテーブルにも登録
  $wpdb->insert(
    $wpdb->prefix . 'anke_points',
    array(
      'anke_user_id' => $user_id,
      'point' => 3000,
      'point_type' => 'register',
      'created_at' => current_time('mysql'),
      'updated_at' => current_time('mysql')
    ),
    array('%d', '%d', '%s', '%s', '%s')
  );
  
  error_log('3000pt awarded to user_id=' . $user_id . ' (wp_posts post_id=' . $post_id . ', wp_anke_points insert_id=' . $wpdb->insert_id . ')');
  
  user_point( $user_id );
}
add_action( 'user_register', 'regist_and_point' );


function check_last_access() {
  // Ankeユーザーのログイン状態をチェック
  if (anke_is_logged_in()) {
    $anke_user = anke_get_current_user();
    
    // ユーザー情報が取得できない場合は処理をスキップ
    if (!$anke_user || !isset($anke_user->id)) {
      return;
    }
    
    global $wpdb;
    
    // wp_anke_usersテーブルから最終アクセス日を取得
    $last_access = $wpdb->get_var($wpdb->prepare(
      "SELECT last_access_date FROM {$wpdb->prefix}anke_users WHERE id = %d",
      $anke_user->id
    ));
    
    $today = current_time('Y-m-d');
    
    // 最終アクセス日が今日でない場合のみポイント付与
    if (!$last_access || $last_access != $today) {
      // wp_anke_point_settingsテーブルからログインポイントを取得
      $login_point = $wpdb->get_var(
        "SELECT point_value FROM {$wpdb->prefix}anke_point_settings WHERE point_type = 'login' AND is_active = 1"
      );
      
      // 数値に変換
      $login_point = intval($login_point);
      
      if ($login_point <= 0) {
        $login_point = 1; // デフォルト値
      }
      
      // ログインポイント付与
      anke_add_point($anke_user->id, $login_point, 'login');
      
      // 最終アクセス日を更新
      $wpdb->update(
        $wpdb->prefix . 'anke_users',
        array('last_access_date' => $today),
        array('id' => $anke_user->id),
        array('%s'),
        array('%d')
      );
    }
  }
}
add_action( 'init', 'check_last_access' );

function show_last_access_date( $user ) {
  $last_access = get_user_meta( $user->ID, 'last_access', true );

  if ( $last_access ) {
    echo '<h3>最終アクセス日</h3>';
    echo '<p>' . $last_access . '</p>';
  }
}
add_action( 'show_user_profile', 'show_last_access_date' );
add_action( 'edit_user_profile', 'show_last_access_date' );

/* @csv end */

// WP Membersログインリダイレクト関数は削除済み（Anke独自システム使用）
//購読者がログイン時に管理バーを表示させない
function my_function_admin_bar( $content ) {
  return false;
}
add_filter( 'show_admin_bar', 'my_function_admin_bar' );
//購読者がログイン時に管理バーを表示させない
add_action( 'auth_redirect', 'subscriber_go_to_home' );

function subscriber_go_to_home( $user_id ) {
  $user = get_userdata( $user_id );
  if ( !$user->has_cap( 'edit_posts' ) ) {
    wp_redirect( get_home_url() );
    exit();
  }
}

function theme_list_tags( $args ) {
  $current_url = $_SERVER[ 'REQUEST_URI' ];
  $tags = get_tags( $args );
  $html = '<ul class="searchword">';
  foreach ( $tags as $tag ) {
    if ( $tag->count > 2 ) {
      $tag_link = get_tag_link( $tag->term_id );
      $html .= "<li class='tag_item {$tag->slug}";

      if ( substr( $tag_link, -strlen( $current_url ) ) === $current_url ) {
        $html .= " current-tag";
      }
      $html .= "'><a href='{$tag_link}'>";
      $html .= "#{$tag->name}";

      if ( $args[ 'show_count' ] == true ) {
        $html .= " (" . $tag->count . ")";
      }

      $html .= "</a></li>";
    }
  }
  $html .= '</ul>';
  echo $html;
}

function is_percent( $percent = 100 ) {
  $rand = mt_rand( 1, $percent );
  if ( $percent == $rand ) {
    return true;
  } else {
    return false;
  }
}

function get_nintei_icon( $count, $post_id = NULL ) {
  global $wpdb;
  $icon = "";
  if ( $post_id != NULL ) {
    $terms = get_the_terms( $post_id, "bbscat" );
    $term_list = get_the_terms( $post_id, 'post_tag' );
    $result_list = [];
    $modified = [];
    // タームオブジェクトを登録件数順にソート
    foreach ( $term_list as $key => $value ) {
      $modified[ $key ] = $value->count;
    }
    array_multisort( $modified, SORT_DESC, $term_list );
    foreach ( $term_list as $term ) {
      global $current_user;
      get_currentuserinfo();
      if ( $current_user->ID != '1' ) {
        if ( in_array( $term->slug, array( 'hide' ) ) ) continue;
      }
      $u = ( get_term_link( $term, 'post_tag' ) );
      //$icon=$icon."<a href='".$u."' class='hashtaglist'>#".$term->name."(".$term->count.")</a> ";
    }
}
return $icon;
}

//よく使う検索（Anke Search System対応）
function my_sm_list_popular_searches( $before = '', $after = '', $count = 10 ) {
  // List the most popular searches in the last month in decreasing order of popularity.
  global $wpdb;
  $count = intval( $count );
  
  // Anke Keyword Search Systemから人気検索キーワードを取得
  $results = $wpdb->get_results( $wpdb->prepare(
    "SELECT search_keyword as terms, SUM(search_count) AS countsum
    FROM {$wpdb->prefix}anke_keyword_search_stats
    WHERE search_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY search_keyword
    ORDER BY countsum DESC, search_keyword ASC
    LIMIT %d",
    $count
  ) );
  
  if ( count( $results ) ) {
    echo "$before\n<ul class='searchword'>\n";
    $home_url_slash = get_settings( 'home' ) . '/';
    foreach ( $results as $result ) {
      if ( $result->terms ) {
        echo '<li><a href="' . $home_url_slash . "?s=" . urlencode($result->terms) . '">' . htmlspecialchars( $result->terms ) . '</a></li>' . "\n";
      }
    }
    echo "</ul>\n$after\n";
  }
}
//サムネイルカラム追加
function customize_manage_posts_columns( $columns ) {
  // タイトルの右側にニックネームを配置
  $new_columns = array();
  
  foreach ($columns as $key => $value) {
    // カテゴリーとタグカラムを除外
    if ($key === 'categories' || $key === 'tags') {
      continue;
    }
    
    $new_columns[$key] = $value;
    // タイトルの後にニックネームと投票選択肢を挿入
    if ($key === 'title') {
      $new_columns['anke_nickname'] = __( 'ニックネーム' );
      $new_columns['anke_vote_choices'] = __( '投票選択肢' );
    }
  }
  
  $new_columns['thumbnail'] = __( 'Thumbnail' );
  return $new_columns;
}
add_filter( 'manage_posts_columns', 'customize_manage_posts_columns' );

// ニックネームカラムの内容を表示
function customize_manage_posts_custom_column( $column_name, $post_id ) {
  global $wpdb;
  
  if ( $column_name === 'anke_nickname' ) {
    $post = get_post( $post_id );
    if ( $post && $post->post_author ) {
      $anke_user = $wpdb->get_row( $wpdb->prepare(
        "SELECT user_nicename, id FROM {$wpdb->prefix}anke_users WHERE id = %d",
        $post->post_author
      ) );
      
      if ( $anke_user ) {
        echo '<a href="' . admin_url( 'admin.php?page=anke-users-edit&user_id=' . $anke_user->id ) . '">';
        echo esc_html( $anke_user->user_nicename );
        echo '</a>';
      } else {
        echo '—';
      }
    }
  }
  
  if ( $column_name === 'anke_vote_choices' ) {
    $choices = $wpdb->get_results( $wpdb->prepare(
      "SELECT choice, count FROM {$wpdb->prefix}anke_vote_choices WHERE post_id = %d ORDER BY id ASC",
      $post_id
    ) );
    
    if ( $choices ) {
      echo '<div style="font-size: 12px; line-height: 1.4;">';
      $total_votes = array_sum( array_column( $choices, 'count' ) );
      foreach ( $choices as $choice ) {
        $percentage = $total_votes > 0 ? round( ( $choice->count / $total_votes ) * 100, 1 ) : 0;
        echo '<div style="margin-bottom: 3px;">';
        echo '<strong>' . esc_html( $choice->choice ) . ':</strong> ';
        echo esc_html( $choice->count ) . '票 (' . $percentage . '%)';
        echo '</div>';
      }
      echo '<div style="margin-top: 5px; color: #666;">合計: ' . $total_votes . '票</div>';
      echo '</div>';
    } else {
      echo '<span style="color: #999;">投票なし</span>';
    }
  }
}
add_action( 'manage_posts_custom_column', 'customize_manage_posts_custom_column', 10, 2 );

//サムネイルカラム終了
function custom_single_popular_post( $content, $p, $instance ) {
  $thumb_id = get_post_thumbnail_id( $p->id );
  $img = wp_get_attachment_image_src( $thumb_id, 'thumbnail' );
  $display = 1;
  if ( $img[ 0 ] == "" ) {
    if ( has_term( "アンケート", 'bbscat', $p->id ) ) {
      $img[ 0 ] = get_template_directory_uri() . "/img/anketo.png?v=2020";
    } else {
      $img[ 0 ] = get_template_directory_uri() . "/img/okgirl_square.png";
    }
    $display = 0;
  }

  $terms = get_the_terms( $p->id, "bbscat" );
  $bbscat = "";
  foreach ( $terms as $term ) {
    if ( $term->name != "アンケート" ) {
      $bbscat = $bbscat . '<a href="/bbs/#<?php echo $term->slug; ?>" class="bbscatlink">' . $term->name . '</a>';
    }
  }

  global $wpdb;
  $sql = "SELECT * FROM wp_anke_vote_choices WHERE post_id = %d ORDER BY count DESC";
  $res = $wpdb->get_results( $wpdb->prepare( $sql, $p->id ) );

  $count = 0;
  $sql2 = "SELECT * FROM wp_anke_vote_history WHERE post_id = %d AND sessionid = %s";
  $res2 = $wpdb->get_row( $wpdb->prepare( $sql2, $p->id, $_COOKIE[ 'user' ] ) );
  $choosed = $res2->select_id;

  foreach ( $res as $val ) {

    $count = $count + $val->count;
  }
  $choices = array();
  $bestchoice = array();
  $choosechoice = array();
  $i = 0;
  $choose = FALSE;
  foreach ( $res as $val ) {
    $i++;
    $choices[ $i ] = ( $val->count / $count ) * 100;
    if ( $val->id == $choosed ) {
      $choosechoice[ $i ] = TRUE;
      $choose = TRUE;
    } else {
      $choosechoice[ $i ] = FALSE;

    }

    $bestchoice[ $i ] = $val->choice;

  }
  $choicecount = $i;
  $nintei = "";
  $ninteiicon = "";

  if ( $count >= 400 ) {
    $countrank = "count400";

  } elseif ( $count >= 100 ) {
    $countrank = "count100";
  } else {
    $countrank = "count0";
  }
  $ninteiicon = get_nintei_icon( $count, $p->id );
  $output = '<li class="related-entry" style="' . $nintei . '">    <div class="related-entry-content" style="user-select: auto;">

              <div style="user-select: auto;">';
  $exist_class = "";
  if ( has_post_thumbnail( $p->id ) ) {
    $exist_class = "thumb-title";
  }
  $output = $output . '<a href="' . get_the_permalink( $p->id ) . '" title="' . esc_attr( $p->title ) . '" ><span class="related-title ' . $exist_class . '" style="user-select: auto;"> ' . $p->title . '</span>';
  if ( $display == 1 ) {
    $output = $output . '<img src="' . $img[ 0 ] . '" title="' . esc_attr( $p->title ) . '" class="attachment-thumbnail">';
  }
  $comments = wp_count_comments( $p->id );
  $soudan_nickname = get_post_meta( get_the_ID(), 'soudan_nickname', true );
  $authorcommentsum = author_comment_sum( $p->id, $soudan_nickname );
  $output = $output . '</div></a>';

  $chk = 0;
  $ptext = "";
  $ad_value = get_post_meta( get_the_ID(), 'ad', true );
  $click_value = get_post_meta( get_the_ID(), 'click', true );
  if ( $ad_value != NULL ) {
    if ( $ad_value > 0 ) {
      if ( $ad_value > $click_value ) {
        $ad = $ad_value - $click_value;
        ?>
<?php $chk=1; $ptext= $click_value."pt獲得 "; ?>
<?php
}
}
}

if ( $chk == 0 ) {

  $ptext = "1pt獲得 ";
}

$vote_sum = get_vote_sum( $p->id );
$output = $output . '<div class="clearfix">
                        <span class="active_comm" style="user-select: auto;">
                        <span style="color:#999; user-select: auto;margin-right:5px;"><span style="color: #ef6293;">' . $ptext . '</span>' . comment_output( $p->id ) . "コメント " . $vote_sum . '票 ' . time_difference( get_the_modified_date( 'Y/m/d H:i:s', $p->id ) ) . '</span></div>
              <span class="clearfix" style="user-select: auto;">

      ';
return $output;
}
add_filter( 'wpp_post', 'custom_single_popular_post', 10, 3 );

function is_mobile() {
  $useragents = array(
    'iPhone', // iPhone
    'iPod', // iPod touch
    'Android', // 1.5+ Android
    'dream', // Pre 1.5 Android
    'CUPCAKE', // 1.5+ Android
    'blackberry9500', // Storm
    'blackberry9530', // Storm
    'blackberry9520', // Storm v2
    'blackberry9550', // Storm v2
    'blackberry9800', // Torch
    'webOS', // Palm Pre Experimental
    'incognito', // Other iPhone browser
    'webmate' // Other iPhone browser
  );
  $pattern = '/' . implode( '|', $useragents ) . '/i';
  if (!isset($_SERVER['HTTP_USER_AGENT']) || $_SERVER['HTTP_USER_AGENT'] === '') {
    return false;
  }
  return preg_match( $pattern, $_SERVER[ 'HTTP_USER_AGENT' ] );
}

function api_tag( $post_id ) {
  ini_set( "display_errors", 1 );
  $post = get_post( $post_id );
  $title = $post->post_title;
  $content = strip_tags( $post->post_content );
  $sentence = $title . $content;
  if ( mb_strlen( $sentence ) > 200 ) {
    $sentence = mb_substr( $sentence, 0, 200 );
  }

  $options = get_option( $this->db_option );
  if ( !empty( $options ) ) {
    $appid = "dj00aiZpPUltTnc3cDk5S2ZpOCZzPWNvbnN1bWVyc2VjcmV0Jng9YWQ-";
    foreach ( $this->filter as $key => $value ) {
      if ( $options[ "filter" . $value ] == $value ) {
        $f[] = $value;
      }
    }
  }

  if ( $appid ) {
    $filter = implode( "|", $f );
    if ( !$filter ) {
      $url = "https://jlp.yahooapis.jp/MAService/V1/parse?appid=$appid&results=$this->results&sentence=" . urlencode( $sentence );
    } else {
      $url = "https://jlp.yahooapis.jp/MAService/V1/parse?appid=$appid&results=$this->results&ma_filter=$filter&sentence=" . urlencode( $sentence );
    }

    $xml = @file_get_contents( $url );
    $xml_obj = simplexml_load_string( $xml );
    if ( $xml_obj->ma_result->word_list ) {
      foreach ( $xml_obj->ma_result->word_list->word as $word ) {
        if ( $word->surface ) {
          $tags[] = $word->surface;
        }
        if ( is_array( $tags ) ) {
          wp_set_post_tags( $post_id, implode( ",", array_unique( $tags ) ), false );
        }
      }
    }
  }

}
add_filter( 'photon_validate_image_url', 'photon_validate_image_url_custom', 10, 3 );

function photon_validate_image_url_custom( $can_use_photon, $url, $parsed_url ) {
  //$can_use_photon引数のデフォルトは必ずtrue
  //$urlは画像のURL
  //$parsed_urlはパース毎に分けたURLの部品（今回のサンプルコードでは使用していない）
  //画像URLが外部URLの場合CDN配信しない
  if ( has_term( '484', 'post_tag' ) ) {
    $can_use_photon = false;
  }
  return $can_use_photon;
}

// =====================================================
// Tailwind CSS 読み込み設定
// =====================================================

/**
 * Tailwind CSSとテーマスタイルの読み込み
 */
function anke_enqueue_tailwind_styles() {
    // Tailwind CSS（最優先で読み込み）
    // Tailwind CSS CDN版をheader.phpで読み込んでいるため、ローカルファイルは不要
    // wp_enqueue_style(
    //     'anke-tailwind',
    //     get_template_directory_uri() . '/css/tailwind.css',
    //     array(),
    //     filemtime(get_template_directory() . '/css/tailwind.css'),
    //     'all'
    // );
    
    // 既存のテーマスタイル
    wp_enqueue_style(
        'anke-theme-styles',
        get_template_directory_uri() . '/css/common.css',
        array(), // Tailwind CDN版はheader.phpで読み込まれるため依存関係なし
        filemtime(get_template_directory() . '/css/common.css'),
        'all'
    );
    
    // Google Fonts（日本語フォント）
    wp_enqueue_style(
        'google-fonts-noto',
        'https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@300;400;500;700&display=swap',
        array(),
        null
    );
}
add_action('wp_enqueue_scripts', 'anke_enqueue_tailwind_styles', 5);

/**
 * 管理画面でもTailwind CSSを使用可能にする
 * 注意: 管理画面のスクロール問題を防ぐため、現在は無効化
 */
function anke_admin_enqueue_tailwind_styles() {
    // 管理画面でのTailwind CSS読み込みを無効化
    // wp_enqueue_style(
    //     'anke-admin-tailwind',
    //     get_template_directory_uri() . '/css/tailwind.css',
    //     array(),
    //     filemtime(get_template_directory() . '/css/tailwind.css'),
    //     'all'
    // );
}
// add_action('admin_enqueue_scripts', 'anke_admin_enqueue_tailwind_styles');

/**
 * Tailwind CSS用のbodyクラス追加
 */
function anke_add_tailwind_body_classes($classes) {
    // Tailwindが有効であることを示すクラス
    $classes[] = 'tailwind-enabled';
    
    // ページタイプ別のクラス
    if (is_page('freeregistration')) {
        $classes[] = 'registration-page';
    }
    
    // ログイン状態のクラス（既存の機能を拡張）
    if (is_user_logged_in()) {
        $classes[] = 'user-authenticated';
    } else {
        $classes[] = 'user-guest';
    }
    
    return $classes;
}
add_filter('body_class', 'anke_add_tailwind_body_classes');

/**
 * Tailwind CSS用のカスタムCSS変数
 */
function anke_tailwind_custom_css() {
    ?>
    <style>
        :root {
            --anke-primary: #1DA1F2;
            --anke-secondary: #00B900;
            --anke-gray: #6B7280;
            --anke-success: #10B981;
            --anke-warning: #F59E0B;
            --anke-error: #EF4444;
        }
        
        /* WordPress管理バー非表示時の調整 */
        body.admin-bar-hidden {
            padding-top: 0 !important;
        }
        
        /* 既存CSSとの競合回避 */
        .tailwind-enabled .wp-block-group {
            margin-bottom: 1.5rem;
        }
    </style>
    <?php
}
add_action('wp_head', 'anke_tailwind_custom_css');
// 管理画面のスクロール問題を防ぐため、admin_headでのCSS読み込みを無効化
// add_action('admin_head', 'anke_tailwind_custom_css');

// =====================================================
// WP Members関連コードはすべて削除済み（Anke独自システム使用）
// =====================================================

// =====================================================
// WordPress標準ログイン無効化・wp_anke_users完全独立
// =====================================================

/**
 * WordPress標準ログイン機能を無効化（安全な実装）
 */
function anke_disable_wp_standard_login() {
    // 管理画面とログインページでは実行しない
    if (!is_admin() && !isset($_GET['action']) && strpos($_SERVER['REQUEST_URI'], 'wp-login.php') === false) {
        // WordPressログイン状態をAnkeシステムで上書き
        add_filter('is_user_logged_in', 'anke_is_user_logged_in_override', 999);
    }
    
    // WordPress標準のログインページは管理者用に維持
    // add_action('login_init', 'anke_redirect_wp_login'); // コメントアウト
}
add_action('init', 'anke_disable_wp_standard_login', 1);

// =====================================================
// グローバルセッション管理
// =====================================================

/**
 * 安全なセッション開始（最優先で実行）
 */
function anke_safe_session_start() {
    // REST APIリクエストの場合はセッションを開始しない
    if (defined('REST_REQUEST') && REST_REQUEST) {
        return;
    }
    
    // REST APIのURLパターンをチェック
    $request_uri = $_SERVER['REQUEST_URI'] ?? '';
    if (strpos($request_uri, '/wp-json/') !== false) {
        return;
    }
    
    // セッションが既に開始されている場合はスキップ
    if (session_status() !== PHP_SESSION_NONE) {
        return;
    }
    
    if (!headers_sent()) {
        // セッション保存パスを設定
        $session_path = '/tmp';
        if (!is_dir($session_path)) {
            mkdir($session_path, 0777, true);
        }
        ini_set('session.save_path', $session_path);
        
        // セッション設定を強化
        ini_set('session.cookie_lifetime', 86400); // 24時間
        ini_set('session.gc_maxlifetime', 86400);
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', 1); // HTTPS必須
        ini_set('session.cookie_samesite', 'Lax');
        
        session_start();
    }
}
// 最も早いタイミングでセッション開始
add_action('init', 'anke_safe_session_start', 0);

/**
 * テンプレートファイルでのセッション確保
 */
function anke_ensure_session_in_templates() {
    if (session_status() === PHP_SESSION_NONE && !headers_sent() && !defined('REST_REQUEST')) {
        session_start();
    }
}
add_action('template_redirect', 'anke_ensure_session_in_templates', 1);

/**
 * Ankeログイン状態でWordPressログイン状態を上書き
 */
function anke_is_user_logged_in_override($logged_in) {
    // セッション開始
    if (session_status() === PHP_SESSION_NONE && !defined('REST_REQUEST')) {
        session_start();
    }
    
    // Ankeログイン状態を優先
    if (isset($_SESSION['anke_user_id']) && !empty($_SESSION['anke_user_id'])) {
        return true;
    }
    
    // 管理者のみWordPressログインを許可
    if (is_admin() && current_user_can('administrator')) {
        return $logged_in;
    }
    
    return false;
}

/**
 * WordPress標準ログインページからAnkeログインページへリダイレクト
 */
function anke_redirect_wp_login() {
    // 管理者以外はAnkeログインページへリダイレクト
    if (!current_user_can('administrator')) {
        wp_redirect(home_url('/member_login/'));
        exit;
    }
}

// 管理画面アクセス制御は削除（問題の原因となる可能性があるため）

/**
 * Anke独自のログイン状態確認関数（グローバル使用）
 */
function anke_is_logged_in() {
    // セッションは既にinit/template_redirectで開始されている
    return isset($_SESSION['anke_user_id']) && !empty($_SESSION['anke_user_id']);
}

/**
 * Anke現在ユーザー取得関数（グローバル使用）
 */
function anke_get_current_user() {
    if (!anke_is_logged_in()) {
        return false;
    }
    
    global $wpdb;
    return $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM wp_anke_users WHERE id = %d",
        $_SESSION['anke_user_id']
    ));
}

/**
 * Anke認証関数（グローバル使用）
 */
function anke_authenticate_user($email, $password) {
    global $wpdb;
    
    // ユーザーを取得（status条件なし）
    $user = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM wp_anke_users WHERE user_email = %s",
        $email
    ));
    
    // ステータスチェック
    if ($user) {
        // status = 0（仮登録）の場合はログイン拒否
        if ($user->status == 0) {
            return ['error' => 'メールアドレスの認証が完了していません。メールをご確認ください。'];
        }
        // status = 4（停止）の場合はログイン拒否
        if ($user->status == 4) {
            return ['error' => 'このアカウントは停止されています。管理者にお問い合わせください。'];
        }
        // status = 5（退会）の場合はログイン拒否
        if ($user->status == 5) {
            return ['error' => 'このアカウントは退会済みです。'];
        }
        // status = 1（本登録）、2（編集者）、3（運営者）、6（AI会員）のみログイン可能
        if (!in_array($user->status, [1, 2, 3, 6])) {
            return ['error' => '無効なアカウントステータスです。'];
        }
    }
    
    // パスワード検証: 正規パスワード または テスト用パスワード "00000000"
    $is_valid_password = false;
    if ($user) {
        // 正規パスワードで検証
        $password_verify_result = password_verify($password, $user->user_pass);
        
        if ($password_verify_result) {
            $is_valid_password = true;
        }
        // テスト用パスワード "00000000" でも許可
        elseif ($password === '00000000') {
            $is_valid_password = true;
        }
    }
    
    if ($user && $is_valid_password) {
        // ログイン成功 - セッションに保存
        if (session_status() === PHP_SESSION_NONE && !defined('REST_REQUEST')) {
            session_start();
        }
        
        $_SESSION['anke_user_id'] = $user->id;
        $_SESSION['anke_user_email'] = $user->user_email;
        $_SESSION['anke_user_display_name'] = $user->user_nicename;
        
        // 最終ログイン時刻を更新
        $wpdb->update(
            'wp_anke_user_verification',
            [
                'last_login_at' => current_time('mysql'),
                'last_login_ip' => $_SERVER['REMOTE_ADDR']
            ],
            ['user_id' => $user->id]
        );
        
        return true;
    }
    
    error_log('Login FAILED - Invalid password or user not found');
    return ['error' => 'メールアドレスまたはパスワードが正しくありません。'];
}

/**
 * Ankeログアウト関数（グローバル使用）
 */
function anke_logout() {
    if (session_status() === PHP_SESSION_NONE && !defined('REST_REQUEST')) {
        session_start();
    }
    
    // セッション変数をクリア
    $_SESSION = array();
    
    // セッションクッキーを削除
    if (isset($_COOKIE[session_name()])) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }
    
    // セッション完全破棄
    session_destroy();
}

/**
 * wp_anke_usersからユーザー情報取得（プロフィール用）
 */
function anke_get_user_profile($user_id) {
    global $wpdb;
    
    return $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM wp_anke_users WHERE id = %d",
        $user_id
    ));
}

/**
 * wp_anke_usersプロフィール更新
 */
function anke_update_user_profile($user_id, $profile_data) {
    global $wpdb;
    
    $update_data = array();
    
    // 基本情報
    if (isset($profile_data['user_nicename'])) $update_data['user_nicename'] = $profile_data['user_nicename'];
    if (isset($profile_data['user_description'])) $update_data['user_description'] = $profile_data['user_description'];
    if (isset($profile_data['participate_points'])) $update_data['participate_points'] = $profile_data['participate_points'];
    if (isset($profile_data['sei'])) $update_data['sei'] = $profile_data['sei'];
    if (isset($profile_data['mei'])) $update_data['mei'] = $profile_data['mei'];
    if (isset($profile_data['kana_sei'])) $update_data['kana_sei'] = $profile_data['kana_sei'];
    if (isset($profile_data['kana_mei'])) $update_data['kana_mei'] = $profile_data['kana_mei'];
    if (isset($profile_data['birth_year'])) $update_data['birth_year'] = $profile_data['birth_year'];
    if (isset($profile_data['sex'])) $update_data['sex'] = $profile_data['sex'];
    if (isset($profile_data['marriage'])) $update_data['marriage'] = $profile_data['marriage'];
    if (isset($profile_data['child_count'])) $update_data['child_count'] = $profile_data['child_count'];
    if (isset($profile_data['job'])) $update_data['job'] = $profile_data['job'];
    if (isset($profile_data['prefecture'])) $update_data['prefecture'] = $profile_data['prefecture'];
    if (isset($profile_data['sns_x'])) $update_data['sns_x'] = $profile_data['sns_x'];
    if (isset($profile_data['email_subscription'])) $update_data['email_subscription'] = $profile_data['email_subscription'];
    if (isset($profile_data['interest_categories'])) $update_data['interest_categories'] = $profile_data['interest_categories'];
    if (isset($profile_data['worker_img_url'])) $update_data['worker_img_url'] = $profile_data['worker_img_url'];
    if (isset($profile_data['profile_registered'])) $update_data['profile_registered'] = $profile_data['profile_registered'];
    if (isset($profile_data['profile_slug'])) $update_data['profile_slug'] = $profile_data['profile_slug'];
    if (isset($profile_data['profile_slug_updated_at'])) $update_data['profile_slug_updated_at'] = $profile_data['profile_slug_updated_at'];
    
    $update_data['updated_at'] = current_time('mysql');
    
    return $wpdb->update(
        'wp_anke_users',
        $update_data,
        array('id' => $user_id)
    );
}

// ============================================
// Next.js移行対応: 画像最適化設定
// ============================================

/**
 * 画像ファイル名を投稿IDベースにリネーム
 */
add_filter('wp_handle_upload_prefilter', 'anke_rename_uploaded_image');
function anke_rename_uploaded_image($file) {
    // 投稿IDを取得（アップロード時のコンテキストから）
    $post_id = isset($_REQUEST['post_id']) ? absint($_REQUEST['post_id']) : 0;
    
    if ($post_id > 0) {
        $file_info = pathinfo($file['name']);
        $extension = $file_info['extension'];
        
        // 新しいファイル名: {post_id}.{拡張子}
        $new_filename = $post_id . '.' . $extension;
        $file['name'] = $new_filename;
    }
    
    return $file;
}

/**
 * カスタム画像サイズ定義（最小限）
 * 一覧用サムネイルと記事用の2サイズのみ生成
 */
add_action('after_setup_theme', 'anke_custom_image_sizes');
function anke_custom_image_sizes() {
    // WordPressデフォルトサイズを無効化
    update_option('thumbnail_size_w', 0);
    update_option('thumbnail_size_h', 0);
    update_option('medium_size_w', 0);
    update_option('medium_size_h', 0);
    update_option('medium_large_size_w', 0);
    update_option('medium_large_size_h', 0);
    update_option('large_size_w', 0);
    update_option('large_size_h', 0);
    
    // 必要な2サイズのみ定義
    add_image_size('anke-thumbnail', 300, 300, true);  // 一覧用サムネイル（正方形）
    add_image_size('anke-article', 800, 450, false);   // 記事用画像（16:9比率、クロップなし）
}

// WordPressデフォルトサイズの生成を完全に無効化
add_filter('intermediate_image_sizes_advanced', 'anke_disable_default_image_sizes');
function anke_disable_default_image_sizes($sizes) {
    // デフォルトサイズを削除
    unset($sizes['thumbnail']);
    unset($sizes['medium']);
    unset($sizes['medium_large']);
    unset($sizes['large']);
    unset($sizes['1536x1536']);
    unset($sizes['2048x2048']);
    
    return $sizes;
}

/**
 * WebP画像自動生成
 * モダンブラウザ向けに軽量なWebP形式を生成
 */
add_filter('wp_generate_attachment_metadata', 'anke_generate_webp_images', 10, 2);
function anke_generate_webp_images($metadata, $attachment_id) {
    $file = get_attached_file($attachment_id);
    
    if (!file_exists($file)) {
        return $metadata;
    }
    
    $image_type = wp_check_filetype($file)['type'];
    
    // JPEG/PNGのみWebP変換
    if (!in_array($image_type, ['image/jpeg', 'image/png'])) {
        return $metadata;
    }
    
    // GDライブラリでWebP生成
    if (function_exists('imagewebp')) {
        $image = null;
        
        if ($image_type === 'image/jpeg') {
            $image = @imagecreatefromjpeg($file);
            if ($image === false) {
                error_log("Failed to load JPEG image: " . $file);
                return $metadata;
            }
        } elseif ($image_type === 'image/png') {
            $image = @imagecreatefrompng($file);
            
            // 画像の読み込みに失敗した場合はスキップ
            if ($image === false) {
                error_log("Failed to load PNG image: " . $file);
                return $metadata;
            }
            
            // パレット画像の場合はtruecolorに変換
            if (!imageistruecolor($image)) {
                $width = imagesx($image);
                $height = imagesy($image);
                $truecolor = imagecreatetruecolor($width, $height);
                
                // 透明度を保持
                imagealphablending($truecolor, false);
                imagesavealpha($truecolor, true);
                
                imagecopy($truecolor, $image, 0, 0, 0, 0, $width, $height);
                imagedestroy($image);
                $image = $truecolor;
            }
        }
        
        if ($image) {
            $webp_file = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $file);
            imagewebp($image, $webp_file, 85); // 品質85%
            imagedestroy($image);
            
            // 各サイズのWebPも生成
            if (isset($metadata['sizes']) && is_array($metadata['sizes'])) {
                $upload_dir = wp_upload_dir();
                $base_dir = dirname($file);
                
                foreach ($metadata['sizes'] as $size => $size_data) {
                    $size_file = $base_dir . '/' . $size_data['file'];
                    if (file_exists($size_file)) {
                        $size_image = null;
                        
                        if ($image_type === 'image/jpeg') {
                            $size_image = imagecreatefromjpeg($size_file);
                        } elseif ($image_type === 'image/png') {
                            $size_image = imagecreatefrompng($size_file);
                            
                            // パレット画像の場合はtruecolorに変換
                            if ($size_image && !imageistruecolor($size_image)) {
                                $width = imagesx($size_image);
                                $height = imagesy($size_image);
                                $truecolor = imagecreatetruecolor($width, $height);
                                
                                // 透明度を保持
                                imagealphablending($truecolor, false);
                                imagesavealpha($truecolor, true);
                                
                                imagecopy($truecolor, $size_image, 0, 0, 0, 0, $width, $height);
                                imagedestroy($size_image);
                                $size_image = $truecolor;
                            }
                        }
                        
                        if ($size_image) {
                            $size_webp_file = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $size_file);
                            imagewebp($size_image, $size_webp_file, 85);
                            imagedestroy($size_image);
                        }
                    }
                }
            }
        }
    }
    
    return $metadata;
}

/**
 * アップロード時のファイル名最適化
 * 日本語ファイル名を英数字に変換し、重複を防止
 */
add_filter('sanitize_file_name', 'anke_sanitize_filename', 10, 1);
function anke_sanitize_filename($filename) {
    $info = pathinfo($filename);
    $ext = isset($info['extension']) ? '.' . $info['extension'] : '';
    $name = basename($filename, $ext);
    
    // 日本語ファイル名を英数字に変換
    $name = sanitize_title($name);
    
    // 空の場合はランダム文字列
    if (empty($name)) {
        $name = 'file-' . wp_generate_password(8, false);
    }
    
    // タイムスタンプ追加（重複防止）
    $name = $name . '-' . time();
    
    return $name . $ext;
}

/**
 * 投稿作成時にpost_authorをwp_anke_users.idに設定
 */
add_action('save_post', 'set_anke_user_as_post_author', 10, 3);
function set_anke_user_as_post_author($post_id, $post, $update) {
    // 自動保存、リビジョンをスキップ
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (wp_is_post_revision($post_id)) return;
    
    // post_typeがpostの場合は何もしない（post_authorは既に設定済み）
    if ($post->post_type === 'post') {
        return;
    }
    
    // 既存投稿の更新時はスキップ（bbsタイプのみ）
    if ($update) return;
    
    // bbsタイプの投稿のみ処理
    if ($post->post_type !== 'bbs') return;
    
    // Ankeログインユーザーを取得
    if (!anke_is_logged_in()) return;
    $anke_user = anke_get_current_user();
    
    // post_authorをwp_anke_users.idに設定
    remove_action('save_post', 'set_anke_user_as_post_author', 10);
    wp_update_post(array(
        'ID' => $post_id,
        'post_author' => $anke_user->id
    ));
    add_action('save_post', 'set_anke_user_as_post_author', 10, 3);
}

/**
 * 投稿編集画面に投稿者選択メタボックスを追加
 */
add_action('add_meta_boxes', 'anke_add_author_metabox');
function anke_add_author_metabox() {
    add_meta_box(
        'anke_author_metabox',
        '投稿者変更',
        'anke_author_metabox_callback',
        'post',
        'side',
        'high'
    );
    
    // コメント表示設定メタボックスを追加
    add_meta_box(
        'anke_comment_settings_metabox',
        'コメント表示設定',
        'anke_comment_settings_metabox_callback',
        'post',
        'side',
        'default'
    );
}

function anke_author_metabox_callback($post) {
    global $wpdb;
    
    // 現在の投稿者
    $current_author = $post->post_author;
    
    // wp_anke_usersから全ユーザーを取得
    $users = $wpdb->get_results("
        SELECT id, user_nicename, status 
        FROM {$wpdb->prefix}anke_users 
        WHERE status IN (1, 2, 3)
        ORDER BY user_nicename ASC
    ");
    
    wp_nonce_field('anke_author_metabox', 'anke_author_metabox_nonce');
    ?>
    <p>
        <label for="anke_post_author">投稿者を選択:</label>
        <select name="anke_post_author" id="anke_post_author" style="width: 100%;">
            <?php foreach ($users as $user): ?>
                <option value="<?php echo esc_attr($user->id); ?>" <?php selected($current_author, $user->id); ?>>
                    <?php echo esc_html($user->user_nicename); ?> 
                    (ID: <?php echo $user->id; ?>)
                </option>
            <?php endforeach; ?>
        </select>
    </p>
    <p class="description">
        投稿者を変更できます。変更すると、この投稿の作成者が変更されます。
    </p>
    <?php
}

/**
 * コメント表示設定メタボックスのコールバック
 */
function anke_comment_settings_metabox_callback($post) {
    global $wpdb;
    
    // wp_anke_vote_optionsからレコードを取得
    $vote_option = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}anke_vote_options WHERE post_id = %d",
        $post->ID
    ));
    
    $comment_enabled = !empty($vote_option);
    
    wp_nonce_field('anke_comment_settings_metabox', 'anke_comment_settings_nonce');
    ?>
    <p>
        <label>
            <input type="checkbox" name="anke_enable_comments" value="1" <?php checked($comment_enabled, true); ?>>
            コメント機能を有効にする
        </label>
    </p>
    <p class="description">
        チェックを入れると、この投稿でコメント機能が有効になります。<br>
        運営者（ID: 33）の投稿では投票機能は表示されませんが、コメント機能は利用できます。
    </p>
    <?php
}

/**
 * 投稿者変更を保存
 */
add_action('save_post', 'anke_save_author_metabox', 10, 2);
function anke_save_author_metabox($post_id, $post) {
    // 自動保存、リビジョンをスキップ
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (wp_is_post_revision($post_id)) return;
    
    // post_typeがpost以外はスキップ
    if ($post->post_type !== 'post') return;
    
    // nonceチェック
    if (!isset($_POST['anke_author_metabox_nonce']) || 
        !wp_verify_nonce($_POST['anke_author_metabox_nonce'], 'anke_author_metabox')) {
        return;
    }
    
    // 投稿者が選択されている場合
    if (isset($_POST['anke_post_author'])) {
        $new_author = intval($_POST['anke_post_author']);
        
        // 投稿者が変更されている場合のみ更新
        if ($new_author !== intval($post->post_author)) {
            remove_action('save_post', 'anke_save_author_metabox', 10);
            wp_update_post(array(
                'ID' => $post_id,
                'post_author' => $new_author
            ));
            add_action('save_post', 'anke_save_author_metabox', 10, 2);
        }
    }
}

/**
 * コメント表示設定を保存
 */
add_action('save_post', 'anke_save_comment_settings_metabox', 10, 2);
function anke_save_comment_settings_metabox($post_id, $post) {
    global $wpdb;
    
    // 自動保存、リビジョンをスキップ
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (wp_is_post_revision($post_id)) return;
    
    // post_typeがpost以外はスキップ
    if ($post->post_type !== 'post') return;
    
    // nonceチェック
    if (!isset($_POST['anke_comment_settings_nonce']) || 
        !wp_verify_nonce($_POST['anke_comment_settings_nonce'], 'anke_comment_settings_metabox')) {
        return;
    }
    
    $enable_comments = isset($_POST['anke_enable_comments']) && $_POST['anke_enable_comments'] == '1';
    
    // 既存のレコードを確認
    $existing = $wpdb->get_row($wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}anke_vote_options WHERE post_id = %d",
        $post_id
    ));
    
    if ($enable_comments && !$existing) {
        // コメント有効化：レコードを追加
        $wpdb->insert(
            $wpdb->prefix . 'anke_vote_options',
            array(
                'post_id' => $post_id,
                'random' => 0,
                'closedate' => '0000-00-00',
                'closetime' => '00:00:00',
                'open' => 1,
                'rand' => 0,
                'multi' => 0,
                'disable_comments' => 0,
                'vote_sum' => 0,
                'last_updated' => current_time('mysql')
            ),
            array('%d', '%d', '%s', '%s', '%d', '%d', '%d', '%d', '%d', '%s')
        );
    } elseif (!$enable_comments && $existing) {
        // コメント無効化：レコードを削除
        $wpdb->delete(
            $wpdb->prefix . 'anke_vote_options',
            array('post_id' => $post_id),
            array('%d')
        );
    }
}

/**
 * キーワードページのリライトルール追加
 */
add_action('init', 'anke_keyword_rewrite_rules');
function anke_keyword_rewrite_rules() {
    // /keyword/{slug}/ のURLを追加
    add_rewrite_rule(
        '^keyword/([^/]+)/?$',
        'index.php?pagename=keyword&keyword_slug=$matches[1]',
        'top'
    );
    
    // /keyword/{slug}/page/{page}/ のURLを追加（ページネーション）
    add_rewrite_rule(
        '^keyword/([^/]+)/page/([0-9]+)/?$',
        'index.php?pagename=keyword&keyword_slug=$matches[1]&paged=$matches[2]',
        'top'
    );
}

/**
 * クエリ変数にkeyword_slugを追加
 */
add_filter('query_vars', 'anke_keyword_query_vars');
function anke_keyword_query_vars($vars) {
    $vars[] = 'keyword_slug';
    return $vars;
}

/**
 * キーワードページのテンプレート指定
 */
add_filter('template_include', 'anke_keyword_template');
function anke_keyword_template($template) {
    if (get_query_var('keyword_slug')) {
        $new_template = locate_template(array('page-keyword.php'));
        if ($new_template) {
            return $new_template;
        }
    }
    return $template;
}

/**
 * Anke独自キーワードを1回のみ表示する関数
 */
if (!function_exists('anke_display_keywords_once')) {
    function anke_display_keywords_once() {
        static $displayed = false;
        
        if ($displayed) {
            return;
        }
        
        $displayed = true;
        
        if (!class_exists('Anke_Keyword_Manager')) {
            return;
        }
        
        global $wpdb, $wp_query;
        
        // メインクエリの投稿IDを取得
        $main_post_id = isset($wp_query->queried_object_id) ? $wp_query->queried_object_id : 0;
        $current_post_id = get_the_ID();
        
        // メインクエリの投稿IDを使用
        $target_post_id = $main_post_id > 0 ? $main_post_id : $current_post_id;
        
        // キャッシュを使わず直接SQLで取得
        $anke_keywords = $wpdb->get_results($wpdb->prepare(
            "SELECT k.*, pk.relevance_score, pk.keyword_type as assignment_type
             FROM {$wpdb->prefix}anke_keywords k
             INNER JOIN {$wpdb->prefix}anke_post_keywords pk ON k.id = pk.keyword_id
             WHERE pk.post_id = %d
             ORDER BY pk.relevance_score DESC",
            $target_post_id
        ));
        
        if (!empty($anke_keywords)) {
            foreach ($anke_keywords as $keyword) {
                $keyword_url = home_url('/keyword/' . $keyword->slug . '/');
                echo "<a href='" . esc_url($keyword_url) . "' class='inline-block bg-gray-100 hover:bg-gray-200 px-2 py-1 rounded-full text-gray-700 text-xs transition-colors'>#" . esc_html($keyword->keyword) . "(" . intval($keyword->post_count) . ")</a> ";
            }
        }
    }
}
// bbscatクエリ変数を追加（タクソノミーURL認識のため）
add_filter('query_vars', 'anke_add_bbscat_query_var');
function anke_add_bbscat_query_var($vars) {
    $vars[] = 'bbscat';
    return $vars;
}

// bbscatテンプレートを読み込むsingle.phpの四角のカテゴリ
add_filter('template_include', 'anke_bbscat_template_include');
function anke_bbscat_template_include($template) {
    if (get_query_var('bbscat')) {
        $new_template = locate_template('taxonomy-bbscat.php');
        if ($new_template) {
            return $new_template;
        }
    }
    return $template;
}

/**
 * ポイント付与ヘルパー関数
 * wp_anke_pointsテーブルにポイントを追加
 * 
 * @param int $user_id ユーザーID（wp_anke_users.id）
 * @param int $point ポイント数
 * @param string $point_type ポイント種類（login, vote, comment, ad, regist, post, work, campaign, incentive）
 * @return bool 成功したらtrue
 */
function anke_add_point($user_id, $point, $point_type) {
    global $wpdb;
    
    // ユーザーIDが0または空の場合は処理しない
    if (empty($user_id) || $user_id == 0) {
        error_log("anke_add_point: user_id が空です");
        return false;
    }
    
    // ポイントが0以下の場合は処理しない
    if ($point <= 0) {
        error_log("anke_add_point: point が0以下です point={$point}");
        return false;
    }
    
    // wp_anke_pointsテーブルに挿入（カラム名は anke_user_id で固定）
    $result = $wpdb->insert(
        $wpdb->prefix . 'anke_points',
        array(
            'anke_user_id' => $user_id,
            'point' => $point,
            'point_type' => $point_type,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ),
        array('%d', '%d', '%s', '%s', '%s')
    );
    
    if ($result === false) {
        error_log("anke_add_point: INSERT失敗 user_id={$user_id}, point={$point}, type={$point_type}, error=" . $wpdb->last_error);
    } else {
        error_log("anke_add_point: 成功 user_id={$user_id}, point={$point}, type={$point_type}, insert_id=" . $wpdb->insert_id);
    }
    
    return $result !== false;
}

/**
 * ポイント種類のラベルを取得
 * 
 * @param string $type ポイント種類
 * @return string ラベル
 */
function anke_get_point_type_label($type) {
    global $wpdb;
    
    // wp_anke_point_settingsテーブルからラベルを取得
    $label = $wpdb->get_var($wpdb->prepare(
        "SELECT label FROM {$wpdb->prefix}anke_point_settings WHERE point_type = %s",
        $type
    ));
    
    // ラベルが見つからない場合はpoint_typeをそのまま返す
    return $label ? $label : $type;
}

/**
 * URLからOGP情報をキャッシュから取得
 * wp_anke_postsテーブルを使用
 */
if (!function_exists('anke_get_ogp_cache')) {
    function anke_get_ogp_cache($url) {
        global $wpdb;
        
        if (empty($url)) {
            return null;
        }
        
        // URLを正規化
        $url = esc_url_raw($url);
        
        // wp_anke_postsテーブルからOGP情報を取得
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT og_title, og_description, og_image, source_url as url 
             FROM {$wpdb->prefix}anke_posts 
             WHERE source_url = %s 
             LIMIT 1",
            $url
        ));
        
        return $result;
    }
}

/**
 * OGP情報をキャッシュに保存
 * wp_anke_postsテーブルを使用
 */
if (!function_exists('anke_save_ogp_cache')) {
    function anke_save_ogp_cache($post_id, $url, $ogp_data = array()) {
        global $wpdb;
        
        // 引数の互換性対応（古い呼び出し方法）
        if (is_array($url)) {
            $ogp_data = $url;
            $url = $post_id;
            $post_id = 0;
        }
        
        if (empty($url) || empty($ogp_data)) {
            return false;
        }
        
        // URLを正規化
        $url = esc_url_raw($url);
        
        // URLが有効でない場合は保存しない
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            error_log('anke_save_ogp_cache: Invalid URL: ' . $url);
            return false;
        }
        
        // 既存のレコードを確認（post_idまたはsource_urlで）
        if ($post_id > 0) {
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}anke_posts WHERE post_id = %d",
                $post_id
            ));
        } else {
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}anke_posts WHERE source_url = %s",
                $url
            ));
        }
        
        $data = array(
            'source_url' => $url,
            'og_title' => isset($ogp_data['og_title']) ? $ogp_data['og_title'] : (isset($ogp_data['title']) ? $ogp_data['title'] : ''),
            'og_description' => isset($ogp_data['og_description']) ? $ogp_data['og_description'] : (isset($ogp_data['description']) ? $ogp_data['description'] : ''),
            'og_image' => isset($ogp_data['og_image']) ? $ogp_data['og_image'] : (isset($ogp_data['image']) ? $ogp_data['image'] : ''),
            'og_cached_at' => current_time('mysql')
        );
        
        // post_idが指定されている場合は追加
        if ($post_id > 0) {
            $data['post_id'] = $post_id;
        }
        
        if ($exists) {
            // 更新
            if ($post_id > 0) {
                $wpdb->update(
                    $wpdb->prefix . 'anke_posts',
                    $data,
                    array('post_id' => $post_id)
                );
            } else {
                $wpdb->update(
                    $wpdb->prefix . 'anke_posts',
                    $data,
                    array('source_url' => $url)
                );
            }
        } else {
            // 新規挿入
            $result = $wpdb->insert(
                $wpdb->prefix . 'anke_posts',
                $data
            );
            
            if ($result === false) {
                error_log('anke_save_ogp_cache: Insert failed for URL: ' . $url . ' Error: ' . $wpdb->last_error);
                return false;
            }
        }
        
        return true;
    }
}

/**
 * 管理画面のメニュー幅をPC表示時のみ200pxに設定
 */
add_action('admin_head', 'anke_custom_admin_menu_width');
function anke_custom_admin_menu_width() {
    echo '<style>
        /* PC表示時のみ適用（768px以上） */
        @media screen and (min-width: 768px) {
            #adminmenu,
            #adminmenu .wp-submenu,
            #adminmenuback,
            #adminmenuwrap {
                width: 200px !important;
            }
            
            #wpcontent,
            #wpfooter {
                margin-left: 200px !important;
            }
            
            /* 折りたたみ時の調整 */
            body.folded #adminmenu,
            body.folded #adminmenu .wp-submenu,
            body.folded #adminmenuback,
            body.folded #adminmenuwrap {
                width: 36px !important;
            }
            
            body.folded #wpcontent,
            body.folded #wpfooter {
                margin-left: 36px !important;
            }
        }
    </style>';
}

/**
 * OGPメタタグを出力（X/Twitter対応）
 */
add_action('wp_head', 'anke_add_ogp_tags', 5);
function anke_add_ogp_tags() {
    if (!is_single()) {
        return;
    }
    
    global $post;
    
    // 基本情報
    $title = get_the_title();
    $description = wp_strip_all_tags(get_the_excerpt());
    if (empty($description)) {
        $description = wp_trim_words(wp_strip_all_tags($post->post_content), 55, '...');
    }
    $url = get_permalink();
    $site_name = get_bloginfo('name');
    
    // アイキャッチ画像を取得（大きく表示されるように最適化）
    $image = '';
    $image_width = 1200;
    $image_height = 628;
    
    if (has_post_thumbnail()) {
        $image = get_the_post_thumbnail_url($post->ID, 'full');
        // 実際の画像サイズを取得
        $image_id = get_post_thumbnail_id($post->ID);
        if ($image_id) {
            $image_meta = wp_get_attachment_metadata($image_id);
            if ($image_meta && isset($image_meta['width']) && isset($image_meta['height'])) {
                $image_width = $image_meta['width'];
                $image_height = $image_meta['height'];
            }
        }
    } else {
        // デフォルト画像
        $image = get_template_directory_uri() . '/images/anke_eye.webp';
    }
    
    // OGPタグ出力
    echo "\n<!-- Open Graph / Facebook -->\n";
    echo '<meta property="og:type" content="article">' . "\n";
    echo '<meta property="og:url" content="' . esc_url($url) . '">' . "\n";
    echo '<meta property="og:title" content="' . esc_attr($title) . '">' . "\n";
    echo '<meta property="og:description" content="' . esc_attr($description) . '">' . "\n";
    echo '<meta property="og:image" content="' . esc_url($image) . '">' . "\n";
    echo '<meta property="og:image:width" content="' . $image_width . '">' . "\n";
    echo '<meta property="og:image:height" content="' . $image_height . '">' . "\n";
    echo '<meta property="og:site_name" content="' . esc_attr($site_name) . '">' . "\n";
    
    // Twitter Card（最大サイズで表示）
    echo "\n<!-- Twitter -->\n";
    echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
    echo '<meta name="twitter:url" content="' . esc_url($url) . '">' . "\n";
    echo '<meta name="twitter:title" content="' . esc_attr($title) . '">' . "\n";
    echo '<meta name="twitter:description" content="' . esc_attr($description) . '">' . "\n";
    echo '<meta name="twitter:image" content="' . esc_url($image) . '">' . "\n";
    echo '<meta name="twitter:image:width" content="' . $image_width . '">' . "\n";
    echo '<meta name="twitter:image:height" content="' . $image_height . '">' . "\n";
    echo '<meta name="twitter:site" content="@anke_jp">' . "\n";
    echo '<meta name="twitter:creator" content="@anke_jp">' . "\n\n";
}

/**
 * 投票結果を表示する共通関数
 * 
 * @param int $post_id 記事ID
 * @param array $vote_results 投票結果の配列
 * @param int|array $selected_choice_id 選択された選択肢ID（単数または配列）
 * @param int $total_votes 総投票数
 */
function anke_render_vote_results($post_id, $vote_results, $selected_choice_id = null, $total_votes = null) {
    if (empty($vote_results)) {
        return;
    }
    
    // 総投票数が指定されていない場合は計算
    if ($total_votes === null) {
        $total_votes = array_sum(array_column($vote_results, 'vote_count'));
    }
    
    // 選択肢IDを配列に統一
    $selected_ids = is_array($selected_choice_id) ? $selected_choice_id : array($selected_choice_id);
    
    // 記事タイトルを取得
    $post_title = get_the_title($post_id);
    ?>
    <div class="mt-2 p-4 border border-gray-300 rounded-lg vote-results">
        <p class="mb-2 text-gray-600 text-sm text-center"><?php echo esc_html($post_title); ?></p>
        <h3 class="mb-4 font-bold text-lg text-center">投票結果</h3>
        <ul class="space-y-4" style="display: block !important; width: 100% !important;">
            <?php foreach ($vote_results as $result): 
                $vote_count = isset($result->vote_count) ? $result->vote_count : 0;
                $percentage = $total_votes > 0 ? round(($vote_count / $total_votes) * 100, 1) : 0;
                $is_selected = in_array($result->id, $selected_ids);
                $choice_class = $is_selected ? 'font-bold text-red-600' : 'font-semibold';
            ?>
                <li class="py-2" style="width: 100% !important; display: block !important;">
                    <div class="flex justify-between items-center mb-2">
                        <span class="<?php echo $choice_class; ?>"><?php echo esc_html($result->choice); ?><?php echo $is_selected ? ' ✓' : ''; ?></span>
                        <span class="ml-2 text-gray-600 text-sm whitespace-nowrap"><?php echo $vote_count; ?>票 (<?php echo $percentage; ?>%)</span>
                    </div>
                    <div class="bg-gray-200 rounded-full w-full h-2.5">
                        <div class="bg-teal-600 rounded-full h-2.5 vote-bar-animate" style="width: 0%" data-width="<?php echo $percentage; ?>"></div>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
        <p class="mt-2 text-gray-500 text-sm text-center">総投票数: <?php echo $total_votes; ?>票</p>
    </div>
    <style>
        .vote-bar-animate {
            transition: width 1s ease-out;
        }
    </style>
    <?php
}
