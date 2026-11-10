<?php
// Variabel: $pageTitle, $active
$nav = [
    'dashboard'    => ['Beranda', 'home', 'pages/dashboard.php'],
    'transactions' => ['Transaksi', 'swap', 'pages/transactions.php'],
    'budgets'      => ['Anggaran', 'gauge', 'pages/budgets.php'],
    'reminders'    => ['Pengingat', 'bell', 'pages/reminders.php'],
    'savings'      => ['Tabungan', 'target', 'pages/savings.php'],
];
?>
<aside class="hidden lg:flex w-64 shrink-0 sticky top-0 h-screen flex-col border-r border-forest/10 bg-white/60 px-5 py-6">
    <a href="<?= h(app_url('pages/dashboard.php')) ?>" class="flex items-center gap-3 px-2 mb-8">
        <?= logo_mark() ?>
        <span class="font-display text-xl font-extrabold tracking-tight">SakuBijak</span>
    </a>
    <p class="nav-label">Overview</p>
    <nav class="space-y-1" aria-label="Navigasi utama">
        <?php foreach ($nav as $key => [$label, $ic, $href]): ?>
            <a href="<?= h(app_url($href)) ?>" class="nav-item <?= ($active ?? '') === $key ? 'is-active' : '' ?>" <?= ($active ?? '') === $key ? 'aria-current="page"' : '' ?>>
                <span class="nav-ic"><?= icon($ic, 'w-[18px] h-[18px]') ?></span><?= h($label) ?>
            </a>
        <?php endforeach; ?>
    </nav>
    <div class="my-6 border-t border-forest/10"></div>
    <p class="nav-label">Preferensi</p>
    <a href="<?= h(app_url('pages/profile.php')) ?>" class="nav-item <?= ($active ?? '') === 'profile' ? 'is-active' : '' ?>">
        <span class="nav-ic"><?= icon('settings', 'w-[18px] h-[18px]') ?></span>Pengaturan
    </a>
    <div class="mt-auto rounded-2xl bg-sage p-4 text-sm">
        <p class="font-display font-bold mb-1">Catat dulu, tenang kemudian.</p>
        <p class="text-forest/70 mb-3">Tiap transaksi kecil ikut menjaga anggaranmu.</p>
        <button type="button" class="btn btn-dark w-full" data-modal-open="modal-quick"><?= icon('plus', 'w-4 h-4') ?> Tambah transaksi</button>
    </div>
</aside>


<!-- Navigasi bawah (mobile/tablet) -->
<nav class="lg:hidden fixed bottom-0 inset-x-0 z-30 border-t border-forest/10 bg-white/95 backdrop-blur px-2 pt-2 pb-[max(0.5rem,env(safe-area-inset-bottom))]" aria-label="Navigasi bawah">
    <ul class="grid grid-cols-5 gap-1 w-full">
        <?php foreach ($nav as $key => [$label, $ic, $href]): ?>
            <li class="min-w-0">
                <a href="<?= h(app_url($href)) ?>" class="bottom-item <?= ($active ?? '') === $key ? 'is-active' : '' ?>">
                    <?= icon($ic, 'w-5 h-5') ?>
                    <span><?= h($label) ?></span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</nav>

