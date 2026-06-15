<?php
/*
Plugin Name: Marketplace Core Logic
Description: Mendaftarkan CPT dan Taxonomy untuk FoodMarket. Handler form ada di functions.php tema.
Version: 1.4
Author: Developer
*/

if ( ! defined('ABSPATH') ) exit;


add_action( 'init', 'foodmarket_register_core_elements' );
function foodmarket_register_core_elements() {

    register_post_type( 'produk', array(
        'labels'       => array(
            'name'          => 'Produk Kuliner',
            'singular_name' => 'Produk',
            'menu_name'     => 'Produk Kuliner',
            'add_new_item'  => 'Tambah Produk Baru',
            'edit_item'     => 'Edit Produk',
            'all_items'     => 'Semua Produk',
        ),
        'public'       => true,
        'has_archive'  => true,
        'show_in_rest' => true,
        'menu_icon'    => 'dashicons-food',
        'supports'     => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
        'rewrite'      => array( 'slug' => 'produk' ),
    ) );

    register_post_type( 'pesanan', array(
        'labels'       => array(
            'name'          => 'Pesanan Masuk',
            'singular_name' => 'Pesanan',
            'menu_name'     => 'Pesanan Masuk',
            'all_items'     => 'Semua Pesanan',
        ),
        'public'       => false,
        'show_ui'      => true,
        'show_in_menu' => true,
        'menu_icon'    => 'dashicons-text-page',
        'supports'     => array( 'title' ),
    ) );

    register_taxonomy( 'kategori_makanan', 'produk', array(
        'labels'            => array(
            'name'          => 'Kategori Makanan',
            'singular_name' => 'Kategori',
            'all_items'     => 'Semua Kategori',
            'add_new_item'  => 'Tambah Kategori Baru',
        ),
        'hierarchical'      => true,
        'show_ui'           => true,
        'show_admin_column' => true,
        'show_in_rest'      => true,
        'query_var'         => true,
        'rewrite'           => array( 'slug' => 'kategori_makanan' ),
    ) );
}

// ============================================================
// 2. REGISTER CUSTOM ORDER STATUSES
// ============================================================
add_action( 'init', 'foodmarket_register_order_statuses' );
function foodmarket_register_order_statuses() {
    $statuses = array(
        'processing' => 'Diproses',
        'completed'  => 'Selesai',
        'cancelled'  => 'Dibatalkan',
    );
    foreach ( $statuses as $slug => $label ) {
        register_post_status( $slug, array(
            'label'                     => $label,
            'public'                    => false,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop(
                $label . ' <span class="count">(%s)</span>',
                $label . ' <span class="count">(%s)</span>'
            ),
        ) );
    }
}

// ============================================================
// 3. KURANGI STOK SAAT PESANAN SELESAI
// ============================================================
add_action( 'post_updated', 'foodmarket_kurangi_stok_saat_selesai', 10, 3 );
function foodmarket_kurangi_stok_saat_selesai( $post_id, $post_after, $post_before ) {
    if ( $post_after->post_type    !== 'pesanan'   ) return;
    if ( $post_before->post_status === 'completed' ) return;
    if ( $post_after->post_status  !== 'completed' ) return;

    $produk_id = get_post_meta( $post_id, '_produk_id', true );
    $qty       = intval( get_post_meta( $post_id, '_qty', true ) );

    if ( $produk_id && $qty > 0 ) {
        $stok_lama = intval( get_post_meta( $produk_id, '_stok_produk', true ) );
        update_post_meta( $produk_id, '_stok_produk', max( 0, $stok_lama - $qty ) );
    }
}