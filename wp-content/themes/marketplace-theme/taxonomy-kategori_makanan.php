<?php
/**
 * Template Name: Halaman Kategori Makanan
 */

get_header(); 

// Ambil data objek kategori (term) yang sedang dibuka saat ini
$current_term = get_queried_object();
$term_slug    = $current_term->slug;
$term_name    = $current_term->name;
$term_desc    = $current_term->description ?: 'Jelajahi ragam kuliner terbaik di kategori ini.';

// Ambil kata kunci pencarian jika ada user yang mengetik di search bar khusus kategori
$search_keyword = isset($_GET['search_kuliner']) ? sanitize_text_field($_GET['search_kuliner']) : '';

// Atur pagination
$paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;

// Modifikasi query agar fleksibel mendukung pencarian kata kunci di dalam kategori ini
$args = array(
    'post_type'      => 'produk',
    'post_status'    => 'publish',
    'posts_per_page' => 12,
    'paged'          => $paged,
    's'              => $search_keyword, // Filter berdasarkan teks pencarian
    'tax_query'      => array(
        array(
            'taxonomy' => 'kategori_makanan',
            'field'    => 'slug',
            'terms'    => $term_slug,
        ),
    ),
);

$produk_query = new WP_Query($args);
?>

<div class="bg-gray-70 border-b border-gray-100 py-12">
    <div class="max-w-6xl mx-auto px-4 space-y-4"> <div class="flex">
            <a href="<?php echo home_url(); ?>" class="inline-flex items-center gap-2 px-3 py-1.5 bg-orange-600 border border-gray-200 rounded-xl text-xs font-semibold text-white hover:text-white hover:border-brand/30 transition shadow-xs group">
                <i class="fa-solid fa-arrow-left" style="color: rgb(233, 183, 7);"></i>
                Kembali ke Beranda
            </a>
        </div>
    <div class="max-w-6xl mx-auto px-4">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
            
            <div class="space-y-1.5 max-w-xl">
                <div class="text-xs font-bold text-brand uppercase tracking-widest">Kategori Kuliner</div>
                <h1 class="text-3xl font-black text-gray-950 tracking-tight"><?php echo esc_html($term_name); ?></h1>
                <p class="text-sm text-gray-500 leading-relaxed"><?php echo esc_html($term_desc); ?></p>
            </div>

            <div class="w-full md:w-80">
                <form method="get" action="<?php echo esc_url(get_term_link($current_term)); ?>" class="relative">
                    <input type="text" name="search_kuliner" value="<?php echo esc_attr($search_keyword); ?>"
                        placeholder="Cari makanan di kategori ini..."
                        class="w-full pl-4 pr-10 py-3 bg-white border border-gray-200 rounded-2xl text-sm focus:border-brand focus:ring-1 focus:ring-brand outline-none transition shadow-sm">
                    <button type="submit" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-brand transition">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-51-5.197m0 0A7.5 7.5 0 1 1 2.25 10.5a7.5 7.5 0 0 1 14.25 0Z" />
                        </svg>
                    </button>
                </form>
                <?php if ( ! empty($search_keyword) ) : ?>
                    <div class="text-[11px] text-gray-400 mt-1.5 px-1 flex items-center justify-between">
                        <span>Menampilkan hasil: "<strong><?php echo esc_html($search_keyword); ?></strong>"</span>
                        <a href="<?php echo esc_url(get_term_link($current_term)); ?>" class="text-red-500 hover:underline font-medium">Reset</a>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

<main class="max-w-6xl mx-auto px-4 py-12">
    <?php if ( $produk_query->have_posts() ) : ?>
        
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            <?php while ( $produk_query->have_posts() ) : $produk_query->the_post(); 
                $product_id = get_the_ID();
                $harga      = get_post_meta($product_id, '_harga_produk', true) ?: 0;
                $stok       = get_post_meta($product_id, '_stok_produk', true) ?: 0;
                $seller_id  = get_post_field('post_author', $product_id);
                $nama_toko  = get_the_author_meta('display_name', $seller_id);
                ?>
                
                <div class="bg-white border border-gray-100 rounded-3xl overflow-hidden shadow-sm hover:shadow-md transition flex flex-col group">
                    
                    <div class="relative aspect-square bg-gray-50 overflow-hidden">
                        <?php if ( has_post_thumbnail() ) : ?>
                            <?php the_post_thumbnail('medium', array('class' => 'w-full h-full object-cover group-hover:scale-105 transition duration-300')); ?>
                        <?php else : ?>
                            <div class="w-full h-full flex items-center justify-center text-gray-300">
                                🍳 No Image
                            </div>
                        <?php endif; ?>
                        
                        <?php if ( $stok <= 0 ) : ?>
                            <div class="absolute inset-0 bg-black/40 backdrop-blur-xs flex items-center justify-center">
                                <span class="bg-red-600 text-white text-[10px] font-black uppercase tracking-wider px-2.5 py-1 rounded-xl shadow-sm">Habis</span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="p-4 flex-1 flex flex-col justify-between space-y-3">
                        <div class="space-y-1">
                            <div class="text-[10px] text-gray-400 font-medium tracking-wide flex items-center gap-1">
                                <?php echo esc_html($nama_toko); ?>
                            </div>
                            <a href="<?php the_permalink(); ?>" class="block text-sm font-bold text-gray-950 hover:text-brand line-clamp-2 transition leading-snug">
                                <?php the_title(); ?>
                            </a>
                        </div>

                        <div class="flex items-center justify-between pt-1">
                            <div class="text-sm font-black text-gray-950">
                                Rp<?php echo number_format($harga, 0, ',', '.'); ?>
                            </div>
                            
                            <a href="<?php the_permalink(   ); ?>" 
                                class="p-2 <?php echo $stok > 0 ? 'bg-brand/10 text-brand hover:bg-brand hover:text-white' : 'bg-gray-100 text-gray-400 cursor-not-allowed'; ?> rounded-xl transition shadow-xs">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                </svg>
                            </a>
                        </div>
                    </div>

                </div>

            <?php endwhile; wp_reset_postdata(); ?>
        </div>

        <div class="mt-12 flex justify-center text-sm font-bold">
            <?php
            echo paginate_links(array(
                'total'        => $produk_query->max_num_pages,
                'current'      => $paged,
                'type'         => 'plain',
                'prev_text'    => '&larr; Prev',
                'next_text'    => 'Next &rarr;',
                'before_page_number' => '<span class="px-3 py-1.5 border border-gray-100 rounded-lg mx-0.5 hover:bg-brand/10 hover:text-brand transition">',
                'after_page_number'  => '</span>'
            ));
            ?>
        </div>

    <?php else : ?>
        <div class="text-center py-20 max-w-md mx-auto space-y-3">
            <div class="text-4xl">🍽️</div>
            <h3 class="text-base font-bold text-gray-900">Kuliner Tidak Ditemukan</h3>
            <p class="text-xs text-gray-400">Maaf, menu makanan pada kategori ini sedang tidak tersedia atau tidak cocok dengan kata kunci pencarian Anda.</p>
            <a href="<?php echo home_url(); ?>" class="inline-block text-xs bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold px-4 py-2 rounded-xl transition">
                Kembali ke Beranda
            </a>
        </div>
    <?php endif; ?>
</main>

<?php get_footer(); ?>