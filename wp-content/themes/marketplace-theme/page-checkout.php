<?php
/**
 * Template Name: Halaman Checkout Custom
 */

if ( session_status() === PHP_SESSION_NONE ) session_start();
if ( ! is_user_logged_in() ) { wp_redirect( home_url('/login') ); exit; }

if ( isset($_POST['fm_cart_action']) && $_POST['fm_cart_action'] === 'update' ) {
    if ( wp_verify_nonce($_POST['_wpnonce'] ?? '', 'fm_cart_update') ) {
        $key = sanitize_text_field($_POST['cart_key'] ?? '');
        $qty = intval($_POST['qty'] ?? 0);
        if ( isset($_SESSION['foodmarket_cart'][$key]) ) {
            if ( $qty <= 0 ) {
                unset($_SESSION['foodmarket_cart'][$key]);
            } else {
                $pid  = $_SESSION['foodmarket_cart'][$key]['product_id'];
                $stok = intval(get_post_meta($pid, '_stok_produk', true));
                $_SESSION['foodmarket_cart'][$key]['quantity'] = min($qty, $stok);
            }
        }
    }
    wp_redirect( home_url('/checkout') ); exit;
}

if ( isset($_GET['hapus']) && wp_verify_nonce($_GET['_wpnonce'] ?? '', 'fm_hapus_cart') ) {
    unset($_SESSION['foodmarket_cart'][sanitize_text_field($_GET['hapus'])]);
    wp_redirect( home_url('/checkout') ); exit;
}

$cart_items = isset($_SESSION['foodmarket_cart']) ? $_SESSION['foodmarket_cart'] : array();

get_header();
?>

<main class="max-w-5xl mx-auto px-4 py-8">

    <!-- Stepper -->
    <div class="flex items-center justify-center mb-8 max-w-lg mx-auto">
        <?php
        $steps = [
            ['icon' => '🛒', 'label' => 'Keranjang',  'active' => true],
            ['icon' => '📍', 'label' => 'Alamat',      'active' => true],
            ['icon' => '💳', 'label' => 'Pembayaran',  'active' => false],
            ['icon' => '✅', 'label' => 'Selesai',      'active' => false],
        ];
        foreach ($steps as $i => $s) : ?>
            <div class="flex flex-col items-center flex-1">
                <div class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold <?php echo $s['active'] ? 'bg-brand text-white shadow-md shadow-brand/20' : 'bg-gray-100 text-gray-400'; ?>">
                    <?php echo $s['icon']; ?>
                </div>
                <span class="text-[10px] font-semibold mt-1.5 <?php echo $s['active'] ? 'text-brand' : 'text-gray-400'; ?>"><?php echo $s['label']; ?></span>
            </div>
            <?php if ($i < count($steps)-1) : ?>
                <div class="h-0.5 flex-1 -mt-5 <?php echo $steps[$i+1]['active'] ? 'bg-brand' : 'bg-gray-200'; ?>"></div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <?php if ( empty($cart_items) ) : ?>
        <div class="bg-white border border-gray-100 rounded-3xl p-16 text-center space-y-4 fm-card max-w-md mx-auto">
            <div class="text-5xl">🛒</div>
            <h3 class="text-base font-bold text-gray-900">Keranjang Belanjamu Masih Kosong</h3>
            <p class="text-xs text-gray-400">Yuk, cari makanan lezat dan tambahkan ke keranjang!</p>
            <a href="<?php echo home_url(); ?>" class="inline-block bg-brand hover:bg-brand-dark text-white text-xs font-bold px-6 py-3 rounded-xl transition shadow-md shadow-brand/20 mt-2">Mulai Belanja</a>
        </div>

    <?php else : ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">

        <div class="lg:col-span-2 space-y-5">

            <!-- Ringkasan Pesanan -->
            <div class="bg-white rounded-2xl border border-gray-100 fm-card overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-50">
                    <h2 class="text-sm font-bold text-gray-800">Ringkasan Pesanan</h2>
                </div>
                <?php
                $total_belanja = 0;
                foreach ( $cart_items as $cart_key => $item ) :
                    $pid      = $item['product_id'];
                    $harga    = intval(get_post_meta($pid, '_harga_produk', true));
                    $stok     = intval(get_post_meta($pid, '_stok_produk', true));
                    $subtotal = $harga * $item['quantity'];
                    $total_belanja += $subtotal;
                    $foto = get_the_post_thumbnail_url($pid, 'thumbnail') ?: 'https://images.unsplash.com/photo-1562608284-c5249ff97e40?w=100';
                    $hapus_url = add_query_arg(['hapus'=>$cart_key,'_wpnonce'=>wp_create_nonce('fm_hapus_cart')], home_url('/checkout'));
                ?>
                <div class="flex items-center gap-3 px-5 py-3.5 border-b border-gray-50 last:border-0">
                    <img src="<?php echo esc_url($foto); ?>" class="w-12 h-12 rounded-xl object-cover border border-gray-100 flex-shrink-0">
                    <div class="flex-1 min-w-0">
                        <a href="<?php echo get_permalink($pid); ?>" class="font-bold text-xs text-gray-900 hover:text-brand transition line-clamp-1"><?php echo get_the_title($pid); ?></a>
                        <p class="text-[11px] text-gray-400 mt-0.5">Level: <span class="text-brand font-medium"><?php echo esc_html($item['pedas']); ?></span></p>
                    </div>
                    <form method="POST" action="<?php echo home_url('/checkout'); ?>" class="flex items-center gap-1">
                        <?php wp_nonce_field('fm_cart_update'); ?>
                        <input type="hidden" name="fm_cart_action" value="update">
                        <input type="hidden" name="cart_key" value="<?php echo esc_attr($cart_key); ?>">
                        <button type="submit" name="qty" value="<?php echo $item['quantity']-1; ?>" class="w-6 h-6 rounded-md border border-gray-200 text-gray-500 hover:bg-gray-50 font-bold text-xs">-</button>
                        <span class="w-6 text-center text-xs font-bold text-gray-900"><?php echo $item['quantity']; ?></span>
                        <button type="submit" name="qty" value="<?php echo min($item['quantity']+1,$stok); ?>" class="w-6 h-6 rounded-md border border-gray-200 text-gray-500 hover:bg-gray-50 font-bold text-xs">+</button>
                    </form>
                    <div class="text-right flex-shrink-0 min-w-[70px]">
                        <p class="text-xs font-black text-gray-900">Rp<?php echo number_format($subtotal,0,',','.'); ?></p>
                        <a href="<?php echo esc_url($hapus_url); ?>" onclick="return confirm('Hapus item ini?')" class="text-[9px] text-red-400 hover:text-red-600 font-semibold">Hapus</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Alamat -->
            <div class="bg-white p-5 rounded-2xl border border-gray-100 fm-card space-y-4">
                <h2 class="text-sm font-bold text-gray-800 border-b border-gray-50 pb-3">📍 Alamat Pengiriman</h2>
                <form id="checkout-form" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="POST" class="space-y-4">
                    <?php wp_nonce_field('foodmarket_proses_checkout_action', 'foodmarket_checkout_nonce'); ?>
                    <input type="hidden" name="action" value="foodmarket_proses_checkout">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Nama Penerima <span class="text-red-500">*</span></label>
                            <input type="text" name="order_nama" required value="<?php echo esc_attr(wp_get_current_user()->display_name); ?>"
                                class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:border-brand outline-none bg-gray-50 focus:bg-white transition">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">No. WhatsApp <span class="text-red-500">*</span></label>
                            <input type="tel" name="order_hp" required placeholder="08123456789"
                                class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:border-brand outline-none bg-gray-50 focus:bg-white transition">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Alamat Lengkap <span class="text-red-500">*</span></label>
                        <textarea name="order_alamat" rows="3" required placeholder="Jalan, nomor rumah, RT/RW, kota..."
                            class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:border-brand outline-none bg-gray-50 focus:bg-white transition"></textarea>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-2">💳 Metode Pembayaran</label>
                        <div class="space-y-2">
                            <label class="flex items-center gap-3 p-3 border-2 border-brand bg-brand/5 rounded-xl cursor-pointer">
                                <input type="radio" name="order_pembayaran" value="transfer" checked class="accent-brand">
                                <span class="text-xs font-bold text-gray-800">🏦 Transfer Bank</span>
                            </label>
                            <label class="flex items-center gap-3 p-3 border border-gray-200 rounded-xl cursor-pointer hover:border-brand transition">
                                <input type="radio" name="order_pembayaran" value="ewallet" class="accent-brand">
                                <span class="text-xs font-bold text-gray-800">📱 E-Wallet (OVO, Dana, GoPay)</span>
                            </label>
                            <label class="flex items-center gap-3 p-3 border border-gray-200 rounded-xl cursor-pointer hover:border-brand transition">
                                <input type="radio" name="order_pembayaran" value="cod" class="accent-brand">
                                <span class="text-xs font-bold text-gray-800">💵 COD (Bayar di Tempat)</span>
                            </label>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Ringkasan Pembayaran -->
        <div class="sticky top-24 space-y-4">
            <div class="bg-white p-5 rounded-2xl border border-gray-100 fm-card space-y-3">
                <h2 class="text-sm font-bold text-gray-800 border-b border-gray-50 pb-3">Total Pembayaran</h2>
                <?php $ongkir = 8000; $biaya_layanan = 2000; $total_akhir = $total_belanja + $ongkir + $biaya_layanan; ?>
                <div class="space-y-2 text-xs">
                    <div class="flex justify-between text-gray-500"><span>Subtotal</span><span class="font-semibold text-gray-800">Rp<?php echo number_format($total_belanja,0,',','.'); ?></span></div>
                    <div class="flex justify-between text-gray-500"><span>Ongkos Kirim</span><span class="font-semibold text-gray-800">Rp<?php echo number_format($ongkir,0,',','.'); ?></span></div>
                    <div class="flex justify-between text-gray-500"><span>Biaya Layanan</span><span class="font-semibold text-gray-800">Rp<?php echo number_format($biaya_layanan,0,',','.'); ?></span></div>
                    <div class="border-t border-gray-100 pt-2.5 flex justify-between font-black text-sm text-gray-900">
                        <span>Total</span><span class="text-brand text-base">Rp<?php echo number_format($total_akhir,0,',','.'); ?></span>
                    </div>
                </div>
                <button type="submit" form="checkout-form" class="w-full bg-brand hover:bg-brand-dark text-white font-bold py-3.5 rounded-2xl transition text-sm shadow-md shadow-brand/20">
                    Buat Pesanan
                </button>
                <p class="text-[10px] text-gray-400 text-center flex items-center justify-center gap-1">🔒 Transaksi aman & terpercaya</p>
                <a href="<?php echo home_url(); ?>" class="block text-center text-xs font-bold text-gray-400 hover:text-brand transition">← Lanjut Belanja</a>
            </div>
        </div>

    </div>
    <?php endif; ?>
</main>

<?php get_footer(); ?>