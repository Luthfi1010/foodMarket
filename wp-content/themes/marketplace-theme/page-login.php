<?php
/**
 * Template Name: Halaman Login Custom
 */

// FIX: Redirect ke ?view=dashboard bukan ?view=tambah-produk
if ( is_user_logged_in() ) {
    $current_user = wp_get_current_user();
    if (in_array('seller', $current_user->roles) ) {
        wp_redirect( home_url('/dashboard-seller/?view=dashboard') );
    } else {
        wp_redirect( home_url() );
    }
    exit;
}

$error_message = '';

if ( isset( $_POST['foodmarket_login_submit'] ) ) {
    if ( ! isset( $_POST['foodmarket_login_nonce_field'] )
        || ! wp_verify_nonce( $_POST['foodmarket_login_nonce_field'], 'foodmarket_login_action' ) ) {
        $error_message = 'Token keamanan kedaluwarsa. Silakan muat ulang halaman.';
    } else {
        $creds = array(
            'user_login'    => sanitize_text_field( $_POST['log_username'] ),
            'user_password' => $_POST['log_password'],
            'remember'      => isset( $_POST['log_remember'] ),
        );

        $user = wp_signon( $creds, false );

        if ( is_wp_error( $user ) ) {
            $error_message = 'Username atau password salah. Silakan coba lagi.';
        } else {
            // FIX: redirect ke ?view=dashboard
            if (in_array('seller', $user->roles) ) {
                wp_redirect( home_url('/dashboard-seller/?view=dashboard') );
            } else {
                wp_redirect( home_url() );
            }
            exit;
        }
    }
}

get_header(); ?>

<main class="max-w-md mx-auto px-4 py-16">
    <div class="bg-white p-8 rounded-3xl border border-gray-100 shadow-sm space-y-6">

        <div class="text-center space-y-2">
            <h2 class="text-2xl font-black text-gray-950 tracking-tight">Masuk ke <span class="text-brand">FoodMarket</span></h2>
            <p class="text-xs text-gray-500">Nikmati kuliner terbaik atau kelola tokomu dengan mudah.</p>
        </div>

        <?php if ( ! empty( $error_message ) ) : ?>
            <div class="bg-red-50 text-red-600 text-xs p-3.5 rounded-xl border border-red-100 font-medium text-center">
                ❌ <?php echo esc_html( $error_message ); ?>
            </div>
        <?php endif; ?>

        <form method="post" class="space-y-4">
            <?php wp_nonce_field('foodmarket_login_action', 'foodmarket_login_nonce_field'); ?>

            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Username atau Email</label>
                <input type="text" name="log_username" required
                    class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm focus:border-brand focus:ring-1 focus:ring-brand outline-none transition"
                    placeholder="Masukkan username Anda">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Password</label>
                <input type="password" name="log_password" required
                    class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm focus:border-brand focus:ring-1 focus:ring-brand outline-none transition"
                    placeholder="••••••••">
            </div>

            <div class="flex items-center justify-between text-xs pt-1">
                <label class="flex items-center gap-2 cursor-pointer text-gray-600">
                    <input type="checkbox" name="log_remember" class="accent-brand rounded"> Ingat Saya
                </label>
                <a href="<?php echo wp_lostpassword_url(); ?>" class="text-brand font-bold hover:underline">Lupa Password?</a>
            </div>

            <button type="submit" name="foodmarket_login_submit"
                class="w-full bg-brand hover:bg-brand-dark text-white font-bold py-3.5 rounded-2xl transition text-sm shadow-md shadow-brand/10 mt-2">
                Masuk Sekarang
            </button>
        </form>

        <div class="text-center border-t border-gray-50 pt-4">
            <p class="text-xs text-gray-500">Belum punya akun? <a href="<?php echo home_url('/register'); ?>" class="text-brand font-bold hover:underline">Daftar Jadi Buyer/Seller</a></p>
        </div>
    </div>
</main>

<?php get_footer(); ?>