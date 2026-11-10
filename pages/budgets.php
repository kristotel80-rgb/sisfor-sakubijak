<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
$db  = db();
$uid = (int) current_user()['id'];

// Anggaran berlaku per bulan: setiap bulan punya batasnya sendiri.
$period  = resolve_period($_GET['month'] ?? 'this');
$budgets = budgets_usage($db, $uid, $period);
$have    = array_column($budgets, 'category_id');
$missing = array_filter(categories()['pengeluaran'], fn($c) => !in_array($c['id'], $have, true));
$totalLimit = array_sum(array_column($budgets, 'limit'));
$totalSpent = array_sum(array_column($budgets, 'spent'));

$pageTitle = 'Anggaran';
$active = 'budgets';
include __DIR__ . '/../includes/header.php';
?>
<section class="mb-5 mt-2 flex flex-wrap items-end justify-between gap-3">
    <div>
        <h1 class="font-display text-2xl font-extrabold tracking-tight sm:text-3xl">Anggaran</h1>
        <p class="text-sm text-forest/60">Batas pengeluaran per kategori · <?= h($period['label']) ?></p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
        <form method="get"><input type="month" name="month" value="<?= h($period['month']) ?>" onchange="this.form.submit()" class="input !w-auto !py-2 text-sm" aria-label="Pilih bulan anggaran"></form>
        <button class="btn btn-primary" data-modal-open="modal-budget"><?= icon('plus', 'w-4 h-4') ?> Atur anggaran</button>
    </div>
</section>

<?php if ($budgets): ?>
<div class="mb-4 grid grid-cols-3 gap-3">
    <div class="card !p-4"><p class="card-title">Total limit</p><p class="font-display text-lg font-extrabold sm:text-xl"><?= h(rupiah($totalLimit)) ?></p></div>
    <div class="card !p-4"><p class="card-title">Terpakai</p><p class="font-display text-lg font-extrabold text-expense sm:text-xl"><?= h(rupiah($totalSpent)) ?></p></div>
    <div class="card !p-4"><p class="card-title">Sisa</p><p class="font-display text-lg font-extrabold text-income sm:text-xl"><?= h(rupiah(max(0, $totalLimit - $totalSpent))) ?></p></div>
</div>
<?php endif; ?>

<div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
    <?php foreach ($budgets as $b): ?>
        <article class="card">
            <div class="flex items-start justify-between gap-2">
                <div class="flex items-center gap-3">
                    <span class="icon-bubble bg-sage text-forest"><?= icon($b['icon']) ?></span>
                    <div>
                        <h2 class="font-display font-bold"><?= h($b['category']) ?></h2>
                        <p class="text-xs text-forest/60"><?= $b['type'] === 'fixed' ? 'Pengeluaran Wajib · dikunci' : 'Pengeluaran Fleksibel' ?> · Limit <?= h(rupiah($b['limit'])) ?></p>
                    </div>
                </div>
                <div class="flex">
                    <button class="icon-btn !h-8 !w-8" aria-label="Ubah limit <?= h($b['category']) ?>" data-modal-open="modal-budget" data-fill-category-id="<?= (int) $b['category_id'] ?>" data-fill-batas-anggaran="<?= h(number_format($b['limit'], 0, ',', '.')) ?>" data-fill-jenis-anggaran="<?= h($b['type']) ?>" data-fill-periode="<?= h($period['month']) ?>"><?= icon('settings', 'w-4 h-4') ?></button>
                    <button class="icon-btn !h-8 !w-8 text-forest/40 hover:text-expense" aria-label="Hapus anggaran <?= h($b['category']) ?>" data-modal-open="modal-confirm" data-fill-do="budget_delete" data-fill-id="<?= (int) $b['id'] ?>" data-fill-label="anggaran <?= h($b['category']) ?>" data-fill-message="Limit kategori ini untuk <?= h($period['label']) ?> akan dihapus. Transaksi tidak ikut terhapus."><?= icon('trash', 'w-4 h-4') ?></button>
                </div>
            </div>
            <div class="mt-4 flex items-end justify-between">
                <p class="font-display text-2xl font-extrabold"><?= (int) round($b['pct']) ?>%</p>
                <p class="text-sm text-forest/70"><?= h(rupiah($b['spent'])) ?> terpakai</p>
            </div>
            <div class="bar bar-lg mt-2"><span class="<?= status_class($b['status']) ?>" style="width: <?= min(100, round($b['pct'], 1)) ?>%"></span></div>
            <p class="mt-2 text-sm <?= $b['over'] > 0 ? 'font-bold text-expense' : 'text-forest/60' ?>">
                <?= $b['over'] > 0 ? 'Melebihi anggaran ' . h(rupiah($b['over'])) : 'Sisa ' . h(rupiah($b['remaining'])) ?>
                <?php if ($b['limit'] === 0): ?> · tanpa limit<?php endif; ?>
            </p>
        </article>
    <?php endforeach; ?>

    <?php if (!$budgets): ?>
        <div class="card md:col-span-2 xl:col-span-3 !p-10 text-center text-sm text-forest/70">
            <p class="mb-3">Belum ada anggaran untuk <?= h($period['label']) ?>. Mulai dari kategori yang paling sering kamu pakai.</p>
            <button class="btn btn-primary" data-modal-open="modal-budget"><?= icon('plus', 'w-4 h-4') ?> Buat anggaran</button>
        </div>
    <?php endif; ?>
</div>

<?php if ($missing && $budgets): ?>
<section class="mt-6">
    <p class="card-title mb-3">Belum beranggaran di <?= h($period['label']) ?></p>
    <div class="flex flex-wrap gap-2">
        <?php foreach ($missing as $c): ?>
            <button class="btn btn-ghost !py-2" data-modal-open="modal-budget" data-fill-category-id="<?= (int) $c['id'] ?>" data-fill-periode="<?= h($period['month']) ?>"><?= icon($c['ikon'], 'w-4 h-4') ?> <?= h($c['nama']) ?></button>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>
<?php include __DIR__ . '/../includes/footer.php'; ?>
