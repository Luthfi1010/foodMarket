<?php
/**
 * Template Name: Dashboard Seller
 */

if ( session_status() === PHP_SESSION_NONE ) session_start();

if ( ! is_user_logged_in() || ( ! in_array('seller', wp_get_current_user()->roles) && ! in_array('administrator', wp_get_current_user()->roles) ) ) {
    wp_redirect( home_url('/login') );
    exit;
}

$current_user_id = get_current_user_id();
$is_admin = current_user_can('administrator');

function fm_seller_meta_query( $is_admin, $seller_id ) {
    if ( $is_admin ) return array();
    return array(array('key'=>'_seller_id','value'=>$seller_id,'compare'=>'='));
}

// INTERCEPTOR: HAPUS PRODUK
if ( isset($_GET['view'],$_GET['action'],$_GET['id'],$_GET['_wpnonce']) && $_GET['view']==='produk' && $_GET['action']==='delete' ) {
    $prod_id = intval($_GET['id']);
    if ( wp_verify_nonce($_GET['_wpnonce'], 'delete_prod_'.$current_user_id) ) {
        $post_author = get_post_field('post_author', $prod_id);
        if ( $post_author == $current_user_id || $is_admin ) {
            wp_trash_post($prod_id);
            wp_redirect( home_url('/dashboard-seller/?view=produk&status=hapus-sukses') ); exit;
        }
    }
    wp_die('Akses ditolak atau token kedaluwarsa.');
}

// INTERCEPTOR: UPDATE STATUS PESANAN
if ( isset($_GET['view'],$_GET['action'],$_GET['id'],$_GET['status_to'],$_GET['_wpnonce']) && in_array($_GET['view'],['pesanan','history']) && $_GET['action']==='update_status' ) {
    $order_id = intval($_GET['id']);
    $new_status = sanitize_text_field($_GET['status_to']);
    $redirect_view = sanitize_text_field($_GET['view']);
    $allowed = ['processing','completed','cancelled'];
    if ( ! in_array($new_status, $allowed) ) wp_die('Status tidak dikenali.');

    if ( wp_verify_nonce($_GET['_wpnonce'], 'update_order_'.$order_id) ) {
        $order_seller_id = get_post_meta($order_id, '_seller_id', true);
        if ( $order_seller_id == $current_user_id || $is_admin ) {
            wp_update_post(array('ID'=>$order_id,'post_status'=>$new_status));
            wp_redirect( home_url('/dashboard-seller/?view='.$redirect_view.'&status=order-updated') ); exit;
        }
        wp_die('Anda tidak berhak mengubah status pesanan ini.');
    }
    wp_die('Token keamanan gagal diverifikasi.');
}

get_header();
$view   = isset($_GET['view'])   ? sanitize_text_field($_GET['view'])   : 'dashboard';
$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
?>

<div class="flex min-h-[calc(100vh-72px)] bg-gray-50">

    <!-- Sidebar -->
    <aside class="w-60 bg-white border-r border-gray-100 p-5 hidden md:flex flex-col">
        <div class="flex items-center gap-2 mb-6 pb-4 border-b border-gray-50">
            <div class="w-9 h-9 bg-brand rounded-xl flex items-center justify-center text-base">🍕</div>
            <div>
                <p class="text-sm font-black text-gray-900 leading-none">FoodMarket</p>
                <p class="text-[10px] text-gray-400">Seller Panel</p>
            </div>
        </div>

        <!-- Profile mini -->
        <div class="flex items-center gap-2.5 mb-5 p-3 bg-gray-50 rounded-xl">
            <div class="w-9 h-9 rounded-full bg-brand-light flex items-center justify-center text-sm">🧑‍🍳</div>
            <div class="min-w-0">
                <p class="text-xs font-bold text-gray-800 truncate"><?php echo esc_html(wp_get_current_user()->display_name); ?></p>
                <p class="text-[10px] text-gray-400">Lihat Toko →</p>
            </div>
        </div>

        <nav class="space-y-1 flex-1">
            <?php
            $nav_items = [
                'dashboard' => ['📊','Dashboard'],
                'produk'    => ['🍔','Produk'],
                'pesanan'   => ['📦','Pesanan'],
                'history'   => ['🕒','Riwayat'],
            ];
            foreach ( $nav_items as $key => $d ) :
                $active = $view === $key ? 'bg-brand text-white shadow-sm shadow-brand/20 font-bold' : 'text-gray-500 hover:bg-gray-50 font-medium';
            ?>
                <a href="?view=<?php echo $key; ?>" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm transition <?php echo $active; ?>">
                    <?php echo $d[0]; ?> <?php echo $d[1]; ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <a href="<?php echo wp_logout_url(home_url('/login')); ?>" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm text-red-500 hover:bg-red-50 transition font-medium mt-2">
            ⏻ Logout
        </a>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 p-6 md:p-8 overflow-x-hidden">

        <?php if ( isset($_GET['status']) ) :
            $st = sanitize_text_field($_GET['status']);
            $is_sukses = in_array($st, ['sukses','update-sukses','hapus-sukses','order-updated']);
            $messages = [
                'sukses'=>'✨ Berhasil menambahkan menu kuliner baru!',
                'update-sukses'=>'✅ Perubahan data produk berhasil disimpan!',
                'hapus-sukses'=>'🗑️ Produk berhasil dihapus dari daftar etalase.',
                'order-updated'=>'📦 Status transaksi berhasil diperbarui!',
                'gagal'=>'❌ Gagal memproses data. Pastikan semua field wajib terisi.',
            ];
            $color = $is_sukses ? 'bg-green-50 text-green-600 border-green-100' : 'bg-red-50 text-red-600 border-red-100';
        ?>
            <div class="mb-6 p-4 rounded-xl text-xs font-semibold border <?php echo $color; ?>"><?php echo $messages[$st] ?? 'Proses selesai.'; ?></div>
        <?php endif; ?>

        <?php
        if ( $view === 'produk' ) :

            if ( $action === 'add' ) : ?>
                <div class="max-w-lg mx-auto bg-white p-6 rounded-2xl border border-gray-100 fm-card space-y-5">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                        <div><h1 class="text-base font-bold text-gray-900">➕ Tambah Menu Baru</h1><p class="text-[11px] text-gray-400">Masukkan info menu makanan Anda.</p></div>
                        <a href="?view=produk" class="text-xs text-gray-400 hover:text-brand font-medium">← Kembali</a>
                    </div>
                    <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="POST" enctype="multipart/form-data" class="space-y-4">
                        <input type="hidden" name="action" value="foodmarket_tambah_produk">
                        <?php wp_nonce_field('foodmarket_tambah_produk_action', 'foodmarket_nonce'); ?>
                        <div><label class="text-xs font-bold text-gray-700 block mb-1">Nama Menu <span class="text-red-500">*</span></label>
                            <input type="text" name="nama_produk" required placeholder="Contoh: Nasi Goreng Gila" class="w-full text-xs border border-gray-200 rounded-xl px-3.5 py-2.5 bg-gray-50 focus:bg-white focus:border-brand focus:outline-none transition-all"></div>
                        <div><label class="text-xs font-bold text-gray-700 block mb-1">Deskripsi Menu</label>
                            <textarea name="deskripsi_produk" rows="3" placeholder="Komposisi, level pedas, atau porsi..." class="w-full text-xs border border-gray-200 rounded-xl px-3.5 py-2.5 bg-gray-50 focus:bg-white focus:border-brand focus:outline-none transition-all"></textarea></div>
                        <div class="grid grid-cols-2 gap-3">
                            <div><label class="text-xs font-bold text-gray-700 block mb-1">Harga (Rp) <span class="text-red-500">*</span></label>
                                <input type="number" name="harga_produk" required placeholder="20000" min="0" class="w-full text-xs border border-gray-200 rounded-xl px-3.5 py-2.5 bg-gray-50 focus:bg-white focus:border-brand focus:outline-none transition-all"></div>
                            <div><label class="text-xs font-bold text-gray-700 block mb-1">Stok Porsi <span class="text-red-500">*</span></label>
                                <input type="number" name="stok_produk" required placeholder="30" min="0" class="w-full text-xs border border-gray-200 rounded-xl px-3.5 py-2.5 bg-gray-50 focus:bg-white focus:border-brand focus:outline-none transition-all"></div>
                        </div>
                        <div><label class="text-xs font-bold text-gray-700 block mb-1">Kategori</label>
                            <select name="kategori_produk" class="w-full text-xs border border-gray-200 rounded-xl px-3.5 py-2.5 bg-gray-50 focus:bg-white focus:border-brand focus:outline-none transition-all">
                                <option value="makanan-berat">Makanan Berat</option><option value="cemilan">Cemilan / Snack</option><option value="minuman">Minuman</option>
                            </select></div>
                        <div><label class="text-xs font-bold text-gray-700 block mb-1">Foto Menu</label>
                            <input type="file" name="foto_produk" accept="image/*" class="w-full text-xs text-gray-500 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-brand/10 file:text-brand hover:file:bg-brand/20"></div>
                        <button type="submit" class="w-full bg-brand hover:bg-brand-dark text-white font-bold py-2.5 rounded-xl text-xs transition shadow-sm">🚀 Publish Menu Jualan</button>
                    </form>
                </div>

            <?php elseif ( $action === 'edit' && isset($_GET['id']) ) :
                $product_id = intval($_GET['id']);
                $post_author = get_post_field('post_author', $product_id);
                if ( $post_author != $current_user_id && ! $is_admin ) :
                    echo '<p class="text-red-500 text-xs font-semibold text-center bg-red-50 p-4 rounded-xl border border-red-100">🔒 Akses Ditolak.</p>';
                else :
                    $post_data = get_post($product_id);
                    $harga_lama = get_post_meta($product_id, '_harga_produk', true);
                    $stok_lama = get_post_meta($product_id, '_stok_produk', true);
                    $thumb_url = get_the_post_thumbnail_url($product_id, 'thumbnail');
                    $terms = wp_get_post_terms($product_id, 'kategori_makanan');
                    $kat_lama = (!empty($terms) && !is_wp_error($terms)) ? $terms[0]->slug : 'makanan-berat';
                ?>
                    <div class="max-w-lg mx-auto bg-white p-6 rounded-2xl border border-gray-100 fm-card space-y-5">
                        <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                            <div><h1 class="text-base font-bold text-gray-900">✏️ Ubah Data Menu</h1><p class="text-[11px] text-gray-400"><?php echo esc_html($post_data->post_title); ?></p></div>
                            <a href="?view=produk" class="text-xs text-gray-400 hover:text-brand font-medium">← Kembali</a>
                        </div>
                        <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="POST" enctype="multipart/form-data" class="space-y-4">
                            <input type="hidden" name="action" value="foodmarket_edit_produk">
                            <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                            <?php wp_nonce_field('foodmarket_edit_produk_action', 'foodmarket_nonce'); ?>
                            <div><label class="text-xs font-bold text-gray-700 block mb-1">Nama Menu <span class="text-red-500">*</span></label>
                                <input type="text" name="nama_produk" required value="<?php echo esc_attr($post_data->post_title); ?>" class="w-full text-xs border border-gray-200 rounded-xl px-3.5 py-2.5 bg-gray-50 focus:bg-white focus:border-brand focus:outline-none transition-all"></div>
                            <div><label class="text-xs font-bold text-gray-700 block mb-1">Deskripsi Menu</label>
                                <textarea name="deskripsi_produk" rows="3" class="w-full text-xs border border-gray-200 rounded-xl px-3.5 py-2.5 bg-gray-50 focus:bg-white focus:border-brand focus:outline-none transition-all"><?php echo esc_textarea($post_data->post_content); ?></textarea></div>
                            <div class="grid grid-cols-2 gap-3">
                                <div><label class="text-xs font-bold text-gray-700 block mb-1">Harga (Rp) <span class="text-red-500">*</span></label>
                                    <input type="number" name="harga_produk" required value="<?php echo esc_attr($harga_lama); ?>" class="w-full text-xs border border-gray-200 rounded-xl px-3.5 py-2.5 bg-gray-50 focus:bg-white focus:border-brand focus:outline-none transition-all"></div>
                                <div><label class="text-xs font-bold text-gray-700 block mb-1">Stok Porsi <span class="text-red-500">*</span></label>
                                    <input type="number" name="stok_produk" required value="<?php echo esc_attr($stok_lama); ?>" class="w-full text-xs border border-gray-200 rounded-xl px-3.5 py-2.5 bg-gray-50 focus:bg-white focus:border-brand focus:outline-none transition-all"></div>
                            </div>
                            <div><label class="text-xs font-bold text-gray-700 block mb-1">Kategori</label>
                                <select name="kategori_produk" class="w-full text-xs border border-gray-200 rounded-xl px-3.5 py-2.5 bg-gray-50 focus:bg-white focus:border-brand focus:outline-none transition-all">
                                    <option value="makanan-berat" <?php selected($kat_lama,'makanan-berat'); ?>>Makanan Berat</option>
                                    <option value="cemilan" <?php selected($kat_lama,'cemilan'); ?>>Cemilan / Snack</option>
                                    <option value="minuman" <?php selected($kat_lama,'minuman'); ?>>Minuman</option>
                                </select></div>
                            <div><label class="text-xs font-bold text-gray-700 block mb-1">Foto Menu</label>
                                <?php if ($thumb_url): ?><img src="<?php echo esc_url($thumb_url); ?>" class="w-12 h-12 object-cover rounded-xl border border-gray-100 mb-2"><?php endif; ?>
                                <input type="file" name="foto_produk" accept="image/*" class="w-full text-xs text-gray-500 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-brand/10 file:text-brand hover:file:bg-brand/20"></div>
                            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 rounded-xl text-xs transition shadow-sm">💾 Simpan Perubahan</button>
                        </form>
                    </div>
                <?php endif;

            else :
                $paged = max(1, get_query_var('paged'));
                $args = array('post_type'=>'produk','post_status'=>array('publish','pending','draft'),'posts_per_page'=>10,'paged'=>$paged);
                if ( ! $is_admin ) $args['author'] = $current_user_id;
                $query = new WP_Query($args);
                ?>
                <div class="space-y-5">
                    <div class="flex items-center justify-between flex-wrap gap-4">
                        <div><h1 class="text-xl font-black text-gray-900">Daftar Menu Kuliner</h1><p class="text-xs text-gray-500 mt-0.5">Kelola, tambah, modifikasi, atau hapus produk Anda.</p></div>
                        <a href="?view=produk&action=add" class="bg-brand hover:bg-brand-dark text-white font-bold text-xs px-4 py-2.5 rounded-xl transition shadow-md shadow-brand/20 inline-flex items-center gap-1.5">➕ Tambah Menu</a>
                    </div>
                    <div class="bg-white rounded-2xl border border-gray-100 fm-card overflow-hidden">
                        <div class="overflow-x-auto fm-scrollbar">
                            <table class="w-full text-left border-collapse">
                                <thead><tr class="bg-gray-50/75 text-gray-500 text-[11px] font-bold uppercase tracking-wider border-b border-gray-100">
                                    <th class="px-5 py-3.5">Menu</th><th class="px-5 py-3.5">Kategori</th><th class="px-5 py-3.5">Harga</th><th class="px-5 py-3.5">Stok</th><th class="px-5 py-3.5">Status</th><th class="px-5 py-3.5 text-center">Aksi</th>
                                </tr></thead>
                                <tbody class="divide-y divide-gray-50 text-xs text-gray-700">
                                    <?php if ($query->have_posts()) : while ($query->have_posts()) : $query->the_post();
                                        $pid=get_the_ID(); $harga=get_post_meta($pid,'_harga_produk',true); $stok=get_post_meta($pid,'_stok_produk',true);
                                        $thumb=get_the_post_thumbnail_url($pid,'thumbnail')?:'https://images.unsplash.com/photo-1562608284-c5249ff97e40?w=150';
                                        $terms=get_the_terms($pid,'kategori_makanan'); $kat=($terms&&!is_wp_error($terms))?$terms[0]->name:'-';
                                        $status=get_post_status(); $stok_class=intval($stok)<5?'text-red-500':'text-gray-600';
                                    ?>
                                        <tr class="hover:bg-gray-50/50 transition">
                                            <td class="px-5 py-3.5"><div class="flex items-center gap-3"><img src="<?php echo esc_url($thumb); ?>" class="w-10 h-10 object-cover rounded-xl border border-gray-100"><span class="font-bold text-gray-900"><?php the_title(); ?></span></div></td>
                                            <td class="px-5 py-3.5 text-gray-500"><?php echo esc_html($kat); ?></td>
                                            <td class="px-5 py-3.5 font-black text-gray-900">Rp<?php echo number_format($harga,0,',','.'); ?></td>
                                            <td class="px-5 py-3.5 font-semibold <?php echo $stok_class; ?>"><?php echo esc_html($stok); ?> Porsi</td>
                                            <td class="px-5 py-3.5"><span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded-full <?php echo $status==='publish'?'bg-green-50 text-green-600':'bg-yellow-50 text-yellow-600'; ?>"><?php echo $status; ?></span></td>
                                            <td class="px-5 py-3.5"><div class="flex items-center justify-center gap-2">
                                                <a href="?view=produk&action=edit&id=<?php echo $pid; ?>" class="p-1.5 bg-gray-50 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition text-gray-400">✏️</a>
                                                <a href="?view=produk&action=delete&id=<?php echo $pid; ?>&_wpnonce=<?php echo wp_create_nonce('delete_prod_'.$current_user_id); ?>" onclick="return confirm('Yakin hapus produk ini?')" class="p-1.5 bg-gray-50 hover:bg-red-50 hover:text-red-600 rounded-lg transition text-gray-400">🗑️</a>
                                            </div></td>
                                        </tr>
                                    <?php endwhile; wp_reset_postdata(); else: ?>
                                        <tr><td colspan="6" class="px-5 py-12 text-center text-gray-400 font-medium">Belum ada menu terdaftar.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif;

        elseif ( $view === 'pesanan' ) :
            $paged = max(1, get_query_var('paged'));
            $order_query = new WP_Query(['post_type'=>'pesanan','post_status'=>array('publish','processing'),'posts_per_page'=>10,'paged'=>$paged,'meta_query'=>fm_seller_meta_query($is_admin,$current_user_id)]);
            ?>
            <div class="space-y-5">
                <div><h1 class="text-xl font-black text-gray-900">Pesanan Masuk Aktif</h1><p class="text-xs text-gray-500 mt-0.5">Pesanan baru yang perlu dikonfirmasi.</p></div>
                <div class="bg-white rounded-2xl border border-gray-100 fm-card overflow-hidden">
                    <div class="overflow-x-auto fm-scrollbar">
                        <table class="w-full text-left border-collapse">
                            <thead><tr class="bg-gray-50/75 text-gray-500 text-[11px] font-bold uppercase tracking-wider border-b border-gray-100">
                                <th class="px-5 py-3.5">ID / Pembeli</th><th class="px-5 py-3.5">Detail Menu</th><th class="px-5 py-3.5">Total</th><th class="px-5 py-3.5">Status</th><th class="px-5 py-3.5 text-center">Aksi</th>
                            </tr></thead>
                            <tbody class="divide-y divide-gray-50 text-xs text-gray-700">
                                <?php if ($order_query->have_posts()) : while ($order_query->have_posts()) : $order_query->the_post();
                                    $oid=get_the_ID(); $nama=get_post_meta($oid,'_nama_pembeli',true)?:'Pelanggan'; $detail=get_post_meta($oid,'_detail_item',true)?:'-'; $total=get_post_meta($oid,'_total_harga',true)?:0;
                                    $status=get_post_status($oid); $badge=$status==='processing'?'bg-blue-50 text-blue-600':'bg-yellow-50 text-yellow-600';
                                ?>
                                    <tr class="hover:bg-gray-50/50 transition">
                                        <td class="px-5 py-3.5"><div class="font-bold text-gray-900">#ORD-<?php echo $oid; ?></div><div class="text-[11px] text-gray-400 mt-0.5"><?php echo esc_html($nama); ?></div></td>
                                        <td class="px-5 py-3.5 font-medium text-gray-600"><?php echo esc_html($detail); ?></td>
                                        <td class="px-5 py-3.5 font-black text-gray-900">Rp<?php echo number_format($total,0,',','.'); ?></td>
                                        <td class="px-5 py-3.5"><span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded-full <?php echo $badge; ?>"><?php echo $status==='processing'?'👩‍🍳 Diproses':'🔔 Baru'; ?></span></td>
                                        <td class="px-5 py-3.5"><div class="flex items-center justify-center gap-1.5">
                                            <?php if ($status!=='processing'): ?><a href="?view=pesanan&action=update_status&id=<?php echo $oid; ?>&status_to=processing&_wpnonce=<?php echo wp_create_nonce('update_order_'.$oid); ?>" class="px-2 py-1 bg-blue-50 text-blue-600 hover:bg-blue-100 rounded-lg text-[10px] font-bold transition">Proses</a><?php endif; ?>
                                            <a href="?view=pesanan&action=update_status&id=<?php echo $oid; ?>&status_to=completed&_wpnonce=<?php echo wp_create_nonce('update_order_'.$oid); ?>" class="px-2 py-1 bg-green-50 text-green-600 hover:bg-green-100 rounded-lg text-[10px] font-bold transition">Selesai</a>
                                            <a href="?view=pesanan&action=update_status&id=<?php echo $oid; ?>&status_to=cancelled&_wpnonce=<?php echo wp_create_nonce('update_order_'.$oid); ?>" onclick="return confirm('Batalkan?')" class="px-2 py-1 bg-red-50 text-red-500 hover:bg-red-100 rounded-lg text-[10px] font-bold transition">Tolak</a>
                                        </div></td>
                                    </tr>
                                <?php endwhile; wp_reset_postdata(); else: ?>
                                    <tr><td colspan="5" class="px-5 py-12 text-center text-gray-400 font-medium">Tidak ada pesanan aktif.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        <?php elseif ( $view === 'history' ) :
            $paged = max(1, get_query_var('paged'));
            $history_query = new WP_Query(['post_type'=>'pesanan','post_status'=>array('completed','cancelled'),'posts_per_page'=>15,'paged'=>$paged,'meta_query'=>fm_seller_meta_query($is_admin,$current_user_id)]);
            ?>
            <div class="space-y-5">
                <div><h1 class="text-xl font-black text-gray-900">Riwayat Transaksi</h1><p class="text-xs text-gray-500 mt-0.5">Arsip transaksi selesai atau dibatalkan.</p></div>
                <div class="bg-white rounded-2xl border border-gray-100 fm-card overflow-hidden">
                    <div class="overflow-x-auto fm-scrollbar">
                        <table class="w-full text-left border-collapse">
                            <thead><tr class="bg-gray-50/75 text-gray-500 text-[11px] font-bold uppercase tracking-wider border-b border-gray-100">
                                <th class="px-5 py-3.5">Waktu</th><th class="px-5 py-3.5">ID / Pembeli</th><th class="px-5 py-3.5">Detail</th><th class="px-5 py-3.5">Total</th><th class="px-5 py-3.5">Status</th>
                            </tr></thead>
                            <tbody class="divide-y divide-gray-50 text-xs text-gray-700">
                                <?php if ($history_query->have_posts()) : while ($history_query->have_posts()) : $history_query->the_post();
                                    $oid=get_the_ID(); $nama=get_post_meta($oid,'_nama_pembeli',true)?:'Pelanggan'; $detail=get_post_meta($oid,'_detail_item',true)?:'-'; $total=get_post_meta($oid,'_total_harga',true)?:0;
                                    $status=get_post_status($oid); $waktu=get_the_modified_date('d M Y, H:i');
                                    $amount_class=$status==='completed'?'text-green-600':'text-gray-400 line-through'; $badge_class=$status==='completed'?'bg-green-50 text-green-600':'bg-red-50 text-red-600';
                                ?>
                                    <tr class="hover:bg-gray-50/50 transition">
                                        <td class="px-5 py-3.5 text-gray-400 text-[11px]">📅 <?php echo $waktu; ?></td>
                                        <td class="px-5 py-3.5"><div class="font-bold text-gray-900">#ORD-<?php echo $oid; ?></div><div class="text-[11px] text-gray-400 mt-0.5"><?php echo esc_html($nama); ?></div></td>
                                        <td class="px-5 py-3.5 text-gray-500"><?php echo esc_html($detail); ?></td>
                                        <td class="px-5 py-3.5 font-black <?php echo $amount_class; ?>">Rp<?php echo number_format($total,0,',','.'); ?></td>
                                        <td class="px-5 py-3.5"><span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded-full <?php echo $badge_class; ?>"><?php echo $status==='completed'?'✅ Selesai':'❌ Dibatalkan'; ?></span></td>
                                    </tr>
                                <?php endwhile; wp_reset_postdata(); else: ?>
                                    <tr><td colspan="5" class="px-5 py-12 text-center text-gray-400 font-medium">Belum ada riwayat transaksi.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php if ($history_query->max_num_pages > 1): ?>
                <div class="pt-2"><?php echo paginate_links(['total'=>$history_query->max_num_pages,'current'=>$paged,'type'=>'plain']); ?></div>
                <?php endif; ?>
            </div>

        <?php else :
            $total_produk = count_user_posts($current_user_id, 'produk');
            $sukses_query = new WP_Query(['post_type'=>'pesanan','post_status'=>'completed','posts_per_page'=>-1,'fields'=>'ids','meta_query'=>fm_seller_meta_query($is_admin,$current_user_id)]);
            $total_sukses = $sukses_query->found_posts; wp_reset_postdata();
            $pending_query = new WP_Query(['post_type'=>'pesanan','post_status'=>array('publish','processing'),'posts_per_page'=>-1,'fields'=>'ids','meta_query'=>fm_seller_meta_query($is_admin,$current_user_id)]);
            $total_pending = $pending_query->found_posts; wp_reset_postdata();

            // Total pendapatan
            $pendapatan_query = new WP_Query(['post_type'=>'pesanan','post_status'=>'completed','posts_per_page'=>-1,'meta_query'=>fm_seller_meta_query($is_admin,$current_user_id)]);
            $total_pendapatan = 0;
            if ($pendapatan_query->have_posts()) {
                while ($pendapatan_query->have_posts()) { $pendapatan_query->the_post();
                    $total_pendapatan += floatval(get_post_meta(get_the_ID(),'_total_harga',true));
                }
                wp_reset_postdata();
            }

            // Pesanan terbaru (5)
            $terbaru_query = new WP_Query(['post_type'=>'pesanan','post_status'=>'any','posts_per_page'=>5,'orderby'=>'date','order'=>'DESC','meta_query'=>fm_seller_meta_query($is_admin,$current_user_id)]);

            function fm_status_badge_dashboard($status){
                $map=['publish'=>['🔔 Baru','bg-yellow-50 text-yellow-600'],'processing'=>['👩‍🍳 Diproses','bg-blue-50 text-blue-600'],'completed'=>['✅ Selesai','bg-green-50 text-green-600'],'cancelled'=>['❌ Batal','bg-red-50 text-red-600']];
                return $map[$status] ?? [$status,'bg-gray-50 text-gray-600'];
            }
            ?>
            <div class="mb-6">
                <h1 class="text-xl font-black text-gray-900">Selamat Datang, <?php echo esc_html(wp_get_current_user()->display_name); ?>! 👋</h1>
                <p class="text-xs text-gray-500 mt-0.5">Ringkasan performa toko kuliner Anda.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white p-5 rounded-2xl border border-gray-100 fm-card">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Total Pendapatan</p>
                    <h3 class="text-lg font-black text-gray-900">Rp<?php echo number_format($total_pendapatan,0,',','.'); ?></h3>
                    <p class="text-[10px] text-green-500 font-semibold mt-1">📈 Akumulasi selesai</p>
                </div>
                <div class="bg-white p-5 rounded-2xl border border-gray-100 fm-card">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Pesanan Aktif</p>
                    <h3 class="text-lg font-black text-yellow-500"><?php echo $total_pending; ?></h3>
                    <p class="text-[10px] text-gray-400 mt-1">Menunggu/diproses</p>
                </div>
                <div class="bg-white p-5 rounded-2xl border border-gray-100 fm-card">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Produk Aktif</p>
                    <h3 class="text-lg font-black text-gray-900"><?php echo $total_produk; ?></h3>
                    <p class="text-[10px] text-gray-400 mt-1">Menu terdaftar</p>
                </div>
                <div class="bg-white p-5 rounded-2xl border border-gray-100 fm-card">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Transaksi Sukses</p>
                    <h3 class="text-lg font-black text-green-600"><?php echo $total_sukses; ?></h3>
                    <p class="text-[10px] text-gray-400 mt-1">Order selesai</p>
                </div>
            </div>

            <?php if ($total_pending > 0) : ?>
            <div class="bg-yellow-50 border border-yellow-100 rounded-2xl p-4 flex items-center justify-between mb-6">
                <div class="flex items-center gap-3">
                    <span class="text-xl">🔔</span>
                    <div><p class="text-sm font-bold text-yellow-800">Ada <?php echo $total_pending; ?> pesanan menunggu konfirmasi!</p><p class="text-xs text-yellow-600">Segera proses agar pelanggan tidak menunggu.</p></div>
                </div>
                <a href="?view=pesanan" class="bg-yellow-500 hover:bg-yellow-600 text-white text-xs font-bold px-4 py-2 rounded-xl transition whitespace-nowrap">Lihat →</a>
            </div>
            <?php endif; ?>

            <!-- Pesanan Terbaru -->
            <div class="bg-white rounded-2xl border border-gray-100 fm-card overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-50 flex items-center justify-between">
                    <h2 class="text-sm font-bold text-gray-800">Pesanan Terbaru</h2>
                    <a href="?view=pesanan" class="text-[11px] text-brand font-bold hover:underline">Lihat Semua</a>
                </div>
                <div class="overflow-x-auto fm-scrollbar">
                    <table class="w-full text-left border-collapse">
                        <thead><tr class="text-gray-400 text-[10px] font-bold uppercase tracking-wider border-b border-gray-50">
                            <th class="px-5 py-2.5">Order ID</th><th class="px-5 py-2.5">Pelanggan</th><th class="px-5 py-2.5">Total</th><th class="px-5 py-2.5">Status</th>
                        </tr></thead>
                        <tbody class="divide-y divide-gray-50 text-xs">
                            <?php if ($terbaru_query->have_posts()) : while ($terbaru_query->have_posts()) : $terbaru_query->the_post();
                                $oid=get_the_ID(); $nama=get_post_meta($oid,'_nama_pembeli',true)?:'Pelanggan'; $total=get_post_meta($oid,'_total_harga',true)?:0; $status=get_post_status($oid);
                                $badge=fm_status_badge_dashboard($status);
                            ?>
                                <tr class="hover:bg-gray-50/50 transition">
                                    <td class="px-5 py-3 font-bold text-gray-900">#ORD-<?php echo $oid; ?></td>
                                    <td class="px-5 py-3 text-gray-600"><?php echo esc_html($nama); ?></td>
                                    <td class="px-5 py-3 font-black text-gray-900">Rp<?php echo number_format($total,0,',','.'); ?></td>
                                    <td class="px-5 py-3"><span class="text-[10px] font-bold px-2 py-0.5 rounded-full <?php echo $badge[1]; ?>"><?php echo $badge[0]; ?></span></td>
                                </tr>
                            <?php endwhile; wp_reset_postdata(); else: ?>
                                <tr><td colspan="4" class="px-5 py-8 text-center text-gray-400">Belum ada pesanan masuk.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php endif; ?>

    </main>
</div>

<?php get_footer(); ?>