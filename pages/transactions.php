<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
$db  = db();
$uid = (int) current_user()['id'];

// Transaksi selalu per bulan. Bulan lain dipilih lewat pemilih bulan.
$period = resolve_period($_GET['month'] ?? 'this');
$month  = $period['month'];

$q    = trim((string) ($_GET['q'] ?? ''));
$tipe = (string) ($_GET['tipe'] ?? '');
$catF = (int) ($_GET['category_id'] ?? 0);
$page = max(1, (int) ($_GET['page'] ?? 1));
$per  = 15;

$where = ['t.user_id = ?', 't.tanggal BETWEEN ? AND ?'];
$args  = [$uid, $period['start'], $period['end']];
if ($q !== '') {
    $like = '%' . addcslashes($q, '%_\\') . '%';
    $where[] = '(t.deskripsi LIKE ? OR c.nama LIKE ?)';
    array_push($args, $like, $like);
}
if (in_array($tipe, ['pemasukan', 'pengeluaran', 'tabungan'], true)) { $where[] = 't.tipe = ?'; $args[] = $tipe; } else { $tipe = ''; }
if ($catF > 0) { $where[] = 't.category_id = ?'; $args[] = $catF; }
$w = implode(' AND ', $where);

$st = $db->prepare("SELECT COUNT(*) FROM transactions t JOIN categories c ON c.id = t.category_id WHERE $w");
$st->execute($args);
$total = (int) $st->fetchColumn();
$pages = max(1, (int) ceil($total / $per));
$page = min($page, $pages);
$offset = ($page - 1) * $per;

$st = $db->prepare(TX_SELECT . " WHERE $w ORDER BY t.tanggal DESC, t.id DESC LIMIT $per OFFSET $offset");
$st->execute($args);
$rows = $st->fetchAll();

// Ringkasan bulan ini (tidak terpengaruh pencarian/filter jenis)
$mt = period_totals($db, $uid, $period['start'], $period['end']);
$reservedFixed = reserved_fixed_budget($db, $uid, $period);
$monthBalance = $mt['pemasukan'] - $mt['pengeluaran'] - $mt['tabungan'] - $reservedFixed;

$qs = function (array $o) use ($q, $tipe, $catF, $month) {
    return '?' . http_build_query(array_filter(array_merge(['month' => $month, 'q' => $q, 'tipe' => $tipe, 'category_id' => $catF], $o), fn($v) => $v !== '' && $v !== null && $v !== 0));
};
$filtered = ($q !== '' || $tipe !== '' || $catF > 0);
$groupLabel = ['pengeluaran' => 'Pengeluaran', 'pemasukan' => 'Pemasukan', 'tabungan' => 'Tabungan'];

$pageTitle = 'Transaksi';
$active = 'transactions';
include __DIR__ . '/../includes/header.php';
?>
<section class="mb-5 mt-2 flex flex-wrap items-end justify-between gap-3">
    <div>
        <h1 class="font-display text-2xl font-extrabold tracking-tight sm:text-3xl">Transaksi</h1>
        <p class="text-sm text-forest/60">Pemasukan dan pengeluaran <?= h($period['label']) ?>.</p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a class="btn btn-light" href="<?= h(app_url('pages/export_pdf.php?month=' . $month)) ?>" target="_blank" rel="noopener">Ekspor PDF</a>
        <button class="btn btn-primary" data-modal-open="modal-quick"><?= icon('plus', 'w-4 h-4') ?> Tambah transaksi</button>
    </div>
</section>

<form method="get" class="card mb-4 grid gap-3 !p-4 sm:grid-cols-2 lg:grid-cols-5">
    <div class="sm:col-span-2 lg:col-span-1">
        <label class="label" for="f-month">Bulan</label>
        <input id="f-month" type="month" name="month" value="<?= h($month) ?>" onchange="this.form.submit()" class="input">
    </div>
    <div class="lg:col-span-2">
        <label class="label" for="f-q">Cari</label>
        <input id="f-q" type="search" name="q" value="<?= h($q) ?>" placeholder="Deskripsi atau kategori" class="input">
    </div>
    <div>
        <label class="label" for="f-tipe">Jenis</label>
        <select id="f-tipe" name="tipe" class="input">
            <option value="">Semua jenis</option>
            <?php foreach ($groupLabel as $v => $l): ?><option value="<?= $v ?>" <?= $tipe === $v ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?>
        </select>
    </div>
    <div>
        <label class="label" for="f-cat">Kategori</label>
        <select id="f-cat" name="category_id" class="input">
            <option value="">Semua kategori</option>
            <?php foreach (categories() as $tp => $list): ?>
                <optgroup label="<?= h($groupLabel[$tp]) ?>">
                    <?php foreach ($list as $c): ?><option value="<?= (int) $c['id'] ?>" <?= $catF === $c['id'] ? 'selected' : '' ?>><?= h($c['nama']) ?></option><?php endforeach; ?>
                </optgroup>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="flex flex-wrap gap-2 sm:col-span-2 lg:col-span-5">
        <button class="btn btn-primary">Cari</button>
        <?php if ($filtered): ?><a href="<?= h(app_url('pages/transactions.php?month=' . $month)) ?>" class="btn btn-ghost">Hapus filter</a><?php endif; ?>
        <?php if (!$period['is_current']): ?><a href="<?= h(app_url('pages/transactions.php')) ?>" class="btn btn-ghost">Kembali ke bulan ini</a><?php endif; ?>
    </div>
</form>

<div class="mb-5 grid grid-cols-2 gap-3 text-center sm:grid-cols-3 sm:text-left">
    <div class="card !p-4"><p class="card-title">Pemasukan</p><p class="stat-value font-display font-extrabold text-income"><?= h(rupiah($mt['pemasukan'])) ?></p></div>
    <div class="card !p-4"><p class="card-title">Pengeluaran</p><p class="stat-value font-display font-extrabold text-expense"><?= h(rupiah($mt['pengeluaran'])) ?></p></div>
    <div class="card !p-4 col-span-2 sm:col-span-1"><p class="card-title">Saldo Bebas</p><p class="stat-value font-display font-extrabold"><?= h(rupiah($monthBalance)) ?></p></div>
</div>

<section class="card">
    <?php if (!$rows): ?>
        <div class="rounded-2xl bg-sage-50 p-10 text-center text-sm text-forest/70">
            <?php if ($filtered): ?>
                <p>Tidak ada transaksi di <?= h($period['label']) ?> yang cocok dengan filter.</p>
            <?php else: ?>
                <p class="mb-3">Belum ada transaksi di <?= h($period['label']) ?>.</p>
                <div class="flex flex-wrap gap-2">
        <a class="btn btn-light" href="<?= h(app_url('pages/export_pdf.php?month=' . $month)) ?>" target="_blank" rel="noopener">Ekspor PDF</a>
        <button class="btn btn-primary" data-modal-open="modal-quick"><?= icon('plus', 'w-4 h-4') ?> Tambah transaksi</button>
    </div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="hidden md:grid tx-row !py-2 text-xs font-bold uppercase tracking-wider text-forest/50">
            <span>Transaksi</span><span>Tanggal</span><span>Kategori</span><span class="text-right">Nominal</span>
        </div>
        <?php foreach ($rows as $t) { echo render_tx_row($t); } ?>
        <?php if ($pages > 1): ?>
            <nav class="mt-4 flex items-center justify-between text-sm" aria-label="Halaman">
                <a class="btn btn-ghost !py-2 <?= $page <= 1 ? 'pointer-events-none opacity-40' : '' ?>" href="<?= h($qs(['page' => $page - 1])) ?>">← Sebelumnya</a>
                <span class="text-forest/60">Halaman <?= $page ?> dari <?= $pages ?></span>
                <a class="btn btn-ghost !py-2 <?= $page >= $pages ? 'pointer-events-none opacity-40' : '' ?>" href="<?= h($qs(['page' => $page + 1])) ?>">Berikutnya →</a>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
