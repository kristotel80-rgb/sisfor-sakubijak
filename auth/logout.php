<?php
require_once __DIR__ . '/../includes/auth.php';

if (!current_user()) {
    header('Location: ' . app_url('auth/login.php'));
    exit;
}

// Keluar hanya lewat POST + token CSRF, setelah dikonfirmasi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_check($_POST['csrf'] ?? null)) {
    logout_user();
    header('Location: ' . app_url('auth/login.php?msg=logout'));
    exit;
}

$pageTitle = 'Keluar';
include __DIR__ . '/../includes/head.php';
?>
<body class="bg-paper text-forest font-sans antialiased">
<main class="flex min-h-screen items-center justify-center p-4">
    <div class="w-full max-w-sm rounded-3xl border border-forest/10 bg-white p-8 text-center">
        <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-sage text-forest"><?= icon('logout', 'w-7 h-7') ?></div>
        <h1 class="font-display text-xl font-extrabold">Keluar dari SakuBijak?</h1>
        <p class="mt-2 text-sm text-forest/70">Sesimu akan dihapus. Kamu perlu masuk lagi untuk melihat data keuangan.</p>
        <form method="post" class="mt-6 space-y-3">
            <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
            <button type="submit" class="btn btn-primary w-full">Ya, keluar</button>
            <a href="<?= h(app_url('pages/dashboard.php')) ?>" class="btn btn-ghost w-full">Kembali ke dashboard</a>
        </form>
    </div>
</main>
</body>
</html>
