        </main>
    </div>
</div>
<?php
// Pesan sukses/gagal dari aksi sebelumnya, dan status formulir bila terjadi kesalahan input
$flash = flash_get();
$fs    = $formState ?? null;
$old   = $fs['old'] ?? [];
$token = h(csrf_token());
$cats  = categories();
// Bulan bawaan untuk form anggaran: mengikuti bulan yang sedang dilihat di halaman
$budgetMonth = (isset($period['month'])) ? $period['month'] : date('Y-m');

$isOpen = function (string $modal) use ($fs): bool { return $fs && $fs['modal'] === $modal; };
$open   = function (string $modal) use ($isOpen): string { return $isOpen($modal) ? ' open' : ''; };
$aria   = function (string $modal) use ($isOpen): string { return $isOpen($modal) ? 'false' : 'true'; };
$err    = function (string $modal) use ($fs, $isOpen): string {
    return $isOpen($modal) ? '<p class="form-error" role="alert">' . h($fs['error']) . '</p>' : '';
};
// Nilai isian lama hanya dipakai pada modal yang sedang gagal disimpan
$val = function (string $modal, string $k, string $d = '') use ($old, $isOpen): string {
    return h($isOpen($modal) ? ($old[$k] ?? $d) : $d);
};

// Formulir transaksi (dipakai bersama oleh Tambah dan Edit)
$txForm = function (string $modal, string $prefix, string $do, string $submit, bool $withId) use ($cats, $token, $val, $err, $isOpen, $old) {
    $tipe = ($isOpen($modal) && ($old['tipe'] ?? '') === 'pemasukan') ? 'pemasukan' : 'pengeluaran';
    ?>
        <form method="post" action="" class="space-y-4" novalidate>
            <input type="hidden" name="csrf" value="<?= $token ?>">
            <input type="hidden" name="do" value="<?= $do ?>">
            <?php if ($withId): ?><input type="hidden" name="id" value="<?= $val($modal, 'id') ?>"><?php endif; ?>
            <div class="segmented" role="radiogroup" aria-label="Jenis transaksi">
                <label><input type="radio" name="tipe" value="pengeluaran" <?= $tipe === 'pengeluaran' ? 'checked' : '' ?>><span>Pengeluaran</span></label>
                <label><input type="radio" name="tipe" value="pemasukan" <?= $tipe === 'pemasukan' ? 'checked' : '' ?>><span>Pemasukan</span></label>
            </div>
            <div>
                <label class="label" for="<?= $prefix ?>-nominal">Nominal</label>
                <div class="relative">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 font-semibold text-forest/60">Rp</span>
                    <input id="<?= $prefix ?>-nominal" name="nominal" type="text" inputmode="numeric" autocomplete="off" placeholder="0" value="<?= $val($modal, 'nominal') ?>" class="input money-input !pl-11 font-display text-lg font-bold" required>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="label" for="<?= $prefix ?>-cat-pengeluaran">Kategori</label>
                    <?php foreach (['pengeluaran', 'pemasukan'] as $tp): ?>
                        <select id="<?= $prefix ?>-cat-<?= $tp ?>" name="category_id" data-cat-for="<?= $tp ?>" aria-label="Kategori <?= $tp ?>" class="input <?= $tipe === $tp ? '' : 'hidden' ?>" <?= $tipe === $tp ? '' : 'disabled' ?>>
                            <?php foreach ($cats[$tp] as $c): ?>
                                <option value="<?= (int) $c['id'] ?>" <?= ($isOpen($modal) && $tipe === $tp && (int) ($old['category_id'] ?? 0) === $c['id']) ? 'selected' : '' ?>><?= h($c['nama']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    <?php endforeach; ?>
                </div>
                <div>
                    <label class="label" for="<?= $prefix ?>-tanggal">Tanggal</label>
                    <input id="<?= $prefix ?>-tanggal" name="tanggal" type="date" value="<?= $val($modal, 'tanggal', date('Y-m-d')) ?>" class="input" required>
                </div>
            </div>
            <div>
                <label class="label" for="<?= $prefix ?>-deskripsi">Deskripsi</label>
                <input id="<?= $prefix ?>-deskripsi" name="deskripsi" type="text" maxlength="100" placeholder="mis. Nasi Padang" value="<?= $val($modal, 'deskripsi') ?>" class="input">
            </div>
            <?= $err($modal) ?>
            <button type="submit" class="btn btn-primary w-full"><?= h($submit) ?></button>
        </form>
    <?php
};
?>

<!-- Tombol tambah cepat (mobile) -->
<button type="button" class="fab lg:hidden" data-modal-open="modal-quick" aria-label="Tambah transaksi"><?= icon('plus', 'w-6 h-6') ?></button>

<div id="toasts" class="toasts" aria-live="polite">
    <?php if ($flash): ?><div class="toast <?= h($flash['t']) ?>" role="status"><?= h($flash['m']) ?></div><?php endif; ?>
</div>

<!-- Quick Add -->
<div id="modal-quick" class="modal<?= $open('modal-quick') ?>" aria-hidden="<?= $aria('modal-quick') ?>" role="dialog" aria-modal="true" aria-labelledby="qa-title">
    <div class="modal-backdrop" data-modal-close></div>
    <div class="modal-panel">
        <div class="mb-5 flex items-start justify-between gap-4">
            <div>
                <h2 id="qa-title" class="font-display text-xl font-extrabold">Tambah transaksi</h2>
                <p class="text-sm text-forest/60">Catat tanpa meninggalkan halaman ini.</p>
            </div>
            <button type="button" class="icon-btn" data-modal-close aria-label="Tutup"><?= icon('x', 'w-5 h-5') ?></button>
        </div>
        <?php $txForm('modal-quick', 'qa', 'tx_create', 'Simpan', false); ?>
    </div>
</div>

<!-- Edit transaksi -->
<div id="modal-edit" class="modal<?= $open('modal-edit') ?>" aria-hidden="<?= $aria('modal-edit') ?>" role="dialog" aria-modal="true" aria-labelledby="ed-title">
    <div class="modal-backdrop" data-modal-close></div>
    <div class="modal-panel">
        <div class="mb-5 flex items-start justify-between gap-4">
            <div>
                <h2 id="ed-title" class="font-display text-xl font-extrabold">Edit transaksi</h2>
                <p class="text-sm text-forest/60">Ubah data lalu simpan. Saldo dihitung ulang otomatis.</p>
            </div>
            <button type="button" class="icon-btn" data-modal-close aria-label="Tutup"><?= icon('x', 'w-5 h-5') ?></button>
        </div>
        <?php $txForm('modal-edit', 'ed', 'tx_update', 'Simpan perubahan', true); ?>
    </div>
</div>

<!-- Konfirmasi hapus -->
<div id="modal-confirm" class="modal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="cf-title">
    <div class="modal-backdrop" data-modal-close></div>
    <div class="modal-panel max-w-md">
        <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-expense/10 text-expense"><?= icon('trash', 'w-6 h-6') ?></div>
        <h2 id="cf-title" class="font-display text-xl font-extrabold">Hapus "<span data-text="label"></span>"?</h2>
        <p class="mt-2 text-sm text-forest/70" data-text="message"></p>
        <form method="post" action="" class="mt-6 flex gap-3">
            <input type="hidden" name="csrf" value="<?= $token ?>">
            <input type="hidden" name="do">
            <input type="hidden" name="id">
            <button type="button" class="btn btn-ghost flex-1" data-modal-close>Batal</button>
            <button type="submit" class="btn btn-danger flex-1">Hapus</button>
        </form>
    </div>
</div>

<!-- Setor tabungan -->
<div id="modal-deposit" class="modal<?= $open('modal-deposit') ?>" aria-hidden="<?= $aria('modal-deposit') ?>" role="dialog" aria-modal="true" aria-labelledby="dp-title">
    <div class="modal-backdrop" data-modal-close></div>
    <div class="modal-panel max-w-md">
        <div class="mb-5 flex items-start justify-between gap-4">
            <div>
                <h2 id="dp-title" class="font-display text-xl font-extrabold">Setor tabungan</h2>
                <p class="text-sm text-forest/60" data-text="label"><?= $val('modal-deposit', 'label') ?></p>
            </div>
            <button type="button" class="icon-btn" data-modal-close aria-label="Tutup"><?= icon('x', 'w-5 h-5') ?></button>
        </div>
        <form method="post" action="" class="space-y-4" novalidate>
            <input type="hidden" name="csrf" value="<?= $token ?>">
            <input type="hidden" name="do" value="goal_deposit">
            <input type="hidden" name="id" value="<?= $val('modal-deposit', 'id') ?>">
            <input type="hidden" name="label" value="<?= $val('modal-deposit', 'label') ?>">
            <div>
                <label class="label" for="dp-nominal">Nominal setoran</label>
                <div class="relative">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 font-semibold text-forest/60">Rp</span>
                    <input id="dp-nominal" name="nominal" type="text" inputmode="numeric" placeholder="0" value="<?= $val('modal-deposit', 'nominal') ?>" class="input money-input !pl-11 font-display text-lg font-bold" required>
                </div>
                <p class="mt-1 text-xs text-forest/60">Dana diambil dari saldo bulan ini.</p>
            </div>
            <?= $err('modal-deposit') ?>
            <button type="submit" class="btn btn-primary w-full">Setor</button>
        </form>
    </div>
</div>

<!-- Target tabungan baru -->
<div id="modal-goal" class="modal<?= $open('modal-goal') ?>" aria-hidden="<?= $aria('modal-goal') ?>" role="dialog" aria-modal="true" aria-labelledby="gl-title">
    <div class="modal-backdrop" data-modal-close></div>
    <div class="modal-panel max-w-md">
        <div class="mb-5 flex items-start justify-between gap-4">
            <h2 id="gl-title" class="font-display text-xl font-extrabold">Target tabungan baru</h2>
            <button type="button" class="icon-btn" data-modal-close aria-label="Tutup"><?= icon('x', 'w-5 h-5') ?></button>
        </div>
        <form method="post" action="" class="space-y-4" novalidate>
            <input type="hidden" name="csrf" value="<?= $token ?>">
            <input type="hidden" name="do" value="goal_create">
            <div>
                <label class="label" for="gl-nama">Nama target</label>
                <input id="gl-nama" name="nama" type="text" maxlength="100" placeholder="mis. Laptop untuk kuliah" value="<?= $val('modal-goal', 'nama') ?>" class="input" required>
            </div>
            <div>
                <label class="label" for="gl-target">Nominal target</label>
                <div class="relative">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 font-semibold text-forest/60">Rp</span>
                    <input id="gl-target" name="target" type="text" inputmode="numeric" placeholder="0" value="<?= $val('modal-goal', 'target') ?>" class="input money-input !pl-11" required>
                </div>
            </div>
            <?= $err('modal-goal') ?>
            <button type="submit" class="btn btn-primary w-full">Simpan target</button>
        </form>
    </div>
</div>

<!-- Atur anggaran (per kategori, per bulan) -->
<div id="modal-budget" class="modal<?= $open('modal-budget') ?>" aria-hidden="<?= $aria('modal-budget') ?>" role="dialog" aria-modal="true" aria-labelledby="bg-title">
    <div class="modal-backdrop" data-modal-close></div>
    <div class="modal-panel max-w-md">
        <div class="mb-5 flex items-start justify-between gap-4">
            <h2 id="bg-title" class="font-display text-xl font-extrabold">Atur anggaran</h2>
            <button type="button" class="icon-btn" data-modal-close aria-label="Tutup"><?= icon('x', 'w-5 h-5') ?></button>
        </div>
        <form method="post" action="" class="space-y-4" novalidate>
            <input type="hidden" name="csrf" value="<?= $token ?>">
            <input type="hidden" name="do" value="budget_save">
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="label" for="bg-cat">Kategori</label>
                    <select id="bg-cat" name="category_id" class="input">
                        <?php foreach ($cats['pengeluaran'] as $c): ?>
                            <option value="<?= (int) $c['id'] ?>" <?= ($isOpen('modal-budget') && (int) ($old['category_id'] ?? 0) === $c['id']) ? 'selected' : '' ?>><?= h($c['nama']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="label" for="bg-periode">Bulan</label>
                    <input id="bg-periode" name="periode" type="month" value="<?= $val('modal-budget', 'periode', $budgetMonth) ?>" class="input" required>
                </div>
            </div>
            <div>
                <label class="label" for="bg-jenis">Jenis anggaran</label>
                <select id="bg-jenis" name="jenis_anggaran" class="input" required>
                    <option value="fixed" <?= $val('modal-budget', 'jenis_anggaran', 'flexible') === 'fixed' ? 'selected' : '' ?>>Pengeluaran Wajib (Fixed) — dikunci</option>
                    <option value="flexible" <?= $val('modal-budget', 'jenis_anggaran', 'flexible') === 'flexible' ? 'selected' : '' ?>>Pengeluaran Fleksibel (Flexible)</option>
                </select>
                <p class="mt-1 text-xs text-forest/60">Anggaran wajib mengurangi saldo bebas sampai dana tersebut digunakan.</p>
            </div>
            <div>
                <label class="label" for="bg-batas">Batas anggaran</label>
                <div class="relative">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 font-semibold text-forest/60">Rp</span>
                    <input id="bg-batas" name="batas_anggaran" type="text" inputmode="numeric" placeholder="0" value="<?= $val('modal-budget', 'batas_anggaran') ?>" class="input money-input !pl-11" required>
                </div>
            </div>
            <?= $err('modal-budget') ?>
            <button type="submit" class="btn btn-primary w-full">Simpan anggaran</button>
        </form>
    </div>
</div>


<!-- Modal pengingat -->
<div id="modal-reminder" class="modal<?= $open('modal-reminder') ?>" aria-hidden="<?= $aria('modal-reminder') ?>" role="dialog" aria-modal="true" aria-labelledby="rm-title">
  <div class="modal-backdrop" data-modal-close></div>
  <div class="modal-panel">
    <div class="mb-5 flex items-start justify-between gap-4"><div><h2 id="rm-title" class="font-display text-xl font-extrabold">Buat pengingat</h2><p class="text-sm text-forest/60">Atur kapan SakuBijak mengingatkanmu.</p></div><button type="button" class="icon-btn" data-modal-close aria-label="Tutup"><?= icon('x','w-5 h-5') ?></button></div>
    <form method="post" class="space-y-4">
      <input type="hidden" name="csrf" value="<?= $token ?>"><input type="hidden" name="do" value="reminder_save">
      <div><label class="label" for="rm-type">Jenis pengingat</label><select id="rm-type" name="reminder_type" class="input" required>
        <option value="daily_expense">Pengingat Pengeluaran Harian</option>
        <option value="bill_payment">Pengingat Pembayaran Tagihan</option>
        <option value="budget_limit">Peringatan Batas Anggaran</option>
      </select></div>
      <div><label class="label" for="rm-title-input">Nama pengingat</label><input id="rm-title-input" name="title" class="input" maxlength="100" placeholder="Contoh: Catat pengeluaran hari ini" required></div>
      <div data-reminder-field="date"><label class="label" for="rm-date">Tanggal jatuh tempo</label><input id="rm-date" name="due_date" type="date" min="<?= date('Y-m-d') ?>" class="input"></div>
      <div data-reminder-field="amount"><label class="label" for="rm-amount">Nominal tagihan (opsional)</label><div class="relative"><span class="absolute left-4 top-1/2 -translate-y-1/2 text-forest/60">Rp</span><input id="rm-amount" name="amount" class="input money-input !pl-11" inputmode="numeric" placeholder="0"></div></div>
      <div data-reminder-field="threshold" class="hidden"><label class="label" for="rm-threshold">Ingatkan saat anggaran terpakai</label><select id="rm-threshold" name="threshold_percent" class="input"><option value="70">70%</option><option value="80" selected>80%</option><option value="90">90%</option><option value="100">100% (melewati batas)</option></select></div>
      <div data-reminder-field="time"><label class="label" for="rm-time">Jam pengingat</label><input id="rm-time" name="reminder_time" type="time" value="20:00" class="input"></div>
      <?= $err('modal-reminder') ?>
      <button class="btn btn-primary w-full" type="submit">Simpan pengingat</button>
    </form>
  </div>
</div>

<?php if (!empty($withCharts)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<?php endif; ?>
<script type="application/json" id="reminder-events"><?= json_encode($reminderEvents ?? [], JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT) ?></script>
<script src="<?= h(app_url('assets/js/reminders.js')) ?>"></script>
<script src="<?= h(app_url('assets/js/dashboard.js')) ?>"></script>
</body>
</html>
