<?php
/*
Plugin Name: Marketplace Core Logic
Description: Mengatur logika dan fitur dasar FoodMarket (CPT & Taxonomy).
Version: 1.1
Author: Developer
*/

if (!defined('ABSPATH')) exit;

function foodmarket_register_core_elements() {
    
    // 1. Registrasi Custom Post Type 'produk' (Tetap seperti sebelumnya)
    register_post_type('produk', array(
        'labels' => array(
            'name'               => 'Produk Kuliner',
            'singular_name'      => 'Produk',
            'menu_name'          => 'Produk Kuliner',
            'add_new_item'       => 'Tambah Produk Baru',
            'edit_item'          => 'Edit Produk',
            'all_items'          => 'Semua Produk',
        ),
        'public'       => true,
        'has_archive'  => true,
        'show_in_rest' => true,
        'menu_icon'    => 'dashicons-food',
        'supports'     => array('title', 'editor', 'thumbnail', 'excerpt'),
        'rewrite'      => array('slug' => 'produk'),
    ));

    // 2. NEW: Registrasi Custom Post Type 'pesanan' untuk Logging Transaksi Belanja
    register_post_type('pesanan', array(
        'labels' => array(
            'name'               => 'Pesanan Masuk',
            'singular_name'      => 'Pesanan',
            'menu_name'          => 'Pesanan Masuk',
            'all_items'          => 'Semua Pesanan',
        ),
        'public'             => false, 
        'show_ui'            => true,  
        'show_in_menu'       => true,
        'menu_icon'          => 'dashicons-text-page',
        'supports'           => array('title'), 
    ));

    // 3. Registrasi Custom Taxonomy 'kategori_makanan' (Tetap seperti sebelumnya)
    register_taxonomy('kategori_makanan', 'produk', array(
        'labels' => array(
            'name'              => 'Kategori Makanan',
            'singular_name'     => 'Kategori',
            'search_items'      => 'Cari Kategori',
            'all_items'         => 'Semua Kategori',
            'edit_item'         => 'Edit Kategori',
            'update_item'       => 'Perbarui Kategori',
            'add_new_item'      => 'Tambah Kategori Baru',
        ),
        'hierarchical'      => true,
        'show_ui'           => true,
        'show_admin_column' => true,
        'show_in_rest'      => true,
        'query_var'         => true,
        'rewrite'           => array('slug' => 'kategori_makanan'),
    ));
}
add_action('init', 'foodmarket_register_core_elements');