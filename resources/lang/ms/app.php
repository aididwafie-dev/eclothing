<?php

/*
 * Rentetan antara muka, Bahasa Malaysia.
 *
 * Kunci yang tiada di sini akan jatuh kembali kepada Bahasa Inggeris
 * (config/app.php: fallback_locale), jadi halaman tidak akan kosong semasa
 * terjemahan masih dilengkapkan.
 */

return [
    'language' => 'Bahasa',
    'language_english' => 'English',
    'language_malay' => 'Bahasa Malaysia',

    'nav' => [
        'close' => 'Tutup',
        'personal_details' => 'Butiran Peribadi',
        'order_uniform' => 'Pesan Pakaian Seragam',
        'uniform_ordered' => 'Pakaian Dipesan',
        'change_email' => 'Tukar E-mel',
        'change_password' => 'Tukar Kata Laluan',
        'logout' => 'Log Keluar',
        'logout_confirm' => 'Adakah anda pasti mahu log keluar? Semua perubahan yang belum disimpan akan hilang.',
    ],

    'login' => [
        'service_id' => 'ID Perkhidmatan',
        'service_id_placeholder' => 'Masukkan ID Perkhidmatan anda',
        'password' => 'Kata Laluan',
        'password_placeholder' => 'Masukkan kata laluan anda',
        'sign_in' => 'Log Masuk',
        'forgot_password' => 'Lupa Kata Laluan?',
        'not_registered' => 'Belum mendaftar?',
        'register_now' => 'Klik di sini untuk mendaftar',
        'admin_login' => 'Log Masuk Pentadbir',
    ],

    'orders' => [
        'title' => 'Status Pesanan Pakaian Seragam',
        'send_mail' => 'Hantar E-mel',
        'intro' => 'Jejak setiap pesanan mengikut statusnya. Klik satu pesanan untuk melihat item dan catatan.',
        'order' => 'Pesanan',
        'uniform' => 'Pakaian Seragam',
        'items' => 'Item',
        'status' => 'Status',
        'collection_date' => 'Tarikh Pengambilan',
        'last_updated' => 'Kemas Kini Terakhir',
        'actions' => 'Tindakan',
        'to_be_updated' => 'Belum ditetapkan',
        'ordered' => 'Tarikh Pesanan',
        'remarks' => 'Catatan',
        'has_remarks' => 'Pesanan ini mempunyai catatan',
        'no_remarks' => 'Tiada catatan lagi.',
        'items_ordered' => 'Item dipesan',
        'size' => 'Saiz',
        'edit' => 'Ubah',
        'kew_ps8' => 'KEW.PS-8',
        'show_details' => 'Papar butiran',
        'none_yet' => 'Anda belum memesan sebarang pakaian seragam.',
        'no_items' => 'Tiada item pada pesanan ini.',
    ],

    'status' => [
        'pending' => 'Menunggu',
        'processing' => 'Dalam Proses',
        'approved' => 'Diluluskan',
        'completed' => 'Selesai',
        'rejected' => 'Ditolak',
        'expired' => 'Tamat Tempoh',
    ],
];
