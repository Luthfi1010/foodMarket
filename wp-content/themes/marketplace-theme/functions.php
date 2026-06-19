<?php

// ============================================================
// 1. ENQUEUE TAILWIND CSS
// ============================================================
add_action( 'wp_enqueue_scripts', 'foodmarket_theme_scripts' );
function foodmarket_theme_scripts() {
    if ( is_admin() ) return;

    wp_enqueue_script( 'tailwind-cdn', 'https://cdn.tailwindcss.com', array(), null, false );
    wp_add_inline_script( 'tailwind-cdn', "
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            DEFAULT: '#E57C23',
                            light:   '#FFF4E0',
                            dark:    '#C86210'
                        }
                    }
                }
            }
        }
    " );
}

// ============================================================
// 2. SETUP TEMA
// ============================================================
add_action( 'after_setup_theme', 'foodmarket_theme_setup' );
function foodmarket_theme_setup() {
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'title-tag' );
}

// ============================================================
// 3. REGISTER ROLE SELLER
// ============================================================
add_action( 'after_switch_theme', 'foodmarket_register_seller_role' );
function foodmarket_register_seller_role() {
    add_role( 'seller', 'Seller (Penjual)', array(
        'read'         => true,
        'upload_files' => true,
    ) );
}

// ============================================================
// 4. REDIRECT SETELAH LOGIN
// ============================================================
add_filter( 'login_redirect', 'foodmarket_custom_login_redirect', 10, 3 );
function foodmarket_custom_login_redirect( $redirect_to, $request, $user ) {
    if ( isset( $user->roles ) && is_array( $user->roles ) ) {
        if ( in_array( 'administrator', $user->roles ) || in_array( 'seller', $user->roles ) ) {
            return home_url( '/dashboard-seller/?view=dashboard' );
        }
        return home_url();
    }
    return $redirect_to;
}

// ============================================================
// 5. SESSION START
// ============================================================
add_action( 'init', 'foodmarket_start_session', 1 );
function foodmarket_start_session() {
    if ( ! session_id() ) {
        session_start();
    }
}

// ============================================================
// FIX: HENTIKAN WORDPRESS DARI MENGANGGAP "?s=" SEBAGAI is_search()
// ============================================================
// PENYEBAB REDIRECT LOOP SEBELUMNYA:
// WordPress menandai is_search() = true SELAMA query var "s" ada di
// URL — termasuk saat sudah berada di homepage. Jadi redirect ke
// home_url('/?s=...') tetap dianggap is_search() oleh WordPress,
// lalu redirect_search_to_home() jalan lagi tanpa henti (loop).
//
// SOLUSI: Matikan flag is_search secara paksa di awal request kalau
// kita berada di halaman depan (front page), supaya WordPress
// memuat front-page.php seperti biasa walau ada "?s=" di URL.
// Logic pencarian sendiri tetap jalan manual lewat $_GET['s'] di
// dalam front-page.php (tidak butuh WP_Query 's' bawaan WordPress).
// ============================================================
add_action( 'parse_query', 'foodmarket_force_front_page_on_search', 0 );
function foodmarket_force_front_page_on_search( $query ) {
    if ( ! is_admin() && $query->is_main_query() && isset( $_GET['s'] ) ) {
        // Jika request ini sebenarnya menuju homepage (tidak ada path lain),
        // paksa WordPress menganggapnya sebagai front page, bukan search.
        $query->is_search = false;
        $query->is_home   = true;
        $query->set( 's', '' ); // kosongkan biar WP tidak ikut query 's' bawaan
    }
}

// ============================================================
// 6. HANDLER: TAMBAH PRODUK BARU
// ============================================================
add_action( 'admin_post_foodmarket_tambah_produk', 'foodmarket_handle_tambah_produk' );
function foodmarket_handle_tambah_produk() {
    if ( ! isset( $_POST['foodmarket_nonce'] )
        || ! wp_verify_nonce( $_POST['foodmarket_nonce'], 'foodmarket_tambah_produk_action' ) ) {
        wp_die( 'Akses ditolak demi keamanan.' );
    }

    if ( ! is_user_logged_in() ) {
        wp_die( 'Anda harus login terlebih dahulu.' );
    }

    $current_user = wp_get_current_user();
    if ( ! in_array( 'administrator', $current_user->roles )
        && ! in_array( 'seller', $current_user->roles ) ) {
        wp_die( 'Anda tidak memiliki izin untuk menambah produk.' );
    }

    $nama_produk      = sanitize_text_field( $_POST['nama_produk']      ?? '' );
    $deskripsi_produk = wp_kses_post(        $_POST['deskripsi_produk'] ?? '' );
    $kategori_slug    = sanitize_text_field( $_POST['kategori_produk']  ?? '' );
    $harga            = intval(              $_POST['harga_produk']      ?? 0 );
    $stok             = intval(              $_POST['stok_produk']       ?? 0 );

    if ( empty( $nama_produk ) || $harga <= 0 ) {
        wp_redirect( home_url( '/dashboard-seller/?view=produk&action=add&status=gagal' ) );
        exit;
    }

    $post_id = wp_insert_post( array(
        'post_title'   => $nama_produk,
        'post_content' => $deskripsi_produk,
        'post_status'  => 'publish',
        'post_type'    => 'produk',
        'post_author'  => get_current_user_id(),
    ) );

    if ( is_wp_error( $post_id ) ) {
        wp_redirect( home_url( '/dashboard-seller/?view=produk&action=add&status=gagal' ) );
        exit;
    }

    update_post_meta( $post_id, '_harga_produk', $harga );
    update_post_meta( $post_id, '_stok_produk',  $stok );

    $term = get_term_by( 'slug', $kategori_slug, 'kategori_makanan' );
    if ( ! $term ) {
        $new_term = wp_insert_term(
            ucwords( str_replace( '-', ' ', $kategori_slug ) ),
            'kategori_makanan',
            array( 'slug' => $kategori_slug )
        );
        $term_id = ( ! is_wp_error( $new_term ) ) ? $new_term['term_id'] : false;
    } else {
        $term_id = $term->term_id;
    }
    if ( $term_id ) {
        wp_set_object_terms( $post_id, intval( $term_id ), 'kategori_makanan' );
    }

    if ( ! empty( $_FILES['foto_produk']['name'] ) ) {
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        $att_id = media_handle_upload( 'foto_produk', $post_id );
        if ( ! is_wp_error( $att_id ) ) {
            set_post_thumbnail( $post_id, $att_id );
        }
    }

    wp_redirect( home_url( '/dashboard-seller/?view=produk&status=sukses' ) );
    exit;
}

// ============================================================
// 7. HANDLER: EDIT PRODUK
// ============================================================
add_action( 'admin_post_foodmarket_edit_produk', 'foodmarket_handle_edit_produk' );
function foodmarket_handle_edit_produk() {
    if ( ! isset( $_POST['foodmarket_nonce'] )
        || ! wp_verify_nonce( $_POST['foodmarket_nonce'], 'foodmarket_edit_produk_action' ) ) {
        wp_die( 'Akses ditolak demi keamanan.' );
    }

    if ( ! is_user_logged_in() ) {
        wp_die( 'Anda harus login terlebih dahulu.' );
    }

    $product_id      = intval( $_POST['product_id'] ?? 0 );
    $post_author     = get_post_field( 'post_author', $product_id );
    $current_user_id = get_current_user_id();

    if ( $post_author != $current_user_id && ! current_user_can( 'administrator' ) ) {
        wp_die( 'Anda tidak memiliki hak untuk mengedit produk ini.' );
    }

    $nama_produk      = sanitize_text_field( $_POST['nama_produk']      ?? '' );
    $deskripsi_produk = wp_kses_post(        $_POST['deskripsi_produk'] ?? '' );
    $kategori_slug    = sanitize_text_field( $_POST['kategori_produk']  ?? '' );
    $harga            = intval(              $_POST['harga_produk']      ?? 0 );
    $stok             = intval(              $_POST['stok_produk']       ?? 0 );

    if ( empty( $nama_produk ) || $harga <= 0 ) {
        wp_redirect( home_url( '/dashboard-seller/?view=produk&action=edit&id=' . $product_id . '&status=gagal' ) );
        exit;
    }

    wp_update_post( array(
        'ID'           => $product_id,
        'post_title'   => $nama_produk,
        'post_content' => $deskripsi_produk,
    ) );

    update_post_meta( $product_id, '_harga_produk', $harga );
    update_post_meta( $product_id, '_stok_produk',  $stok );

    $term = get_term_by( 'slug', $kategori_slug, 'kategori_makanan' );
    if ( $term ) {
        wp_set_object_terms( $product_id, intval( $term->term_id ), 'kategori_makanan' );
    }

    if ( ! empty( $_FILES['foto_produk']['name'] ) ) {
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        $att_id = media_handle_upload( 'foto_produk', $product_id );
        if ( ! is_wp_error( $att_id ) ) {
            set_post_thumbnail( $product_id, $att_id );
        }
    }

    wp_redirect( home_url( '/dashboard-seller/?view=produk&status=update-sukses' ) );
    exit;
}

// ============================================================
// 8. AJAX: TAMBAH KE KERANJANG
// ============================================================
add_action( 'wp_ajax_add_to_cart',        'foodmarket_add_to_cart_handler' );
add_action( 'wp_ajax_nopriv_add_to_cart', 'foodmarket_add_to_cart_handler' );
function foodmarket_add_to_cart_handler() {
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'foodmarket_cart_nonce' ) ) {
        wp_send_json_error( array( 'message' => 'Akses ilegal (Invalid Nonce).' ) );
    }

    $product_id = isset( $_POST['product_id'] ) ? intval( $_POST['product_id'] )              : 0;
    $quantity   = isset( $_POST['quantity'] )   ? intval( $_POST['quantity'] )                 : 1;
    $note       = isset( $_POST['note'] )       ? sanitize_text_field( $_POST['note'] )        : '';
    $pedas      = isset( $_POST['pedas'] )      ? sanitize_text_field( $_POST['pedas'] )       : 'Sedang';

    if ( $product_id <= 0 ) {
        wp_send_json_error( array( 'message' => 'Produk tidak valid.' ) );
    }

    $cart     = isset( $_SESSION['foodmarket_cart'] ) ? $_SESSION['foodmarket_cart'] : array();
    $cart_key = $product_id . '_' . sanitize_title( $pedas );

    if ( isset( $cart[$cart_key] ) ) {
        $cart[$cart_key]['quantity'] += $quantity;
        $cart[$cart_key]['note']      = $note;
    } else {
        $cart[$cart_key] = array(
            'product_id' => $product_id,
            'quantity'   => $quantity,
            'pedas'      => $pedas,
            'note'       => $note,
        );
    }

    $_SESSION['foodmarket_cart'] = $cart;

    $total_items = 0;
    foreach ( $cart as $item ) {
        $total_items += $item['quantity'];
    }

    wp_send_json_success( array(
        'message'     => 'Berhasil ditambahkan ke keranjang!',
        'total_items' => $total_items,
    ) );
}

// ============================================================
// 9. HANDLER: PROSES CHECKOUT
// ============================================================
add_action( 'admin_post_foodmarket_proses_checkout',        'foodmarket_proses_checkout_handler' );
add_action( 'admin_post_nopriv_foodmarket_proses_checkout', 'foodmarket_proses_checkout_handler' );
function foodmarket_proses_checkout_handler() {
    if ( ! isset( $_POST['foodmarket_checkout_nonce'] )
        || ! wp_verify_nonce( $_POST['foodmarket_checkout_nonce'], 'foodmarket_proses_checkout_action' ) ) {
        wp_die( 'Akses ditolak. Token keamanan tidak valid.' );
    }

    if ( ! is_user_logged_in()
        || ! isset( $_SESSION['foodmarket_cart'] )
        || empty( $_SESSION['foodmarket_cart'] ) ) {
        wp_redirect( home_url() );
        exit;
    }

    $cart_items = $_SESSION['foodmarket_cart'];

    foreach ( $cart_items as $item ) {
        $pid           = intval( $item['product_id'] );
        $qty_beli      = intval( $item['quantity'] );
        $stok_tersedia = intval( get_post_meta( $pid, '_stok_produk', true ) );

        if ( $stok_tersedia < $qty_beli ) {
            wp_redirect( home_url( '/checkout/?status=stok_kurang&produk=' . urlencode( get_the_title( $pid ) ) ) );
            exit;
        }
    }

    $nama_penerima = sanitize_text_field(     $_POST['order_nama']       ?? '' );
    $no_hp         = sanitize_text_field(     $_POST['order_hp']         ?? '' );
    $metode_bayar  = sanitize_text_field(     $_POST['order_pembayaran'] ?? '' );
    $alamat_kirim  = sanitize_textarea_field( $_POST['order_alamat']     ?? '' );
    $current_buyer = wp_get_current_user();

    $invoice_number    = 'INV-' . date('Ymd') . '-' . strtoupper( wp_generate_password( 5, false, false ) );
    $total_belanja     = 0;
    $ongkir            = 10000;
    $primary_seller_id = 0;
    $detail_items      = array();

    foreach ( $cart_items as $item ) {
        $pid   = $item['product_id'];
        $harga = intval( get_post_meta( $pid, '_harga_produk', true ) );
        $total_belanja += $harga * $item['quantity'];

        $seller_id = get_post_field( 'post_author', $pid );
        if ( empty( $primary_seller_id ) ) {
            $primary_seller_id = $seller_id;
        }

        $detail_items[] = array(
            'id_produk'    => $pid,
            'nama_produk'  => get_the_title( $pid ),
            'quantity'     => $item['quantity'],
            'level_pedas'  => $item['pedas'],
            'catatan'      => $item['note'],
            'harga_satuan' => $harga,
            'id_seller'    => $seller_id,
        );
    }

    $total_pembayaran = $total_belanja + $ongkir;
    $jumlah_item      = count( $cart_items );

    $order_id = wp_insert_post( array(
        'post_title'  => $invoice_number,
        'post_status' => 'publish',
        'post_type'   => 'pesanan',
        'post_author' => $current_buyer->ID,
    ) );

    if ( is_wp_error( $order_id ) ) {
        wp_die( 'Gagal memproses pesanan.' );
    }

    update_post_meta( $order_id, '_status_order', 'pending_approval' );
    update_post_meta( $order_id, '_order_nama_penerima',  $nama_penerima );
    update_post_meta( $order_id, '_order_no_hp',          $no_hp );
    update_post_meta( $order_id, '_order_metode_bayar',   $metode_bayar );
    update_post_meta( $order_id, '_order_alamat_kirim',   $alamat_kirim );
    update_post_meta( $order_id, '_order_total_harga',    $total_pembayaran );
    update_post_meta( $order_id, '_order_items_detail',   $detail_items );

    update_post_meta( $order_id, '_nama_pembeli', $nama_penerima );
    update_post_meta( $order_id, '_total_harga',  $total_pembayaran );
    update_post_meta( $order_id, '_seller_id',    $primary_seller_id );
    update_post_meta( $order_id, '_detail_item',  $jumlah_item . 'x Menu Makanan' );

    unset( $_SESSION['foodmarket_cart'] );

    wp_redirect( home_url( '/pembayaran-order/?order_id=' . $order_id ) );
    exit;
}

// ============================================================
// 10. AJAX: CHECK NEW ORDERS
// ============================================================
add_action( 'wp_ajax_check_new_orders', 'foodmarket_check_new_orders' );
function foodmarket_check_new_orders() {
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => 'Unauthorized.' ) );
    }

    $current_user_id = get_current_user_id();
    $is_admin        = current_user_can( 'administrator' );

    $order_args = array(
        'post_type'      => 'pesanan',
        'post_status'    => array( 'publish', 'processing' ),
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'meta_query'     => $is_admin ? array() : array(
            array(
                'key'     => '_seller_id',
                'value'   => $current_user_id,
                'compare' => '=',
            )
        ),
    );

    $order_query = new WP_Query( $order_args );

    wp_send_json_success( array(
        'count' => $order_query->found_posts,
    ) );
}

// ============================================================
// 11. HANDLER: SELLER APPROVAL
// ============================================================
add_action( 'admin_post_foodmarket_seller_decision', 'foodmarket_handle_seller_decision' );
function foodmarket_handle_seller_decision() {
    if ( ! is_user_logged_in() ) {
        wp_die( 'Anda harus login terlebih dahulu.' );
    }

    $current_user = wp_get_current_user();
    if ( ! in_array( 'administrator', $current_user->roles ) && ! in_array( 'seller', $current_user->roles ) ) {
        wp_die( 'Anda tidak memiliki hak untuk memproses pesanan ini.' );
    }

    $order_id = isset( $_POST['order_id'] ) ? intval( $_POST['order_id'] ) : 0;
    $decision = isset( $_POST['seller_action'] ) ? sanitize_text_field( $_POST['seller_action'] ) : '';

    if ( ! $order_id || ! $decision ) {
        wp_die( 'Data transaksi tidak valid.' );
    }

    $detail_items = get_post_meta( $order_id, '_order_items_detail', true );

    if ( $decision === 'approve' ) {

        if ( is_array( $detail_items ) ) {
            foreach ( $detail_items as $item ) {
                $pid           = intval( $item['id_produk'] );
                $qty_pesan     = intval( $item['quantity'] );
                $stok_sekarang = intval( get_post_meta( $pid, '_stok_produk', true ) );

                if ( $stok_sekarang < $qty_pesan ) {
                    wp_die( 'Gagal Menyetujui! Stok untuk menu "' . get_the_title( $pid ) . '" saat ini tidak mencukupi.' );
                }
            }

            foreach ( $detail_items as $item ) {
                $pid           = intval( $item['id_produk'] );
                $qty_pesan     = intval( $item['quantity'] );
                $stok_sekarang = intval( get_post_meta( $pid, '_stok_produk', true ) );
                $stok_baru     = max( 0, $stok_sekarang - $qty_pesan );
                update_post_meta( $pid, '_stok_produk', $stok_baru );
            }
        }

        update_post_meta( $order_id, '_status_order', 'waiting_payment' );

    } elseif ( $decision === 'reject' ) {
        update_post_meta( $order_id, '_status_order', 'rejected' );
    }

    wp_redirect( home_url('/dashboard-seller/?view=pesanan') );
    exit;
}

// ============================================================
// 12. REST API ENDPOINT: CEK STATUS ORDER
// ============================================================
add_action( 'rest_api_init', 'foodmarket_register_order_status_api' );
function foodmarket_register_order_status_api() {
    register_rest_route( 'foodmarket/v1', '/order-status/', array(
        'methods'             => 'GET',
        'callback'            => 'foodmarket_get_order_status_callback',
        'permission_callback' => '__return_true',
    ) );
}

function foodmarket_get_order_status_callback( $request ) {
    $order_id = intval( $request->get_param( 'order_id' ) );
    if ( ! $order_id || get_post_type( $order_id ) !== 'pesanan' ) {
        return new WP_Error( 'no_order', 'Order tidak ditemukan', array( 'status' => 404 ) );
    }

    $status_order = get_post_meta( $order_id, '_status_order', true ) ?: 'pending_approval';

    return array(
        'order_id' => $order_id,
        'status'   => $status_order,
    );
}

// ============================================================
// 13. HANDLER: UPLOAD BUKTI PEMBAYARAN
// ============================================================
add_action( 'admin_post_foodmarket_upload_bukti_bayar', 'foodmarket_handle_upload_bukti_bayar' );
function foodmarket_handle_upload_bukti_bayar() {
    if ( ! isset( $_POST['foodmarket_bukti_nonce'] ) || ! wp_verify_nonce( $_POST['foodmarket_bukti_nonce'], 'foodmarket_upload_bukti_action' ) ) {
        wp_die( 'Akses ilegal.' );
    }

    $order_id = isset( $_POST['order_id'] ) ? intval( $_POST['order_id'] ) : 0;
    if ( ! $order_id || get_post_type($order_id) !== 'pesanan' ) {
        wp_die( 'Pesanan tidak valid.' );
    }

    if ( ! empty( $_FILES['bukti_transfer']['name'] ) ) {
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';

        $attachment_id = media_handle_upload( 'bukti_transfer', $order_id );

        if ( ! is_wp_error( $attachment_id ) ) {
            update_post_meta( $order_id, '_bukti_pembayaran_id', $attachment_id );
            update_post_meta( $order_id, '_status_order', 'paid_processing' );

            wp_redirect( home_url( '/upload-bukti/?order_id=' . $order_id . '&status=sukses' ) );
            exit;
        } else {
            wp_die( 'Gagal mengunggah gambar bukti transfer: ' . $attachment_id->get_error_message() );
        }
    }

    wp_die( 'Mohon pilih berkas bukti terlebih dahulu.' );
}

// ============================================================
// 14. SYNC STATUS COMPLETED
// ============================================================
add_action( 'save_post_pesanan', 'foodmarket_sync_custom_status_completed_save', 10, 3 );
function foodmarket_sync_custom_status_completed_save( $post_id, $post, $update ) {
    if ( $post->post_status === 'completed' ) {
        update_post_meta( $post_id, '_status_order', 'completed' );
    }
}

add_action( 'init', 'foodmarket_handle_direct_complete_action' );
function foodmarket_handle_direct_complete_action() {
    if ( ! is_user_logged_in() || ! isset( $_GET['action'] ) || ! isset( $_GET['order_id'] ) ) {
        return;
    }

    if ( $_GET['action'] === 'complete_order' && current_user_can( 'edit_posts' ) ) {
        $order_id = intval( $_GET['order_id'] );

        if ( get_post_type( $order_id ) === 'pesanan' ) {
            update_post_meta( $order_id, '_status_order', 'completed' );
            wp_update_post( array(
                'ID'          => $order_id,
                'post_status' => 'completed',
            ) );

            wp_redirect( home_url( '/dashboard-seller/?view=pesanan' ) );
            exit;
        }
    }
}
