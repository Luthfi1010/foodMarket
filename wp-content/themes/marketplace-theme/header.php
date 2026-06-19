<?php
if ( session_status() === PHP_SESSION_NONE ) {
    session_start();
}

if ( ! is_user_logged_in() && ! is_page('login') && ! is_page('register') ) {
    wp_redirect( home_url('/login') );
    exit;
}

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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <?php wp_head(); ?>
</head>
<body class="bg-gray-50 text-gray-800 font-sans">

<header class="bg-white border-b border-gray-100 sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 h-20 flex items-center justify-between gap-4">

        <!-- Logo -->
        <a href="<?php echo home_url(); ?>" class="text-2xl font-bold text-brand flex items-center gap-1 hover:opacity-90 transition">
            FoodMarket
        </a>

        <!-- Search Bar -->
        <form action="<?php echo home_url('/'); ?>" method="GET" class="flex-1 max-w-xl relative">
            <input type="text"
                   name="s"
                   value="<?php echo esc_attr(get_search_query()); ?>"
                   placeholder="Cari makanan, minuman, resto..."
                   class="w-full bg-gray-100 px-4 py-2.5 pl-10 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-brand/20 border border-transparent focus:border-brand transition">

            <button type="submit" class="absolute left-3.5 top-3 text-gray-400 text-xs hover:text-brand transition">
                🔍
            </button>
        </form>

        <!-- Nav Kanan -->
        <div class="flex items-center gap-6 text-sm">
            <div class="text-gray-600 flex items-center gap-1">
                📍 <span class="font-medium text-gray-900">Jakarta Selatan</span>
            </div>

            <?php if ( is_user_logged_in() ) :
                $current_user = wp_get_current_user(); ?>

                <!-- LOGGED IN -->
                <div class="flex items-center gap-4 text-gray-500 text-lg border-r border-gray-100 pr-4">
                    <a href="<?php echo home_url('/checkout'); ?>" class="hover:text-brand relative text-xl">
                        <i class="fa-solid fa-cart-shopping" style="color: rgb(218, 109, 6);"></i>
                        <?php if ( $total_cart_items > 0 ) : ?>
                            <span class="absolute -top-1.5 -right-2 bg-red-500 text-white text-[10px] font-black w-4 h-4 flex items-center justify-center rounded-full border border-white">
                                <?php echo $total_cart_items; ?>
                            </span>
                        <?php endif; ?>
                    </a>
                    <button class="hover:text-brand text-xl"><i class="fa-solid fa-heart" style="color: #ef4444;"></i></button>
                </div>

                <!-- Dropdown Profil (FIX: hanya satu blok dropdown, dikontrol murni oleh JS) -->
                <div class="relative flex items-center" id="profile-dropdown-wrapper">
                    <button id="profile-btn" class="flex items-center justify-center w-10 h-10 rounded-full bg-gray-100 hover:bg-yellow-200 text-gray-600 transition focus:outline-none">
                        <i class="fa-regular fa-user" style="color: rgb(233, 183, 7);"></i>
                    </button>

                    <div id="profile-dropdown" class="absolute right-0 top-full mt-2 w-48 bg-white rounded-xl shadow-lg border border-gray-100 py-2 hidden z-50 transition-all duration-200">

                        <div class="px-4 py-2 border-b border-gray-50 flex flex-col">
                            <span class="text-[10px] text-gray-400 leading-none">Selamat datang,</span>
                            <span class="text-xs font-bold text-gray-900 mt-1 truncate"><?php echo esc_html($current_user->display_name); ?></span>
                        </div>

                        <?php if ( in_array('administrator', $current_user->roles) || in_array('seller', $current_user->roles) ) : ?>
                            <div class="p-1.5">
                                <a href="<?php echo home_url('/dashboard-seller/?view=dashboard'); ?>"
                                   class="flex items-center gap-2 text-xs text-brand font-semibold px-3 py-2 rounded-lg bg-orange-50 hover:bg-brand hover:text-white transition w-full">
                                    <i class="fa-solid fa-briefcase text-xs"></i> Seller Panel
                                </a>
                            </div>
                            <div class="border-b border-gray-50"></div>
                        <?php endif; ?>

                        <div class="p-1.5">
                            <a href="<?php echo wp_logout_url(home_url('/login')); ?>"
                               class="flex items-center gap-2 text-xs text-red-500 font-semibold px-3 py-2 rounded-lg hover:bg-red-50 transition w-full">
                                <i class="fa-solid fa-right-from-bracket text-xs"></i> Keluar
                            </a>
                        </div>
                    </div>
                </div>

                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const profileBtn = document.getElementById('profile-btn');
                    const profileDropdown = document.getElementById('profile-dropdown');
                    const dropdownWrapper = document.getElementById('profile-dropdown-wrapper');

                    profileBtn.addEventListener('click', function(e) {
                        e.stopPropagation();
                        profileDropdown.classList.toggle('hidden');
                    });

                    document.addEventListener('click', function(e) {
                        if (!dropdownWrapper.contains(e.target)) {
                            profileDropdown.classList.add('hidden');
                        }
                    });
                });
                </script>

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