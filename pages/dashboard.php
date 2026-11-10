<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
$db  = db();
$uid = (int) current_user()['id'];

$period = resolve_period($_GET['period'] ?? 'this');
// Semua angka dihitung hanya untuk bulan yang dipilih (tanpa saldo bawaan bulan lain)
$pt     = period_totals($db, $uid, $period['start'], $period['end']);
$bal    = $pt;
$reservedFixed = reserved_fixed_budget($db, $uid, $period);
$balance = $pt['pemasukan'] - $pt['pengeluaran'] - $pt['tabungan'] - $reservedFixed;
$prev   = period_totals($db, $uid, $period['prev_start'], $period['prev_end']);
$budgets = budgets_usage($db, $uid, $period);
usort($budgets, fn($a, $b) => $b['pct'] <=> $a['pct']);
$totalLimit    = array_sum(array_column($budgets, 'limit'));
$budgetedSpent = array_sum(array_column($budgets, 'spent'));
$usedPct = $totalLimit > 0 ? $budgetedSpent / $totalLimit * 100 : null;
$byCat   = expense_by_category($db, $uid, $period['start'], $period['end']);
$recent  = recent_transactions($db, $uid, 6, $period['start'], $period['end']);
$goals   = savings_goals($db, $uid);
$totalExpense = $pt['pengeluaran'];

// Data grafik dikirim lewat atribut HTML (#chart-data), dibaca oleh dashboard.js
$chartLabels = implode('|', array_column($byCat, 'kategori'));
$chartValues = implode(',', array_map('intval', array_column($byCat, 'total')));
$chartColors = implode(',', array_column($byCat, 'warna'));

$diffText = null; $diffGood = true;
if ($prev['pengeluaran'] > 0) {
    $diff = ($pt['pengeluaran'] - $prev['pengeluaran']) / $prev['pengeluaran'] * 100;
    $diffGood = $diff <= 0;
    $diffText = ($diff <= 0 ? '↓ ' : '↑ ') . abs((int) round($diff)) . '% dibanding ' . $period['prev_label'];
}

$pageTitle = 'Beranda';
$active = 'dashboard';
$withCharts = true;
include __DIR__ . '/../includes/header.php';
$user = current_user();
?>
<section class="mb-6 mt-2 flex flex-wrap items-end justify-between gap-4">
    <div>
        <p class="text-sm font-medium text-forest/60"><?= h(tgl(date('Y-m-d'), true)) ?></p>
        <h1 class="font-display text-2xl font-extrabold tracking-tight sm:text-3xl">Keuanganmu, lebih terkendali.</h1>
    </div>
    <form method="get" class="flex flex-wrap items-center gap-2" aria-label="Filter periode">
        <div class="segmented !grid-cols-2 !p-1 text-sm">
            <a href="?period=this" class="rounded-[14px] px-3 py-1.5 text-center font-bold <?= $period['key'] === 'this' ? 'bg-white shadow-sm' : '' ?>">Bulan ini</a>
            <a href="?period=prev" class="rounded-[14px] px-3 py-1.5 text-center font-bold <?= $period['key'] === 'prev' ? 'bg-white shadow-sm' : '' ?>">Bulan lalu</a>
        </div>
        <input type="month" name="period" value="<?= h($period['month']) ?>" max="<?= date('Y-m') ?>" onchange="this.form.submit()" class="input !w-auto !py-2 text-sm" aria-label="Pilih bulan tertentu">
    </form>
</section>

<div class="grid grid-cols-1 gap-4 lg:grid-cols-12">

    <!-- Saldo utama -->
    <section id="balance-card" class="card deco-card lg:col-span-7" aria-labelledby="t-saldo">
        <svg class="deco" viewBox="0 0 280 280" aria-hidden="true">
            <circle cx="170" cy="110" r="110" fill="#3E5A49"/>
            <circle cx="210" cy="150" r="60" fill="none" stroke="#6fa986" stroke-width="2"/>
            <rect x="120" y="120" width="90" height="90" rx="28" fill="#2A3D31" transform="rotate(18 165 165)"/>
        </svg>
        <div class="flex items-start justify-between">
            <p id="t-saldo" class="text-xs font-extrabold uppercase tracking-widest text-sage/80">Saldo <?= $period['is_current'] ? 'bulan ini' : h($period['label']) ?></p>
            <button type="button" data-toggle-balance class="icon-btn !h-9 !w-9 bg-white/10 text-sage hover:bg-white/20" aria-label="Sembunyikan atau tampilkan nominal saldo" aria-pressed="false">
                <span data-eye="on"><?= icon('eye', 'w-[18px] h-[18px]') ?></span>
                <span data-eye="off" class="hidden"><?= icon('eye-off', 'w-[18px] h-[18px]') ?></span>
            </button>
        </div>
        <p class="mt-3 font-display text-4xl font-extrabold tracking-tight sm:text-5xl" data-sensitive><?= h(rupiah($balance)) ?></p>
        <?php if ($reservedFixed > 0): ?><p class="mt-2 text-xs text-sage/80">Dana wajib yang masih dikunci: <strong><?= h(rupiah($reservedFixed)) ?></strong></p><?php endif; ?>
        <div class="mt-5 flex flex-wrap gap-x-6 gap-y-2 text-sm">
            <p class="flex items-center gap-2"><span class="flex h-6 w-6 items-center justify-center rounded-full bg-income/30 text-[#8be0b0]"><?= icon('arrow-up', 'w-3.5 h-3.5') ?></span>Pemasukan <strong data-sensitive><?= h(rupiah($bal['pemasukan'])) ?></strong></p>
            <p class="flex items-center gap-2"><span class="flex h-6 w-6 items-center justify-center rounded-full bg-expense/30 text-[#ffb1aa]"><?= icon('arrow-down', 'w-3.5 h-3.5') ?></span>Pengeluaran <strong data-sensitive><?= h(rupiah($bal['pengeluaran'])) ?></strong></p>
            <?php if ($bal['tabungan'] > 0): ?>
            <p class="flex items-center gap-2"><span class="flex h-6 w-6 items-center justify-center rounded-full bg-white/15"><?= icon('target', 'w-3.5 h-3.5') ?></span>Ditabung <strong data-sensitive><?= h(rupiah($bal['tabungan'])) ?></strong></p>
            <?php endif; ?>
        </div>
    </section>

    <!-- Ringkasan pengeluaran -->
    <section class="card lg:col-span-5" aria-labelledby="t-keluar">
        <div class="flex items-center justify-between">
            <p id="t-keluar" class="card-title">Pengeluaran <?= $period['is_current'] ? 'bulan ini' : h($period['label']) ?></p>
            <span class="icon-bubble bg-expense/10 text-expense !h-9 !w-9"><?= icon('arrow-down', 'w-4 h-4') ?></span>
        </div>
        <p class="mt-3 font-display text-3xl font-extrabold text-expense" data-sensitive><?= h(rupiah($pt['pengeluaran'])) ?></p>
        <?php if ($diffText): ?>
            <p class="mt-1 text-sm font-semibold <?= $diffGood ? 'text-income' : 'text-expense' ?>"><?= h($diffText) ?></p>
        <?php else: ?>
            <p class="mt-1 text-sm text-forest/60">Belum ada pembanding dari <?= h($period['prev_label']) ?>.</p>
        <?php endif; ?>
        <div class="mt-4">
            <?php if ($usedPct !== null): ?>
                <div class="mb-1.5 flex justify-between text-sm">
                    <span class="font-semibold"><?= (int) round($usedPct) ?>% dari anggaran</span>
                    <span class="text-forest/60"><?= h(rupiah($budgetedSpent)) ?> / <?= h(rupiah($totalLimit)) ?></span>
                </div>
                <div class="bar bar-lg"><span class="<?= status_class($usedPct >= 90 ? 'danger' : ($usedPct >= 70 ? 'warn' : 'ok')) ?>" style="width: <?= min(100, round($usedPct, 1)) ?>%"></span></div>
                <p class="mobile-muted mt-2 text-xs text-forest/60"> </p>
            <?php else: ?>
                <p class="rounded-2xl bg-sage-50 p-3 text-sm text-forest/70">Belum ada anggaran untuk <?= h($period['label']) ?>. <a class="font-bold underline" href="<?= h(app_url('pages/budgets.php')) ?>">Atur anggaran</a> untuk melihat persentase penggunaan.</p>
            <?php endif; ?>
        </div>
    </section>

    <!-- Anggaran aktif -->
    <section class="card lg:col-span-5" aria-labelledby="t-anggaran">
        <div class="mb-4 flex items-center justify-between">
            <p id="t-anggaran" class="card-title">Anggaran aktif</p>
            <a href="<?= h(app_url('pages/budgets.php?month=' . $period['month'])) ?>" class="text-sm font-bold no-underline">Kelola</a>
        </div>
        <?php if (!$budgets): ?>
            <div class="rounded-2xl bg-sage-50 p-5 text-center text-sm text-forest/70">
                <p class="mb-3">Belum ada anggaran untuk <?= h($period['label']) ?>. Batasi pengeluaran per kategori agar tidak kebablasan.</p>
                <button class="btn btn-primary" data-modal-open="modal-budget"><?= icon('plus', 'w-4 h-4') ?> Buat anggaran</button>
            </div>
        <?php else: ?>
            <ul class="space-y-4">
                <?php foreach (array_slice($budgets, 0, 5) as $b): ?>
                    <li>
                        <div class="mb-1.5 flex items-center justify-between gap-3 text-sm">
                            <span class="flex items-center gap-2 font-semibold"><span class="h-2.5 w-2.5 rounded-full <?= status_class($b['status']) ?>"></span><?= h($b['category']) ?></span>
                            <span class="font-display font-bold"><?= (int) round($b['pct']) ?>%</span>
                        </div>
                        <div class="bar"><span class="<?= status_class($b['status']) ?>" style="width: <?= min(100, round($b['pct'], 1)) ?>%"></span></div>
                        <p class="mt-1 text-xs text-forest/60">
                            <?= h(rupiah($b['spent'])) ?> terpakai ·
                            <?php if ($b['over'] > 0): ?><span class="font-bold text-expense">melebihi <?= h(rupiah($b['over'])) ?></span>
                            <?php else: ?>sisa <?= h(rupiah($b['remaining'])) ?><?php endif; ?>
                        </p>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <!-- Pola pengeluaran -->
    <section class="card lg:col-span-7" aria-labelledby="t-pola">
        <div class="mb-4 flex items-center justify-between">
            <p id="t-pola" class="card-title">Pola pengeluaran · <?= h($period['label']) ?></p>
        </div>
        <?php if (!$byCat): ?>
            <div class="rounded-2xl bg-sage-50 p-8 text-center text-sm text-forest/70">
                Belum ada pengeluaran pada periode ini. Grafik akan muncul setelah kamu mencatat transaksi.
            </div>
        <?php else: ?>
            <div class="grid gap-6 md:grid-cols-5">
                <div id="chart-data" hidden data-labels="<?= h($chartLabels) ?>" data-values="<?= h($chartValues) ?>" data-colors="<?= h($chartColors) ?>"></div>
                <div class="relative h-56 md:col-span-3" role="img" aria-label="Grafik batang pengeluaran per kategori">
                    <div class="skeleton-cover"></div>
                    <canvas id="chart-bar"></canvas>
                </div>
                <div class="md:col-span-2">
                    <div class="relative mx-auto h-40 w-40" role="img" aria-label="Grafik donat proporsi kategori">
                        <div class="skeleton-cover !rounded-full"></div>
                        <canvas id="chart-donut"></canvas>
                    </div>
                    <ul class="mt-3 space-y-1.5 text-xs">
                        <?php foreach ($byCat as $c): $pc = $totalExpense > 0 ? $c['total'] / $totalExpense * 100 : 0; ?>
                            <li class="flex items-center justify-between gap-2">
                                <span class="flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full" style="background: <?= h($c['warna']) ?>"></span><?= h($c['kategori']) ?></span>
                                <span class="font-semibold"><?= (int) round($pc) ?>%</span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>
    </section>

    <!-- Target tabungan -->
    <section class="card lg:col-span-5" aria-labelledby="t-tabungan">
        <div class="mb-4 flex items-center justify-between">
            <p id="t-tabungan" class="card-title">Target tabungan</p>
            <a href="<?= h(app_url('pages/savings.php')) ?>" class="text-sm font-bold no-underline">Semua</a>
        </div>
        <?php if (!$goals): ?>
            <div class="rounded-2xl bg-sage-50 p-5 text-center text-sm text-forest/70">
                <p class="mb-3">Belum ada target. Mulai dengan satu tujuan kecil.</p>
                <button class="btn btn-primary" data-modal-open="modal-goal"><?= icon('plus', 'w-4 h-4') ?> Buat target</button>
            </div>
        <?php else: ?>
            <div class="space-y-5">
                <?php foreach (array_slice($goals, 0, 2) as $g): ?>
                    <div>
                        <p class="font-display font-bold"><?= h($g['nama_target']) ?></p>
                        <p class="text-sm text-forest/70"><span data-sensitive><?= h(rupiah($g['saved'])) ?></span> dari <?= h(rupiah($g['target_nominal'])) ?></p>
                        <div class="mt-2 flex items-center gap-3">
                            <div class="bar bar-lg flex-1"><span class="bg-forest" style="width: <?= round($g['pct'], 1) ?>%"></span></div>
                            <span class="font-display text-sm font-bold"><?= (int) round($g['pct']) ?>%</span>
                        </div>
                        <?php if ($g['pct'] < 100): ?>
                        <button type="button" class="btn btn-light mt-3 !py-2" data-modal-open="modal-deposit" data-fill-id="<?= (int) $g['id'] ?>" data-fill-label="<?= h($g['nama_target']) ?>"><?= icon('plus', 'w-4 h-4') ?> Setor tabungan</button>
                        <?php else: ?>
                        <p class="mt-3 text-sm font-bold text-income">Target tercapai 🎉</p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- Transaksi terbaru -->
    <section class="card lg:col-span-7" aria-labelledby="t-transaksi">
        <div class="mb-2 flex items-center justify-between">
            <p id="t-transaksi" class="card-title">Transaksi · <?= h($period['label']) ?></p>
            <a href="<?= h(app_url('pages/transactions.php?month=' . $period['month'])) ?>" class="text-sm font-bold no-underline">Lihat semua</a>
        </div>
        <?php if (!$recent): ?>
            <div class="rounded-2xl bg-sage-50 p-8 text-center text-sm text-forest/70">
                <p class="mb-3">Belum ada transaksi di <?= h($period['label']) ?>.</p>
                <button class="btn btn-primary" data-modal-open="modal-quick"><?= icon('plus', 'w-4 h-4') ?> Tambah transaksi</button>
            </div>
        <?php else: foreach ($recent as $t) { echo render_tx_row($t, false); } endif; ?>
    </section>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
