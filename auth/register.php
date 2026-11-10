<?php
require_once __DIR__ . '/../includes/auth.php';
if (current_user()) { header('Location: ' . app_url('pages/dashboard.php')); exit; }

$errors = [];
$name = $email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim((string) ($_POST['name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $pass  = (string) ($_POST['password'] ?? '');
    $pass2 = (string) ($_POST['password2'] ?? '');

    if (!csrf_check($_POST['csrf'] ?? null)) { $errors[] = 'Sesi formulir kedaluwarsa. Muat ulang halaman.'; }
    if ($name === '' || mb_strlen($name) > 80) { $errors[] = 'Nama wajib diisi (maksimal 80 karakter).'; }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150) { $errors[] = 'Format email tidak valid.'; }
    if (strlen($pass) < 8) { $errors[] = 'Kata sandi minimal 8 karakter.'; }
    if ($pass !== $pass2) { $errors[] = 'Konfirmasi kata sandi tidak sama.'; }

    if (!$errors) {
        $db = db();
        try {
            $db->beginTransaction();
            $db->prepare('INSERT INTO users (nama,email,password_hash) VALUES (?,?,?)')
               ->execute([$name, $email, password_hash($pass, PASSWORD_DEFAULT)]);
            $uid = (int) $db->lastInsertId();
            $db->commit();
            login_user(['id' => $uid, 'nama' => $name, 'email' => $email]);
            flash_set('Akun dibuat. Selamat datang di SakuBijak!');
            header('Location: ' . app_url('pages/dashboard.php'));
            exit;
        } catch (PDOException $e) {
            if ($db->inTransaction()) { $db->rollBack(); }
            $errors[] = $e->getCode() === '23000' ? 'Email sudah terdaftar. Silakan masuk.' : 'Terjadi kesalahan saat menyimpan. Coba lagi.';
        }
    }
}
$pageTitle = 'Daftar';
include __DIR__ . '/../includes/head.php';
?>
<body class="bg-paper text-forest font-sans antialiased">
<div class="grid min-h-screen lg:grid-cols-2">
    <main class="flex items-center justify-center p-4 sm:p-8">
        <div class="w-full max-w-md rounded-3xl border border-forest/10 bg-white p-7 sm:p-10">
            <div class="mb-8 flex items-center gap-3">
                <?= logo_mark('w-10 h-10') ?>
                <span class="font-display text-2xl font-extrabold tracking-tight">SakuBijak</span>
            </div>
            <h1 class="font-display text-2xl font-extrabold">Buat akun baru</h1>
            <p class="mt-1 mb-6 text-sm text-forest/70">Data keuanganmu hanya bisa dilihat oleh akunmu.</p>

            <?php if ($errors): ?>
                <div class="form-error mb-4" role="alert"><ul class="list-disc pl-4"><?php foreach ($errors as $er): ?><li><?= h($er) ?></li><?php endforeach; ?></ul></div>
            <?php endif; ?>

            <form method="post" class="space-y-4" novalidate>
                <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
                <div>
                    <label class="label" for="name">Nama</label>
                    <input id="name" name="name" type="text" maxlength="80" value="<?= h($name) ?>" autocomplete="name" class="input" required autofocus>
                </div>
                <div>
                    <label class="label" for="email">Email</label>
                    <input id="email" name="email" type="email" value="<?= h($email) ?>" autocomplete="email" class="input" required>
                </div>
                <div>
                    <div class="flex items-center justify-between">
                        <label class="label" for="password">Kata sandi</label>
                        <button type="button" class="mb-1 text-xs font-bold text-income" data-toggle-password="password">Tampilkan</button>
                    </div>
                    <input id="password" name="password" type="password" autocomplete="new-password" class="input" placeholder="Minimal 8 karakter" required>
                </div>
                <div>
                    <label class="label" for="password2">Ulangi kata sandi</label>
                    <input id="password2" name="password2" type="password" autocomplete="new-password" class="input" required>
                </div>
                <button type="submit" class="btn btn-primary w-full">Daftar</button>
            </form>
            <p class="mt-6 text-center text-sm text-forest/70">Sudah punya akun? <a href="<?= h(app_url('auth/login.php')) ?>" class="font-bold text-forest underline">Masuk</a></p>
        </div>
    </main>
    <aside class="auth-art hidden lg:flex items-center justify-center p-12" aria-hidden="true">
        <svg viewBox="0 0 400 400" class="absolute inset-0 h-full w-full" preserveAspectRatio="xMidYMid slice">
            <circle cx="60" cy="60" r="150" fill="#3E5A49"/>
            <circle cx="350" cy="370" r="170" fill="#2A3D31"/>
            <circle cx="335" cy="70" r="34" fill="#DCE7DA" opacity=".9"/>
            <path d="M52 360c0-30 14-48 44-48 0 30-14 48-44 48z" fill="#DCE7DA" opacity=".9"/>
            <circle cx="70" cy="330" r="0" fill="none"/>
        </svg>
        <div class="relative z-10 max-w-sm rounded-3xl bg-sage p-8 text-black shadow-xl">
            <p class="font-display text-3xl font-extrabold leading-tight">Mulai dari catatan kecil.</p>
            <p class="mt-3 text-black/75">Satu transaksi hari ini membuat keputusan besok jadi lebih bijak.</p>
        </div>
    </aside>
</div>
<script src="<?= h(app_url('assets/js/dashboard.js')) ?>"></script>
</body>
</html>
