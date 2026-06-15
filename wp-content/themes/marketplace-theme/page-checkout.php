<?php
/**
 * Template Name: Halaman Checkout Custom
 */

if ( session_status() === PHP_SESSION_NONE ) session_start();

if ( ! is_user_logged_in() ) {
    wp_redirect( home_url('/login') );
    exit;
}

// ============================================================
// HANDLE AJAX: UPDATE QUANTITY DI CART
// ============================================================
if ( isset($_POST['fm_cart_action']) && $_POST['fm_cart_action'] === 'update' ) {
    if ( wp_verify_nonce($_POST['_wpnonce'] ?? '', 'fm_cart_update') ) {
        $key = sanitize_text_field($_POST['cart_key'] ?? '');
        $qty = intval($_POST['qty'] ?? 0);

        if ( isset($_SESSION['foodmarket_cart'][$key]) ) {
            if ( $qty <= 0 ) {
                unset($_SESSION['foodmarket_cart'][$key]);
            } else {
                $pid   = $_SESSION['foodmarket_cart'][$key]['product_id'];
                $stok  = intval(get_post_meta($pid, '_stok_produk', true));
                $_SESSION['foodmarket_cart'][$key]['quantity'] = min($qty, $stok);
            }
        }
    }
    wp_redirect( home_url('/checkout') );
    exit;
}

// ============================================================
// HANDLE: HAPUS SATU ITEM DARI CART
// ============================================================
if ( isset($_GET['hapus']) && wp_verify_nonce($_GET['_wpnonce'] ?? '', 'fm_hapus_cart') ) {
    $key = sanitize_text_field($_GET['hapus']);
    unset($_SESSION['foodmarket_cart'][$key]);
    wp_redirect( home_url('/checkout') );
    exit;
}

$cart_items = isset($_SESSION['foodmarket_cart']) ? $_SESSION['foodmarket_cart'] : array();

get_header();
?>

<main class="max-w-5xl mx-auto px-4 py-10">
    <div class="mb-6">
        <h1 class="text-2xl font-black text-gray-950 tracking-tight">🛒 Keranjang & <span class="text-brand">Checkout</span></h1>
        <p class="text-xs text-gray-500 mt-1">Review pesananmu, ubah kuantitas, lalu selesaikan pembayaran.</p>
    </div>

    <?php if ( empty($cart_items) ) : ?>
        <div class="bg-white border border-gray-100 rounded-3xl p-16 text-center space-y-4 shadow-sm">
            <div class="text-5xl">🛒</div>
            <h3 class="text-base font-bold text-gray-900">Keranjang Belanjamu Masih Kosong</h3>
            <p class="text-xs text-gray-400 max-w-xs mx-auto">Yuk, cari makanan lezat dan tambahkan ke keranjang!</p>
            <a href="<?php echo home_url(); ?>" class="inline-block bg-brand hover:bg-brand-dark text-white text-xs font-bold px-6 py-3 rounded-xl transition shadow-md shadow-brand/10 mt-2">
                Mulai Belanja
            </a>
        </div>

    <?php else : ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">

        <!-- Kiri: Cart + Form -->
        <div class="lg:col-span-2 space-y-6">

            <!-- ===== TABEL CART ===== -->
            <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-50 flex items-center justify-between">
                    <h2 class="text-xs font-bold text-gray-700 uppercase tracking-wider">Item di Keranjang</h2>
                    <span class="text-xs text-gray-400"><?php echo count($cart_items); ?> item</span>
                </div>

                <?php
                $total_belanja = 0;
                foreach ( $cart_items as $cart_key => $item ) :
                    $pid      = $item['product_id'];
                    $harga    = intval(get_post_meta($pid, '_harga_produk', true));
                    $stok     = intval(get_post_meta($pid, '_stok_produk', true));
                    $subtotal = $harga * $item['quantity'];
                    $total_belanja += $subtotal;
                    $foto     = get_the_post_thumbnail_url($pid, 'thumbnail') ?: 'https://images.unsplash.com/photo-1562608284-c5249ff97e40?w=100';
                    $hapus_url = add_query_arg([
                        'hapus'    => $cart_key,
                        '_wpnonce' => wp_create_nonce('fm_hapus_cart'),
                    ], home_url('/checkout'));
                ?>
                    <div class="flex items-center gap-4 px-6 py-4 border-b border-gray-50 last:border-0">
                        <img src="<?php echo esc_url($foto); ?>" class="w-16 h-16 rounded-2xl object-cover border border-gray-100 flex-shrink-0">

                        <div class="flex-1 min-w-0">
                            <a href="<?php echo get_permalink($pid); ?>" class="font-bold text-sm text-gray-900 hover:text-brand transition line-clamp-1">
                                <?php echo get_the_title($pid); ?>
                            </a>
                            <p class="text-xs text-gray-400 mt-0.5">
                                Level: <span class="text-brand font-medium"><?php echo esc_html($item['pedas']); ?></span>
                                <?php if (!empty($item['note'])) : ?>
                                    · <span class="text-yellow-600">📝 <?php echo esc_html($item['note']); ?></span>
                                <?php endif; ?>
                            </p>
                            <p class="text-xs font-bold text-gray-900 mt-1">Rp<?php echo number_format($harga, 0, ',', '.'); ?> / porsi</p>
                        </div>

                        <!-- Kontrol qty -->
                        <form method="POST" action="<?php echo home_url('/checkout'); ?>" class="flex items-center gap-1">
                            <?php wp_nonce_field('fm_cart_update'); ?>
                            <input type="hidden" name="fm_cart_action" value="update">
                            <input type="hidden" name="cart_key" value="<?php echo esc_attr($cart_key); ?>">
                            <button type="submit" name="qty" value="<?php echo $item['quantity'] - 1; ?>"
                                class="w-7 h-7 rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 font-bold text-sm transition flex items-center justify-center">-</button>
                            <span class="w-8 text-center text-sm font-bold text-gray-900"><?php echo $item['quantity']; ?></span>
                            <button type="submit" name="qty" value="<?php echo min($item['quantity'] + 1, $stok); ?>"
                                class="w-7 h-7 rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 font-bold text-sm transition flex items-center justify-center">+</button>
                        </form>

                        <div class="text-right flex-shrink-0 min-w-[80px]">
                            <p class="text-sm font-black text-gray-900">Rp<?php echo number_format($subtotal, 0, ',', '.'); ?></p>
                            <a href="<?php echo esc_url($hapus_url); ?>"
                                onclick="return confirm('Hapus item ini dari keranjang?')"
                                class="text-[10px] text-red-400 hover:text-red-600 font-semibold mt-1 block transition">Hapus</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- ===== FORM PENGIRIMAN ===== -->
            <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm space-y-4">
                <h2 class="text-xs font-bold text-gray-400 uppercase tracking-wider border-b border-gray-50 pb-3">Informasi Pengiriman</h2>
                <form id="checkout-form" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="POST" class="space-y-4">
                    <?php wp_nonce_field('foodmarket_proses_checkout_action', 'foodmarket_checkout_nonce'); ?>
                    <input type="hidden" name="action" value="foodmarket_proses_checkout">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Nama Penerima <span class="text-red-500">*</span></label>
                            <input type="text" name="order_nama" required
                                value="<?php echo esc_attr(wp_get_current_user()->display_name); ?>"
                                class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm focus:border-brand outline-none bg-gray-50 focus:bg-white transition">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Nomor WhatsApp/HP <span class="text-red-500">*</span></label>
                            <input type="tel" name="order_hp" required placeholder="08123456789"
                                class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm focus:border-brand outline-none bg-gray-50 focus:bg-white transition">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Alamat Lengkap Pengiriman <span class="text-red-500">*</span></label>
                        <textarea name="order_alamat" rows="3" required
                            placeholder="Jalan, nomor rumah, RT/RW, kelurahan, kota, atau patokan lokasi..."
                            class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm focus:border-brand outline-none bg-gray-50 focus:bg-white transition"></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Metode Pembayaran</label>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="flex items-center gap-3 p-3 border-2 border-brand bg-brand/5 rounded-xl cursor-pointer">
                                <input type="radio" name="order_pembayaran" value="cod" checked class="accent-brand">
                                <div>
                                    <p class="text-xs font-bold text-gray-800">💵 Bayar di Tempat</p>
                                    <p class="text-[10px] text-gray-400">Cash on Delivery</p>
                                </div>
                            </label>
                            <label class="flex items-center gap-3 p-3 border border-gray-200 rounded-xl cursor-pointer hover:border-brand transition">
                                <input type="radio" name="order_pembayaran" value="transfer" class="accent-brand">
                                <div>
                                    <p class="text-xs font-bold text-gray-800">🏦 Transfer Bank</p>
                                    <p class="text-[10px] text-gray-400">Konfirmasi manual</p>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Catatan Tambahan (Opsional)</label>
                        <input type="text" name="order_catatan" placeholder="Contoh: Hubungi sebelum diantar, titip di pos satpam..."
                            class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm focus:border-brand outline-none bg-gray-50 focus:bg-white transition">
                    </div>
                </form>
            </div>
        </div>

        <!-- Kanan: Ringkasan -->
        <div class="sticky top-24 space-y-4">
            <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm space-y-4">
                <h2 class="text-xs font-bold text-gray-400 uppercase tracking-wider">Ringkasan Pembayaran</h2>

                <?php $ongkir = 10000; $total_akhir = $total_belanja + $ongkir; ?>
                <div class="space-y-2 text-xs">
                    <div class="flex justify-between text-gray-500">
                        <span>Subtotal (<?php echo count($cart_items); ?> item)</span>
                        <span class="font-semibold text-gray-800">Rp<?php echo number_format($total_belanja, 0, ',', '.'); ?></span>
                    </div>
                    <div class="flex justify-between text-gray-500">
                        <span>Ongkos Kirim</span>
                        <span class="font-semibold text-gray-800">Rp<?php echo number_format($ongkir, 0, ',', '.'); ?></span>
                    </div>
                    <div class="border-t border-gray-100 pt-2 flex justify-between font-black text-sm text-gray-900">
                        <span>Total Pembayaran</span>
                        <span class="text-brand text-base">Rp<?php echo number_format($total_akhir, 0, ',', '.'); ?></span>
                    </div>
                </div>

                <button type="submit" form="checkout-form"
                    class="w-full bg-brand hover:bg-brand-dark text-white font-bold py-4 rounded-2xl transition text-sm shadow-md shadow-brand/10">
                    🛵 Pesan Sekarang
                </button>

                <div class="text-center space-y-1">
                    <p class="text-[10px] text-gray-400">🔒 Transaksi aman & terenkripsi</p>
                    <a href="?view=produk ?>" class="block text-xs font-bold text-gray-400 hover:text-brand transition">
                        ← Lanjut Belanja
                    </a>
                </div>
            </div>

            <!-- Info seller -->
            <?php
            $seller_ids = array_unique(array_map(function($item) {
                return get_post_field('post_author', $item['product_id']);
            }, $cart_items));
            ?>
            <div class="bg-gray-50 p-4 rounded-2xl border border-gray-100 space-y-2">
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Dipesan dari</p>
                <?php foreach ($seller_ids as $sid) :
                    $seller = get_user_by('id', $sid);
                    if (!$seller) continue;
                ?>
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-full bg-brand/10 flex items-center justify-center text-xs">🏪</div>
                        <span class="text-xs font-semibold text-gray-700"><?php echo esc_html($seller->display_name); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>
    <?php endif; ?>
</main>

<?php get_footer(); ?>