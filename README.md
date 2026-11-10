# SakuBijak

Aplikasi pencatat keuangan mahasiswa: PHP + MySQL, Tailwind CSS (CDN), JavaScript, Chart.js.

## Cara menjalankan (XAMPP/Laragon)
1. Salin folder `sakubijak/` ke `htdocs` (XAMPP) atau `www` (Laragon).
2. Impor `database.sql` lewat phpMyAdmin (database `sakubijak` dibuat otomatis). File ini menghapus tabel lama lalu membuat ulang tabel beserta 13 kategori bawaan.
3. Sesuaikan kredensial di `config/database.php` bila perlu.
4. Buka `http://localhost/sakubijak/`, daftar akun baru. Akun baru dimulai kosong: saldo Rp0, tanpa transaksi, anggaran, atau target tabungan.

Butuh PHP 8.0+ dan koneksi internet (Tailwind, Google Fonts, dan Chart.js dimuat dari CDN).

## Catatan
- Saldo = pemasukan − pengeluaran − setoran tabungan (semua dihitung dari tabel `transactions`).
- Setoran tabungan disimpan sebagai transaksi `saving`, sehingga saldo, progres target, dan riwayat selalu konsisten.
- Semua formulir dikirim sebagai POST biasa (tanpa JSON/API) dan diproses di `includes/actions.php`, lalu halaman dimuat ulang.
- Semua data dikaitkan ke `user_id`; setiap aksi mengecek sesi dan token CSRF.
- Kata sandi memakai `password_hash()` / `password_verify()`.