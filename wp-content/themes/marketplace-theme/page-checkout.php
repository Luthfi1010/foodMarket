<?php
/**
 * Template Name: Halaman Checkout Custom
 */

// Proteksi Hak Akses: Pembeli harus login dulu untuk checkout
if ( !is_user_logged_in() ) {
    wp_redirect( home_url('/login') );
    exit;
}

$cart_items = isset($_SESSION['foodmarket_cart']) ? $_SESSION['foodmarket_cart'] : array();

get_header(); ?>

<main class="max-w-5xl mx-auto px-4 py-10">
    <div class="mb-6">
        <h1 class="text-2xl font-black text-gray-950 tracking-tight">Selesaikan <span class="text-brand">Pesananmu</span></h1>
        <p class="text-xs text-gray-500">Periksa kembali makanan pilihanmu dan isi alamat pengantaran.</p>
    </div>

    <?php if ( empty($cart_items) ) : ?>
        <div class="bg-white border border-gray-100 rounded-3xl p-12 text-center space-y-4 shadow-sm">
            <div class="text-4xl">🛒</div>
            <h3 class="text-sm font-bold text-gray-900">Keranjang Belanjamu Kosong</h3>
            <p class="text-xs text-gray-400 max-w-xs mx-auto">Yuk, kembali ke beranda dan cari makanan atau minuman lezat kesukaanmu!</p>
            <a href="<?php echo home_url(); ?>" class="inline-block bg-brand hover:bg-brand-dark text-white text-xs font-bold px-6 py-3 rounded-xl transition shadow-md shadow-brand/10">
                Cari Kuliner
            </a>
        </div>
    <?php else : ?>
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
            
            <div class="lg:col-span-2 space-y-6">
                
                <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm space-y-4">
                    <h2 class="text-xs font-bold text-gray-400 uppercase tracking-wider">Review Menu Kuliner</h2>
                    <div class="divide-y divide-gray-50">
                        <?php 
                        $total_belanja = 0;
                        foreach ( $cart_items as $key => $item ) : 
                            $product_id = $item['product_id'];
                            $harga      = get_post_meta($product_id, '_harga_produk', true);
                            $subtotal   = $harga * $item['quantity'];
                            $total_belanja += $subtotal;
                            
                            $foto = get_the_post_thumbnail_url($product_id, 'thumbnail');
                            if (!$foto) {
                                $foto = 'https://images.unsplash.com/photo-1562608284-c5249ff97e40?w=100';
                            }
                        ?>
                            <div class="flex items-start gap-4 py-4 first:pt-0 last:pb-0">
                                <img src="<?php echo esc_url($foto); ?>" class="w-16 h-16 rounded-xl object-cover border border-gray-100 shadow-sm flex-shrink-0">
                                <div class="flex-1 min-w-0">
                                    <h4 class="font-bold text-sm text-gray-900 truncate"><?php echo get_the_title($product_id); ?></h4>
                                    <p class="text-xs text-gray-400 mt-0.5">Level: <span class="text-brand font-medium"><?php echo esc_html($item['pedas']); ?></span></p>
                                    <?php if(!empty($item['note'])): ?>
                                        <p class="text-[11px] text-yellow-600 bg-yellow-50 px-2 py-0.5 rounded mt-1 inline-block">📝 <?php echo esc_html($item['note']); ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="text-right flex-shrink-0">
                                    <span class="text-xs text-gray-400 block"><?php echo $item['quantity']; ?>x</span>
                                    <span class="text-sm font-bold text-gray-900">Rp<?php echo number_format($subtotal, 0, ',', '.'); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm space-y-4">
                    <h2 class="text-xs font-bold text-gray-400 uppercase tracking-wider">Informasi Pengiriman</h2>
                    
                    <form id="checkout-form" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="POST" class="space-y-4">
                        <?php wp_nonce_field('foodmarket_proses_checkout_action', 'foodmarket_checkout_nonce'); ?>
                        <input type="hidden" name="action" value="foodmarket_proses_checkout">
                        
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Nama Penerima</label>
                            <input type="text" name="order_nama" required value="<?php echo esc_attr(wp_get_current_user()->display_name); ?>" class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm focus:border-brand outline-none bg-gray-50/50 focus:bg-white transition">
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Nomor WhatsApp/HP</label>
                                <input type="tel" name="order_hp" required placeholder="Contoh: 08123456789" class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm focus:border-brand outline-none bg-gray-50/50 focus:bg-white transition">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Metode Pembayaran</label>
                                <select name="order_pembayaran" class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm focus:border-brand outline-none bg-gray-50/50 focus:bg-white transition">
                                    <option value="cod">Bayar di Tempat (COD)</option>
                                    <option value="transfer">Transfer Bank (Manual)</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Alamat Lengkap Rumah / Lokasi Pengantaran</label>
                            <textarea name="order_alamat" rows="3" required placeholder="Tuliskan nama jalan, nomor rumah, RT/RW, atau patokan lokasi..." class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm focus:border-brand outline-none bg-gray-50/50 focus:bg-white transition"></textarea>
                        </div>
                    </form>
                </div>
            </div>

            <div class="space-y-4 sticky top-24">
                <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm space-y-4">
                    <h2 class="text-xs font-bold text-gray-400 uppercase tracking-wider">Ringkasan Pembayaran</h2>
                    
                    <div class="space-y-2 text-xs divide-y divide-gray-50 pt-1">
                        <div class="flex justify-between text-gray-600 pb-2">
                            <span>Total Harga Makanan</span>
                            <span class="font-semibold text-gray-900">Rp<?php echo number_format($total_belanja, 0, ',', '.'); ?></span>
                        </div>
                        <div class="flex justify-between text-gray-600 py-2">
                            <span>Ongkos Kirim (Flat)</span>
                            <?php $ongkir = 10000; // Contoh tarif flat pengantaran ?>
                            <span class="font-semibold text-gray-900">Rp<?php echo number_format($ongkir, 0, ',', '.'); ?></span>
                        </div>
                        <div class="flex justify-between text-sm py-3 font-black text-gray-950">
                            <span>Total Pembayaran</span>
                            <span class="text-brand text-base">Rp<?php echo number_format($total_belanja + $ongkir, 0, ',', '.'); ?></span>
                        </div>
                    </div>

                    <button type="submit" form="checkout-form" class="w-full bg-brand hover:bg-brand-dark text-white font-bold py-4 rounded-2xl transition text-sm shadow-md shadow-brand/10 block text-center">
                        🛵 Pesan Sekarang
                    </button>
                </div>
                
                <a href="<?php echo home_url(); ?>" class="block text-center text-xs font-bold text-gray-400 hover:text-brand transition">
                    ← Tambah Menu Lain
                </a>
            </div>

        </div>

    <?php endif; ?>
</main>

<?php get_header(); ?>