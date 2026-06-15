<?php
// ============================================================
// FIX #1: Mulai session di awal, sebelum output apapun
// Tanpa ini, $_SESSION['foodmarket_cart'] selalu kosong
// ============================================================
if ( session_status() === PHP_SESSION_NONE ) {
    session_start();
}

// Proteksi halaman: redirect ke login jika belum login
// (kecuali sedang di halaman login / register itu sendiri)
if ( ! is_user_logged_in() && ! is_page('login') && ! is_page('register') ) {
    wp_redirect( home_url('/login') );
    exit;
}

// Hitung total item di keranjang untuk badge
$total_cart_items = 0;
if ( isset($_SESSION['foodmarket_cart']) && ! empty($_SESSION['foodmarket_cart']) ) {
    foreach ( $_SESSION['foodmarket_cart'] as $item ) {
        $total_cart_items += intval($item['quantity']);
    }
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php wp_head(); ?>
</head>
<body class="bg-gray-50 text-gray-800 font-sans">

<header class="bg-white border-b border-gray-100 sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 h-20 flex items-center justify-between gap-4">

        <!-- Logo -->
        <a href="<?php echo home_url(); ?>" class="text-2xl font-bold text-brand flex items-center gap-1 hover:opacity-90 transition">
            🍊 FoodMarket
        </a>

        <!-- Search Bar -->
        <div class="flex-1 max-w-xl relative">
            <input type="text" placeholder="Cari makanan, minuman, resto..."
                class="w-full bg-gray-100 px-4 py-2.5 pl-10 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-brand/20 border border-transparent focus:border-brand transition">
            <span class="absolute left-3.5 top-3.5 text-gray-400 text-xs">🔍</span>
        </div>

        <!-- Nav Kanan -->
        <div class="flex items-center gap-6 text-sm">
            <div class="text-gray-600 flex items-center gap-1">
                📍 <span class="font-medium text-gray-900">Jakarta Selatan</span>
            </div>

            <?php if ( is_user_logged_in() ) :
                $current_user = wp_get_current_user(); ?>

                <!-- LOGGED IN -->
                <div class="flex items-center gap-4 text-gray-500 text-lg border-r border-gray-100 pr-4">
                    <!-- Keranjang dengan badge -->
                    <a href="<?php echo home_url('/checkout'); ?>" class="hover:text-brand relative text-xl">
                        🛒
                        <?php if ( $total_cart_items > 0 ) : ?>
                            <span class="absolute -top-1.5 -right-2 bg-red-500 text-white text-[10px] font-black w-4 h-4 flex items-center justify-center rounded-full border border-white">
                                <?php echo $total_cart_items; ?>
                            </span>
                        <?php endif; ?>
                    </a>
                    <button class="hover:text-brand text-xl">❤️</button>
                </div>

                <div class="flex items-center gap-3">
                    <div class="flex flex-col text-right">
                        <span class="text-[11px] text-gray-400 leading-none">Selamat datang,</span>
                        <span class="text-xs font-bold text-gray-900 mt-0.5"><?php echo esc_html($current_user->display_name); ?></span>
                    </div>

                    <?php if ( in_array('administrator', $current_user->roles) || in_array('seller', $current_user->roles) ) : ?>
                        <!-- FIX #5: URL yang benar adalah ?view=dashboard, bukan ?view=tambah-produk -->
                        <a href="<?php echo home_url('/dashboard-seller/?view=dashboard'); ?>"
                            class="text-xs bg-orange-50 hover:bg-brand hover:text-white text-brand font-bold px-3 py-2 rounded-xl transition flex items-center gap-1">
                            💼 Seller Panel
                        </a>
                    <?php endif; ?>

                    <a href="<?php echo wp_logout_url(home_url('/login')); ?>"
                        class="text-xs text-red-500 font-semibold hover:underline ml-1">Keluar</a>
                </div>

            <?php else : ?>

                <!-- BELUM LOGIN -->
                <div class="flex items-center gap-4 text-gray-400 text-lg border-r border-gray-100 pr-4">
                    <a href="<?php echo home_url('/checkout'); ?>" class="hover:text-brand relative text-xl flex items-center">
                        🛒
                        <span id="cart-count-badge"
                            class="<?php echo ($total_cart_items == 0) ? 'hidden' : ''; ?> absolute -top-1.5 -right-2 bg-red-500 text-white text-[10px] font-black w-4 h-4 flex items-center justify-center rounded-full border border-white">
                            <?php echo $total_cart_items; ?>
                        </span>
                    </a>
                    <button class="hover:text-brand text-xl">❤️</button>
                </div>

                <div class="flex items-center gap-3">
                    <a href="<?php echo home_url('/login'); ?>" class="text-xs text-gray-600 font-bold hover:text-brand transition">Masuk</a>
                    <a href="<?php echo home_url('/register'); ?>" class="text-brand font-bold hover:underline">Daftar</a>
                </div>

            <?php endif; ?>
        </div>
    </div>
</header>