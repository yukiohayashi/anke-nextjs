<!DOCTYPE html>
<html lang="ja" prefix="og: http://ogp.me/ns# fb: http://ogp.me/ns/fb# article: http://ogp.me/ns/article#">
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="robots" content="index,follow">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
<meta name="google-site-verification" content="rHE7-z8EMCBLQvgJPVMQnv2f_vvj-vygnz4Rv_gW-XA" />
<!-- DNS Prefetch for performance -->
<link rel="dns-prefetch" href="//pagead2.googlesyndication.com">
<link rel="dns-prefetch" href="//www.googleadservices.com">
<link rel="dns-prefetch" href="//googleads.g.doubleclick.net">
<link rel="dns-prefetch" href="//code.jquery.com">
<link rel="dns-prefetch" href="//cdnjs.cloudflare.com">
<link rel="preconnect" href="//pagead2.googlesyndication.com" crossorigin>
<link rel="preconnect" href="//www.googleadservices.com" crossorigin>
<?php
// REST APIやAJAXリクエストの場合はセッションを開始しない
$is_rest_or_ajax = (
    (strpos($_SERVER['REQUEST_URI'] ?? '', '/wp-json/') !== false) ||
    (defined('DOING_AJAX') && DOING_AJAX) ||
    (defined('REST_REQUEST') && REST_REQUEST)
);

if (session_status() == PHP_SESSION_NONE && !$is_rest_or_ajax) {
  session_start();
}
if ( empty( $_COOKIE[ 'user' ] ) ) {
  $session_id = hash( 'sha256', random_bytes( 32 ) );
  setcookie( "user", $session_id, time() + 3600 * 24 * 30 * 24 ); /* 有効期限は24Mです */
  $_COOKIE[ 'user' ] = $session_id; // 即座に$_COOKIEに反映
}

// header.php内で使用するヘルパー関数
function header_display_avatar() {
    if (anke_is_logged_in()) {
        $anke_user = anke_get_current_user();
        if ($anke_user && $anke_user->worker_img_url) {
            echo '<img src="' . esc_url($anke_user->worker_img_url) . '" alt="プロフィール画像" class="rounded-full w-10 h-10 object-cover" id="header-avatar">';
        } else {
            echo '<img src="' . get_template_directory_uri() . '/images/default_avatar.jpg" alt="デフォルトプロフィール画像" class="rounded-full w-10 h-10 object-cover" id="header-avatar">';
        }
    } else {
        echo '<img src="' . get_template_directory_uri() . '/images/default_avatar.jpg" alt="デフォルトプロフィール画像" class="rounded-full w-10 h-10 object-cover" id="header-avatar">';
    }
}
?>

<title>
<?php
if ( is_single() || is_page() ) {
  $page_title = get_the_title();
  $suffix = '｜' . get_bloginfo( 'description' ) . ' anke（アンケ）';
  $max_length = 35;
  
  // タイトルが長すぎる場合は切り詰める
  if (mb_strlen($page_title . $suffix) > $max_length) {
    $available_length = $max_length - mb_strlen($suffix) - 1; // -1は「…」の分
    $page_title = mb_substr($page_title, 0, $available_length) . '…';
  }
  
  echo $page_title . $suffix;
} elseif ( is_archive() ) {
  $archive_title = '';
  if ( is_tax( 'bbscat' ) ) {
    $archive_title = strip_tags( get_queried_object()->name );
  } elseif ( is_tag() ) {
    $archive_title = '「' . single_tag_title( '', false ) . '」に関するアンケートのまとめ';
  }
  $suffix = '｜' . get_bloginfo( 'description' );
  $max_length = 35;
  
  // タイトルが長すぎる場合は切り詰める
  if (mb_strlen($archive_title . $suffix) > $max_length) {
    $available_length = $max_length - mb_strlen($suffix) - 1;
    $archive_title = mb_substr($archive_title, 0, $available_length) . '…';
  }
  
  echo $archive_title . $suffix;
} else {
  $site_title = 'anke（アンケ）｜' . get_bloginfo( 'description' );
  echo mb_substr($site_title, 0, 35);
}
?>
</title>
<?php wp_head(); ?>
<style>
#wpadminbar { display: none !important; }
/* サイドバー開閉状態 */
.L-icon-is-open .Lside_nav { visibility: visible !important; opacity: 1 !important; }
.L-icon-is-open .Lside_wrapper { transform: translateX(0) !important; opacity: 1 !important; }
.L-icon-is-open .Lside_nav-overlay { display: block !important; }
.R-icon-is-open .Rside_nav { visibility: visible !important; opacity: 1 !important; }
.R-icon-is-open .Rside_wrapper { transform: translateX(0) !important; opacity: 1 !important; }
.R-icon-is-open .Rside_nav-overlay { display: block !important; }
/* アバター境界線 */
.avatar_myself img { border: 1px solid #d1d5db !important; border-radius: 50% !important; }
/* two_row画像サイズ - PCビュー */
@media (min-width: 768px) {
  .two-row-image { height: 12rem !important; }
  .two_row { max-width: 100%; }
}
</style>
<link rel="stylesheet" href="<?php echo get_template_directory_uri(); ?>/css/tailwind.css?v=<?php echo filemtime(get_template_directory() . '/css/tailwind.css'); ?>">
</head>
<body <?php body_class(); ?> class="!mt-0">
<header class="md:hidden top-0 right-0 left-0 z-[50] fixed bg-white shadow-md sp" id="fixed-header">
  <?php
  // ページスラッグが"ankeworks"の場合とそれ以外で異なるロゴを表示する
  if ( !is_page( 'ankeworks' ) && !is_singular( 'worker' ) ) {
    ?>
  <div class="top-[15px] left-[3%] z-[101] fixed L_icon_trigger"><a href="#L-nav"><i class="fas fa-search"></i> </a></div>
  <?php } ?>

  <div class="flex flex-col justify-center items-center py-2">
    <?php
    if ( is_page( 'ankeworks' ) || is_singular( 'worker' ) ) {
      ?>
    <div class="mx-auto w-[150px] text-center"><a href="<?php echo esc_url( home_url('/ankeworks/') ); ?>"><img src="<?php echo get_template_directory_uri(); ?>/images/ankeworks.webp" alt="アンケ" class="w-full"></a></div>
    <div class="w-full text-[0.5rem] text-center">アンケート作成＆条件達成で高額報酬ゲット!</div>
    <?php } else { ?>
    <div class="mx-auto w-[150px] text-center"><a href="<?php echo esc_url( home_url('/') ); ?>"><img src="<?php echo get_template_directory_uri(); ?>/images/anke.svg" alt="アンケ" class="w-full"></a></div>
    <div class="w-full text-[0.5rem] text-center">
      <?php bloginfo( 'description' ); ?>
    </div>
    <?php } ?>
  </div>
  
  <div class="block top-[6px] right-[2%] z-[99999] fixed text-center cursor-pointer">
      <div class="R_icon_trigger"><a href="#R-nav"> <span class="avatar_myself">
        <?php header_display_avatar(); ?>
        </span>
        <span class="block z-[999] pt-[2px] text-[7px] text-gray-500 text-center">
        <?php if (anke_is_logged_in()) { ?>
        <?php display_notification_dot(); ?>
        <?php } ?>
        </span></a></div>

    </div>
  </div>
</header>
<header class="top-0 z-[4] flex header_L_sp">
  <div id="L-nav" class="invisible top-0 left-0 z-[9998] fixed opacity-0 w-[85%] h-full transition-all duration-500 Lside_nav">
    <div class="hidden z-[9997] fixed inset-0 bg-black/50 Lside_nav-overlay">
      <a href="#L-nav" class="top-[10px] right-[5%] z-[9999] fixed text-white text-xl"><i class="fas fa-window-close"></i></a>
    </div>
    <div class="z-[9998] relative bg-white opacity-0 px-[5%] pb-5 h-full overflow-y-auto transition-all -translate-x-full duration-500 Lside_wrapper transform">
      <div class="L_menu">
        <?php get_template_part('includes/templates/inc-search-keywords'); ?>
        <?php get_template_part('includes/templates/inc-ranking'); ?>
      </div>
    </div>
    <!-- .cd-navigation-wrapper --> 
  </div>
</header>
<!--end / header_L_sp -->
<header class="z-[5] flex header_R_sp">
  <div id="R-nav" class="invisible top-0 right-0 z-[9998] fixed opacity-0 w-[85%] h-full transition-all duration-500 Rside_nav">
    <div class="z-[9998] relative bg-white opacity-0 px-[5%] pb-5 h-full overflow-y-auto transition-all translate-x-full duration-500 Rside_wrapper transform">
      <div class="R_menu">
        <?php get_template_part('includes/templates/inc-login_regist'); ?>
        <?php get_template_part( "includes/templates/inc-pc_mypagemenu" ); ?>
        <?php if (anke_is_logged_in()) { ?>
        <h3 class="px-2.5 pt-2.5 text-[0.85rem] text-left">運営者からのお知らせ</h3>
        <div class="p-2.5 text-[0.85rem] text-left info_list">
          <?php // ID=2（運営者）の投稿を最新2件取得
          query_posts( 'author=33&post_status=publish&posts_per_page=2' );
          // WordPressループを開始します
          if ( have_posts() ) {
            while ( have_posts() ) {
              the_post();
              ?>
          <a href="<?php the_permalink(); ?>">
          <li class="mb-3 list-none">
            <?php the_title(); ?>
            (<?php echo convert_to_fuzzy_time(get_the_time('Y/m/d H:i')); ?>) </li>
          </a>
          <?php
          }
          }
          // クエリのリセットを行います
          wp_reset_query();
          ?>
        </div>
        <?php } else { ?>
        <div class="bg-white p-[5px] pc">ゲストさん<br>
          所有ポイント:0pt<br>
          今なら<a href="<?php echo esc_url( home_url('/regist/') ); ?>" class="txt__link">新規会員登録</a>で3,000pt獲得</div>
        <ul class="side_menu">

          <div class="p-2.5">
            <?php get_template_part( 'includes/templates/inc-bnr_rectangle' ); ?>
          </div>
        </ul>
        <?php } ?>
      </div>
    </div>
  </div>
  <div class="hidden z-[9997] fixed inset-0 bg-black/50 Rside_nav-overlay">
    <a href="#R-nav" class="top-[10px] left-[5%] z-[9999] fixed text-white text-xl"><i class="fas fa-window-close"></i></a>
  </div>
</header>
<!--end / header_R_sp -->
<header class="md:top-0 md:right-0 md:left-0 md:z-[1000] md:fixed md:flex md:justify-center md:items-center md:bg-white md:shadow-md md:border-t-[5px] md:border-t-pink-400 md:border-b md:border-b-gray-300 md:h-[58px] pc" id="fixed-header">
  <div class="md:flex md:justify-start md:items-center w-full max-w-[1240px] md:h-full">
    <div class="L_icon_trigger sp"><a href="#cd-nav"><i class="fas fa-search"></i> </a></div>
    <?php
    // ページスラッグが"ankeworks"の場合とそれ以外で異なるロゴを表示する
    if ( is_page( 'ankeworks' ) || is_singular( 'worker' ) ) {
      ?>
    <div class="w-[150px]"> <a href="<?php echo esc_url( home_url('/ankeworks/') ); ?>"> <img src="<?php echo get_template_directory_uri(); ?>/images/ankeworks.webp" alt="アンケ"> </a> </div>
    <div class="ml-3 text-[0.5rem] pc"> アンケート作成＆条件達成で高額報酬ゲット! </div>

    <?php }else { ?>
    <div class="w-[150px]"> <a href="<?php echo esc_url( home_url('/') ); ?>"> <img src="<?php echo get_template_directory_uri(); ?>/images/anke.svg" alt="アンケ"> </a> </div>
    <div class="ml-3 text-[0.5rem] pc">
      <?php

      $post_count = wp_count_posts(); // 投稿のステータスごとの数を取得

      if ( isset( $post_count->publish ) ) {
        echo 'アンケート合計数: ' . number_format( $post_count->publish ) . '本';
      }
      ?>
      <br>
      <?php bloginfo( 'description' ); ?>
    </div>
    <?php } ?>
    <div id="header_login_btn" class="hidden md:flex md:items-center md:ml-auto md:px-2.5 md:border-l md:border-l-gray-300">
    <?php if (anke_is_logged_in()) { 
    $anke_user = anke_get_current_user();
    ?>
    <a href="<?php echo esc_url(home_url('')); ?>/mypage/" class="relative flex items-center"><span class="flex items-center avatar_myself">
        <?php
        if ($anke_user && $anke_user->worker_img_url) {
            echo '<img src="' . esc_url($anke_user->worker_img_url) . '" alt="プロフィール画像" class="rounded-full w-10 h-10 object-cover" id="header-avatar">';
        } else {
            echo '<img src="' . get_template_directory_uri() . '/images/default_avatar.jpg" alt="デフォルトプロフィール画像" class="rounded-full w-10 h-10 object-cover" id="header-avatar">';
        }
        display_notification_dot();
        ?>

    </a></span>
<?php } else { ?>
    <a href="<?php echo esc_url( home_url('/member_login/') ); ?>">
        <img src="<?php echo get_template_directory_uri(); ?>/images/default_avatar.jpg" alt="デフォルトアバター" class="rounded-full w-10 h-10 object-cover" id="header-avatar">
    </a>

<?php } ?>

    </div>
    <div class="sp">
      <?php header_display_avatar(); ?>
      <span>
      <?php if (anke_is_logged_in()) { ?>
      <?php display_notification_dot(); ?>
      <?php } ?>
      </span> </div>
    <?php
    // ページスラッグが"ankeworks"の場合とそれ以外で異なるロゴを表示する
    if ( is_page( 'ankeworks' ) || is_singular( 'worker' ) ) {
      ?>
    <a href="<?php echo esc_url( home_url('/') ); ?>" 
       class="hidden md:flex md:items-center md:bg-[#FFE3D6] md:py-[7px] md:pr-[5px] md:pl-[85px] md:border-gray-300 md:border-r md:border-l md:max-w-[200px] md:text-[0.8rem] md:text-left pc"
       style="background-image: url('<?php echo get_template_directory_uri(); ?>/images/anke.webp'); background-repeat: no-repeat; background-position: left center; background-size: 80px;">
      <div>アンケートで<br>
      人と繋がる! <i class="fa-arrow-circle-right fa"></i></div>
    </a>
    <?php } else { ?>
    <a href="<?php echo esc_url( home_url('') ); ?>/ankeworks/" 
       class="hidden md:flex md:items-center md:bg-[#FFE3D6] md:py-[7px] md:pr-[5px] md:pl-[85px] md:border-gray-300 md:border-r md:border-l md:max-w-[200px] md:text-[0.8rem] md:text-left pc"
       style="background-image: url('<?php echo get_template_directory_uri(); ?>/images/ankeworks.webp'); background-repeat: no-repeat; background-position: left center; background-size: 80px;">
      <div>アンケートで<br>
      副業!! <i class="fa-arrow-circle-right fa"></i></div>
    </a>
  <?php } ?>
</header>

<!--end / header PC --> 
<!--end / all-wrapper -->