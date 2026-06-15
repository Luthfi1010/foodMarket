<?php
/**
 * Template Name: Halaman Registrasi Custom
 */

// 1. PROTEKSI ROUTING: Jika user sudah login, arahkan secara presisi sesuai role
if (is_user_logged_in()) {
    $current_user = wp_get_current_user();
    if (in_array('administrator', $current_user->roles) || in_array('seller', $current_user->roles)) {
        wp_redirect(home_url('/dashboard-seller/?view=tambah-produk'));
    } else {
        wp_redirect(home_url());
    }
    exit;
}

$success_message = '';
$error_message = '';

// 2. PROSES FORM REGISTRASI
if (isset($_POST['foodmarket_register_submit'])) {
    
    // SINKRONISASI KEAMANAN: Cek Token Nonce dengan validasi super ketat
    if (!isset($_POST['foodmarket_reg_nonce_field']) || !wp_verify_nonce($_POST['foodmarket_reg_nonce_field'], 'foodmarket_register_action')) {
        $error_message = 'Token keamanan kedaluwarsa. Silakan muat ulang halaman atau bersihkan cache browser Anda.';
    } else {
        $username   = sanitize_user($_POST['reg_username']);
        $email      = sanitize_email($_POST['reg_email']);
        $password   = $_POST['reg_password'];
        $fullname   = sanitize_text_field($_POST['reg_fullname']);
        $role_input = sanitize_text_field($_POST['reg_role']); // 'buyer' atau 'seller'

        // Kunci pilihan tipe akun agar tidak bisa di-inject role lain
        if (!in_array($role_input, array('buyer', 'seller'))) {
            $role_input = 'buyer'; 
        }

        // Validasi Ketersediaan Data
        if (username_exists($username)) {
            $error_message = 'Username sudah digunakan. Silakan pilih username lain.';
        } elseif (email_exists($email)) {
            $error_message = 'Email sudah terdaftar. Silakan gunakan email lain.';
        } elseif (strlen($password) < 6) {
            $error_message = 'Password terlalu pendek! Minimal wajib 6 karakter.';
        } else {
            // Buat user baru di WordPress
            $user_id = wp_create_user($username, $password, $email);

            if (is_wp_error($user_id)) {
                $error_message = 'Terjadi kesalahan saat mendaftar: ' . $user_id->get_error_message();
            } else {
                // Update nama lengkap
                wp_update_user(array(
                    'ID'           => $user_id,
                    'display_name' => $fullname,
                    'nickname'     => $fullname
                ));

                // Alokasikan Role
                $user_obj = new WP_User($user_id);
                if ($role_input === 'seller') {
                    $user_obj->set_role('seller');
                } else {
                    $user_obj->set_role('subscriber'); // Subscriber bertindak sebagai Buyer
                }

                $success_message = 'Registrasi berhasil! Silakan masuk menggunakan akun Anda.';
            }
        }
    }
}

get_header(); ?>

<main class="max-w-md mx-auto px-4 py-12">
    <div class="bg-white p-8 rounded-3xl border border-gray-100 shadow-sm space-y-6">
        
        <div class="text-center space-y-2">
            <h2 class="text-2xl font-black text-gray-950 tracking-tight">Daftar Akun <span class="text-brand">Baru</span></h2>
            <p class="text-xs text-gray-500">Bergabunglah dan jelajahi surga kuliner FoodMarket.</p>
        </div>

        <?php if (!empty($success_message)) : ?>
            <div class="bg-green-50 text-green-600 text-xs p-4 rounded-xl border border-green-100 font-medium text-center space-y-2">
                <p>✅ <?php echo esc_html($success_message); ?></p>
                <a href="<?php echo home_url('/login'); ?>" class="block text-brand font-bold underline mt-1">Klik disini untuk Masuk</a>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_message)) : ?>
            <div class="bg-red-50 text-red-600 text-xs p-3.5 rounded-xl border border-red-100 font-medium text-center">
                ❌ <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <?php if (empty($success_message)) : ?>
            <form method="post" class="space-y-4">
                
                <?php wp_nonce_field('foodmarket_register_action', 'foodmarket_reg_nonce_field'); ?>
                
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Daftar Sebagai</label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="flex items-center justify-center gap-2 p-3 border border-gray-200 rounded-xl cursor-pointer hover:bg-gray-50 transition">
                            <input type="radio" name="reg_role" value="buyer" checked class="accent-brand">
                            <span class="text-xs font-bold text-gray-800">🛍️ Buyer</span>
                        </label>
                        <label class="flex items-center justify-center gap-2 p-3 border border-gray-200 rounded-xl cursor-pointer hover:bg-gray-50 transition">
                            <input type="radio" name="reg_role" value="seller" class="accent-brand">
                            <span class="text-xs font-bold text-gray-800">🧑‍🍳 Seller (Penjual)</span>
                        </label>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Nama Lengkap</label>
                    <input type="text" name="reg_fullname" required class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm focus:border-brand focus:ring-1 focus:ring-brand outline-none transition" placeholder="Masukkan nama lengkap Anda">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Username</label>
                    <input type="text" name="reg_username" required class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm focus:border-brand focus:ring-1 focus:ring-brand outline-none transition" placeholder="Contoh: budiseller">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Alamat Email</label>
                    <input type="email" name="reg_email" required class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm focus:border-brand focus:ring-1 focus:ring-brand outline-none transition" placeholder="budi@example.com">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Password</label>
                    <input type="password" name="reg_password" required class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm focus:border-brand focus:ring-1 focus:ring-brand outline-none transition" placeholder="Minimal 6 karakter">
                </div>

                <button type="submit" name="foodmarket_register_submit" class="w-full bg-brand hover:bg-brand-dark text-white font-bold py-3.5 rounded-2xl transition text-sm shadow-md shadow-brand/10 mt-2">
                    Mendaftar Sekarang
                </button>
            </form>
        <?php endif; ?>

        <div class="text-center border-t border-gray-50 pt-4">
            <p class="text-xs text-gray-500">Sudah punya akun? <a href="<?php echo home_url('/login'); ?>" class="text-brand font-bold hover:underline">Masuk disini</a></p>
        </div>

    </div>
</main>

<?php get_footer(); ?>