<?php

// 1. Memuat Script dan Konfigurasi Tailwind CSS (Diproteksi dari Halaman Admin)
function foodmarket_theme_scripts() {
    // PROTEKSI: Jika membuka area wp-admin, batalkan pemuatan Tailwind agar UI asli tidak hancur
    if ( is_admin() ) {
        return;
    }

    wp_enqueue_script('tailwind-cdn', 'https://cdn.tailwindcss.com', array(), null, false);
    
    wp_add_inline_script('tailwind-cdn', "
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            DEFAULT: '#E57C23',
                            light: '#FFF4E0',
                            dark: '#C86210'
                        }
                    }
                }
            }
        }
    ");
}
add_action('wp_enqueue_scripts', 'foodmarket_theme_scripts');

// 2. Setup Dasar Tema
function foodmarket_theme_setup() {
    add_theme_support('post-thumbnails');
    add_theme_support('title-tag');
}
add_action('after_setup_theme', 'foodmarket_theme_setup');


// 3. DAFTARKAN ROLE SELLER (Optimasi: Hanya dieksekusi saat tema diaktifkan pertama kali)
function foodmarket_register_seller_role() {
    add_role('seller', 'Seller (Penjual)', array(
        'read'         => true,
        'upload_files' => true,
    ));
}
add_action('after_switch_theme', 'foodmarket_register_seller_role');

// 4. PENGALIHAN SETELAH LOGIN SUKSES
function foodmarket_custom_login_redirect($redirect_to, $request, $user) {
    if (isset($user->roles) && is_array($user->roles)) {
        if (in_array('administrator', $user->roles) || in_array('seller', $user->roles)) {
            return home_url('/dashboard-seller/?view=produk');
        } else {
            return home_url();
        }
    }
    return $redirect_to;
}
add_filter('login_redirect', 'foodmarket_custom_login_redirect', 10, 3);

// 5. HANDLER: PROSES SIMPAN / TAMBAH PRODUK BARU DARI DASHBOARD SELLER
function foodmarket_handle_tambah_produk() {
    if ( !isset($_POST['foodmarket_nonce']) || !wp_verify_nonce($_POST['foodmarket_nonce'], 'foodmarket_tambah_produk_action') ) {
        wp_die('Akses ditolak demi keamanan.');
    }

    if ( !is_user_logged_in() ) {
        wp_die('Anda harus login terlebih dahulu.');
    }
    
    $current_user = wp_get_current_user();
    if ( !in_array('administrator', $current_user->roles) && !in_array('seller', $current_user->roles) ) {
        wp_die('Anda tidak memiliki izin untuk menambah produk.');
    }

    $nama_produk      = sanitize_text_field($_POST['nama_produk']);
    $deskripsi_produk = wp_kses_post($_POST['deskripsi_produk']);
    $kategori_slug    = sanitize_text_field($_POST['kategori_produk']);
    $harga            = intval($_POST['harga_produk']);
    $stok             = intval($_POST['stok_produk']);

    if ( empty($nama_produk) || empty($harga) ) {
        wp_redirect( home_url('/dashboard-seller/?view=produk&status=gagal') );
        exit;
    }

    $new_post = array(
        'post_title'    => $nama_produk,
        'post_content'  => $deskripsi_produk,
        'post_status'   => 'publish', 
        'post_type'     => 'produk',
        'post_author'   => get_current_user_id()
    );

    $post_id = wp_insert_post($new_post);

    if ( $post_id && !is_wp_error($post_id) ) {
        update_post_meta($post_id, '_harga_produk', $harga);
        update_post_meta($post_id, '_stok_produk', $stok);

        $term = get_term_by('slug', $kategori_slug, 'kategori_makanan');
        if (!$term) {
            $new_term = wp_insert_term(ucwords(str_replace('-', ' ', $kategori_slug)), 'kategori_makanan', array('slug' => $kategori_slug));
            $term_id = !is_wp_error($new_term) ? $new_term['term_id'] : false;
        } else {
            $term_id = $term->term_id;
        }

        if ($term_id) {
            wp_set_object_terms($post_id, intval($term_id), 'kategori_makanan');
        }

        if ( !empty($_FILES['foto_produk']['name']) ) {
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');

            $attachment_id = media_handle_upload('foto_produk', $post_id);
            if ( !is_wp_error($attachment_id) ) {
                set_post_thumbnail($post_id, $attachment_id);
            }
        }

        wp_redirect( home_url('/dashboard-seller/?view=produk&status=sukses') );
        exit;
    } else {
        wp_redirect( home_url('/dashboard-seller/?view=produk&status=gagal') );
        exit;
    }
}
add_action('admin_post_foodmarket_tambah_produk', 'foodmarket_handle_tambah_produk');
add_action('admin_post_nopriv_foodmarket_tambah_produk', 'foodmarket_handle_tambah_produk');


// 5B. PROCESS UPDATE / EDIT PRODUK DARI DASHBOARD SELLER
function foodmarket_handle_edit_produk() {
    if ( !isset($_POST['foodmarket_nonce']) || !wp_verify_nonce($_POST['foodmarket_nonce'], 'foodmarket_edit_produk_action') ) {
        wp_die('Akses ditolak demi keamanan.');
    }

    if ( !is_user_logged_in() ) {
        wp_die('Anda harus login terlebih dahulu.');
    }

    $product_id = intval($_POST['product_id']);
    $post_author = get_post_field('post_author', $product_id);
    $current_user_id = get_current_user_id();

    if ( $post_author != $current_user_id && !current_user_can('administrator') ) {
        wp_die('Anda tidak memiliki hak untuk mengedit produk ini.');
    }

    $nama_produk      = sanitize_text_field($_POST['nama_produk']);
    $deskripsi_produk = wp_kses_post($_POST['deskripsi_produk']);
    $kategori_slug    = sanitize_text_field($_POST['kategori_produk']);
    $harga            = intval($_POST['harga_produk']);
    $stok             = intval($_POST['stok_produk']);

    if ( empty($nama_produk) || empty($harga) ) {
        wp_redirect( home_url('/dashboard-seller/?view=produk&action=edit&id='.$product_id.'&status=gagal') );
        exit;
    }

    $updated_post = array(
        'ID'           => $product_id,
        'post_title'   => $nama_produk,
        'post_content' => $deskripsi_produk,
    );
    wp_update_post($updated_post);

    update_post_meta($product_id, '_harga_produk', $harga);
    update_post_meta($product_id, '_stok_produk', $stok);

    $term = get_term_by('slug', $kategori_slug, 'kategori_makanan');
    if ($term) {
        wp_set_object_terms($product_id, intval($term->term_id), 'kategori_makanan');
    }

    if ( !empty($_FILES['foto_produk']['name']) ) {
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $attachment_id = media_handle_upload('foto_produk', $product_id);
        if ( !is_wp_error($attachment_id) ) {
            set_post_thumbnail($product_id, $attachment_id);
        }
    }

    wp_redirect( home_url('/dashboard-seller/?view=produk&status=update-sukses') );
    exit;
}
add_action('admin_post_foodmarket_edit_produk', 'foodmarket_handle_edit_produk');


// 6. ENGINE SESSION PHP FOR CART SYSTEM
function foodmarket_start_session() {
    if ( ! session_id() ) {
        session_start();
    }
}
add_action( 'init', 'foodmarket_start_session', 1 );

// 7. Handler AJAX: Tambah ke Keranjang Belanja
function foodmarket_add_to_cart_handler() {
    if ( ! isset($_POST['nonce']) || ! wp_verify_nonce($_POST['nonce'], 'foodmarket_cart_nonce') ) {
        wp_send_json_error( array( 'message' => 'Akses ilegal (Invalid Nonce).' ) );
    }

    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $quantity   = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
    $note       = isset($_POST['note']) ? sanitize_text_field($_POST['note']) : '';
    $pedas      = isset($_POST['pedas']) ? sanitize_text_field($_POST['pedas']) : 'Sedang';

    if ( $product_id <= 0 ) {
        wp_send_json_error( array( 'message' => 'Produk tidak valid.' ) );
    }

    $cart = isset($_SESSION['foodmarket_cart']) ? $_SESSION['foodmarket_cart'] : array();
    $cart_key = $product_id . '_' . sanitize_title($pedas);

    if ( isset($cart[$cart_key]) ) {
        $cart[$cart_key]['quantity'] += $quantity;
        $cart[$cart_key]['note']      = $note; 
    } else {
        $cart[$cart_key] = array(
            'product_id' => $product_id,
            'quantity'   => $quantity,
            'pedas'      => $pedas,
            'note'       => $note
        );
    }

    $_SESSION['foodmarket_cart'] = $cart;

    $total_items = 0;
    foreach ( $cart as $item ) {
        $total_items += $item['quantity'];
    }

    wp_send_json_success( array(
        'message'     => 'Berhasil ditambahkan ke keranjang!',
        'total_items' => $total_items
    ) );
}
add_action( 'wp_ajax_add_to_cart', 'foodmarket_add_to_cart_handler' );
add_action( 'wp_ajax_nopriv_add_to_cart', 'foodmarket_add_to_cart_handler' );


// 8. HANDLER: PROSES DATA FORM CHECKOUT (SIMPAN TRANSAKSI KE CPT PESANAN)
function foodmarket_proses_checkout_handler() {
    if ( ! isset($_POST['foodmarket_checkout_nonce']) || ! wp_verify_nonce($_POST['foodmarket_checkout_nonce'], 'foodmarket_proses_checkout_action') ) {
        wp_die('Akses ditolak. Token keamanan tidak valid.');
    }

    if ( ! is_user_logged_in() || ! isset($_SESSION['foodmarket_cart']) || empty($_SESSION['foodmarket_cart']) ) {
        wp_redirect( home_url() );
        exit;
    }

    $nama_penerima = sanitize_text_field($_POST['order_nama']);
    $no_hp         = sanitize_text_field($_POST['order_hp']);
    $metode_bayar  = sanitize_text_field($_POST['order_pembayaran']);
    $alamat_kirim  = sanitize_textarea_field($_POST['order_alamat']);
    $current_buyer = wp_get_current_user();

    $invoice_number = 'INV-' . date('Ymd') . '-' . strtoupper( wp_generate_password(5, false, false) );

    $cart_items    = $_SESSION['foodmarket_cart'];
    $total_belanja = 0;
    $ongkir        = 10000; 
    $detail_item_pesanan = array();
    $primary_seller_id = 0; // Menyimpan ID seller utama untuk relasi dashboard

    foreach ( $cart_items as $item ) {
        $product_id = $item['product_id'];
        $harga      = get_post_meta($product_id, '_harga_produk', true);
        $subtotal   = $harga * $item['quantity'];
        $total_belanja += $subtotal;

        $seller_id  = get_post_field('post_author', $product_id);
        if( empty($primary_seller_id) ) {
            $primary_seller_id = $seller_id; // Mapping seller pertama
        }

        $detail_item_pesanan[] = array(
            'id_produk'     => $product_id,
            'nama_produk'   => get_the_title($product_id),
            'quantity'      => $item['quantity'],
            'level_pedas'   => $item['pedas'],
            'catatan'       => $item['note'],
            'harga_satuan'  => $harga,
            'id_seller'     => $seller_id
        );
    }

    $total_pembayaran = $total_belanja + $ongkir;

    // FIX: Gunakan 'post_status' asli untuk workflow status ('publish' diganti 'pending' sebagai default pesanan masuk baru)
    $order_id = wp_insert_post(array(
        'post_title'   => $invoice_number,
        'post_status'  => 'publish', // Di dashboard Anda dibaca sebagai Order Baru / Processing
        'post_type'    => 'pesanan',
        'post_author'  => $current_buyer->ID,
    ));

    if ( ! is_wp_error($order_id) ) {
        update_post_meta($order_id, '_order_nama_penerima', $nama_penerima);
        update_post_meta($order_id, '_nama_pembeli', $nama_penerima); // Sinkronisasi key dashboard Anda
        update_post_meta($order_id, '_order_no_hp', $no_hp);
        update_post_meta($order_id, '_order_metode_bayar', $metode_bayar);
        update_post_meta($order_id, '_order_alamat_kirim', $alamat_kirim);
        update_post_meta($order_id, '_order_total_harga', $total_pembayaran);
        update_post_meta($order_id, '_total_harga', $total_pembayaran); // Sinkronisasi key dashboard Anda
        
        // FIX: Tambahkan key '_seller_id' dan '_detail_item' agar dibaca oleh query dashboard seller Anda
        update_post_meta($order_id, '_seller_id', $primary_seller_id); 
        
        $text_summary = $cart_items ? count($cart_items) . 'x Menu Makanan' : 'Pesanan Kuliner';
        update_post_meta($order_id, '_detail_item', $text_summary);

        update_post_meta($order_id, '_order_items_detail', $detail_item_pesanan); 

        unset($_SESSION['foodmarket_cart']);

        wp_redirect( home_url('/?status=pesanan-sukses&invoice=' . $invoice_number) );
        exit;
    } else {
        wp_die('Gagal memproses pesanan.');
    }
}
add_action('admin_post_foodmarket_proses_checkout', 'foodmarket_proses_checkout_handler');
add_action('admin_post_nopriv_foodmarket_proses_checkout', 'foodmarket_proses_checkout_handler');


// ==========================================================
// FIX AJAX HANDLER: CEK PESANAN BARU (POLLING - ISOLASI DATA)
// ==========================================================
function foodmarket_check_new_orders() {
    if ( ! is_user_logged_in() ) {
        wp_send_json_error(array('message' => 'Unauthorized.'));
    }

    $current_user_id = get_current_user_id();
    $is_admin = current_user_can('administrator');

    // FIX: Batasi hitungan data pesanan baru hanya milik seller yang sedang aktif request
    $order_args = array(
        'post_type'      => 'pesanan',
        'post_status'    => array('publish', 'processing'), // Status order aktif baru masuk
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'meta_query'     => $is_admin ? '' : array(
            array(
                'key'     => '_seller_id',
                'value'   => $current_user_id,
                'compare' => '='
            )
        )
    );
    
    $order_query = new WP_Query($order_args);
    
    wp_send_json_success(array(
        'count' => $order_query->found_posts
    ));
}
add_action('wp_ajax_check_new_orders', 'foodmarket_check_new_orders');
add_action('wp_ajax_nopriv_check_new_orders', 'foodmarket_check_new_orders');