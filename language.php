<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

//1. Set default language if not already set
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'id'; // Default: Indonesian
}

//2. Change language when user clicks the switcher link
if (isset($_GET['lang']) && in_array($_GET['lang'], ['id', 'en'])) {
    $_SESSION['lang'] = $_GET['lang'];

    //Redirect to remove the ?lang= query parameter from URL
    $clean_url = strtok($_SERVER["REQUEST_URI"], '?');
    header("Location: " . $clean_url);
    exit();
}

//3. Translation dictionary
$lang = [
    'id' => [
        'welcome'       => 'Selamat Datang',
        'logout'        => 'Keluar',
        'add_book'      => 'Tambah Buku Baru',
        'book_title'    => 'Judul Buku',
        'author'        => 'Penulis / Pengarang',
        'publisher'     => 'Penerbit',
        'year'          => 'Tahun Terbit',
        'stock'         => 'Jumlah Stok',
        'submit'        => 'Simpan Buku',
        'select_lang'   => 'Bahasa:',
        'success'       => 'Buku berhasil disimpan!',
        'err_title'     => 'Judul buku wajib diisi.',
        'err_year'      => 'Tahun terbit harus berupa angka yang valid (1000 sampai tahun ini).',
        'err_stock'     => 'Stok harus berupa angka 0 atau lebih.',
        //New UI
        'menu'          => 'Menu',
        'my_books'      => 'Katalog Buku',
        'search'        => 'Cari judul, penulis, penerbit...',
        'read_more'     => 'Selengkapnya',
        'no_books'      => 'Belum ada buku di database.',
        'no_results'    => 'Tidak ada buku yang cocok.',
        'filter_all'    => 'Semua Stok',
        'filter_in'     => 'Tersedia',
        'filter_out'    => 'Habis',
        'in_stock'      => 'Tersedia',
        'out_of_stock'  => 'Habis',
        'details'       => 'Detail Buku',
        'close'         => 'Tutup',
        'cancel'        => 'Batal',
        'unknown'       => 'Tidak diketahui',
        'books_count'   => 'buku',
        'by'            => 'Oleh',
        'published'     => 'Diterbitkan',
        'in'            => 'tahun',
        //Login
        'login_title'   => 'Masuk ke Bookshelf',
        'login_sub'     => 'Silakan masuk untuk mengelola buku Anda.',
        'username'      => 'Username',
        'password'      => 'Password',
        'login_btn'     => 'Masuk',
        'err_empty'     => 'Username dan password tidak boleh kosong!',
        'err_pass'      => 'Password salah!',
        'err_user'      => 'Username tidak ditemukan!',
        //Edit & Delete
        'edit'          => 'Ubah',
        'edit_book'     => 'Ubah Data Buku',
        'update'        => 'Perbarui Buku',
        'delete'        => 'Hapus',
        'delete_title'  => 'Hapus Buku',
        'delete_confirm'=> 'Yakin ingin menghapus buku ini? Tindakan ini tidak dapat dibatalkan.',
        'updated'       => 'Buku berhasil diperbarui!',
        'deleted'       => 'Buku berhasil dihapus!',
        'err_invalid'   => 'Permintaan tidak valid. Silakan muat ulang halaman.'
    ],
    'en' => [
        'welcome'       => 'Welcome',
        'logout'        => 'Logout',
        'add_book'      => 'Add New Book',
        'book_title'    => 'Book Title',
        'author'        => 'Author',
        'publisher'     => 'Publisher',
        'year'          => 'Publication Year',
        'stock'         => 'Stock Quantity',
        'submit'        => 'Save Book',
        'select_lang'   => 'Language:',
        'success'       => 'Book saved successfully!',
        'err_title'     => 'Book title is required.',
        'err_year'      => 'Publication year must be a valid number (1000 to this year).',
        'err_stock'     => 'Stock must be a number 0 or greater.',
        //New UI
        'menu'          => 'Menu',
        'my_books'      => 'Book Catalog',
        'search'        => 'Search title, author, publisher...',
        'read_more'     => 'Read More',
        'no_books'      => 'No books found in database.',
        'no_results'    => 'No matching books.',
        'filter_all'    => 'All Stock',
        'filter_in'     => 'In Stock',
        'filter_out'    => 'Out of Stock',
        'in_stock'      => 'In stock',
        'out_of_stock'  => 'Out of stock',
        'details'       => 'Book Details',
        'close'         => 'Close',
        'cancel'        => 'Cancel',
        'unknown'       => 'Unknown',
        'books_count'   => 'books',
        'by'            => 'By',
        'published'     => 'Published',
        'in'            => 'in',
        //Login
        'login_title'   => 'Sign in to Bookshelf',
        'login_sub'     => 'Please sign in to manage your books.',
        'username'      => 'Username',
        'password'      => 'Password',
        'login_btn'     => 'Log In',
        'err_empty'     => 'Username and password cannot be empty!',
        'err_pass'      => 'Wrong password!',
        'err_user'      => 'Username not found!',
        //Edit & delete
        'edit'          => 'Edit',
        'edit_book'     => 'Edit Book',
        'update'        => 'Update Book',
        'delete'        => 'Delete',
        'delete_title'  => 'Delete Book',
        'delete_confirm'=> 'Are you sure you want to delete this book? This cannot be undone.',
        'updated'       => 'Book updated successfully!',
        'deleted'       => 'Book deleted successfully!',
        'err_invalid'   => 'Invalid request. Please reload the page.'
    ]
];

//Helper shorthand function to retrieve translated text
function txt($key) {
    global $lang;
    $current_lang = $_SESSION['lang'];
    return $lang[$current_lang][$key] ?? $key;
}
?>