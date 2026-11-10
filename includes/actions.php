<?php
// Memproses aksi formulir (tambah/ubah transaksi, setor tabungan, anggaran, dll).
// Di-include oleh header.php. Formulir dikirim sebagai POST biasa ke halaman yang sedang dibuka.
// Sukses  -> simpan pesan, lalu redirect ke halaman yang sama.
// Gagal   -> $formState diisi agar footer membuka modal lagi dengan isian yang sama.
$formState = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['do']) && current_user()) {
    $uid  = (int) current_user()['id'];
    $db   = db();
    $do   = (string) $_POST['do'];
    $back = $_SERVER['REQUEST_URI'] ?? app_url('pages/dashboard.php');

    $done = function (string $msg, string $level = 'success') use ($back): void {
        flash_set($msg, $level);
        header('Location: ' . $back);
        exit;
    };

    if (!csrf_check($_POST['csrf'] ?? null)) {
        $done('Token keamanan tidak valid. Muat ulang halaman lalu coba lagi.', 'error');
    }

    switch ($do) {

        case 'tx_create':
        case 'tx_update':
            $isUpdate = ($do === 'tx_update');
            $verb = $isUpdate ? 'diperbarui' : 'disimpan';
            $err = null;
            $tipe    = (string) ($_POST['tipe'] ?? '');
            $nominal = parse_amount($_POST['nominal'] ?? '');
            $catId   = (int) ($_POST['category_id'] ?? 0);
            $tanggal = (string) ($_POST['tanggal'] ?? '');
            $desk    = trim((string) ($_POST['deskripsi'] ?? ''));
            $dt  = DateTime::createFromFormat('Y-m-d', $tanggal);
            $cat = find_category($catId, $tipe);

            if (!in_array($tipe, ['pemasukan', 'pengeluaran'], true)) { $err = 'Pilih jenis transaksi.'; }
            elseif ($nominal <= 0) { $err = 'Nominal harus lebih dari 0.'; }
            elseif ($nominal > 9999999999) { $err = 'Nominal terlalu besar.'; }
            elseif (!$cat) { $err = 'Pilih kategori yang sesuai dengan jenis transaksi.'; }
            elseif (!$dt || $dt->format('Y-m-d') !== $tanggal) { $err = 'Tanggal tidak valid.'; }
            elseif (mb_strlen($desk) > 100) { $err = 'Deskripsi maksimal 100 karakter.'; }

            if ($err === null) {
                if ($desk === '') { $desk = $cat['nama']; }
                try {
                    if ($isUpdate) {
                        // Hanya pemasukan/pengeluaran milik pengguna ini yang boleh diedit
                        $own = $db->prepare("SELECT id FROM transactions WHERE id=? AND user_id=? AND tipe IN ('pemasukan','pengeluaran')");
                        $own->execute([(int) ($_POST['id'] ?? 0), $uid]);
                        if (!$own->fetch()) {
                            $err = 'Transaksi tidak ditemukan atau tidak bisa diedit.';
                        } else {
                            $db->prepare('UPDATE transactions SET tipe=?, category_id=?, nominal=?, deskripsi=?, tanggal=? WHERE id=? AND user_id=?')
                               ->execute([$tipe, $catId, $nominal, $desk, $tanggal, (int) $_POST['id'], $uid]);
                        }
                    } else {
                        $db->prepare('INSERT INTO transactions (user_id,category_id,tipe,nominal,deskripsi,tanggal) VALUES (?,?,?,?,?,?)')
                           ->execute([$uid, $catId, $tipe, $nominal, $desk, $tanggal]);
                    }
                } catch (PDOException $e) {
                    $err = 'Gagal menyimpan ke database. Coba lagi.';
                }
            }
            if ($err !== null) { $formState = ['modal' => $isUpdate ? 'modal-edit' : 'modal-quick', 'error' => $err, 'old' => $_POST]; break; }

            $msg = "Transaksi {$verb}.";
            $level = 'success';
            if (substr($tanggal, 0, 7) !== date('Y-m')) {
                $msg = "Transaksi {$verb} di bulan " . resolve_period(substr($tanggal, 0, 7))['label'] . '.';
            }
            if ($tipe === 'pengeluaran') {
                $b = budgets_usage($db, $uid, resolve_period(substr($tanggal, 0, 7)), $catId)[0] ?? null;
                if ($b && $b['status'] !== 'ok') {
                    $level = 'warning';
                    $msg .= $b['over'] > 0
                        ? " Peringatan: anggaran {$cat['nama']} terlampaui " . rupiah($b['over']) . '.'
                        : " Peringatan: anggaran {$cat['nama']} sudah " . (int) round($b['pct']) . '% terpakai.';
                }
            }
            $done($msg, $level);
            break;

        case 'tx_delete':
            $st = $db->prepare('DELETE FROM transactions WHERE id=? AND user_id=?');
            $st->execute([(int) ($_POST['id'] ?? 0), $uid]);
            $done($st->rowCount() ? 'Transaksi dihapus.' : 'Transaksi tidak ditemukan.', $st->rowCount() ? 'success' : 'error');
            break;

        case 'goal_create':
            $err = null;
            $nama = trim((string) ($_POST['nama'] ?? ''));
            $target = parse_amount($_POST['target'] ?? '');
            if ($nama === '' || mb_strlen($nama) > 100) { $err = 'Nama target wajib diisi (maks. 100 karakter).'; }
            elseif ($target <= 0 || $target > 9999999999) { $err = 'Nominal target harus lebih dari 0.'; }
            if ($err !== null) { $formState = ['modal' => 'modal-goal', 'error' => $err, 'old' => $_POST]; break; }
            $db->prepare('INSERT INTO savings (user_id,nama_target,target_nominal) VALUES (?,?,?)')->execute([$uid, $nama, $target]);
            $done('Target tabungan dibuat.');
            break;

        case 'goal_deposit':
            $err = null;
            $id = (int) ($_POST['id'] ?? 0);
            $nominal = parse_amount($_POST['nominal'] ?? '');
            $saveCat = array_key_first(categories()['tabungan']);
            if ($nominal <= 0) {
                $err = 'Nominal setoran harus lebih dari 0.';
            } else {
                try {
                    $db->beginTransaction();
                    // Kunci baris target agar dua setoran bersamaan tidak melewati validasi
                    $st = $db->prepare('SELECT id,nama_target,target_nominal FROM savings WHERE id=? AND user_id=? FOR UPDATE');
                    $st->execute([$id, $uid]);
                    $goal = $st->fetch();
                    if (!$goal) {
                        $err = 'Target tidak ditemukan.';
                    } else {
                        $sv = $db->prepare("SELECT COALESCE(SUM(nominal),0) FROM transactions WHERE savings_id=? AND tipe='tabungan'");
                        $sv->execute([$goal['id']]);
                        $remaining = (int) $goal['target_nominal'] - (int) $sv->fetchColumn();
                        $balance = current_balance($db, $uid);
                        if ($nominal > $balance) {
                            $err = 'Saldo bulan ini tidak cukup. Saldo tersedia ' . rupiah($balance) . '.';
                        } elseif ($nominal > $remaining) {
                            $err = $remaining <= 0 ? 'Target ini sudah tercapai.' : 'Melebihi sisa target. Maksimal setoran ' . rupiah($remaining) . '.';
                        } else {
                            $db->prepare('INSERT INTO transactions (user_id,category_id,savings_id,tipe,nominal,deskripsi,tanggal) VALUES (?,?,?,?,?,?,?)')
                               ->execute([$uid, $saveCat, $goal['id'], 'tabungan', $nominal, 'Setoran: ' . $goal['nama_target'], date('Y-m-d')]);
                        }
                    }
                    if ($err === null) { $db->commit(); } else { $db->rollBack(); }
                } catch (PDOException $e) {
                    if ($db->inTransaction()) { $db->rollBack(); }
                    $err = 'Gagal menyimpan setoran. Coba lagi.';
                }
            }
            if ($err !== null) { $formState = ['modal' => 'modal-deposit', 'error' => $err, 'old' => $_POST]; break; }
            $done('Setoran ' . rupiah($nominal) . ' berhasil.');
            break;

        case 'goal_delete':
            // Setoran ikut terhapus (ON DELETE CASCADE) sehingga dana kembali ke saldo
            $st = $db->prepare('DELETE FROM savings WHERE id=? AND user_id=?');
            $st->execute([(int) ($_POST['id'] ?? 0), $uid]);
            $done($st->rowCount() ? 'Target dihapus. Dana setoran kembali ke saldo.' : 'Target tidak ditemukan.', $st->rowCount() ? 'success' : 'error');
            break;

        case 'budget_save':
            $err = null;
            $catId   = (int) ($_POST['category_id'] ?? 0);
            $batas   = parse_amount($_POST['batas_anggaran'] ?? '');
            $jenis   = (string) ($_POST['jenis_anggaran'] ?? 'flexible');
            $periode = (string) ($_POST['periode'] ?? '');
            $cat = find_category($catId, 'pengeluaran');
            if (!$cat) { $err = 'Pilih kategori pengeluaran.'; }
            elseif (!in_array($jenis, ['fixed', 'flexible'], true)) { $err = 'Pilih jenis anggaran yang valid.'; }
            elseif (!preg_match('/^(\d{4})-(0[1-9]|1[0-2])$/', $periode)) { $err = 'Pilih bulan anggaran.'; }
            elseif ($batas <= 0 || $batas > 9999999999) { $err = 'Batas anggaran harus lebih dari 0.'; }
            if ($err !== null) { $formState = ['modal' => 'modal-budget', 'error' => $err, 'old' => $_POST]; break; }
            $p = resolve_period($periode);

            // Validasi agar total seluruh alokasi anggaran tidak melebihi pemasukan bulan tersebut.
            // Anggaran kategori yang sedang diedit dikecualikan agar nilainya tidak dihitung dua kali.
            $incomeStmt = $db->prepare("SELECT COALESCE(SUM(nominal),0) FROM transactions
                WHERE user_id=? AND tipe='pemasukan' AND tanggal BETWEEN ? AND ?");
            $incomeStmt->execute([$uid, $p['start'], $p['end']]);
            $monthlyIncome = (int) $incomeStmt->fetchColumn();

            $otherBudgetStmt = $db->prepare('SELECT COALESCE(SUM(batas_anggaran),0) FROM budgets
                WHERE user_id=? AND bulan=? AND tahun=? AND category_id<>?');
            $otherBudgetStmt->execute([$uid, $p['bulan'], $p['tahun'], $catId]);
            $otherBudgets = (int) $otherBudgetStmt->fetchColumn();
            $maxAllowed = max(0, $monthlyIncome - $otherBudgets);

            if ($monthlyIncome > 0 && ($otherBudgets + $batas) > $monthlyIncome) {
                $err = 'Total anggaran tidak boleh melebihi pemasukan ' . rupiah($monthlyIncome) .
                    '. Sisa batas anggaran untuk kategori ini hanya ' . rupiah($maxAllowed) .
                    ' (setelah anggaran lain sebesar ' . rupiah($otherBudgets) . ').';
                $formState = ['modal' => 'modal-budget', 'error' => $err, 'old' => $_POST];
                break;
            }

            $db->prepare('INSERT INTO budgets (user_id,category_id,batas_anggaran,bulan,tahun,jenis_anggaran) VALUES (?,?,?,?,?,?)
                          ON DUPLICATE KEY UPDATE batas_anggaran=VALUES(batas_anggaran), jenis_anggaran=VALUES(jenis_anggaran)')
               ->execute([$uid, $catId, $batas, $p['bulan'], $p['tahun'], $jenis]);
            $done("Anggaran {$cat['nama']} untuk {$p['label']} disimpan.");
            break;

        case 'budget_delete':
            $st = $db->prepare('DELETE FROM budgets WHERE id=? AND user_id=?');
            $st->execute([(int) ($_POST['id'] ?? 0), $uid]);
            $done($st->rowCount() ? 'Anggaran dihapus.' : 'Anggaran tidak ditemukan.', $st->rowCount() ? 'success' : 'error');
            break;

        case 'reminder_save':
            $id = (int) ($_POST['id'] ?? 0);
            $type = (string) ($_POST['reminder_type'] ?? '');
            $title = trim((string) ($_POST['title'] ?? ''));
            $date = (string) ($_POST['due_date'] ?? '');
            $time = (string) ($_POST['reminder_time'] ?? '20:00');
            $amount = parse_amount($_POST['amount'] ?? '');
            $threshold = (int) ($_POST['threshold_percent'] ?? 80);
            $dateObj = $date !== '' ? DateTime::createFromFormat('Y-m-d', $date) : null;
            $validDate = $date === '' || ($dateObj && $dateObj->format('Y-m-d') === $date);
            if (!in_array($type, ['daily_expense','bill_payment','budget_limit'], true)) { $err = 'Jenis pengingat tidak valid.'; }
            elseif ($title === '' || mb_strlen($title) > 100) { $err = 'Nama pengingat wajib diisi (maksimal 100 karakter).'; }
            elseif (!preg_match('/^(?:[01]\\d|2[0-3]):[0-5]\\d$/', $time)) { $err = 'Jam pengingat tidak valid.'; }
            elseif (!$validDate || ($type === 'bill_payment' && $date === '')) { $err = 'Tanggal jatuh tempo tidak valid.'; }
            elseif ($type === 'budget_limit' && ($threshold < 1 || $threshold > 100)) { $err = 'Batas persentase harus 1–100.'; }
            else {
                if ($type === 'daily_expense') { $date = null; $amount = null; $threshold = 80; }
                elseif ($type === 'bill_payment') { $threshold = 80; }
                else { $date = null; $amount = null; }
                if ($id > 0) {
                    $st = $db->prepare('UPDATE reminders SET reminder_type=?, title=?, due_date=?, reminder_time=?, amount=?, threshold_percent=?, is_active=1 WHERE id=? AND user_id=?');
                    $st->execute([$type,$title,$date,$time,$amount,$threshold,$id,$uid]);
                } else {
                    $st = $db->prepare('INSERT INTO reminders (user_id,reminder_type,title,due_date,reminder_time,amount,threshold_percent) VALUES (?,?,?,?,?,?,?)');
                    $st->execute([$uid,$type,$title,$date,$time,$amount,$threshold]);
                }
                $done('Pengingat berhasil disimpan.');
            }
            if ($err !== null) { $formState = ['modal' => 'modal-reminder', 'error' => $err, 'old' => $_POST]; break; }
            break;

        case 'reminder_delete':
            $st = $db->prepare('DELETE FROM reminders WHERE id=? AND user_id=?');
            $st->execute([(int) ($_POST['id'] ?? 0), $uid]);
            $done($st->rowCount() ? 'Pengingat dihapus.' : 'Pengingat tidak ditemukan.', $st->rowCount() ? 'success' : 'error');
            break;
    }
}
