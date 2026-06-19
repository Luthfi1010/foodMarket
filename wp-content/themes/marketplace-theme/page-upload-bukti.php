<?php
/**
 * Template Name: Halaman Upload Bukti
 */

get_header();

$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
$status_order = get_post_meta($order_id, '_status_order', true);
$invoice_num = get_the_title($order_id);

// Proteksi halaman jika order tidak valid
if ( ! $order_id || get_post_type($order_id) !== 'pesanan' ) {
    echo '<main class="max-w-md mx-auto px-4 py-20 text-center"><p class="text-xs text-gray-500">Pesanan tidak ditemukan.</p></main>';
    get_footer();
    exit;
}
?>

<main class="max-w-md mx-auto px-4 py-8 min-h-screen bg-gray-50/60 font-sans">
    <div class="bg-white border border-gray-100 rounded-3xl p-6 shadow-sm space-y-6">
        
        <div>
            <h3 class="text-base font-black text-gray-900">Konfirmasi Pembayaran</h3>
            <p class="text-xs text-gray-400 mt-1">Kirimkan bukti transfer untuk pesanan <span class="font-mono font-bold text-gray-700"><?php echo esc_html($invoice_num); ?></span></p>
        </div>

        <?php if ( isset($_GET['status']) && $_GET['status'] === 'sukses' ) : ?>
            <div class="p-4 bg-green-50 border border-green-100 rounded-2xl text-center space-y-2">
                <span class="text-3xl">🚀</span>
                <p class="text-xs font-bold text-green-700">Bukti Pembayaran Berhasil Dikirim!</p>
                <p class="text-[11px] text-green-600">Dapur sedang menyiapkan pesanan Anda. Silakan pantau berkala.</p>
            </div>
            <div class="pt-2">
                <a href="<?php echo home_url(); ?>" class="w-full block text-center bg-gray-900 text-white font-bold py-3.5 rounded-xl text-xs">Kembali ke Beranda</a>
            </div>

        <?php else : ?>
            <form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="POST" enctype="multipart/form-data" class="space-y-4">
                <input type="hidden" name="action" value="foodmarket_upload_bukti_bayar">
                <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
                <?php wp_nonce_field( 'foodmarket_upload_bukti_action', 'foodmarket_bukti_nonce' ); ?>

                <div class="space-y-1">
                    <label class="text-[11px] font-bold text-gray-400 uppercase">Pilih Foto / Gambar Bukti</label>
                    <input type="file" name="bukti_transfer" accept="image/*" required
                        class="w-full text-xs text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-[11px] file:font-bold file:bg-brand/10 file:text-brand hover:file:bg-brand/20 border border-gray-100 rounded-xl p-2 bg-gray-50/50">
                </div>

                <div class="pt-4 border-t border-gray-50">
                    <button type="submit" class="w-full bg-brand hover:bg-brand-dark text-white font-bold py-3.5 rounded-xl transition text-xs shadow-md shadow-brand/10">
                        📤 Kirim Bukti Pembayaran
                    </button>
                </div>
            </form>
        <?php endif; ?>

    </div>
</main>

<?php 
get_footer(); 
?>