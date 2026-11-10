<?php
require_once __DIR__ . '/../includes/auth.php';
if (current_user()) { header('Location: ' . app_url('pages/dashboard.php')); exit; }

$error = '';
$email = '';
$info  = isset($_GET['msg']) && $_GET['msg'] === 'logout' ? 'Kamu sudah keluar dengan aman.' : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string) ($_POST['email'] ?? ''));
    $pass  = (string) ($_POST['password'] ?? '');
    if (!csrf_check($_POST['csrf'] ?? null)) {
        $error = 'Sesi formulir kedaluwarsa. Muat ulang halaman lalu coba lagi.';
    } elseif ($email === '' || $pass === '') {
        $error = 'Email dan kata sandi wajib diisi.';
    } else {
        $st = db()->prepare('SELECT id,nama,email,password_hash FROM users WHERE email=? LIMIT 1');
        $st->execute([$email]);
        $u = $st->fetch();
        if ($u && password_verify($pass, $u['password_hash'])) {
            if (password_needs_rehash($u['password_hash'], PASSWORD_DEFAULT)) {
                db()->prepare('UPDATE users SET password_hash=? WHERE id=?')->execute([password_hash($pass, PASSWORD_DEFAULT), $u['id']]);
            }
            login_user($u);
            header('Location: ' . app_url('pages/dashboard.php'));
            exit;
        }
        $error = 'Email atau kata sandi salah.';
    }
}
$pageTitle = 'Masuk';
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
            <h1 class="font-display text-2xl font-extrabold">Selamat datang kembali</h1>
            <p class="mt-1 mb-6 text-sm text-forest/70">Masuk untuk melihat kondisi keuanganmu.</p>

            <?php if ($info): ?><p class="mb-4 rounded-xl bg-sage px-4 py-3 text-sm"><?= h($info) ?></p><?php endif; ?>
            <?php if ($error): ?><p class="form-error mb-4" role="alert"><?= h($error) ?></p><?php endif; ?>

            <form method="post" class="space-y-4" novalidate>
                <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
                <div>
                    <label class="label" for="email">Email</label>
                    <input id="email" name="email" type="email" value="<?= h($email) ?>" autocomplete="email" class="input" placeholder="kamu@email.com" required autofocus>
                </div>
                <div>
                    <div class="flex items-center justify-between">
                        <label class="label" for="password">Kata sandi</label>
                        <button type="button" class="mb-1 text-xs font-bold text-income" data-toggle-password="password">Tampilkan</button>
                    </div>
                    <input id="password" name="password" type="password" autocomplete="current-password" class="input" required>
                </div>
                <button type="submit" class="btn btn-primary w-full">Masuk</button>
            </form>
            <p class="mt-6 text-center text-sm text-forest/70">Belum punya akun? <a href="<?= h(app_url('auth/register.php')) ?>" class="font-bold text-forest underline">Daftar</a></p>
        </div>
    </main>

    <aside class="auth-art hidden lg:flex items-center justify-center p-12" aria-hidden="true">
        <svg viewBox="0 0 400 400" class="absolute inset-0 h-full w-full" preserveAspectRatio="xMidYMid slice">
            <circle cx="340" cy="60" r="150" fill="#3E5A49"/>
            <circle cx="40" cy="380" r="170" fill="#2A3D31"/>
            <rect x="40" y="50" width="70" height="70" rx="22" fill="#DCE7DA" opacity=".9" transform="rotate(14 75 85)"/>
            <circle cx="330" cy="330" r="42" fill="none" stroke="#DCE7DA" stroke-width="3"/>
            <path d="M250 60c30-30 60-30 90 0" fill="none" stroke="#6fa986" stroke-width="3" stroke-linecap="round"/>
        </svg>
        <div class="relative z-10 max-w-sm rounded-3xl bg-sage p-8 text-black shadow-xl">
            <p class="font-display text-3xl font-extrabold leading-tight">Keuanganmu, lebih terkendali.</p>
            <p class="mt-3 text-black/75">Catat, pantau anggaran, dan capai target tabungan tanpa berpindah halaman.</p>
        </div>
    </aside>
</div>
<script src="<?= h(app_url('assets/js/dashboard.js')) ?>"></script>
</body>
</html>
