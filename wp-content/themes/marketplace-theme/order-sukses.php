<?php
/**
 * Template Name: Halaman Order Sukses
 */

if ( session_status() === PHP_SESSION_NONE ) session_start();

if ( ! is_user_logged_in() ) {
    wp_redirect( home_url('/login') );
    exit;
}

// Ambil invoice dari URL
$invoice = isset($_GET['invoice']) ? sanitize_text_field($_GET['invoice']) : '';

// Cari order berdasarkan invoice (post_title)
$order_post = null;
if ( $invoice ) {
    $found = get_posts([
        'post_type'      => 'pesanan',
        'title'          => $invoice,
        'post_status'    => 'any',
        'posts_per_page' => 1,
        'author'         => get_current_user_id(),
    ]);
    if ($found) $order_post = $found[0];
}

get_header();
?>

<main class="max-w-xl mx-auto px-4 py-16">

    <?php if ($order_post) :
        $oid         = $order_post->ID;
        $total       = get_post_meta($oid, '_total_harga', true);
        $nama        = get_post_meta($oid, '_order_nama_penerima', true);
        $alamat      = get_post_meta($oid, '_order_alamat_kirim', true);
        $metode      = get_post_meta($oid, '_order_metode_bayar', true);
        $seller_id   = get_post_meta($oid, '_seller_id', true);
        $seller      = $seller_id ? get_user_by('id', $seller_id) : null;
        $items       = get_post_meta($oid, '_order_items_detail', true);
    ?>

    <div class="text-center mb-8 space-y-3">
        <div class="w-20 h-20 bg-green-50 rounded-full flex items-center justify-center text-4xl mx-auto border-4 border-green-100">
            ✅
        </div>
        <h1 class="text-2xl font-black text-gray-900">Pesanan Berhasil Dikirim!</h1>
        <p class="text-sm text-gray-500">Seller akan segera mengkonfirmasi dan memproses pesananmu.</p>
    </div>

    <!-- Kartu Invoice -->
    <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden mb-6">
        <div class="bg-brand/5 border-b border-brand/10 px-6 py-4 flex items-center justify-between">
            <div>
                <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">Nomor Invoice</p>
                <p class="text-sm font-black text-brand font-mono mt-0.5"><?php echo esc_html($invoice); ?></p>
            </div>
            <div class="text-right">
                <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">Total Bayar</p>
                <p class="text-lg font-black text-gray-900 mt-0.5">Rp<?php echo number_format($total, 0, ',', '.'); ?></p>
            </div>
        </div>

        <!-- Detail item -->
        <?php if (is_array($items) && !empty($items)) : ?>
        <div class="px-6 py-4 space-y-3 border-b border-gray-50">
            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Item Pesanan</p>
            <?php foreach ($items as $it) : ?>
                <div class="flex items-center gap-3">
                    <?php $thumb = get_the_post_thumbnail_url($it['id_produk'], 'thumbnail'); ?>
                    <?php if ($thumb) : ?>
                        <img src="<?php echo esc_url($thumb); ?>" class="w-10 h-10 rounded-xl object-cover border border-gray-100">
                    <?php else : ?>
                        <div class="w-10 h-10 bg-gray-100 rounded-xl flex items-center justify-center">🍽️</div>
                    <?php endif; ?>
                    <div class="flex-1">
                        <p class="text-xs font-bold text-gray-800"><?php echo esc_html($it['nama_produk']); ?></p>
                        <p class="text-[11px] text-gray-400"><?php echo $it['quantity']; ?>x</p>
                    </div>
                    <p class="text-xs font-black text-gray-900">Rp<?php echo number_format($it['harga_satuan'] * $it['quantity'], 0, ',', '.'); ?></p>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Info pengiriman -->
        <div class="px-6 py-4 space-y-2.5">
            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-2">Detail Pengiriman</p>
            <div class="flex gap-2 text-xs">
                <span class="text-gray-400 w-24 flex-shrink-0">Penerima</span>
                <span class="font-semibold text-gray-800"><?php echo esc_html($nama); ?></span>
            </div>
            <div class="flex gap-2 text-xs">
                <span class="text-gray-400 w-24 flex-shrink-0">Alamat</span>
                <span class="font-semibold text-gray-800"><?php echo esc_html($alamat); ?></span>
            </div>
            <div class="flex gap-2 text-xs">
                <span class="text-gray-400 w-24 flex-shrink-0">Pembayaran</span>
                <span class="font-semibold text-gray-800"><?php echo $metode === 'cod' ? '💵 Bayar di Tempat (COD)' : '🏦 Transfer Bank'; ?></span>
            </div>
            <?php if ($seller) : ?>
            <div class="flex gap-2 text-xs">
                <span class="text-gray-400 w-24 flex-shrink-0">Seller</span>
                <span class="font-semibold text-gray-800">🏪 <?php echo esc_html($seller->display_name); ?></span>
            </div>
            <?php endif; ?>
        </div>

        <!-- Status tracking sederhana -->
        <div class="px-6 py-4 border-t border-gray-50 bg-gray-50/30">
            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-3">Status Pesanan</p>
            <div class="flex items-center gap-0">
                <?php
                $steps = [
                    ['icon' => '📝', 'label' => 'Order Dibuat',    'done' => true],
                    ['icon' => '🔔', 'label' => 'Menunggu Seller', 'done' => true],
                    ['icon' => '👩‍🍳', 'label' => 'Diproses',        'done' => false],
                    ['icon' => '🛵', 'label' => 'Dikirim',         'done' => false],
                    ['icon' => '✅', 'label' => 'Selesai',         'done' => false],
                ];
                foreach ($steps as $i => $step) :
                ?>
                    <div class="flex-1 flex flex-col items-center text-center">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm mb-1 <?php echo $step['done'] ? 'bg-brand text-white' : 'bg-gray-100 text-gray-400'; ?>">
                            <?php echo $step['icon']; ?>
                        </div>
                        <p class="text-[9px] text-gray-500 leading-tight"><?php echo $step['label']; ?></p>
                    </div>
                    <?php if ($i < count($steps) - 1) : ?>
                        <div class="w-6 h-0.5 <?php echo $step['done'] ? 'bg-brand' : 'bg-gray-200'; ?> mb-4 flex-shrink-0"></div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <?php if ($metode === 'transfer') : ?>
    <div class="bg-yellow-50 border border-yellow-100 rounded-2xl p-4 mb-6 text-xs text-yellow-800 space-y-1">
        <p class="font-bold">⚠️ Instruksi Transfer Bank</p>
        <p>Silakan transfer sebesar <strong>Rp<?php echo number_format($total, 0, ',', '.'); ?></strong> ke rekening berikut:</p>
        <p class="font-mono bg-yellow-100 px-3 py-2 rounded-lg mt-2">BCA · 1234567890 · a.n FoodMarket</p>
        <p class="text-yellow-600 mt-1">Sertakan nomor invoice <strong><?php echo esc_html($invoice); ?></strong> pada keterangan transfer.</p>
    </div>
    <?php endif; ?>

    <div class="grid grid-cols-2 gap-3">
        <a href="<?php echo home_url('/pesanan-saya'); ?>"
            class="block text-center bg-white border border-brand text-brand hover:bg-brand hover:text-white font-bold py-3 rounded-2xl transition text-sm">
            📋 Lacak Pesanan
        </a>
        <a href="<?php echo home_url(); ?>"
            class="block text-center bg-brand hover:bg-brand-dark text-white font-bold py-3 rounded-2xl transition text-sm shadow-md shadow-brand/10">
            🍔 Pesan Lagi
        </a>
    </div>

    <?php else : ?>

    <!-- Jika tidak ada invoice valid, tampilkan fallback -->
    <div class="text-center space-y-4">
        <div class="text-5xl">✅</div>
        <h1 class="text-2xl font-black text-gray-900">Pesanan Berhasil!</h1>
        <p class="text-sm text-gray-500">Seller akan segera memproses pesananmu.</p>
        <div class="flex gap-3 justify-center pt-4">
            <a href="<?php echo home_url('/pesanan-saya'); ?>"
                class="bg-white border border-brand text-brand hover:bg-brand hover:text-white font-bold px-5 py-3 rounded-2xl transition text-sm">
                📋 Pesanan Saya
            </a>
            <a href="<?php echo home_url(); ?>"
                class="bg-brand hover:bg-brand-dark text-white font-bold px-5 py-3 rounded-2xl transition text-sm shadow-md">
                🍔 Belanja Lagi
            </a>
        </div>
    </div>

    <?php endif; ?>

</main>

<?php get_footer(); ?>