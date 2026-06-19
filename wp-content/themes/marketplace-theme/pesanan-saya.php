<?php
/**
 * Template Name: Pesanan Saya (Buyer)
 */

if ( session_status() === PHP_SESSION_NONE ) session_start();

if ( ! is_user_logged_in() ) {
    wp_redirect( home_url('/login') );
    exit;
}

// Hanya buyer (subscriber) yang akses halaman ini
$current_user = wp_get_current_user();
if ( in_array('seller', $current_user->roles) || in_array('administrator', $current_user->roles) ) {
    wp_redirect( home_url('/dashboard-seller/?view=pesanan') );
    exit;
}

$current_user_id = get_current_user_id();
$tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'aktif';

// Query pesanan milik buyer ini
function fm_buyer_order_query($buyer_id, $statuses, $paged = 1) {
    return new WP_Query([
        'post_type'      => 'pesanan',
        'post_status'    => $statuses,
        'posts_per_page' => 10,
        'paged'          => $paged,
        'author'         => $buyer_id,
    ]);
}

$paged = max(1, get_query_var('paged'));

if ($tab === 'selesai') {
    $query = fm_buyer_order_query($current_user_id, ['completed', 'cancelled'], $paged);
    $tab_label = 'Riwayat Pesanan';
} else {
    $query = fm_buyer_order_query($current_user_id, ['publish', 'processing'], $paged);
    $tab_label = 'Pesanan Aktif';
}

// Status label & warna
function fm_status_badge($status) {
    $map = [
        'publish'    => ['label' => '🔔 Menunggu Konfirmasi', 'class' => 'bg-yellow-50 text-yellow-700 border-yellow-100'],
        'processing' => ['label' => '👩‍🍳 Sedang Diproses',     'class' => 'bg-blue-50 text-blue-700 border-blue-100'],
        'completed'  => ['label' => '✅ Selesai',               'class' => 'bg-green-50 text-green-700 border-green-100'],
        'cancelled'  => ['label' => '❌ Dibatalkan',            'class' => 'bg-red-50 text-red-600 border-red-100'],
    ];
    return $map[$status] ?? ['label' => $status, 'class' => 'bg-gray-50 text-gray-600 border-gray-100'];
}

get_header();
?>

<main class="max-w-3xl mx-auto px-4 py-10">

    <div class="mb-6">
        <h1 class="text-2xl font-black text-gray-950">📋 Pesanan Saya</h1>
        <p class="text-xs text-gray-500 mt-1">Lacak dan pantau semua pesanan makananmu di sini.</p>
    </div>

    <!-- Tab -->
    <div class="flex gap-2 mb-6 bg-gray-100 p-1 rounded-2xl w-fit">
        <a href="?tab=aktif"
            class="px-4 py-2 rounded-xl text-xs font-bold transition <?php echo $tab === 'aktif' ? 'bg-white text-brand shadow-sm' : 'text-gray-500 hover:text-gray-700'; ?>">
            Aktif
        </a>
        <a href="?tab=selesai"
            class="px-4 py-2 rounded-xl text-xs font-bold transition <?php echo $tab === 'selesai' ? 'bg-white text-brand shadow-sm' : 'text-gray-500 hover:text-gray-700'; ?>">
            Riwayat
        </a>
    </div>

    <div class="space-y-4">
        <?php if ($query->have_posts()) :
            while ($query->have_posts()) : $query->the_post();
                $oid          = get_the_ID();
                $invoice      = get_the_title($oid);
                $total        = get_post_meta($oid, '_total_harga', true);
                $detail       = get_post_meta($oid, '_detail_item', true) ?: '-';
                $alamat       = get_post_meta($oid, '_order_alamat_kirim', true);
                $metode       = get_post_meta($oid, '_order_metode_bayar', true);
                $seller_id    = get_post_meta($oid, '_seller_id', true);
                $seller       = $seller_id ? get_user_by('id', $seller_id) : null;
                $status       = get_post_status($oid);
                $waktu        = get_the_date('d M Y, H:i', $oid);
                $badge        = fm_status_badge($status);
                $items_detail = get_post_meta($oid, '_order_items_detail', true);
        ?>
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">

                <!-- Header order -->
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-50 bg-gray-50/50">
                    <div>
                        <p class="text-[10px] text-gray-400 font-mono"><?php echo esc_html($invoice); ?></p>
                        <p class="text-[11px] text-gray-500 mt-0.5">📅 <?php echo $waktu; ?></p>
                    </div>
                    <span class="text-[10px] font-bold px-3 py-1 rounded-full border <?php echo $badge['class']; ?>">
                        <?php echo $badge['label']; ?>
                    </span>
                </div>

                <!-- Detail item -->
                <div class="px-5 py-4 space-y-3">
                    <?php if (is_array($items_detail) && !empty($items_detail)) :
                        foreach ($items_detail as $it) : ?>
                            <div class="flex items-center gap-3">
                                <?php $thumb = get_the_post_thumbnail_url($it['id_produk'], 'thumbnail'); ?>
                                <?php if ($thumb) : ?>
                                    <img src="<?php echo esc_url($thumb); ?>" class="w-10 h-10 rounded-xl object-cover border border-gray-100">
                                <?php else : ?>
                                    <div class="w-10 h-10 rounded-xl bg-gray-100 flex items-center justify-center text-base">🍽️</div>
                                <?php endif; ?>
                                <div class="flex-1">
                                    <p class="text-xs font-bold text-gray-800"><?php echo esc_html($it['nama_produk']); ?></p>
                                    <p class="text-[11px] text-gray-400"><?php echo $it['quantity']; ?>x · Rp<?php echo number_format($it['harga_satuan'], 0, ',', '.'); ?></p>
                                </div>
                                <p class="text-xs font-black text-gray-900">Rp<?php echo number_format($it['harga_satuan'] * $it['quantity'], 0, ',', '.'); ?></p>
                            </div>
                        <?php endforeach;
                    else : ?>
                        <p class="text-xs text-gray-500"><?php echo esc_html($detail); ?></p>
                    <?php endif; ?>
                </div>

                <!-- Footer -->
                <div class="px-5 py-4 border-t border-gray-50 bg-gray-50/30 space-y-2">
                    <div class="flex items-center justify-between">
                        <div class="space-y-0.5">
                            <?php if ($seller) : ?>
                                <p class="text-[11px] text-gray-400">🏪 Dari: <span class="font-semibold text-gray-700"><?php echo esc_html($seller->display_name); ?></span></p>
                            <?php endif; ?>
                            <?php if ($metode) : ?>
                                <p class="text-[11px] text-gray-400">💳 <?php echo $metode === 'cod' ? 'Bayar di Tempat (COD)' : 'Transfer Bank'; ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="text-right">
                            <p class="text-[10px] text-gray-400">Total Pembayaran</p>
                            <p class="text-sm font-black text-brand">Rp<?php echo number_format($total, 0, ',', '.'); ?></p>
                        </div>
                    </div>

                    <?php if ($status === 'publish') : ?>
                        <div class="bg-yellow-50 text-yellow-700 text-[11px] font-medium px-3 py-2 rounded-xl border border-yellow-100 mt-2">
                            ⏳ Pesanan sedang menunggu konfirmasi dari seller. Mohon tunggu sebentar.
                        </div>
                    <?php elseif ($status === 'processing') : ?>
                        <div class="bg-blue-50 text-blue-700 text-[11px] font-medium px-3 py-2 rounded-xl border border-blue-100 mt-2">
                            👩‍🍳 Seller sedang memproses dan menyiapkan pesananmu!
                        </div>
                    <?php elseif ($status === 'completed') : ?>
                        <div class="bg-green-50 text-green-700 text-[11px] font-medium px-3 py-2 rounded-xl border border-green-100 mt-2">
                            ✅ Pesanan selesai. Terima kasih sudah memesan di FoodMarket!
                        </div>
                    <?php elseif ($status === 'cancelled') : ?>
                        <div class="bg-red-50 text-red-600 text-[11px] font-medium px-3 py-2 rounded-xl border border-red-100 mt-2">
                            ❌ Pesanan ini dibatalkan oleh seller.
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        <?php endwhile; wp_reset_postdata();
        else : ?>
            <div class="bg-white rounded-2xl border border-gray-100 p-12 text-center shadow-sm space-y-3">
                <div class="text-4xl"><?php echo $tab === 'aktif' ? '📭' : '📜'; ?></div>
                <p class="text-sm font-bold text-gray-800">
                    <?php echo $tab === 'aktif' ? 'Belum ada pesanan aktif.' : 'Belum ada riwayat pesanan.'; ?>
                </p>
                <a href="<?php echo home_url(); ?>" class="inline-block bg-brand hover:bg-brand-dark text-white text-xs font-bold px-5 py-2.5 rounded-xl transition">
                    Mulai Pesan Sekarang
                </a>
            </div>
        <?php endif; ?>

        <!-- Pagination -->
        <?php if ($query->max_num_pages > 1) : ?>
            <div class="pt-2 text-center text-xs">
                <?php echo paginate_links(['total' => $query->max_num_pages, 'current' => $paged, 'type' => 'plain']); ?>
            </div>
        <?php endif; ?>

            <a href="<?php echo home_url('/'); ?>" class="inline-block bg-gray-200 hover:bg-gray-300 text-gray-800 text-xs font-bold px-5 py-2.5 rounded-xl transition">
                Kembali
            </a>
    </div>

</main>

<?php get_footer(); ?>