<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
$db  = db();
$uid = (int) current_user()['id'];

$goals = savings_goals($db, $uid);
$st = $db->prepare("SELECT t.*, c.nama AS kategori, c.ikon FROM transactions t JOIN categories c ON c.id=t.category_id WHERE t.user_id=? AND t.tipe='tabungan' ORDER BY t.tanggal DESC, t.id DESC LIMIT 10");
$st->execute([$uid]);
$deposits = $st->fetchAll();
$balance = current_balance($db, $uid);

$pageTitle = 'Tabungan';
$active = 'savings';
include __DIR__ . '/../includes/header.php';
?>
<section class="mb-5 mt-2 flex flex-wrap items-end justify-between gap-3">
    <div>
        <h1 class="font-display text-2xl font-extrabold tracking-tight sm:text-3xl">Tabungan</h1>
        <p class="text-sm text-forest/60">Saldo bulan ini yang bisa disetor: <strong><?= h(rupiah($balance)) ?></strong></p>
    </div>
    <button class="btn btn-primary" data-modal-open="modal-goal"><?= icon('plus', 'w-4 h-4') ?> Target baru</button>
</section>

<div class="grid gap-4 md:grid-cols-2">
    <?php foreach ($goals as $g): $done = $g['pct'] >= 100; ?>
        <article class="card">
            <div class="flex items-start justify-between gap-3">
                <div class="flex items-center gap-3">
                    <span class="icon-bubble bg-sage text-forest"><?= icon('target') ?></span>
                    <div>
                        <h2 class="font-display text-lg font-bold"><?= h($g['nama_target']) ?></h2>
                        <p class="text-sm text-forest/60"><?= h(rupiah($g['saved'])) ?> dari <?= h(rupiah($g['target_nominal'])) ?></p>
                    </div>
                </div>
                <button class="icon-btn !h-8 !w-8 text-forest/40 hover:text-expense" aria-label="Hapus target <?= h($g['nama_target']) ?>" data-modal-open="modal-confirm" data-fill-do="goal_delete" data-fill-id="<?= (int) $g['id'] ?>" data-fill-label="<?= h($g['nama_target']) ?>" data-fill-message="Target dihapus dan seluruh dana setoran dikembalikan ke saldo."><?= icon('trash', 'w-4 h-4') ?></button>
            </div>
            <div class="mt-5 flex items-center gap-3">
                <div class="bar bar-lg flex-1"><span class="bg-forest" style="width: <?= round($g['pct'], 1) ?>%"></span></div>
                <span class="font-display font-bold"><?= (int) round($g['pct']) ?>%</span>
            </div>
            <p class="mt-2 text-sm text-forest/60"><?= $done ? 'Target tercapai 🎉' : 'Kurang ' . h(rupiah($g['target_nominal'] - $g['saved'])) ?></p>
            <?php if (!$done): ?>
                <button class="btn btn-light mt-4" data-modal-open="modal-deposit" data-fill-id="<?= (int) $g['id'] ?>" data-fill-label="<?= h($g['nama_target']) ?>"><?= icon('plus', 'w-4 h-4') ?> Setor tabungan</button>
            <?php endif; ?>
        </article>
    <?php endforeach; ?>
    <?php if (!$goals): ?>
        <div class="card md:col-span-2 !p-10 text-center text-sm text-forest/70">
            <p class="mb-3">Belum ada target tabungan. Tentukan tujuanmu, mis. laptop atau dana darurat.</p>
            <button class="btn btn-primary" data-modal-open="modal-goal"><?= icon('plus', 'w-4 h-4') ?> Buat target pertama</button>
        </div>
    <?php endif; ?>
</div>

<?php if ($deposits): ?>
<section class="card mt-6">
    <p class="card-title mb-2">Riwayat setoran</p>
    <?php foreach ($deposits as $t) { echo render_tx_row($t); } ?>
</section>
<?php endif; ?>
<?php include __DIR__ . '/../includes/footer.php'; ?>
