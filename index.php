<?php
require_once __DIR__ . '/includes/auth.php';

function lp_icon(string $name): string
{
    $paths = [
        'swap'  => '<path d="M8 3 4 7l4 4"/><path d="M4 7h16"/><path d="m16 21 4-4-4-4"/><path d="M20 17H4"/>',
        'piggy' => '<path d="M19 5c-1.5 0-2.8 1.4-3 2-3.5-1.5-11-.3-11 5 0 1.8 0 3 2 4.5V20h4v-2h3v2h4v-4c1-.5 1.7-1 2-2h2v-4h-2c0-1-.5-1.5-1-2V5z"/><path d="M2 9v1c0 1.1.9 2 2 2h1"/><path d="M16 11h.01"/>',
    ];
    return '<svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $paths[$name] . '</svg>';
}

$pageTitle = 'Keuangan Lebih Terkendali';
include __DIR__ . '/includes/head.php';
?>
<style>
/* ===== Landing SakuBijak: mobile-first, tidak bergantung pada build Tailwind ===== */
html, body { overflow-x: hidden; }
.lp-wrap { width: 100%; max-width: 72rem; margin: 0 auto; padding: 0 1.25rem; }
.lp-sec { padding: 3rem 0; }
.lp-h2 { font-size: clamp(1.75rem, 6vw, 3rem); line-height: 1.1; font-weight: 800; letter-spacing: -.02em; }
.lp-lead { font-size: 1.0625rem; line-height: 1.65; }
.lp-ico { display: inline-flex; flex: none; align-items: center; justify-content: center; width: 2.5rem; height: 2.5rem; border-radius: .875rem; }
.lp-ico svg { width: 1.25rem; height: 1.25rem; }
.lp-ico.lg { width: 3rem; height: 3rem; border-radius: 1rem; }
.lp-ico.lg svg { width: 1.5rem; height: 1.5rem; }
.lp-card { border: 1px solid rgba(0,0,0,.09); background: #fff; border-radius: 1.5rem; padding: 1.5rem; min-width: 0; }
.lp-card h3 { margin-top: 1.1rem; font-size: 1.375rem; font-weight: 800; }
.lp-card p.lp-sub { margin-top: .5rem; line-height: 1.6; }
.lp-row { display: flex; align-items: center; justify-content: space-between; gap: .75rem; min-width: 0; }
.lp-row > span:first-child { min-width: 0; overflow-wrap: anywhere; }
.lp-row b { white-space: nowrap; }
.lp-btn { display: inline-flex; align-items: center; justify-content: center; min-height: 48px; padding: 0 1.75rem; border-radius: 999px; font-weight: 700; text-align: center; }
.lp-btn:focus-visible, .lp-focus:focus-visible { outline: 3px solid var(--income); outline-offset: 3px; }

/* Navbar */
.lp-navwrap { position: sticky; top: 0; z-index: 50; padding: .6rem .75rem 0; }
.lp-nav { position: relative; max-width: 72rem; margin: 0 auto; display: flex; align-items: center; justify-content: space-between; gap: .5rem;
    padding: .4rem .4rem .4rem .75rem; border: 1px solid rgba(0,0,0,.08); border-radius: 999px; background: rgba(255,255,255,.88);
    -webkit-backdrop-filter: blur(14px); backdrop-filter: blur(14px); box-shadow: 0 1px 3px rgba(0,0,0,.06); }
.lp-brand { display: flex; align-items: center; gap: .5rem; font-size: 1.05rem; font-weight: 800; border-radius: 999px; }
.lp-brand img { width: 2.25rem; height: 2.25rem; object-fit: contain; border-radius: .75rem; }
.lp-links { display: none; gap: .25rem; font-size: .875rem; font-weight: 600; }
.lp-links a { padding: .5rem 1rem; border-radius: 999px; }
.lp-links a:hover, .lp-menu-panel a:hover { background: #e8f1ea; }
.lp-navr { display: flex; align-items: center; gap: .25rem; }
.lp-navr .lp-btn { min-height: 40px; padding: 0 1.1rem; font-size: .875rem; }
.lp-login { display: none; padding: .5rem 1rem; font-size: .875rem; font-weight: 600; border-radius: 999px; }
.lp-menu summary { list-style: none; cursor: pointer; display: flex; align-items: center; justify-content: center; width: 40px; height: 40px; border-radius: 999px; }
.lp-menu summary::-webkit-details-marker { display: none; }
.lp-menu summary svg { width: 22px; height: 22px; }
.lp-menu-panel { position: absolute; left: 0; right: 0; top: calc(100% + .5rem); display: grid; padding: .5rem; border: 1px solid rgba(0,0,0,.08);
    border-radius: 1.25rem; background: #fff; box-shadow: 0 12px 30px rgba(0,0,0,.12); }
.lp-menu-panel a { padding: .85rem 1rem; border-radius: .75rem; font-weight: 600; }

/* Hero */
.lp-hero { position: relative; overflow: hidden;
    background: radial-gradient(60% 45% at 90% 0%, rgba(34,197,94,.14), transparent 70%); }
.lp-hero-grid { display: grid; gap: 2.75rem; padding-top: 2.5rem; padding-bottom: 3.5rem; }
.lp-pill { display: inline-flex; align-items: center; gap: .5rem; max-width: 100%; padding: .3rem .9rem .3rem .35rem; border: 1px solid rgba(0,0,0,.09);
    border-radius: 999px; background: #fff; font-size: .8125rem; font-weight: 600; }
.lp-pill i { font-style: normal; padding: .1rem .6rem; border-radius: 999px; background: var(--income); color: #fff; font-size: .75rem; font-weight: 700; }
.lp-h1 { margin-top: 1.25rem; font-size: clamp(2.25rem, 9.5vw, 4.5rem); line-height: 1.06; font-weight: 800; letter-spacing: -.025em; }
.lp-cta { display: flex; flex-direction: column; gap: .75rem; margin-top: 1.75rem; }
.lp-cta .lp-btn { width: 100%; }
.lp-checks { display: flex; flex-wrap: wrap; gap: .5rem 1.25rem; margin-top: 1.5rem; font-size: .875rem; font-weight: 500; }
.lp-checks span::before { content: ""; display: inline-block; width: 6px; height: 6px; margin-right: .5rem; border-radius: 50%; background: var(--income); vertical-align: middle; }
.lp-mock { position: relative; width: 100%; max-width: 28rem; margin: 0 auto; }
.lp-mock-card { padding: 1.25rem; border: 1px solid rgba(0,0,0,.08); border-radius: 1.75rem; background: #fff; box-shadow: 0 25px 50px -12px rgba(0,0,0,.15); }
.lp-amount { margin-top: .6rem; font-size: clamp(1.9rem, 9vw, 2.75rem); font-weight: 800; letter-spacing: -.02em; line-height: 1.1; }
.lp-bar { display: flex; height: 12px; overflow: hidden; border-radius: 999px; background: rgba(0,0,0,.07); }
.lp-bar > span { display: block; height: 100%; transform-origin: left; animation: lp-fill 1.1s .3s cubic-bezier(.2,.8,.2,1) both; }
.lp-dot { display: inline-block; width: 10px; height: 10px; margin-right: .5rem; border-radius: 50%; background: currentColor; }
.lp-legend { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 1rem; font-size: .8125rem; }
.lp-legend b { display: block; margin-top: .2rem; font-size: .9375rem; }
.lp-tx { display: grid; gap: .75rem; margin-top: 1.25rem; padding-top: 1.1rem; border-top: 1px solid rgba(0,0,0,.08); font-size: .875rem; }
.lp-tx .lp-row > span:first-child { display: flex; align-items: center; gap: .75rem; font-weight: 600; }
.lp-float { display: none; position: absolute; padding: .85rem 1rem; border-radius: 1rem; box-shadow: 0 15px 30px rgba(0,0,0,.15); animation: lp-float 6s ease-in-out infinite; }
@keyframes lp-fill { from { transform: scaleX(0); } to { transform: scaleX(1); } }
@keyframes lp-float { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-8px); } }

/* Bento */
.lp-bento { display: grid; grid-template-columns: minmax(0,1fr); gap: 1rem; margin-top: 2rem; }
.lp-dark { color: #fff; border-radius: 1.5rem; padding: 1.5rem; }
.lp-dark .lp-track { height: 8px; border-radius: 999px; background: rgba(255,255,255,.18); overflow: hidden; }
.lp-dark .lp-track i { display: block; height: 100%; border-radius: 999px; }
.lp-list { margin-top: 1.25rem; border: 1px solid rgba(0,0,0,.08); border-radius: 1rem; font-size: .875rem; }
.lp-list .lp-row { padding: .8rem 1rem; }
.lp-list .lp-row + .lp-row { border-top: 1px solid rgba(0,0,0,.08); }
.lp-tags { display: flex; flex-wrap: wrap; gap: .5rem; margin-top: 1.25rem; font-size: .875rem; font-weight: 600; }
.lp-tags span { padding: .5rem .9rem; border-radius: 999px; }
.lp-goal { margin-top: 1.25rem; padding: 1rem; border-radius: 1rem; }
.lp-goal .lp-row { flex-wrap: wrap; gap: .25rem .75rem; font-size: .875rem; margin-bottom: .75rem; }

/* Steps */
.lp-steps { display: grid; grid-template-columns: minmax(0,1fr); gap: 1rem; margin-top: 2rem; list-style: none; padding: 0; }
.lp-steps li { padding: 1.5rem; border-radius: 1.5rem; }
.lp-steps h3 { margin-top: 1rem; font-size: 1.2rem; font-weight: 800; }
.lp-steps p { margin-top: .5rem; line-height: 1.6; }
.lp-num { display: inline-flex; align-items: center; justify-content: center; width: 2.5rem; height: 2.5rem; border-radius: 50%; color: #fff; font-weight: 800; background: currentColor; position: relative; }
.lp-num b { position: absolute; color: #fff; }

/* CTA */
.lp-cta-box { position: relative; overflow: hidden; text-align: center; padding: 3rem 1.25rem; border-radius: 1.75rem; }
.lp-cta-box > * { position: relative; }
.lp-cta-box .glow { position: absolute; right: -6rem; top: -6rem; width: 18rem; height: 18rem; border-radius: 50%; background: var(--income); opacity: .3; filter: blur(60px); }
.lp-cta-box img { width: 4rem; height: 4rem; margin: 0 auto; object-fit: contain; border-radius: 1rem; }
.lp-white-btn { background: #fff; color: #14352a; margin-top: 1.75rem; width: 100%; }

/* Footer */
.lp-foot { display: flex; flex-direction: column; align-items: center; gap: 1rem; padding-top: 1.5rem; padding-bottom: 2.5rem; text-align: center; }

/* ===== Tablet ===== */
@media (min-width: 640px) {
    .lp-cta { flex-direction: row; }
    .lp-cta .lp-btn { width: auto; }
    .lp-float { display: block; }
    .lp-mock { padding: 1.5rem 0; }
    .lp-mock-card { padding: 1.75rem; }
    .lp-login { display: inline-flex; }
    .lp-white-btn { width: auto; }
    .lp-foot { flex-direction: row; justify-content: space-between; text-align: left; }
    .lp-card, .lp-dark { padding: 1.75rem; }
}
@media (min-width: 768px) {
    .lp-sec { padding: 4rem 0; }
    .lp-links { display: flex; }
    .lp-menu { display: none; }
    .lp-bento { grid-template-columns: repeat(2, minmax(0,1fr)); }
    .lp-c4, .lp-c3w { grid-column: span 2; }
    .lp-steps { grid-template-columns: repeat(3, minmax(0,1fr)); }
    .lp-cta-box { padding: 4.5rem 3rem; border-radius: 2rem; }
}
/* ===== Desktop ===== */
@media (min-width: 1024px) {
    .lp-sec { padding: 5rem 0; }
    .lp-hero-grid { grid-template-columns: repeat(2, minmax(0,1fr)); align-items: center; gap: 3rem; padding-top: 3.5rem; padding-bottom: 3rem; }
}
#fitur.lp-sec { padding-top: 2rem; }
@media (prefers-reduced-motion: reduce) { .lp-bar > span, .lp-float { animation: none; } }
</style>
<body class="bg-paper text-forest font-sans antialiased">

<!-- NAVBAR -->
<header class="lp-navwrap">
    <nav class="lp-nav" aria-label="Navigasi utama">
        <a href="<?= h(app_url('index.php')) ?>" class="lp-brand lp-focus">
            <img src="<?= h(app_url('assets/img/sakubijak-icon.jpg')) ?>" alt="SakuBijak">
            <span class="font-display">Saku<span class="text-income">Bijak</span></span>
        </a>

        <div class="lp-links">
            <a href="#beranda" class="lp-focus">Beranda</a>
            <a href="#fitur" class="lp-focus">Fitur</a>
            <a href="#cara-kerja" class="lp-focus">Cara kerja</a>
            <a href="#tentang" class="lp-focus">Tentang</a>
        </div>

        <div class="lp-navr">
            <a href="<?= h(app_url('auth/login.php')) ?>" class="lp-login lp-focus">Masuk</a>
            <a href="<?= h(app_url('auth/register.php')) ?>" class="btn btn-primary lp-btn">Daftar</a>

            <details class="lp-menu" id="lpMenu">
                <summary aria-label="Buka menu">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
                </summary>
                <div class="lp-menu-panel">
                    <a href="#beranda">Beranda</a>
                    <a href="#fitur">Fitur</a>
                    <a href="#cara-kerja">Cara kerja</a>
                    <a href="#tentang">Tentang</a>
                    <a href="<?= h(app_url('auth/login.php')) ?>">Masuk</a>
                </div>
            </details>
        </div>
    </nav>
</header>

<!-- HERO -->
<section id="beranda" class="lp-hero">
    <div class="lp-wrap lp-hero-grid">

        <div>
            <a href="#fitur" class="lp-pill lp-focus"><i>Baru</i> Kelola Keuangan Tanpa Drama Tanggal Tua</a>

            <h1 class="lp-h1 font-display">
                Atur keuangan lebih bijak,
                <span class="text-income">jalani hari tanpa cemas.</span>
            </h1>

            <p class="lp-lead mt-5 max-w-md text-forest/70">
                SakuBijak membantumu memetakan pengeluaran wajib dan sisa anggaran secara transparan, membuatmu selalu siap menghadapi akhir bulan.
            </p>

            <div class="lp-cta">
                <a href="<?= h(app_url('auth/register.php')) ?>" class="btn btn-primary lp-btn">Mulai sekarang</a>
                <a href="#cara-kerja" class="btn btn-ghost lp-btn">Lihat cara kerja</a>
            </div>

            <div class="lp-checks text-forest/70">
                <span>Gratis dipakai</span>
                <span>Tanpa kartu kredit</span>
                <span>Siap dalam 2 menit</span>
            </div>
        </div>

        <!-- Ilustrasi: saldo bebas -->
        <div class="lp-mock">
            <div class="lp-mock-card">
                <div class="lp-row">
                    <span class="text-sm font-semibold text-forest/60">Saldo bebas bulan ini</span>
                    <span class="rounded-full bg-sage px-3 py-1 text-xs font-bold" style="white-space:nowrap">Contoh</span>
                </div>

                <p class="lp-amount font-display">Rp1.500.000</p>

                <div class="lp-bar mt-5" role="img" aria-label="Rp2.700.000 kebutuhan wajib dan Rp1.500.000 saldo bebas dari total Rp4.200.000">
                    <span class="text-forest" style="width:64%;background:currentColor"></span>
                    <span style="width:36%;background:var(--income)"></span>
                </div>

                <div class="lp-legend">
                    <div>
                        <span class="text-forest/60"><i class="lp-dot text-forest"></i>Kebutuhan wajib</span>
                        <b>Rp2.700.000</b>
                    </div>
                    <div>
                        <span class="text-forest/60"><i class="lp-dot text-income"></i>Saldo bebas</span>
                        <b class="text-income">Rp1.500.000</b>
                    </div>
                </div>

                <div class="lp-tx">
                    <div class="lp-row">
                        <span><span class="lp-ico bg-sage text-income"><?= lp_icon('swap') ?></span>Gaji bulanan</span>
                        <b class="text-income">+Rp4.200.000</b>
                    </div>
                    <div class="lp-row">
                        <span><span class="lp-ico bg-red-50 text-expense"><?= icon('wallet', 'w-6 h-6') ?></span>Sewa kos</span>
                        <b class="text-expense">−Rp1.200.000</b>
                    </div>
                </div>
            </div>

            <div class="lp-float bg-white" style="right:-.5rem;top:-.25rem;border:1px solid rgba(0,0,0,.08)">
                <div class="lp-row" style="gap:.75rem">
                    <span class="lp-ico bg-sage text-income"><?= lp_icon('piggy') ?></span>
                    <span><span class="text-xs text-forest/60" style="display:block">Dana darurat</span><b class="text-sm">72% tercapai</b></span>
                </div>
            </div>

            <div class="lp-float deco-card" style="left:-.5rem;bottom:-.25rem;color:#fff;animation-delay:-3s">
                <div class="lp-row" style="gap:.75rem">
                    <span class="lp-ico" style="background:rgba(255,255,255,.18)"><?= icon('bell', 'w-6 h-6') ?></span>
                    <span><span class="text-xs" style="display:block;opacity:.7">Pengingat</span><b class="text-sm">Listrik jatuh tempo besok</b></span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FITUR -->
<section id="fitur" class="lp-sec">
    <div class="lp-wrap">
        <h2 class="lp-h2 font-display max-w-2xl">Satu tempat untuk seluruh uangmu.</h2>
        <p class="lp-lead mt-4 max-w-xl text-forest/65">
            Empat fitur sederhana yang saling terhubung, jadi kamu tidak perlu berpindah-pindah catatan.
        </p>

        <div class="lp-bento">

            <div class="lp-card lp-c4">
                <span class="lp-ico lg bg-sage text-forest"><?= lp_icon('swap') ?></span>
                <h3 class="font-display">Transaksi</h3>
                <p class="lp-sub max-w-md text-forest/65">Catat pemasukan dan pengeluaran dalam hitungan detik, lalu telusuri riwayatnya kapan saja.</p>
                <div class="lp-list bg-paper">
                    <div class="lp-row"><span class="font-semibold">Makan siang</span><b class="text-expense">−Rp25.000</b></div>
                    <div class="lp-row"><span class="font-semibold">Proyek freelance</span><b class="text-income">+Rp750.000</b></div>
                    <div class="lp-row"><span class="font-semibold">Bensin</span><b class="text-expense">−Rp50.000</b></div>
                </div>
            </div>

            <div class="lp-dark deco-card lp-c2">
                <span class="lp-ico lg" style="background:rgba(255,255,255,.15)"><?= icon('wallet', 'w-6 h-6') ?></span>
                <h3 class="font-display" style="margin-top:1.1rem;font-size:1.375rem;font-weight:800">Anggaran</h3>
                <p style="margin-top:.5rem;line-height:1.6;opacity:.75">Pisahkan pengeluaran wajib dan fleksibel agar saldo bebas tidak menipu.</p>
                <div style="margin-top:1.5rem;display:grid;gap:1rem;font-size:.875rem">
                    <div>
                        <div class="lp-row" style="margin-bottom:.4rem"><span>Wajib</span><b>80%</b></div>
                        <div class="lp-track"><i style="width:80%;background:#fff"></i></div>
                    </div>
                    <div>
                        <div class="lp-row" style="margin-bottom:.4rem"><span>Fleksibel</span><b>45%</b></div>
                        <div class="lp-track"><i style="width:45%;background:var(--income)"></i></div>
                    </div>
                </div>
            </div>

            <div class="lp-card lp-c3">
                <span class="lp-ico lg bg-sage text-forest"><?= icon('bell', 'w-6 h-6') ?></span>
                <h3 class="font-display">Pengingat</h3>
                <p class="lp-sub text-forest/65">Jangan lewatkan pencatatan, tagihan, atau batas anggaran.</p>
                <div class="lp-tags">
                    <span class="bg-sage">Catat pengeluaran</span>
                    <span class="bg-sage">Bayar tagihan</span>
                    <span class="bg-red-50 text-expense">Anggaran hampir habis</span>
                </div>
            </div>

            <div class="lp-card lp-c3w">
                <span class="lp-ico lg bg-sage text-forest"><?= lp_icon('piggy') ?></span>
                <h3 class="font-display">Tabungan</h3>
                <p class="lp-sub text-forest/65">Buat target dan pantau kemajuan tiap tujuanmu.</p>
                <div class="lp-goal bg-paper">
                    <div class="lp-row">
                        <span class="font-semibold">Laptop baru</span>
                        <b class="text-income">Rp6 jt / Rp8 jt</b>
                    </div>
                    <div class="lp-bar"><span style="width:75%;background:var(--income)"></span></div>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- CARA KERJA -->
<section id="cara-kerja" class="lp-sec bg-white" style="border-top:1px solid rgba(0,0,0,.07);border-bottom:1px solid rgba(0,0,0,.07)">
    <div class="lp-wrap">
        <h2 class="lp-h2 font-display max-w-xl">Tiga langkah, lalu tinggal jalan.</h2>

        <ol class="lp-steps">
            <li class="bg-paper">
                <span class="lp-num text-forest"><b>1</b></span>
                <h3 class="font-display">Catat uang masuk dan keluar</h3>
                <p class="text-forest/65">Tambahkan transaksi harianmu. Cukup nominal, kategori, dan tanggal.</p>
            </li>
            <li class="bg-paper">
                <span class="lp-num text-forest"><b>2</b></span>
                <h3 class="font-display">Tandai kebutuhan wajib</h3>
                <p class="text-forest/65">Sewa, listrik, cicilan: pisahkan dari pengeluaran yang masih bisa diatur.</p>
            </li>
            <li class="bg-paper">
                <span class="lp-num text-income"><b>3</b></span>
                <h3 class="font-display">Pantau saldo bebas</h3>
                <p class="text-forest/65">Lihat berapa yang aman dipakai, dan berapa yang bisa masuk tabungan.</p>
            </li>
        </ol>
    </div>
</section>

<!-- TENTANG / CTA -->
<section id="tentang" style="padding:3rem .75rem">
    <div class="lp-cta-box deco-card lp-dark" style="max-width:72rem;margin:0 auto">
        <div class="glow" aria-hidden="true"></div>
        <img src="<?= h(app_url('assets/img/sakubijak-icon.jpg')) ?>" alt="Logo SakuBijak">
        <h2 class="lp-h2 font-display" style="margin:1.5rem auto 0;max-width:36rem">Catat dulu, tenang kemudian.</h2>
        <p class="lp-lead" style="margin:1.25rem auto 0;max-width:34rem;opacity:.75">
            SakuBijak membantu kamu memahami kondisi keuangan, mengatur pengeluaran,
            dan membangun kebiasaan finansial yang lebih baik. Keputusan kecil hari ini
            ikut menentukan masa depanmu.
        </p>
        <a href="<?= h(app_url('auth/register.php')) ?>" class="lp-btn lp-white-btn">Mulai kelola keuangan</a>
    </div>
</section>

<!-- FOOTER -->
<footer>
    <div class="lp-wrap lp-foot">
        <div style="display:flex;align-items:center;gap:.5rem">
            <img src="<?= h(app_url('assets/img/sakubijak-icon.jpg')) ?>" style="width:2rem;height:2rem;border-radius:.5rem" alt="SakuBijak">
            <span class="font-display font-extrabold">SakuBijak</span>
        </div>
        <p class="text-sm text-forest/55">© <?= date('Y') ?> SakuBijak. Kelola keuangan lebih bijak.</p>
    </div>
</footer>

<script>
    // Tutup menu mobile setelah salah satu tautan diketuk
    document.querySelectorAll('#lpMenu a').forEach(function (a) {
        a.addEventListener('click', function () { document.getElementById('lpMenu').removeAttribute('open'); });
    });
</script>
</body>
</html>