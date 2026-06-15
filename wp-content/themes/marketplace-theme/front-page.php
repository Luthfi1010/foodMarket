<?php get_header(); ?>

<main class="max-w-7xl mx-auto px-4 py-8 grid grid-cols-1 lg:grid-cols-4 gap-8">
    
    <aside class="space-y-6">
        <div class="bg-white p-4 rounded-2xl border border-gray-100 space-y-2">
            <a href="<?php echo home_url(); ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-xl bg-brand-light text-brand font-medium">🏠 Beranda</a>
            <a href="#" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-gray-600 hover:bg-gray-50 transition">📂 Kategori</a>
            <a href="#" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-gray-600 hover:bg-gray-50 transition">🏷️ Promo</a>
            <a href="#" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-gray-600 hover:bg-gray-50 transition">❤️ Favorit</a>
            <a href="#" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-gray-600 hover:bg-gray-50 transition">📋 Pesanan Saya</a>
        </div>

        <?php if (!is_user_logged_in()) : ?>
            <div class="bg-brand-light p-5 rounded-2xl border border-brand/10 space-y-4">
                <h4 class="font-bold text-gray-900 leading-snug">Jadi Seller</h4>
                <p class="text-xs text-gray-600">Jualan makananmu dan jangkau lebih banyak pelanggan.</p>
                <a href="<?php echo home_url('/register'); ?>" class="block text-center w-full bg-brand hover:bg-brand-dark text-white text-xs font-semibold py-2.5 rounded-xl transition shadow-sm shadow-brand/10">
                    Daftar Sekarang
                </a>
            </div>
        <?php endif; ?>
    </aside>

    <section class="lg:col-span-3 space-y-10">
        
        <div class="bg-brand-light rounded-3xl p-8 md:p-12 flex flex-col md:flex-row items-center justify-between gap-6 overflow-hidden relative">
            <div class="space-y-4 max-w-md z-10">
                <h1 class="text-3xl md:text-4xl font-extrabold text-gray-950 leading-tight">Temukan Kuliner Terbaik di Sekitarmu</h1>
                <p class="text-sm text-gray-600">Aneka makanan lezat dari penjual terpercaya, dikirim cepat sampai ke tanganmu.</p>
                <button class="bg-brand hover:bg-brand-dark text-white text-sm font-semibold px-6 py-3 rounded-xl transition shadow-lg shadow-brand/20">Pesan Sekarang</button>
            </div>
            <div class="w-64 h-64 bg-gray-200 rounded-full border-4 border-white shadow-xl flex-shrink-0 bg-cover bg-center" style="background-image: url('https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=500');"></div>
        </div>

        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="font-bold text-lg text-gray-900">Kategori Populer</h3>
                <a href="#" class="text-xs text-brand font-semibold hover:underline">Lihat Semua</a>
            </div>
            <div class="grid grid-cols-3 sm:grid-cols-6 gap-4">
                <?php 
                // Ambil daftar kategori makanan langsung dari custom taxonomy WordPress
                $categories = get_terms(array(
                    'taxonomy'   => 'kategori_makanan',
                    'hide_empty' => false, // Tetap munculkan walaupun belum ada produknya
                ));

                // Jika database kosong, gunakan fallback ini agar tampilan prototyping tidak sepi
                if (empty($categories) || is_wp_error($categories)) {
                    $fallback_cats = [
                        'makanan-berat' => ['nama' => 'Makanan Berat', 'emoji' => '🍱'],
                        'minuman'       => ['nama' => 'Minuman', 'emoji' => '🥤'],
                        'jajanan'       => ['nama' => 'Camilan / Jajanan', 'emoji' => '🍿'],
                    ];
                    foreach ($fallback_cats as $slug => $data) {
                        echo '
                        <div class="bg-white p-4 rounded-2xl border border-gray-100 flex flex-col items-center text-center gap-2 hover:shadow-sm transition cursor-pointer">
                            <div class="w-12 h-12 bg-brand-light rounded-xl flex items-center justify-center text-lg">'. $data['emoji'] .'</div>
                            <span class="text-xs font-medium text-gray-700">'. $data['nama'] .'</span>
                        </div>';
                    }
                } else {
                    // Jika ada data asli di database, lakukan looping di sini
                    foreach($categories as $cat): 
                        // Pasang penyesuaian emoji sederhana berdasarkan slug kategori
                        $emoji = '🍽️';
                        if (strpos($cat->slug, 'makan') !== false) $emoji = '🍱';
                        if (strpos($cat->slug, 'minum') !== false) $emoji = '🥤';
                        if (strpos($cat->slug, 'jajan') !== false || strpos($cat->slug, 'camilan') !== false) $emoji = '🍿';
                        ?>
                        <a href="<?php echo esc_url(get_term_link($cat)); ?>" class="bg-white p-4 rounded-2xl border border-gray-100 flex flex-col items-center text-center gap-2 hover:shadow-sm transition cursor-pointer">
                            <div class="w-12 h-12 bg-brand-light rounded-xl flex items-center justify-center text-lg"><?php echo $emoji; ?></div>
                            <span class="text-xs font-medium text-gray-700"><?php echo esc_html($cat->name); ?></span>
                        </a>
                    <?php endforeach; 
                } ?>
            </div>
        </div>

        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="font-bold text-lg text-gray-900">Rekomendasi Untukmu</h3>
                <a href="#" class="text-xs text-brand font-semibold hover:underline">Lihat Semua</a>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
                <?php
                $args = array(
                    'post_type'      => 'produk', // Menyasar Custom Post Type produk
                    'posts_per_page' => 6,         
                    'post_status'    => 'publish', 
                    'orderby'        => 'date',    
                    'order'          => 'DESC'
                );

                $produk_query = new WP_Query($args);

                if ($produk_query->have_posts()) :
                    while ($produk_query->have_posts()) : $produk_query->the_post(); 
                        
                        $harga_produk = get_post_meta(get_the_ID(), '_harga_produk', true);
                        
                        $foto_url = get_the_post_thumbnail_url(get_the_ID(), 'medium');
                        if (!$foto_url) {
                            $foto_url = 'https://images.unsplash.com/photo-1562608284-c5249ff97e40?w=500'; 
                        }

                        $nama_seller = get_the_author();
                        ?>

                        <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden hover:shadow-md transition flex flex-col group">
                            <div class="h-40 bg-gray-100 overflow-hidden relative">
                                <img src="<?php echo esc_url($foto_url); ?>" alt="<?php the_title_attribute(); ?>" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                            </div>
                            
                            <div class="p-4 flex-1 flex flex-col justify-between space-y-3">
                                <div>
                                    <a href="<?php the_permalink(); ?>" class="block">
                                        <h4 class="font-bold text-gray-900 text-sm group-hover:text-brand transition line-clamp-2">
                                            <?php the_title(); ?>
                                        </h4>
                                    </a>
                                    <p class="text-xs text-gray-400 mt-0.5">🏪 <?php echo esc_html($nama_seller); ?></p>
                                </div>
                                
                                <div class="flex items-center justify-between pt-2 border-t border-gray-50">
                                    <span class="text-sm font-bold text-brand">
                                        <?php echo $harga_produk ? 'Rp' . number_format($harga_produk, 0, ',', '.') : 'Rp0'; ?>
                                    </span>
                                    <span class="text-xs text-gray-500">⭐ 4.8 (120)</span>
                                </div>
                            </div>
                        </div>
                        <?php 
                    endwhile;
                    wp_reset_postdata(); 
                else : ?>
                    <div class="col-span-full bg-white p-8 rounded-2xl border border-gray-100 text-center">
                        <span class="text-3xl block mb-2">🍽️</span>
                        <p class="text-sm text-gray-500 font-medium">Belum ada makanan yang dijual. Silakan tambahkan via Dashboard Seller!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </section>
</main>

<?php get_footer(); ?>