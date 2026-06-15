<?php
/**
 * Template Name: Detail Produk Kuliner
 * Template Post Type: produk
 */

// FIX: Sebelumnya admin ikut kena redirect karena cek role 'seller' saja
// Sekarang: hanya seller NON-admin pemilik produk yang diredirect
if ( is_user_logged_in() ) {
    $current_user   = wp_get_current_user();
    $post_author_id = get_post_field( 'post_author', get_the_ID() );
    $is_admin       = current_user_can('administrator');

    // Hanya redirect jika: bukan admin DAN (adalah seller ATAU adalah pemilik produk)
    if ( ! $is_admin && (
        $current_user->ID == $post_author_id ||
        in_array( 'seller', $current_user->roles )
    ) ) {
        wp_redirect( home_url('/dashboard-seller/?view=produk') );
        exit;
    }
}

get_header(); ?>

<main class="max-w-4xl mx-auto px-4 py-8">
    <a href="<?php echo home_url(); ?>" class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-brand transition mb-6 block">
        ← Kembali ke Beranda
    </a>

    <?php if ( have_posts() ) : while ( have_posts() ) : the_post();

        $harga_produk = get_post_meta( get_the_ID(), '_harga_produk', true );
        $stok_produk  = get_post_meta( get_the_ID(), '_stok_produk',  true );
        $foto_url     = get_the_post_thumbnail_url( get_the_ID(), 'large' )
                        ?: 'https://images.unsplash.com/photo-1562608284-c5249ff97e40?w=600';

        $kategori_terms = get_the_terms( get_the_ID(), 'kategori_makanan' );
        $nama_kategori  = ( $kategori_terms && ! is_wp_error( $kategori_terms ) ) ? $kategori_terms[0]->name : 'Kuliner';
        $stok_int       = intval( $stok_produk );
    ?>
        <div class="bg-white rounded-3xl border border-gray-100 p-6 grid grid-cols-1 md:grid-cols-2 gap-8 shadow-sm">

            <!-- Gambar -->
            <div class="space-y-4">
                <div class="aspect-square bg-gray-100 rounded-2xl overflow-hidden border border-gray-100">
                    <img src="<?php echo esc_url( $foto_url ); ?>" alt="<?php the_title_attribute(); ?>" class="w-full h-full object-cover">
                </div>
                <div class="grid grid-cols-4 gap-2">
                    <div class="aspect-square bg-gray-100 rounded-lg border-2 border-brand overflow-hidden">
                        <img src="<?php echo esc_url( $foto_url ); ?>" class="w-full h-full object-cover opacity-80">
                    </div>
                    <div class="aspect-square bg-gray-50 rounded-lg border border-dashed border-gray-200 flex items-center justify-center text-gray-300 text-xs">📸</div>
                    <div class="aspect-square bg-gray-50 rounded-lg border border-dashed border-gray-200 flex items-center justify-center text-gray-300 text-xs">📸</div>
                    <div class="aspect-square bg-gray-50 rounded-lg border border-dashed border-gray-200 flex items-center justify-center text-gray-300 text-xs">📸</div>
                </div>
            </div>

            <!-- Info Produk -->
            <div class="space-y-5" id="product-container"
                data-product-id="<?php the_ID(); ?>"
                data-nonce="<?php echo wp_create_nonce('foodmarket_cart_nonce'); ?>">

                <div>
                    <span class="bg-brand/10 text-brand text-[10px] font-black uppercase tracking-wider px-2.5 py-1 rounded-full mb-2 inline-block">
                        🍕 <?php echo esc_html( $nama_kategori ); ?>
                    </span>
                    <h1 class="text-2xl font-bold text-gray-900"><?php the_title(); ?></h1>
                    <div class="flex items-center gap-2 mt-1">
                        <span class="text-xs text-gray-500">🏪 Seller: <?php the_author(); ?></span>
                        <span class="text-xs text-yellow-500">⭐ 4.8 (120 ulasan)</span>
                    </div>
                </div>

                <div class="text-2xl font-black text-brand">
                    <?php echo $harga_produk ? 'Rp' . number_format( $harga_produk, 0, ',', '.' ) : 'Rp0'; ?>
                </div>

                <div class="text-xs text-gray-600 leading-relaxed prose max-w-none">
                    <?php the_content(); ?>
                </div>

                <!-- Pilihan Pedas -->
                <div class="space-y-2">
                    <label class="text-xs font-bold text-gray-700 block">Pilihan Level Pedas</label>
                    <div class="flex flex-wrap gap-2" id="pedas-selector">
                        <button type="button" data-level="Tidak Pedas" class="pedas-btn px-3 py-1.5 rounded-lg border border-gray-200 text-xs hover:border-brand transition">Tidak Pedas 🍃</button>
                        <button type="button" data-level="Sedang"      class="pedas-btn px-3 py-1.5 rounded-lg border-2 border-brand bg-brand-light text-brand text-xs font-medium">Sedang 🔥</button>
                        <button type="button" data-level="Pedas"       class="pedas-btn px-3 py-1.5 rounded-lg border border-gray-200 text-xs hover:border-brand transition">Pedas 🔥🔥🔥</button>
                    </div>
                </div>

                <!-- Catatan -->
                <div class="space-y-2">
                    <label class="text-xs font-bold text-gray-700 block">Catatan untuk Penjual (Opsional)</label>
                    <input type="text" id="cart-note" placeholder="Contoh: tanpa timun, tambah sambal..."
                        class="w-full text-xs border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-brand bg-gray-50 focus:bg-white transition-all">
                </div>

                <!-- Stok & Qty -->
                <div class="pt-4 border-t border-gray-100 space-y-4">
                    <?php if ( $stok_int <= 0 ) : ?>
                        <div class="bg-red-50 text-red-500 text-xs font-bold px-4 py-3 rounded-xl border border-red-100 text-center">
                            ❌ Stok habis, menu ini tidak tersedia saat ini.
                        </div>
                    <?php else : ?>
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-medium text-gray-600">
                                Stok: <b class="<?php echo $stok_int < 5 ? 'text-red-500' : 'text-gray-900'; ?>" id="stock-val"><?php echo $stok_int; ?></b> Porsi
                            </span>
                            <div class="flex items-center border border-gray-200 rounded-lg overflow-hidden bg-white shadow-sm">
                                <button type="button" id="btn-minus" class="px-3 py-1 text-gray-500 hover:bg-gray-50 font-bold text-sm transition">-</button>
                                <span class="px-4 py-1 text-gray-900 font-bold select-none text-sm" id="qty-val">1</span>
                                <button type="button" id="btn-plus"  class="px-3 py-1 text-gray-500 hover:bg-gray-50 font-bold text-sm transition">+</button>
                            </div>
                        </div>

                        <div class="flex gap-3">
                            <button type="button" class="px-4 py-3 rounded-xl border border-gray-200 text-gray-400 hover:text-red-500 hover:bg-red-50 hover:border-red-200 transition text-sm shadow-sm">❤️</button>
                            <button type="button" id="btn-add-to-cart"
                                class="flex-1 bg-brand hover:bg-brand-dark text-white font-semibold py-3 rounded-xl transition text-sm shadow-md shadow-brand/10">
                                + Keranjang Belanja
                            </button>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>

        <!-- FIX: Toast notification menggantikan alert() yang mengganggu -->
        <div id="toast-notif"
            class="fixed bottom-6 right-6 z-50 hidden max-w-xs bg-gray-900 text-white text-xs font-semibold px-5 py-3 rounded-2xl shadow-xl transition-all">
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function () {
            const btnPlus    = document.getElementById('btn-plus');
            const btnMinus   = document.getElementById('btn-minus');
            const qtyVal     = document.getElementById('qty-val');
            const btnAddCart = document.getElementById('btn-add-to-cart');
            const toast      = document.getElementById('toast-notif');
            if ( ! btnAddCart ) return; // Stok habis — tidak ada tombol

            const pedasBtns  = document.querySelectorAll('.pedas-btn');
            let currentQty   = 1;
            let selectedPedas = 'Sedang';
            const maxStock   = parseInt( document.getElementById('stock-val').innerText ) || 0;

            function showToast( msg, isError ) {
                toast.textContent = msg;
                toast.className = 'fixed bottom-6 right-6 z-50 max-w-xs text-white text-xs font-semibold px-5 py-3 rounded-2xl shadow-xl '
                    + ( isError ? 'bg-red-600' : 'bg-gray-900' );
                setTimeout(() => toast.classList.add('hidden'), 3000);
            }

            if ( btnPlus ) btnPlus.addEventListener('click', () => {
                if ( currentQty < maxStock ) { currentQty++; qtyVal.innerText = currentQty; }
            });

            if ( btnMinus ) btnMinus.addEventListener('click', () => {
                if ( currentQty > 1 ) { currentQty--; qtyVal.innerText = currentQty; }
            });

            pedasBtns.forEach(btn => {
                btn.addEventListener('click', function () {
                    pedasBtns.forEach(b => b.className = 'pedas-btn px-3 py-1.5 rounded-lg border border-gray-200 text-xs hover:border-brand transition');
                    this.className = 'pedas-btn px-3 py-1.5 rounded-lg border-2 border-brand bg-brand-light text-brand text-xs font-medium';
                    selectedPedas = this.getAttribute('data-level');
                });
            });

            btnAddCart.addEventListener('click', function () {
                const container = document.getElementById('product-container');
                const formData  = new FormData();
                formData.append('action',     'add_to_cart');
                formData.append('product_id', container.getAttribute('data-product-id'));
                formData.append('quantity',   currentQty);
                formData.append('pedas',      selectedPedas);
                formData.append('note',       document.getElementById('cart-note').value);
                formData.append('nonce',      container.getAttribute('data-nonce'));

                btnAddCart.textContent = 'Memasukkan...';
                btnAddCart.disabled = true;

                fetch('<?php echo admin_url("admin-ajax.php"); ?>', { method: 'POST', body: formData })
                    .then(r => r.json())
                    .then(res => {
                        if ( res.success ) {
                            showToast('✅ ' + res.data.message, false);
                            const badge = document.getElementById('cart-count-badge');
                            if ( badge ) {
                                badge.textContent = res.data.total_items;
                                badge.classList.remove('hidden');
                            }
                            currentQty = 1;
                            qtyVal.innerText = 1;
                            document.getElementById('cart-note').value = '';
                        } else {
                            showToast('❌ ' + res.data.message, true);
                        }
                    })
                    .catch(() => showToast('❌ Gagal terhubung ke server.', true))
                    .finally(() => {
                        btnAddCart.textContent = '+ Keranjang Belanja';
                        btnAddCart.disabled = false;
                    });
            });
        });
        </script>

    <?php endwhile; endif; ?>
</main>

<?php get_footer(); ?>