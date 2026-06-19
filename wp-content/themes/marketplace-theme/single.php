<?php
/**
 * Template Name: Detail Produk Kuliner
 * Template Post Type: produk
 */

if ( is_user_logged_in() ) {
    $current_user   = wp_get_current_user();
    $post_author_id = get_post_field('post_author', get_the_ID());
    $is_admin       = current_user_can('administrator');

    if ( ! $is_admin && ( $current_user->ID == $post_author_id || in_array('seller', $current_user->roles) ) ) {
        wp_redirect( home_url('/dashboard-seller/?view=produk') );
        exit;
    }
}

get_header(); ?>

<main class="max-w-4xl mx-auto px-4 py-6">
    <a href="<?php echo home_url(); ?>" class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-brand transition mb-5 font-medium">
        ← Kembali
    </a>

    <?php if (have_posts()) : while (have_posts()) : the_post();

        $harga_produk = get_post_meta(get_the_ID(), '_harga_produk', true);
        $stok_produk  = intval(get_post_meta(get_the_ID(), '_stok_produk', true));
        $foto_url     = get_the_post_thumbnail_url(get_the_ID(), 'large') ?: 'https://images.unsplash.com/photo-1562608284-c5249ff97e40?w=600';
        $kategori_terms = get_the_terms(get_the_ID(), 'kategori_makanan');
        $nama_kategori  = ($kategori_terms && !is_wp_error($kategori_terms)) ? $kategori_terms[0]->name : 'Kuliner';

        $author_id   = get_the_author_meta('ID');
        $author_name = get_the_author();
        $total_menu_seller = count_user_posts($author_id, 'produk');
    ?>

        <div class="bg-white rounded-3xl border border-gray-100 fm-card overflow-hidden">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-0">

                <!-- Gambar -->
                <div class="p-6 space-y-3 border-r border-gray-50">
                    <div class="aspect-square bg-gray-100 rounded-2xl overflow-hidden border border-gray-100">
                        <img src="<?php echo esc_url($foto_url); ?>" alt="<?php the_title_attribute(); ?>" class="w-full h-full object-cover">
                    </div>
                    <div class="grid grid-cols-4 gap-2">
                        <div class="aspect-square bg-gray-100 rounded-xl border-2 border-brand overflow-hidden">
                            <img src="<?php echo esc_url($foto_url); ?>" class="w-full h-full object-cover opacity-80">
                        </div>
                        <?php for ($i=0;$i<3;$i++): ?>
                            <div class="aspect-square bg-gray-50 rounded-xl border border-dashed border-gray-200 flex items-center justify-center text-gray-300 text-xs">📸</div>
                        <?php endfor; ?>
                    </div>
                </div>

                <!-- Info -->
                <div class="p-6 space-y-4" id="product-container" data-product-id="<?php the_ID(); ?>" data-nonce="<?php echo wp_create_nonce('foodmarket_cart_nonce'); ?>">
                    <div>
                        <span class="bg-brand/10 text-brand text-[10px] font-black uppercase tracking-wider px-2.5 py-1 rounded-full mb-2 inline-block">
                            🍕 <?php echo esc_html($nama_kategori); ?>
                        </span>
                        <h1 class="text-xl font-bold text-gray-900 leading-snug"><?php the_title(); ?></h1>
                        <div class="flex items-center gap-2 mt-1.5">
                            <span class="text-xs text-gray-500">🏪 <?php echo esc_html($author_name); ?></span>
                            <span class="text-xs text-yellow-500 font-semibold">⭐ 4.8 (120)</span>
                        </div>
                    </div>

                    <div class="text-2xl font-black text-brand">
                        <?php echo $harga_produk ? 'Rp'.number_format($harga_produk,0,',','.') : 'Rp0'; ?>
                    </div>

                    <div class="text-xs text-gray-600 leading-relaxed">
                        <?php the_content(); ?>
                    </div>

                    <div class="space-y-2">
                        <label class="text-xs font-bold text-gray-700 block">Pilihan Level Pedas</label>
                        <div class="flex flex-wrap gap-2" id="pedas-selector">
                            <button type="button" data-level="Tidak Pedas" class="pedas-btn px-3 py-1.5 rounded-lg border border-gray-200 text-xs font-medium hover:border-brand transition">Tidak Pedas 🍃</button>
                            <button type="button" data-level="Sedang" class="pedas-btn px-3 py-1.5 rounded-lg border-2 border-brand bg-brand-light text-brand text-xs font-bold">Sedang 🔥</button>
                            <button type="button" data-level="Pedas" class="pedas-btn px-3 py-1.5 rounded-lg border border-gray-200 text-xs font-medium hover:border-brand transition">Pedas 🔥🔥</button>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="text-xs font-bold text-gray-700 block">Catatan untuk Penjual (Opsional)</label>
                        <input type="text" id="cart-note" placeholder="Contoh: tanpa timun, tambah sambal..." maxlength="100"
                            class="w-full text-xs border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-brand bg-gray-50 focus:bg-white transition-all">
                    </div>

                    <div class="pt-3 border-t border-gray-100 space-y-3">
                        <?php if ($stok_produk <= 0) : ?>
                            <div class="bg-red-50 text-red-500 text-xs font-bold px-4 py-3 rounded-xl border border-red-100 text-center">❌ Stok habis saat ini.</div>
                        <?php else : ?>
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-medium text-gray-600 flex items-center gap-1">
                                    <span class="text-green-500">●</span> Stok tersedia: <b class="<?php echo $stok_produk<5?'text-red-500':'text-gray-900'; ?>" id="stock-val"><?php echo $stok_produk; ?></b>
                                </span>
                                <div class="flex items-center border border-gray-200 rounded-lg overflow-hidden bg-white">
                                    <button type="button" id="btn-minus" class="px-3 py-1.5 text-gray-500 hover:bg-gray-50 font-bold text-sm transition">-</button>
                                    <span class="px-4 py-1.5 text-gray-900 font-bold select-none text-sm" id="qty-val">1</span>
                                    <button type="button" id="btn-plus" class="px-3 py-1.5 text-gray-500 hover:bg-gray-50 font-bold text-sm transition">+</button>
                                </div>
                            </div>
                            <div class="flex gap-2.5">
                                <button type="button" class="px-4 py-3 rounded-xl border border-gray-200 text-gray-400 hover:text-red-500 hover:bg-red-50 hover:border-red-200 transition text-sm">❤️</button>
                                <button type="button" id="btn-add-to-cart" class="flex-1 bg-white border-2 border-brand text-brand hover:bg-brand-light font-bold py-3 rounded-xl transition text-sm">+ Keranjang</button>
                                <a href="#" id="btn-beli-sekarang" class="flex-1 bg-brand hover:bg-brand-dark text-white font-bold py-3 rounded-xl transition text-sm text-center shadow-md shadow-brand/20">Beli Sekarang</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Info Seller -->
            <div class="border-t border-gray-50 px-6 py-5 bg-gray-50/50 flex items-center justify-between flex-wrap gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-brand-light rounded-full flex items-center justify-center text-lg">🧑‍🍳</div>
                    <div>
                        <p class="text-sm font-bold text-gray-900"><?php echo esc_html($author_name); ?></p>
                        <p class="text-[11px] text-gray-400">Penjual makanan rumahan</p>
                    </div>
                </div>
                <div class="flex items-center gap-5 text-center">
                    <div>
                        <p class="text-sm font-black text-gray-900">⭐ 4.8</p>
                        <p class="text-[10px] text-gray-400">Rating</p>
                    </div>
                    <div>
                        <p class="text-sm font-black text-gray-900"><?php echo $total_menu_seller; ?></p>
                        <p class="text-[10px] text-gray-400">Menu</p>
                    </div>
                    <div>
                        <p class="text-sm font-black text-gray-900">~30m</p>
                        <p class="text-[10px] text-gray-400">Waktu Proses</p>
                    </div>
                </div>
            </div>
        </div>

        <div id="toast-notif" class="fixed bottom-6 right-6 z-50 hidden max-w-xs bg-gray-900 text-white text-xs font-semibold px-5 py-3 rounded-2xl shadow-xl transition-all"></div>

        <script>
        document.addEventListener('DOMContentLoaded', function () {
            const btnPlus = document.getElementById('btn-plus');
            const btnMinus = document.getElementById('btn-minus');
            const qtyVal = document.getElementById('qty-val');
            const btnAddCart = document.getElementById('btn-add-to-cart');
            const btnBeliSekarang = document.getElementById('btn-beli-sekarang');
            const toast = document.getElementById('toast-notif');
            if (!btnAddCart) return;

            const pedasBtns = document.querySelectorAll('.pedas-btn');
            let currentQty = 1, selectedPedas = 'Sedang';
            const maxStock = parseInt(document.getElementById('stock-val').innerText) || 0;

            function showToast(msg, isError) {
                toast.textContent = msg;
                toast.className = 'fixed bottom-6 right-6 z-50 max-w-xs text-white text-xs font-semibold px-5 py-3 rounded-2xl shadow-xl ' + (isError ? 'bg-red-600' : 'bg-gray-900');
                setTimeout(() => toast.classList.add('hidden'), 3000);
            }

            if (btnPlus) btnPlus.addEventListener('click', () => { if (currentQty < maxStock) { currentQty++; qtyVal.innerText = currentQty; } });
            if (btnMinus) btnMinus.addEventListener('click', () => { if (currentQty > 1) { currentQty--; qtyVal.innerText = currentQty; } });

            pedasBtns.forEach(btn => btn.addEventListener('click', function () {
                pedasBtns.forEach(b => b.className = 'pedas-btn px-3 py-1.5 rounded-lg border border-gray-200 text-xs font-medium hover:border-brand transition');
                this.className = 'pedas-btn px-3 py-1.5 rounded-lg border-2 border-brand bg-brand-light text-brand text-xs font-bold';
                selectedPedas = this.getAttribute('data-level');
            }));

            function addToCart(redirectAfter) {
                const container = document.getElementById('product-container');
                const formData = new FormData();
                formData.append('action', 'add_to_cart');
                formData.append('product_id', container.getAttribute('data-product-id'));
                formData.append('quantity', currentQty);
                formData.append('pedas', selectedPedas);
                formData.append('note', document.getElementById('cart-note').value);
                formData.append('nonce', container.getAttribute('data-nonce'));

                return fetch('<?php echo admin_url("admin-ajax.php"); ?>', { method: 'POST', body: formData })
                    .then(r => r.json())
                    .then(res => {
                        if (res.success) {
                            const badge = document.getElementById('cart-count-badge');
                            if (badge) { badge.textContent = res.data.total_items; badge.classList.remove('hidden'); }
                            if (redirectAfter) {
                                window.location.href = '<?php echo home_url("/checkout"); ?>';
                            } else {
                                showToast('✅ ' + res.data.message, false);
                                currentQty = 1; qtyVal.innerText = 1;
                                document.getElementById('cart-note').value = '';
                            }
                        } else {
                            showToast('❌ ' + res.data.message, true);
                        }
                    })
                    .catch(() => showToast('❌ Gagal terhubung ke server.', true));
            }

            btnAddCart.addEventListener('click', function () {
                btnAddCart.textContent = 'Memasukkan...';
                btnAddCart.disabled = true;
                addToCart(false).finally(() => { btnAddCart.textContent = '+ Keranjang'; btnAddCart.disabled = false; });
            });

            if (btnBeliSekarang) {
                btnBeliSekarang.addEventListener('click', function (e) {
                    e.preventDefault();
                    btnBeliSekarang.textContent = 'Memproses...';
                    addToCart(true);
                });
            }
        });
        </script>

    <?php endwhile; endif; ?>
</main>

<?php get_footer(); ?>