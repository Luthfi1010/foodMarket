<?php get_header(); ?>

<?php
// ============================================================
// SEARCH & FILTER LOGIC
// ============================================================
$search_query = isset($_GET['s'])   ? sanitize_text_field($_GET['s'])   : '';
$filter_kat   = isset($_GET['kat']) ? sanitize_text_field($_GET['kat']) : '';
$filter_sort  = isset($_GET['sort'])? sanitize_text_field($_GET['sort']): 'terbaru';
$paged        = max(1, get_query_var('paged'));

$produk_args = [
    'post_type'      => 'produk',
    'post_status'    => 'publish',
    'posts_per_page' => 9,
    'paged'          => $paged,
];

// Search
if ($search_query) {
    $produk_args['s'] = $search_query;
}

// Filter kategori
if ($filter_kat) {
    $produk_args['tax_query'] = [[
        'taxonomy' => 'kategori_makanan',
        'field'    => 'slug',
        'terms'    => $filter_kat,
    ]];
}

// Sort
switch ($filter_sort) {
    case 'termurah':
        $produk_args['meta_key'] = '_harga_produk';
        $produk_args['orderby']  = 'meta_value_num';
        $produk_args['order']    = 'ASC';
        break;
    case 'termahal':
        $produk_args['meta_key'] = '_harga_produk';
        $produk_args['orderby']  = 'meta_value_num';
        $produk_args['order']    = 'DESC';
        break;
    default:
        $produk_args['orderby'] = 'date';
        $produk_args['order']   = 'DESC';
}

$produk_query = new WP_Query($produk_args);
$is_searching = $search_query || $filter_kat || $filter_sort !== 'terbaru';
?>

<main class="max-w-7xl mx-auto px-4 py-8 grid grid-cols-1 lg:grid-cols-4 gap-8">

    <!-- ===== SIDEBAR ===== -->
    <aside class="space-y-6">
        <div class="bg-white p-4 rounded-2xl border border-gray-100 space-y-1">
            <a href="<?php echo home_url(); ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-xl <?php echo !$is_searching ? 'bg-brand-light text-brand font-bold' : 'text-gray-600 hover:bg-gray-50'; ?> transition text-sm">🏠 Beranda</a>
            <a href="#kategori" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-gray-600 hover:bg-gray-50 transition text-sm">📂 Kategori</a>
            <?php if (is_user_logged_in()) : ?>
                <a href="<?php echo home_url('/pesanan-saya'); ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-gray-600 hover:bg-gray-50 transition text-sm">📋 Pesanan Saya</a>
                <a href="<?php echo home_url('/checkout'); ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-gray-600 hover:bg-gray-50 transition text-sm">🛒 Keranjang</a>
            <?php endif; ?>
        </div>

        <!-- Filter Kategori -->
        <div class="bg-white p-4 rounded-2xl border border-gray-100">
            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-3">Filter Kategori</p>
            <?php
            $all_cats = get_terms(['taxonomy' => 'kategori_makanan', 'hide_empty' => false]);
            ?>
            <a href="<?php echo home_url('/?s=' . urlencode($search_query) . '&sort=' . $filter_sort); ?>"
                class="block px-3 py-2 rounded-xl text-xs mb-1 transition <?php echo !$filter_kat ? 'bg-brand-light text-brand font-bold' : 'text-gray-600 hover:bg-gray-50'; ?>">
                🍽️ Semua Kategori
            </a>
            <?php if (!empty($all_cats) && !is_wp_error($all_cats)) :
                foreach ($all_cats as $cat) :
                    $emoji = '🍽️';
                    if (strpos($cat->slug, 'makan') !== false)  $emoji = '🍱';
                    if (strpos($cat->slug, 'minum') !== false)  $emoji = '🥤';
                    if (strpos($cat->slug, 'cemilan') !== false || strpos($cat->slug, 'jajan') !== false) $emoji = '🍿';
                    $url = home_url('/?kat=' . urlencode($cat->slug) . '&s=' . urlencode($search_query) . '&sort=' . $filter_sort);
                ?>
                    <a href="<?php echo esc_url($url); ?>"
                        class="block px-3 py-2 rounded-xl text-xs mb-1 transition <?php echo $filter_kat === $cat->slug ? 'bg-brand-light text-brand font-bold' : 'text-gray-600 hover:bg-gray-50'; ?>">
                        <?php echo $emoji . ' ' . esc_html($cat->name); ?>
                        <span class="text-gray-400">(<?php echo $cat->count; ?>)</span>
                    </a>
                <?php endforeach;
            else :
                foreach (['makanan-berat' => ['🍱','Makanan Berat'], 'minuman' => ['🥤','Minuman'], 'cemilan' => ['🍿','Cemilan']] as $slug => $d) : ?>
                    <a href="<?php echo home_url('/?kat=' . $slug); ?>"
                        class="block px-3 py-2 rounded-xl text-xs mb-1 text-gray-600 hover:bg-gray-50 transition">
                        <?php echo $d[0] . ' ' . $d[1]; ?>
                    </a>
                <?php endforeach;
            endif; ?>
        </div>

        <?php if (!is_user_logged_in()) : ?>
        <div class="bg-brand-light p-5 rounded-2xl border border-brand/10 space-y-3">
            <h4 class="font-bold text-gray-900 text-sm">Jadi Seller</h4>
            <p class="text-xs text-gray-600">Jualan makananmu dan jangkau lebih banyak pelanggan.</p>
            <a href="<?php echo home_url('/register'); ?>"
                class="block text-center w-full bg-brand hover:bg-brand-dark text-white text-xs font-semibold py-2.5 rounded-xl transition shadow-sm">
                Daftar Sekarang
            </a>
        </div>
        <?php endif; ?>
    </aside>

    <!-- ===== KONTEN UTAMA ===== -->
    <section class="lg:col-span-3 space-y-8">

        <?php if (!$is_searching) : ?>
        <!-- Hero Banner — hanya tampil saat tidak search -->
        <div class="bg-brand-light rounded-3xl p-8 md:p-10 flex flex-col md:flex-row items-center justify-between gap-6 overflow-hidden relative">
            <div class="space-y-4 max-w-md z-10">
                <h1 class="text-3xl md:text-4xl font-extrabold text-gray-950 leading-tight">Temukan Kuliner Terbaik di Sekitarmu</h1>
                <p class="text-sm text-gray-600">Aneka makanan lezat dari penjual terpercaya, dikirim cepat.</p>
                <!-- Search bar di hero -->
                <form method="GET" action="<?php echo home_url('/'); ?>" class="flex gap-2">
                    <input type="text" name="s" placeholder="Cari nasi goreng, bakso, mie ayam..."
                        class="flex-1 px-4 py-3 rounded-xl text-sm border border-white/50 bg-white focus:outline-none focus:ring-2 focus:ring-brand/30">
                    <button type="submit" class="bg-brand hover:bg-brand-dark text-white font-bold px-5 py-3 rounded-xl transition shadow-md text-sm">
                        Cari
                    </button>
                </form>
            </div>
            <div class="w-52 h-52 rounded-full border-4 border-white shadow-xl flex-shrink-0 bg-cover bg-center"
                style="background-image: url('https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=500');"></div>
        </div>

        <!-- Kategori Populer -->
        <div class="space-y-3" id="kategori">
            <div class="flex items-center justify-between">
                <h3 class="font-bold text-lg text-gray-900">Kategori Populer</h3>
            </div>
            <div class="grid grid-cols-3 sm:grid-cols-6 gap-3">
                <?php
                $cats_show = (!empty($all_cats) && !is_wp_error($all_cats)) ? $all_cats : [];
                if (empty($cats_show)) {
                    $cats_show = [
                        (object)['slug'=>'makanan-berat','name'=>'Makanan Berat'],
                        (object)['slug'=>'minuman','name'=>'Minuman'],
                        (object)['slug'=>'cemilan','name'=>'Cemilan'],
                    ];
                }
                foreach ($cats_show as $cat) :
                    $emoji = '🍽️';
                    if (strpos($cat->slug,'makan')!==false) $emoji='🍱';
                    if (strpos($cat->slug,'minum')!==false) $emoji='🥤';
                    if (strpos($cat->slug,'cemilan')!==false||strpos($cat->slug,'jajan')!==false) $emoji='🍿';
                ?>
                    <a href="<?php echo home_url('/?kat=' . urlencode($cat->slug)); ?>"
                        class="bg-white p-4 rounded-2xl border border-gray-100 flex flex-col items-center text-center gap-2 hover:shadow-sm hover:border-brand/20 transition">
                        <div class="w-12 h-12 bg-brand-light rounded-xl flex items-center justify-center text-xl"><?php echo $emoji; ?></div>
                        <span class="text-xs font-medium text-gray-700"><?php echo esc_html($cat->name); ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- ===== SEARCH BAR (selalu tampil saat searching) ===== -->
        <?php if ($is_searching) : ?>
        <div class="bg-white rounded-2xl border border-gray-100 p-4 shadow-sm">
            <form method="GET" action="<?php echo home_url('/'); ?>" class="flex gap-2">
                <input type="text" name="s" value="<?php echo esc_attr($search_query); ?>"
                    placeholder="Cari makanan, minuman..."
                    class="flex-1 px-4 py-2.5 rounded-xl text-sm border border-gray-200 bg-gray-50 focus:bg-white focus:border-brand focus:outline-none transition">
                <?php if ($filter_kat) : ?>
                    <input type="hidden" name="kat" value="<?php echo esc_attr($filter_kat); ?>">
                <?php endif; ?>
                <input type="hidden" name="sort" value="<?php echo esc_attr($filter_sort); ?>">
                <button type="submit" class="bg-brand hover:bg-brand-dark text-white font-bold px-4 py-2.5 rounded-xl transition text-sm">Cari</button>
                <a href="<?php echo home_url(); ?>" class="px-4 py-2.5 border border-gray-200 rounded-xl text-xs text-gray-500 hover:bg-gray-50 transition font-medium whitespace-nowrap">Reset</a>
            </form>
        </div>
        <?php endif; ?>

        <!-- ===== PRODUK LISTING ===== -->
        <div class="space-y-4" id="produk-rekomendasi">
            <div class="flex items-center justify-between flex-wrap gap-3">
                <div>
                    <h3 class="font-bold text-lg text-gray-900">
                        <?php if ($search_query) : ?>
                            Hasil pencarian: "<span class="text-brand"><?php echo esc_html($search_query); ?></span>"
                        <?php elseif ($filter_kat) : ?>
                            Kategori: <span class="text-brand"><?php echo esc_html(ucwords(str_replace('-', ' ', $filter_kat))); ?></span>
                        <?php else : ?>
                            Rekomendasi Untukmu
                        <?php endif; ?>
                    </h3>
                    <?php if ($produk_query->found_posts > 0) : ?>
                        <p class="text-xs text-gray-400 mt-0.5"><?php echo $produk_query->found_posts; ?> produk ditemukan</p>
                    <?php endif; ?>
                </div>

                <!-- Sort -->
                <form method="GET" action="<?php echo home_url('/'); ?>" class="flex items-center gap-2">
                    <?php if ($search_query) : ?><input type="hidden" name="s" value="<?php echo esc_attr($search_query); ?>"><?php endif; ?>
                    <?php if ($filter_kat) : ?><input type="hidden" name="kat" value="<?php echo esc_attr($filter_kat); ?>"><?php endif; ?>
                    <select name="sort" onchange="this.form.submit()"
                        class="text-xs border border-gray-200 rounded-xl px-3 py-2 bg-white focus:border-brand focus:outline-none">
                        <option value="terbaru"  <?php selected($filter_sort,'terbaru'); ?>>Terbaru</option>
                        <option value="termurah" <?php selected($filter_sort,'termurah'); ?>>Harga Termurah</option>
                        <option value="termahal" <?php selected($filter_sort,'termahal'); ?>>Harga Tertinggi</option>
                    </select>
                </form>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-5">
                <?php if ($produk_query->have_posts()) :
                    while ($produk_query->have_posts()) : $produk_query->the_post();
                        $harga = get_post_meta(get_the_ID(), '_harga_produk', true);
                        $stok  = intval(get_post_meta(get_the_ID(), '_stok_produk', true));
                        $foto  = get_the_post_thumbnail_url(get_the_ID(), 'medium')
                                 ?: 'https://images.unsplash.com/photo-1562608284-c5249ff97e40?w=500';
                        $habis = $stok <= 0;
                        $terms = get_the_terms(get_the_ID(), 'kategori_makanan');
                        $kat   = ($terms && !is_wp_error($terms)) ? $terms[0]->name : '';
                ?>
                    <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden hover:shadow-md transition flex flex-col group <?php echo $habis ? 'opacity-60' : ''; ?>">
                        <div class="h-40 bg-gray-100 overflow-hidden relative">
                            <img src="<?php echo esc_url($foto); ?>" alt="<?php the_title_attribute(); ?>"
                                class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                            <?php if ($habis) : ?>
                                <div class="absolute inset-0 bg-gray-900/50 flex items-center justify-center">
                                    <span class="bg-white text-gray-700 text-[10px] font-black px-3 py-1 rounded-full uppercase">Stok Habis</span>
                                </div>
                            <?php endif; ?>
                            <?php if ($kat) : ?>
                                <span class="absolute top-2 left-2 bg-white/90 text-gray-700 text-[9px] font-bold px-2 py-0.5 rounded-full">
                                    <?php echo esc_html($kat); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="p-4 flex-1 flex flex-col justify-between space-y-3">
                            <div>
                                <a href="<?php the_permalink(); ?>" class="block">
                                    <h4 class="font-bold text-gray-900 text-sm group-hover:text-brand transition line-clamp-2">
                                        <?php the_title(); ?>
                                    </h4>
                                </a>
                                <p class="text-xs text-gray-400 mt-0.5">🏪 <?php the_author(); ?></p>
                            </div>
                            <div class="flex items-center justify-between pt-2 border-t border-gray-50">
                                <span class="text-sm font-bold text-brand">
                                    <?php echo $harga ? 'Rp' . number_format($harga, 0, ',', '.') : 'Rp0'; ?>
                                </span>
                                <?php if (!$habis) : ?>
                                    <a href="<?php the_permalink(); ?>"
                                        class="text-[10px] bg-brand/10 text-brand hover:bg-brand hover:text-white font-bold px-3 py-1.5 rounded-xl transition">
                                        + Pesan
                                    </a>
                                <?php else : ?>
                                    <span class="text-[10px] text-gray-400 font-medium">Habis</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; wp_reset_postdata();
                else : ?>
                    <div class="col-span-full bg-white p-10 rounded-2xl border border-gray-100 text-center space-y-3">
                        <div class="text-4xl">🔍</div>
                        <p class="text-sm font-bold text-gray-800">
                            <?php echo $search_query ? 'Tidak ada hasil untuk "' . esc_html($search_query) . '"' : 'Belum ada produk tersedia.'; ?>
                        </p>
                        <?php if ($is_searching) : ?>
                            <a href="<?php echo home_url(); ?>" class="inline-block text-xs text-brand font-bold hover:underline">← Lihat semua produk</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Pagination -->
            <?php if ($produk_query->max_num_pages > 1) : ?>
                <div class="pt-4 flex justify-center">
                    <?php echo paginate_links([
                        'total'   => $produk_query->max_num_pages,
                        'current' => $paged,
                        'type'    => 'plain',
                    ]); ?>
                </div>
            <?php endif; ?>
        </div>

    </section>
</main>

<?php get_footer(); ?>