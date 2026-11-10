<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();

$db = db();
$uid = (int) current_user()['id'];
$period = resolve_period($_GET['month'] ?? 'this');
$totals = period_totals($db, $uid, $period['start'], $period['end']);
$net = $totals['pemasukan'] - $totals['pengeluaran'] - $totals['tabungan'];
$st = $db->prepare(TX_SELECT . ' WHERE t.user_id=? AND t.tanggal BETWEEN ? AND ? ORDER BY t.tanggal ASC, t.id ASC');
$st->execute([$uid, $period['start'], $period['end']]);
$transactions = $st->fetchAll();

function pdf_text(string $value): string {
    $value = html_entity_decode($value, ENT_QUOTES, 'UTF-8');
    if (function_exists('iconv')) $value = iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $value);
    return str_replace(["\\", '(', ')', "\r", "\n"], ["\\\\", '\\(', '\\)', '', ' '], $value);
}
function pdf_money($n): string { return 'Rp ' . number_format((int)$n, 0, ',', '.'); }
function pdf_make(array $pages): string {
    $objects = [];
    $objects[] = '<< /Type /Catalog /Pages 2 0 R >>';
    $objects[] = '';
    $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
    $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';
    $kids = [];
    $fontRegular = 3; $fontBold = 4;
    foreach ($pages as $i => $stream) {
        $pageObj = 5 + $i * 2; $contentObj = $pageObj + 1; $kids[] = "$pageObj 0 R";
        $objects[] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 $fontRegular 0 R /F2 $fontBold 0 R >> >> /Contents $contentObj 0 R >>";
        $objects[] = '<< /Length ' . strlen($stream) . " >>\nstream\n" . $stream . "\nendstream";
    }
    $objects[1] = '<< /Type /Pages /Kids [' . implode(' ', $kids) . '] /Count ' . count($pages) . ' >>';
    $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n"; $offsets = [0];
    foreach ($objects as $i => $obj) { $offsets[] = strlen($pdf); $pdf .= ($i+1) . " 0 obj\n" . $obj . "\nendobj\n"; }
    $xref = strlen($pdf); $pdf .= "xref\n0 " . (count($objects)+1) . "\n0000000000 65535 f \n";
    for ($i=1; $i<=count($objects); $i++) $pdf .= sprintf('%010d 00000 n ', $offsets[$i]) . "\n";
    $pdf .= 'trailer << /Size ' . (count($objects)+1) . " /Root 1 0 R >>\nstartxref\n$xref\n%%EOF";
    return $pdf;
}
function pdf_line_text(string $text, float $x, float $y, int $size=9, bool $bold=false, array $color=[0.15,0.19,0.16]): string {
    return sprintf("q %.3f %.3f %.3f rg BT /F%d %d Tf 1 0 0 1 %.2f %.2f Tm (%s) Tj ET Q\n", $color[0],$color[1],$color[2],$bold?2:1,$size,$x,$y,pdf_text($text));
}
function pdf_rect(float $x,float $y,float $w,float $h,array $color): string {
    return sprintf("q %.3f %.3f %.3f rg %.2f %.2f %.2f %.2f re f Q\n",$color[0],$color[1],$color[2],$x,$y,$w,$h);
}
function pdf_rule(float $x1,float $y1,float $x2,float $y2,array $color=[0.36,0.44,0.35],float $width=1): string {
    return sprintf("q %.3f %.3f %.3f RG %.2f w %.2f %.2f m %.2f %.2f l S Q\n",$color[0],$color[1],$color[2],$width,$x1,$y1,$x2,$y2);
}

$printed = date('d-m-Y H:i');
$pages = []; $stream = ''; $pageNo = 0;
$startPage = function() use (&$stream,&$pageNo,$period,$printed) {
    $pageNo++; $stream = '';
    $stream .= pdf_line_text('SakuBijak',42,802,18,true,[0.22,0.32,0.25]);
    $stream .= pdf_line_text('LAPORAN KEUANGAN',42,772,15,true);
    $stream .= pdf_line_text('Nama pengguna: ' . current_user()['name'],42,749,9);
    $stream .= pdf_line_text('Periode: ' . $period['label'],42,734,9);
    $stream .= pdf_line_text('Tanggal cetak: ' . $printed,410,802,8,false,[0.35,0.39,0.35]);
    $stream .= pdf_rule(42,720,553,720,[0.36,0.44,0.35],2);
};
$finishPage = function() use (&$stream,&$pages,$pageNo,$printed) {
    $stream .= pdf_rule(42,45,553,45,[0.36,0.44,0.35],0.6);
    $stream .= pdf_line_text('Dicetak via SakuBijak',42,29,8,false,[0.62,0.66,0.62]);
    $stream .= pdf_line_text('Halaman ' . $pageNo,505,29,8,false,[0.45,0.48,0.45]);
    $pages[] = $stream;
};
$startPage();
$cardY = 650; $cardW = 162; $gap = 10;
$cards = [ ['Total Pemasukan',pdf_money($totals['pemasukan'])], ['Total Pengeluaran',pdf_money($totals['pengeluaran'])], ['Net Saldo',pdf_money($net)] ];
foreach ($cards as $i=>$card) {
    $x = 42 + $i*($cardW+$gap);
    $stream .= pdf_rect($x,$cardY,$cardW,54,[0.95,0.965,0.94]);
    $stream .= pdf_line_text($card[0],$x+10,$cardY+35,8,false,[0.32,0.38,0.33]);
    $stream .= pdf_line_text($card[1],$x+10,$cardY+15,11,true,[0.20,0.29,0.22]);
}
$stream .= pdf_line_text('Rincian Transaksi',42,622,11,true);
$headers = ['Tanggal','Deskripsi','Kategori','Jenis','Nominal'];
$xs = [42,112,300,395,470];
$stream .= pdf_rect(42,594,511,22,[0.36,0.44,0.35]);
foreach ($headers as $i=>$h) $stream .= pdf_line_text($h,$xs[$i]+4,602,8,true,[1,1,1]);
$y = 594; $rowH = 22; $count=0;
foreach ($transactions as $tx) {
    if ($y-$rowH < 58) { $finishPage(); $startPage(); $stream .= pdf_line_text('Rincian Transaksi (lanjutan)',42,690,11,true); $stream .= pdf_rect(42,662,511,22,[0.36,0.44,0.35]); foreach ($headers as $i=>$h) $stream .= pdf_line_text($h,$xs[$i]+4,670,8,true,[1,1,1]); $y=662; }
    $y -= $rowH; $count++;
    if ($count % 2 === 0) $stream .= pdf_rect(42,$y,511,$rowH,[0.955,0.965,0.95]);
    $type = ['pemasukan'=>'Pemasukan','pengeluaran'=>'Pengeluaran','tabungan'=>'Tabungan'][$tx['tipe']] ?? $tx['tipe'];
    $desc = mb_strimwidth((string)$tx['deskripsi'],0,30,'…','UTF-8');
    $cat = mb_strimwidth((string)$tx['kategori'],0,14,'…','UTF-8');
    $vals = [date('d/m/Y',strtotime($tx['tanggal'])),$desc,$cat,$type,pdf_money($tx['nominal'])];
    foreach ($vals as $i=>$v) $stream .= pdf_line_text($v,$xs[$i]+4,$y+7,7.5,false,[0.16,0.19,0.17]);
}
if (!$transactions) $stream .= pdf_line_text('Tidak ada transaksi pada periode ini.',48,565,9,false,[0.4,0.43,0.4]);
$finishPage();
$pdf = pdf_make($pages);
$filename = 'Laporan-Keuangan-SakuBijak-' . $period['month'] . '.pdf';
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($pdf));
echo $pdf;
exit;
