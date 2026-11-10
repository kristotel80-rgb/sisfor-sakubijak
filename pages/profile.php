<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
$db  = db();
$uid = (int) current_user()['id'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? null)) {
        $errors[] = 'Sesi formulir kedaluwarsa. Muat ulang halaman.';
    } else {
        $act = $_POST['action'] ?? '';
        $row = $db->prepare('SELECT password_hash FROM users WHERE id=?');
        $row->execute([$uid]);
        $hash = (string) $row->fetchColumn();

        if ($act === 'name') {
            $name = trim((string) ($_POST['name'] ?? ''));
            if ($name === '' || mb_strlen($name) > 80) { $errors[] = 'Nama wajib diisi (maksimal 80 karakter).'; }
            else {
                $db->prepare('UPDATE users SET nama=? WHERE id=?')->execute([$name, $uid]);
                $_SESSION['user']['name'] = $name;
                flash_set('Profil diperbarui.');
                header('Location: profile.php'); exit;
            }
        } elseif ($act === 'password') {
            $old = (string) ($_POST['old'] ?? ''); $new = (string) ($_POST['new'] ?? ''); $new2 = (string) ($_POST['new2'] ?? '');
            if (!password_verify($old, $hash)) { $errors[] = 'Kata sandi lama salah.'; }
            elseif (strlen($new) < 8) { $errors[] = 'Kata sandi baru minimal 8 karakter.'; }
            elseif ($new !== $new2) { $errors[] = 'Konfirmasi kata sandi baru tidak sama.'; }
            else {
                $db->prepare('UPDATE users SET password_hash=? WHERE id=?')->execute([password_hash($new, PASSWORD_DEFAULT), $uid]);
                session_regenerate_id(true);
                flash_set('Kata sandi diganti.');
                header('Location: profile.php'); exit;
            }
        } elseif ($act === 'reset') {
            if (!password_verify((string) ($_POST['confirm_password'] ?? ''), $hash)) { $errors[] = 'Kata sandi salah. Pembukuan tidak direset.'; }
            else {
                try {
                    $db->beginTransaction();
                    foreach (['transactions', 'budgets', 'savings'] as $tb) {
                        $db->prepare("DELETE FROM $tb WHERE user_id=?")->execute([$uid]);
                    }
                    $db->commit();
                    flash_set('Pembukuan direset. Akunmu kembali kosong.');
                    header('Location: profile.php'); exit;
                } catch (PDOException $e) {
                    if ($db->inTransaction()) { $db->rollBack(); }
                    $errors[] = 'Gagal mereset pembukuan.';
                }
            }
        }
    }
}

$user = current_user();
$pageTitle = 'Pengaturan';
$active = 'profile';
include __DIR__ . '/../includes/header.php';
?>
<section class="mb-5 mt-2">
    <h1 class="font-display text-2xl font-extrabold tracking-tight sm:text-3xl">Pengaturan</h1>
    <p class="text-sm text-forest/60">Profil, akun, dan reset pembukuan.</p>
</section>

<?php if ($errors): ?>
    <div class="form-error mb-4 max-w-2xl" role="alert"><ul class="list-disc pl-4"><?php foreach ($errors as $er): ?><li><?= h($er) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<div class="grid max-w-5xl gap-4 md:grid-cols-2">
    <section class="card">
        <p class="card-title mb-4">Profil</p>
        <form method="post" class="space-y-4">
            <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
            <input type="hidden" name="action" value="name">
            <div><label class="label" for="pn">Nama</label><input id="pn" name="name" type="text" maxlength="80" value="<?= h($user['name']) ?>" class="input" required></div>
            <div><label class="label" for="pe">Email</label><input id="pe" type="email" value="<?= h($user['email']) ?>" class="input opacity-70" disabled></div>
            <button class="btn btn-primary">Simpan profil</button>
        </form>
    </section>

    <section class="card">
        <p class="card-title mb-4">Ganti kata sandi</p>
        <form method="post" class="space-y-4">
            <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
            <input type="hidden" name="action" value="password">
            <div><label class="label" for="po">Kata sandi lama</label><input id="po" name="old" type="password" autocomplete="current-password" class="input" required></div>
            <div><label class="label" for="pn1">Kata sandi baru</label><input id="pn1" name="new" type="password" autocomplete="new-password" class="input" placeholder="Minimal 8 karakter" required></div>
            <div><label class="label" for="pn2">Ulangi kata sandi baru</label><input id="pn2" name="new2" type="password" autocomplete="new-password" class="input" required></div>
            <button class="btn btn-primary">Ganti kata sandi</button>
        </form>
    </section>

    <section class="card md:col-span-2 border-expense/30">
        <p class="card-title mb-1 !text-expense">Zona berbahaya</p>
        <h2 class="font-display text-lg font-bold">Reset pembukuan</h2>
        <p class="mt-1 mb-4 max-w-xl text-sm text-forest/70">Menghapus semua transaksi, anggaran, dan target tabungan milik akunmu. Akun tetap ada. Tindakan ini tidak bisa dibatalkan.</p>
        <form method="post" class="flex max-w-xl flex-wrap items-end gap-3" onsubmit="return confirm('Hapus SEMUA data pembukuan? Tindakan ini tidak bisa dibatalkan.');">
            <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
            <input type="hidden" name="action" value="reset">
            <div class="min-w-[14rem] flex-1"><label class="label" for="pc">Konfirmasi kata sandi</label><input id="pc" name="confirm_password" type="password" autocomplete="current-password" class="input" required></div>
            <button class="btn btn-danger">Reset pembukuan</button>
        </form>
    </section>

    <section class="card md:col-span-2 flex flex-wrap items-center justify-between gap-3">
        <div><p class="font-display font-bold">Keluar dari akun</p><p class="text-sm text-forest/60">Sesi akan dihapus di perangkat ini.</p></div>
        <a href="<?= h(app_url('auth/logout.php')) ?>" class="btn btn-ghost"><?= icon('logout', 'w-4 h-4') ?> Keluar</a>
    </section>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
