<?php
/**
 * Template Name: Halaman Register Custom
 */

// Proteksi: Jika user sudah login, langsung alihkan ke tempat yang sesuai
if ( is_user_logged_in() ) {
    $current_user = wp_get_current_user();
    if ( in_array('administrator', $current_user->roles) || in_array('seller', $current_user->roles) ) {
        wp_redirect( home_url('/dashboard-seller/?view=dashboard') );
    } else {
        wp_redirect( home_url() );
    }
    exit;
}

$error_message   = '';
$success_message = '';

if ( isset( $_POST['foodmarket_register_submit'] ) ) {
    // 1. Validasi Token Keamanan Nonce
    if ( ! isset( $_POST['foodmarket_register_nonce_field'] ) || ! wp_verify_nonce( $_POST['foodmarket_register_nonce_field'], 'foodmarket_register_action' ) ) {
        $error_message = 'Token keamanan kedaluwarsa. Silakan muat ulang halaman.';
    } else {
        // 2. Sanitasi Data Input Form
        $username   = sanitize_user( $_POST['reg_username'] );
        $email      = sanitize_email( $_POST['reg_email'] );
        $password   = $_POST['reg_password'];
        $nama_penuh = sanitize_text_field( $_POST['reg_fullname'] );
        $role_pilih = sanitize_text_field( $_POST['reg_role'] ); // Nilai: 'subscriber' atau 'seller'

        // 3. Validasi Kelayakan Data
        if ( username_exists( $username ) ) {
            $error_message = 'Username sudah digunakan oleh orang lain.';
        } elseif ( ! is_email( $email ) ) {
            $error_message = 'Format alamat email tidak valid.';
        } elseif ( email_exists( $email ) ) {
            $error_message = 'Alamat email sudah terdaftar di sistem.';
        } elseif ( strlen( $password ) < 6 ) {
            $error_message = 'Password terlalu pendek. Minimal menggunakan 6 karakter.';
        } elseif ( ! in_array( $role_pilih, array( 'subscriber', 'seller' ) ) ) {
            $error_message = 'Pilihan tipe akun tidak valid.';
        } else {
            // 4. Proses Pendaftaran User Baru ke Database WordPress
            $user_id = wp_create_user( $username, $password, $email );

            if ( is_wp_error( $user_id ) ) {
                $error_message = 'Gagal membuat akun: ' . $user_id->get_error_message();
            } else {
                // Set Nama Lengkap Display
                wp_update_user( array(
                    'ID'           => $user_id,
                    'display_name' => $nama_penuh,
                    'first_name'   => $nama_penuh
                ) );

                // 5. PENETAPAN ROLE YANG TEPAT (Isolasi Hak Akses)
                $user_obj = new WP_User( $user_id );
                $user_obj->set_role( $role_pilih );

                $success_message = 'Pendaftaran berhasil! Silakan masuk menggunakan akun baru Anda.';
                
                // Opsional: Otomatis login setelah daftar bisa ditaruh di sini, 
                // namun demi keamanan alur lebih baik diarahkan untuk ketik login manual.
            }
        }
    }
}

get_header(); ?>

<main class="max-w-md mx-auto px-4 py-12">
    <div class="bg-white p-8 rounded-3xl border border-gray-100 shadow-sm space-y-6">

        <div class="text-center space-y-2">
            <h2 class="text-2xl font-black text-gray-950 tracking-tight">Daftar Akun <span class="text-brand">FoodMarket</span></h2>
            <p class="text-xs text-gray-500">Bergabunglah untuk mulai memanjakan lidah atau mengelola tokomu sendiri.</p>
        </div>

        <?php if ( ! empty( $error_message ) ) : ?>
            <div class="bg-red-50 text-red-600 text-xs p-3.5 rounded-xl border border-red-100 font-medium text-center">
                ❌ <?php echo esc_html( $error_message ); ?>
            </div>
        <?php endif; ?>

        <?php if ( ! empty( $success_message ) ) : ?>
            <div class="bg-green-50 text-green-600 text-xs p-3.5 rounded-xl border border-green-100 font-medium text-center space-y-2">
                <div>🎉 <?php echo esc_html( $success_message ); ?></div>
                <div>
                    <a href="<?php echo home_url('/login'); ?>" class="inline-block text-[11px] bg-green-600 text-white px-3 py-1 rounded-lg font-bold uppercase tracking-wider mt-1 hover:bg-green-700 transition">Ke Halaman Masuk</a>
                </div>
            </div>
        <?php endif; ?>

        <form method="post" class="space-y-4">
            <?php wp_nonce_field('foodmarket_register_action', 'foodmarket_register_nonce_field'); ?>

            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Tipe Akun Anda</label>
                <div class="grid grid-cols-2 gap-3">
                    <label class="border border-gray-200 rounded-xl p-3 flex items-center gap-2.5 cursor-pointer hover:border-brand/50 transition">
                        <input type="radio" name="reg_role" value="subscriber" checked class="accent-brand">
                        <div class="text-left">
                            <div class="text-xs font-bold text-gray-900">Buyer (Pembeli)</div>
                            <div class="text-[10px] text-gray-400">Saya ingin jajan kuliner</div>
                        </div>
                    </label>
                    <label class="border border-gray-200 rounded-xl p-3 flex items-center gap-2.5 cursor-pointer hover:border-brand/50 transition">
                        <input type="radio" name="reg_role" value="seller" class="accent-brand">
                        <div class="text-left">
                            <div class="text-xs font-bold text-gray-900">Seller (Penjual)</div>
                            <div class="text-[10px] text-gray-400">Saya ingin jualan makanan</div>
                        </div>
                    </label>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Nama Lengkap</label>
                <input type="text" name="reg_fullname" required value="<?php echo isset($_POST['reg_fullname']) ? esc_attr($_POST['reg_fullname']) : ''; ?>"
                    class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm focus:border-brand focus:ring-1 focus:ring-brand outline-none transition"
                    placeholder="Contoh: Ahmad Subagja">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Username</label>
                <input type="text" name="reg_username" required value="<?php echo isset($_POST['reg_username']) ? esc_attr($_POST['reg_username']) : ''; ?>"
                    class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm focus:border-brand focus:ring-1 focus:ring-brand outline-none transition"
                    placeholder="Masukkan username tanpa spasi">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Alamat Email</label>
                <input type="email" name="reg_email" required value="<?php echo isset($_POST['reg_email']) ? esc_attr($_POST['reg_email']) : ''; ?>"
                    class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm focus:border-brand focus:ring-1 focus:ring-brand outline-none transition"
                    placeholder="nama@email.com">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Password</label>
                <input type="password" name="reg_password" required
                    class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm focus:border-brand focus:ring-1 focus:ring-brand outline-none transition"
                    placeholder="Minimal gunakan 6 karakter">
            </div>

            <p class="text-[11px] text-gray-400 leading-relaxed text-center pt-1">
                Dengan mendaftar, Anda menyetujui seluruh aturan layanan operasional platform transaksi kuliner kami.
            </p>

            <button type="submit" name="foodmarket_register_submit"
                class="w-full bg-brand hover:bg-brand-dark text-white font-bold py-3.5 rounded-2xl transition text-sm shadow-md shadow-brand/10 mt-2">
                Daftar Akun Baru
            </button>
        </form>

        <div class="text-center border-t border-gray-50 pt-4">
            <p class="text-xs text-gray-500">Sudah memiliki akun? <a href="<?php echo home_url('/login'); ?>" class="text-brand font-bold hover:underline">Masuk di Sini</a></p>
        </div>
    </div>
</main>

<?php get_footer(); ?>