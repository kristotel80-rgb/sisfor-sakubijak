<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
$db = db();
$uid = (int) current_user()['id'];
$st = $db->prepare('SELECT * FROM reminders WHERE user_id=? ORDER BY is_active DESC, reminder_type, due_date');
$st->execute([$uid]);
$reminders = $st->fetchAll();
$period = resolve_period('this');
$budgetRows = budgets_usage($db, $uid, $period);
$budgetEvents = [];
foreach ($budgetRows as $b) {
    foreach ($reminders as $r) {
        if ($r['reminder_type'] === 'budget_limit' && (int)$r['is_active'] === 1 && $b['pct'] >= (int)$r['threshold_percent']) {
            $budgetEvents[] = ['key'=>'budget-'.$b['category_id'].'-'.$period['month'], 'title'=>'Batas anggaran: '.$b['category'], 'body'=>'Pengeluaran sudah mencapai '.(int)round($b['pct']).'% dari anggaran.'];
        }
    }
}
$events = [];
foreach ($reminders as $r) {
    if (!(int)$r['is_active']) continue;
    if ($r['reminder_type'] === 'daily_expense') {
        $events[] = ['type'=>'daily_expense','key'=>'daily-'.$r['id'],'title'=>$r['title'],'body'=>'Yuk, catat pengeluaran hari ini.','time'=>substr($r['reminder_time'],0,5)];
    } elseif ($r['reminder_type'] === 'bill_payment') {
        $events[] = ['type'=>'bill_payment','key'=>'bill-'.$r['id'],'title'=>$r['title'],'body'=>'Jatuh tempo hari ini'.($r['amount'] ? ' · '.rupiah($r['amount']) : '').'.','date'=>$r['due_date'],'time'=>substr($r['reminder_time'],0,5)];
    }
}
$events = array_merge($events, $budgetEvents);
$pageTitle = 'Pengingat';
$active = 'reminders';
include __DIR__ . '/../includes/header.php';
$labels = ['daily_expense'=>'Harian','bill_payment'=>'Tagihan','budget_limit'=>'Batas anggaran'];
$descs = ['daily_expense'=>'Ingatkan untuk mencatat pengeluaran setiap hari.','bill_payment'=>'Ingatkan saat tagihan mendekati atau tiba jatuh tempo.','budget_limit'=>'Peringatan saat penggunaan anggaran mencapai persentase tertentu.'];
?>
<section class="mb-6 mt-2 flex flex-wrap items-end justify-between gap-3">
  <div><h1 class="font-display text-2xl font-extrabold tracking-tight sm:text-3xl">Pengingat</h1><p class="text-sm text-forest/60">Biar tidak lupa mencatat dan membayar tagihan.</p></div>
  <div class="flex flex-wrap gap-2">
    <button type="button" class="btn btn-light" id="enable-notifications">Aktifkan notifikasi</button>
    <button type="button" class="btn btn-primary" data-modal-open="modal-reminder">+ Buat pengingat</button>
  </div>
</section>
<div class="grid gap-4 md:grid-cols-3 mb-5">
<?php foreach ($labels as $key=>$label): ?>
  <article class="card reminder-intro">
    <p class="card-title"><?= h($label) ?></p><h2 class="mt-2 font-display font-bold"><?= h($key==='daily_expense'?'Catat pengeluaran':($key==='bill_payment'?'Bayar tagihan':'Jaga batas budget')) ?></h2>
    <p class="mt-1 text-sm text-forest/60"><?= h($descs[$key]) ?></p>
  </article>
<?php endforeach; ?>
</div>
<section class="card">
  <div class="mb-4 flex items-center justify-between"><h2 class="font-display font-extrabold">Pengingat saya</h2><span class="chip"><?= count(array_filter($reminders,fn($r)=>(int)$r['is_active'])) ?> aktif</span></div>
  <?php if (!$reminders): ?>
    <div class="rounded-2xl bg-sage-50 p-7 text-center"><p class="font-semibold">Belum ada pengingat</p><p class="mt-1 text-sm text-forest/60">Buat pengingat pertama supaya keuangan lebih teratur.</p><button class="btn btn-primary mt-4" data-modal-open="modal-reminder">Buat pengingat</button></div>
  <?php else: ?>
    <div class="space-y-3">
    <?php foreach ($reminders as $r): ?>
      <article class="reminder-row">
        <div class="min-w-0">
          <div class="flex flex-wrap items-center gap-2"><strong><?= h($r['title']) ?></strong><span class="chip"><?= h($labels[$r['reminder_type']]) ?></span><?php if (!(int)$r['is_active']): ?><span class="text-xs text-forest/50">Nonaktif</span><?php endif; ?></div>
          <p class="mt-1 text-sm text-forest/60">
            <?php if ($r['reminder_type']==='daily_expense'): ?>Setiap hari · <?= h(substr($r['reminder_time'],0,5)) ?>
            <?php elseif ($r['reminder_type']==='bill_payment'): ?>Jatuh tempo <?= h(tgl($r['due_date'])) ?> · <?= h(substr($r['reminder_time'],0,5)) ?><?= $r['amount'] ? ' · '.h(rupiah($r['amount'])) : '' ?>
            <?php else: ?>Saat anggaran mencapai <?= (int)$r['threshold_percent'] ?>%<?php endif; ?>
          </p>
        </div>
        <form method="post" class="shrink-0" onsubmit="return confirm('Hapus pengingat ini?')"><input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>"><input type="hidden" name="do" value="reminder_delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="icon-btn text-expense" aria-label="Hapus pengingat"><?= icon('trash','w-4 h-4') ?></button></form>
      </article>
    <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
