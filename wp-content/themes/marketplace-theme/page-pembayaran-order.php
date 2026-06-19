<?php
/**
 * Template Name: Pesanan Saya (Buyer)
 */

if ( session_status() === PHP_SESSION_NONE ) session_start();
if ( ! is_user_logged_in() ) { wp_redirect( home_url('/login') ); exit; }

$current_user = wp_get_current_user();
if ( in_array('seller', $current_user->roles) || in_array('administrator', $current_user->roles) ) {
    wp_redirect( home_url('/dashboard-seller/?view=pesanan') ); exit;
}

$current_user_id = get_current_user_id();
$tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'semua';

function fm_buyer_order_query($buyer_id, $statuses, $paged = 1) {
    return new WP_Query(['post_type'=>'pesanan','post_status'=>$statuses,'posts_per_page'=>10,'paged'=>$paged,'author'=>$buyer_id]);
}

$paged = max(1, get_query_var('paged'));

switch ($tab) {
    case 'diproses': $query = fm_buyer_order_query($current_user_id, ['publish','processing'], $paged); break;
    case 'dikirim':  $query = fm_buyer_order_query($current_user_id, ['processing'], $paged); break;
    case 'selesai':  $query = fm_buyer_order_query($current_user_id, ['completed','cancelled'], $paged); break;
    default:         $query = fm_buyer_order_query($current_user_id, ['publish','processing','completed','cancelled'], $paged);
}

function fm_status_badge($status) {
    $map = [
        'publish'    => ['label' => '🔔 Diproses',  'class' => 'bg-yellow-50 text-yellow-700 border-yellow-100'],
        'processing' => ['label' => '👩‍🍳 Dikirim',  'class' => 'bg-blue-50 text-blue-700 border-blue-100'],
        'completed'  => ['label' => '✅ Selesai',    'class' => 'bg-green-50 text-green-700 border-green-100'],
        'cancelled'  => ['label' => '❌ Dibatalkan', 'class' => 'bg-red-50 text-red-600 border-red-100'],
    ];
    return $map[$status] ?? ['label' => $status, 'class' => 'bg-gray-50 text-gray-600 border-gray-100'];
}

get_header();
?>

<main class="max-w-3xl mx-auto px-4 py-8">

    <div class="mb-5">
        <h1 class="text-xl font-black text-gray-950">📋 Pesanan Saya</h1>
        <p class="text-xs text-gray-500 mt-1">Lacak dan pantau semua pesanan makananmu.</p>
    </div>

    <!-- Tab -->
    <div class="flex gap-1 mb-6 bg-white p-1 rounded-2xl border border-gray-100 fm-card w-fit overflow-x-auto fm-scrollbar">
        <?php
        $tabs = ['semua'=>'Semua', 'diproses'=>'Diproses', 'dikirim'=>'Dikirim', 'selesai'=>'Selesai'];
        foreach ($tabs as $key => $label) : ?>
            <a href="?tab=<?php echo $key; ?>"
                class="px-4 py-2 rounded-xl text-xs font-bold transition whitespace-nowrap <?php echo $tab===$key ? 'bg-brand text-white shadow-sm shadow-brand/20' : 'text-gray-500 hover:text-gray-700'; ?>">
                <?php echo $label; ?>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="space-y-4">
        <?php if ($query->have_posts()) : while ($query->have_posts()) : $query->the_post();
            $oid = get_the_ID();
            $invoice = get_the_title($oid);
            $total = get_post_meta($oid, '_total_harga', true);
            $detail = get_post_meta($oid, '_detail_item', true) ?: '-';
            $seller_id = get_post_meta($oid, '_seller_id', true);
            $seller = $seller_id ? get_user_by('id', $seller_id) : null;
            $status = get_post_status($oid);
            $waktu = get_the_date('d M Y', $oid);
            $badge = fm_status_badge($status);
            $items_detail = get_post_meta($oid, '_order_items_detail', true);
            $first_item = is_array($items_detail) && !empty($items_detail) ? $items_detail[0] : null;
        ?>
            <a href="<?php echo home_url('/pesanan-saya/?order='.$oid); ?>" class="block bg-white rounded-2xl border border-gray-100 fm-card overflow-hidden hover:shadow-md transition">
                <div class="flex items-center justify-between px-5 py-3 border-b border-gray-50 bg-gray-50/50">
                    <p class="text-[11px] text-gray-400 font-mono"><?php echo esc_html($invoice); ?> · <?php echo $waktu; ?></p>
                    <span class="text-[10px] font-bold px-2.5 py-1 rounded-full border <?php echo $badge['class']; ?>"><?php echo $badge['label']; ?></span>
                </div>
                <div class="px-5 py-4 flex items-center gap-3">
                    <?php if ($first_item) :
                        $thumb = get_the_post_thumbnail_url($first_item['id_produk'], 'thumbnail');
                    ?>
                        <?php if ($thumb) : ?>
                            <img src="<?php echo esc_url($thumb); ?>" class="w-12 h-12 rounded-xl object-cover border border-gray-100">
                        <?php else : ?>
                            <div class="w-12 h-12 bg-gray-100 rounded-xl flex items-center justify-center text-lg">🍽️</div>
                        <?php endif; ?>
                        <div class="flex-1">
                            <p class="text-xs font-bold text-gray-800"><?php echo esc_html($first_item['nama_produk']); ?><?php if (count($items_detail) > 1) echo ' +'.(count($items_detail)-1).' lainnya'; ?></p>
                            <?php if ($seller) : ?><p class="text-[11px] text-gray-400 mt-0.5">🏪 <?php echo esc_html($seller->display_name); ?></p><?php endif; ?>
                        </div>
                    <?php else : ?>
                        <div class="w-12 h-12 bg-gray-100 rounded-xl flex items-center justify-center text-lg">🍽️</div>
                        <div class="flex-1"><p class="text-xs font-bold text-gray-800"><?php echo esc_html($detail); ?></p></div>
                    <?php endif; ?>
                    <div class="text-right">
                        <p class="text-[10px] text-gray-400">Total</p>
                        <p class="text-sm font-black text-brand">Rp<?php echo number_format($total,0,',','.'); ?></p>
                    </div>
                </div>
            </a>
        <?php endwhile; wp_reset_postdata();
        else : ?>
            <div class="bg-white rounded-2xl border border-gray-100 fm-card p-12 text-center space-y-3">
                <div class="text-4xl">📭</div>
                <p class="text-sm font-bold text-gray-800">Belum ada pesanan di kategori ini.</p>
                <a href="<?php echo home_url(); ?>" class="inline-block bg-brand hover:bg-brand-dark text-white text-xs font-bold px-5 py-2.5 rounded-xl transition">Mulai Pesan Sekarang</a>
            </div>
        <?php endif; ?>

        <?php if ($query->max_num_pages > 1) : ?>
            <div class="pt-2 text-center text-xs"><?php echo paginate_links(['total'=>$query->max_num_pages,'current'=>$paged,'type'=>'plain']); ?></div>
        <?php endif; ?>
    </div>

</main>

<?php get_footer(); ?>