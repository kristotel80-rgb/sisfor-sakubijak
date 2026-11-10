<?php
// Fungsi bantu: format, kategori, ikon, query keuangan.

function h($s): string { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); }

function app_url(string $path = ''): string { return BASE_URL . '/' . ltrim($path, '/'); }

function rupiah($n): string
{
    $n = (int) $n;
    return ($n < 0 ? '-' : '') . 'Rp' . number_format(abs($n), 0, ',', '.');
}

function parse_amount($s): int { return (int) preg_replace('/\D/', '', (string) $s); }

const BULAN = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
const HARI  = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

function tgl(string $date, bool $withDay = false): string
{
    $t = strtotime($date);
    $s = (int) date('j', $t) . ' ' . BULAN[(int) date('n', $t)] . ' ' . date('Y', $t);
    return $withDay ? HARI[(int) date('w', $t)] . ', ' . $s : $s;
}

function tgl_pendek(string $date): string
{
    $t = strtotime($date);
    return (int) date('j', $t) . ' ' . mb_substr(BULAN[(int) date('n', $t)], 0, 3) . ' ' . date('Y', $t);
}

// ---------- Kategori (dibaca dari tabel categories) ----------
// Hasil: ['pemasukan' => [id => baris], 'pengeluaran' => [...], 'tabungan' => [...]]
function categories(): array
{
    static $all = null;
    if ($all === null) {
        $all = ['pemasukan' => [], 'pengeluaran' => [], 'tabungan' => []];
        foreach (db()->query('SELECT id, nama, tipe, ikon, warna FROM categories ORDER BY id') as $r) {
            $r['id'] = (int) $r['id'];
            $all[$r['tipe']][$r['id']] = $r;
        }
    }
    return $all;
}

// Cari satu kategori berdasarkan id; $tipe (opsional) memastikan jenisnya cocok.
function find_category(int $id, ?string $tipe = null): ?array
{
    foreach (categories() as $t => $rows) {
        if (isset($rows[$id]) && ($tipe === null || $t === $tipe)) { return $rows[$id]; }
    }
    return null;
}

// ---------- Ikon garis ----------
function icon(string $name, string $class = 'w-5 h-5'): string
{
    static $p = [
        'home' => '<path d="M3 11l9-8 9 8"/><path d="M5 10v10h14V10"/>',
        'swap' => '<path d="M7 4L3 8l4 4"/><path d="M3 8h14"/><path d="M17 20l4-4-4-4"/><path d="M21 16H7"/>',
        'gauge' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'target' => '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3M4.9 4.9l2.1 2.1M17 17l2.1 2.1M4.9 19.1L7 17M17 7l2.1-2.1"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/>',
        'bell' => '<path d="M6 8a6 6 0 0112 0c0 7 3 8 3 8H3s3-1 3-8"/><path d="M10 20a2 2 0 004 0"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'eye' => '<path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
        'eye-off' => '<path d="M3 3l18 18"/><path d="M10.6 6.1A9.8 9.8 0 0112 5c6 0 10 7 10 7a17 17 0 01-3.2 3.9M6.6 6.6A17 17 0 002 12s4 7 10 7a9.7 9.7 0 004.2-1"/>',
        'arrow-up' => '<path d="M12 19V5M5 12l7-7 7 7"/>',
        'arrow-down' => '<path d="M12 5v14M5 12l7 7 7-7"/>',
        'utensils' => '<path d="M5 3v8a2 2 0 002 2 2 2 0 002-2V3M7 3v18"/><path d="M17 3c-2 2-3 5-3 8h6V3zM17 11v10"/>',
        'bus' => '<rect x="4" y="3" width="16" height="14" rx="3"/><path d="M4 11h16M8 21v-4M16 21v-4"/>',
        'book' => '<path d="M4 5a2 2 0 012-2h13v16H6a2 2 0 00-2 2z"/><path d="M4 19V5"/>',
        'film' => '<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M8 4v16M16 4v16M3 9h5M3 15h5M16 9h5M16 15h5"/>',
        'bag' => '<path d="M5 8h14l-1 12H6z"/><path d="M9 8a3 3 0 016 0"/>',
        'heart' => '<path d="M12 20s-8-5-8-11a4.5 4.5 0 018-2.5A4.5 4.5 0 0120 9c0 6-8 11-8 11z"/>',
        'dots' => '<circle cx="6" cy="12" r="1.2"/><circle cx="12" cy="12" r="1.2"/><circle cx="18" cy="12" r="1.2"/>',
        'wallet' => '<path d="M3 7a2 2 0 012-2h13v4"/><path d="M3 7v11a2 2 0 002 2h15V9H5a2 2 0 01-2-2z"/><circle cx="16.5" cy="14.5" r="1"/>',
        'cap' => '<path d="M2 9l10-5 10 5-10 5z"/><path d="M6 11v5c3 2 9 2 12 0v-5"/>',
        'briefcase' => '<rect x="3" y="7" width="18" height="13" rx="2"/><path d="M9 7V5a2 2 0 012-2h2a2 2 0 012 2v2"/>',
        'coins' => '<circle cx="9" cy="9" r="6"/><path d="M15 9.5A6 6 0 1112.5 20"/>',
        'edit' => '<path d="M4 20h4L19 9l-4-4L4 16z"/><path d="M13.5 6.5l4 4"/>',
        'trash' => '<path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13"/>',
        'x' => '<path d="M6 6l12 12M18 6L6 18"/>',
        'logout' => '<path d="M9 21H5V3h4M16 17l5-5-5-5M21 12H9"/>',
        'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0116 0"/>',
        'check' => '<path d="M5 13l4 4L19 7"/>',
        'alert' => '<path d="M12 3l10 18H2z"/><path d="M12 10v5M12 18v.01"/>',
        'chevron-down' => '<path d="M6 9l6 6 6-6"/>',
        'chevron-right' => '<path d="M9 6l6 6-6 6"/>',
    ];
    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="' . $class . '" aria-hidden="true">' . ($p[$name] ?? $p['dots']) . '</svg>';
}

// ---------- Periode ----------
function resolve_period(?string $p): array
{
    $p = $p ?: 'this';
    $now = new DateTimeImmutable('today');
    if ($p === 'prev') {
        $first = $now->modify('first day of last month');
    } elseif (preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $p)) {
        $first = new DateTimeImmutable($p . '-01');
    } else {
        $p = 'this';
        $first = $now->modify('first day of this month');
    }
    $last = $first->modify('last day of this month');
    $pf = $first->modify('first day of last month');
    $pl = $pf->modify('last day of this month');
    $isCurrent = $first->format('Y-m') === $now->format('Y-m');
    return [
        'key' => $p,
        'month' => $first->format('Y-m'),
        'bulan' => (int) $first->format('n'),
        'tahun' => (int) $first->format('Y'),
        'start' => $first->format('Y-m-d'),
        'end' => $last->format('Y-m-d'),
        'prev_start' => $pf->format('Y-m-d'),
        'prev_end' => $pl->format('Y-m-d'),
        'label' => BULAN[(int) $first->format('n')] . ' ' . $first->format('Y'),
        'prev_label' => BULAN[(int) $pf->format('n')] . ' ' . $pf->format('Y'),
        'is_current' => $isCurrent,
    ];
}

// ---------- Query keuangan ----------
const TX_SELECT = 'SELECT t.*, c.nama AS kategori, c.ikon FROM transactions t JOIN categories c ON c.id = t.category_id';

function period_totals(PDO $db, int $uid, string $start, string $end): array
{
    $st = $db->prepare('SELECT tipe, COALESCE(SUM(nominal),0) s FROM transactions WHERE user_id=? AND tanggal BETWEEN ? AND ? GROUP BY tipe');
    $st->execute([$uid, $start, $end]);
    $r = ['pemasukan' => 0, 'pengeluaran' => 0, 'tabungan' => 0];
    foreach ($st as $row) { $r[$row['tipe']] = (int) $row['s']; }
    return $r;
}

function expense_by_category(PDO $db, int $uid, string $start, string $end): array
{
    $st = $db->prepare("SELECT c.id, c.nama AS kategori, c.warna, SUM(t.nominal) total
                        FROM transactions t JOIN categories c ON c.id = t.category_id
                        WHERE t.user_id=? AND t.tipe='pengeluaran' AND t.tanggal BETWEEN ? AND ?
                        GROUP BY c.id, c.nama, c.warna ORDER BY total DESC");
    $st->execute([$uid, $start, $end]);
    return $st->fetchAll();
}

// Anggaran satu bulan ($period dari resolve_period) beserta pemakaiannya.
function budgets_usage(PDO $db, int $uid, array $period, ?int $categoryId = null): array
{
    $sql = "SELECT b.id, b.category_id, c.nama AS kategori, c.ikon, b.jenis_anggaran, b.batas_anggaran,
              COALESCE((SELECT SUM(t.nominal) FROM transactions t
                        WHERE t.user_id=b.user_id AND t.tipe='pengeluaran' AND t.category_id=b.category_id
                          AND t.tanggal BETWEEN ? AND ?),0) terpakai
            FROM budgets b JOIN categories c ON c.id = b.category_id
            WHERE b.user_id=? AND b.bulan=? AND b.tahun=?";
    $args = [$period['start'], $period['end'], $uid, $period['bulan'], $period['tahun']];
    if ($categoryId !== null) { $sql .= ' AND b.category_id=?'; $args[] = $categoryId; }
    $st = $db->prepare($sql . ' ORDER BY c.nama');
    $st->execute($args);
    $out = [];
    foreach ($st as $r) {
        $limit = (int) $r['batas_anggaran'];
        $spent = (int) $r['terpakai'];
        // Limit nol ditangani khusus: tidak ada pembagian dengan nol
        $pct = $limit > 0 ? ($spent / $limit) * 100 : ($spent > 0 ? 100 : 0);
        $out[] = [
            'id' => (int) $r['id'], 'category_id' => (int) $r['category_id'],
            'category' => $r['kategori'], 'icon' => $r['ikon'], 'type' => $r['jenis_anggaran'] ?? 'flexible',
            'limit' => $limit, 'spent' => $spent,
            'remaining' => max(0, $limit - $spent), 'over' => max(0, $spent - $limit),
            'pct' => $pct, 'status' => $pct >= 90 ? 'danger' : ($pct >= 70 ? 'warn' : 'ok'),
        ];
    }
    return $out;
}

function budget_alerts(PDO $db, int $uid): array
{
    $list = array_filter(budgets_usage($db, $uid, resolve_period('this')), fn($b) => $b['status'] !== 'ok');
    usort($list, fn($a, $b) => $b['pct'] <=> $a['pct']);
    return $list;
}

function recent_transactions(PDO $db, int $uid, int $limit, string $start, string $end): array
{
    $limit = max(1, min(50, $limit));
    $st = $db->prepare(TX_SELECT . " WHERE t.user_id=? AND t.tanggal BETWEEN ? AND ? ORDER BY t.tanggal DESC, t.id DESC LIMIT $limit");
    $st->execute([$uid, $start, $end]);
    return $st->fetchAll();
}

function savings_goals(PDO $db, int $uid): array
{
    $st = $db->prepare("SELECT s.*, COALESCE((SELECT SUM(t.nominal) FROM transactions t WHERE t.savings_id=s.id AND t.tipe='tabungan'),0) saved
                        FROM savings s WHERE s.user_id=? ORDER BY s.id");
    $st->execute([$uid]);
    $out = [];
    foreach ($st as $r) {
        $target = (int) $r['target_nominal'];
        $saved = (int) $r['saved'];
        $r['target_nominal'] = $target;
        $r['saved'] = $saved;
        $r['pct'] = $target > 0 ? min(100, $saved / $target * 100) : 0;
        $out[] = $r;
    }
    return $out;
}

// Saldo satu bulan saja: pemasukan - pengeluaran - setoran tabungan pada bulan itu.
// Tidak ada saldo yang terbawa dari bulan sebelumnya.
function reserved_fixed_budget(PDO $db, int $uid, array $period): int
{
    // Hanya sisa anggaran wajib yang dikunci; pembayaran yang sudah dicatat
    // mengurangi dana terkunci sehingga tidak terjadi penghitungan ganda.
    $st = $db->prepare("SELECT COALESCE(SUM(GREATEST(b.batas_anggaran - COALESCE(x.terpakai,0),0)),0)
        FROM budgets b LEFT JOIN (
            SELECT category_id, SUM(nominal) terpakai FROM transactions
            WHERE user_id=? AND tipe='pengeluaran' AND tanggal BETWEEN ? AND ? GROUP BY category_id
        ) x ON x.category_id=b.category_id
        WHERE b.user_id=? AND b.bulan=? AND b.tahun=? AND b.jenis_anggaran='fixed'");
    $st->execute([$uid, $period['start'], $period['end'], $uid, $period['bulan'], $period['tahun']]);
    return (int) $st->fetchColumn();
}

function current_balance(PDO $db, int $uid, ?string $month = null): int
{
    $p = resolve_period($month ?: 'this');
    $t = period_totals($db, $uid, $p['start'], $p['end']);
    return $t['pemasukan'] - $t['pengeluaran'] - $t['tabungan'] - reserved_fixed_budget($db, $uid, $p);
}

// ---------- Tampilan ----------
function status_class(string $status): string
{
    return ['ok' => 'bg-income', 'warn' => 'bg-amber-400', 'danger' => 'bg-expense'][$status] ?? 'bg-income';
}

function render_tx_row(array $t, bool $deletable = true): string
{
    $tipe = $t['tipe'];
    $sign = $tipe === 'pemasukan' ? '+' : '-';
    $color = $tipe === 'pemasukan' ? 'text-income' : ($tipe === 'pengeluaran' ? 'text-expense' : 'text-forest');
    $bubble = $tipe === 'pemasukan' ? 'bg-income/10 text-income' : ($tipe === 'pengeluaran' ? 'bg-expense/10 text-expense' : 'bg-sage text-forest');
    ob_start(); ?>
    <div class="tx-row group">
        <div class="flex items-center gap-3 min-w-0">
            <span class="icon-bubble <?= $bubble ?>"><?= icon($t['ikon'], 'w-5 h-5') ?></span>
            <div class="min-w-0">
                <p class="font-semibold truncate"><?= h($t['deskripsi']) ?></p>
                <p class="text-xs text-forest/60 md:hidden"><?= h($t['kategori']) ?> · <?= h(tgl_pendek($t['tanggal'])) ?></p>
            </div>
        </div>
        <span class="hidden md:block text-sm text-forest/60"><?= h(tgl_pendek($t['tanggal'])) ?></span>
        <span class="hidden md:inline-flex"><span class="chip"><?= h($t['kategori']) ?></span></span>
        <div class="flex items-center justify-end gap-2">
            <span class="font-display font-bold <?= $color ?> whitespace-nowrap" data-sensitive-tx><?= $sign . rupiah($t['nominal']) ?></span>
            <?php if ($deletable && $tipe !== 'tabungan'): ?>
            <button type="button" class="icon-btn text-forest/40 hover:text-forest" aria-label="Edit transaksi"
                data-modal-open="modal-edit" data-fill-id="<?= (int) $t['id'] ?>" data-fill-tipe="<?= h($tipe) ?>"
                data-fill-nominal="<?= h(number_format((int) $t['nominal'], 0, ',', '.')) ?>" data-fill-category-id="<?= (int) $t['category_id'] ?>"
                data-fill-tanggal="<?= h($t['tanggal']) ?>" data-fill-deskripsi="<?= h($t['deskripsi']) ?>">
                <?= icon('edit', 'w-4 h-4') ?>
            </button>
            <?php endif; ?>
            <?php if ($deletable): ?>
            <button type="button" class="icon-btn text-forest/40 hover:text-expense" aria-label="Hapus transaksi"
                data-modal-open="modal-confirm" data-fill-do="tx_delete"
                data-fill-id="<?= (int) $t['id'] ?>" data-fill-label="<?= h($t['deskripsi']) ?>"
                data-fill-message="Transaksi ini akan dihapus permanen dan saldo dihitung ulang.">
                <?= icon('trash', 'w-4 h-4') ?>
            </button>
            <?php endif; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

function flash_set(string $msg, string $type = 'success'): void { $_SESSION['flash'] = ['m' => $msg, 't' => $type]; }
function flash_get(): ?array { $f = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $f; }
