<?php

require_once __DIR__ . '/bootstrap.php';

use App\Helper\MaintenanceMode;

if (!MaintenanceMode::isEnabled($ac) || MaintenanceMode::hasAccessPermission($ac)) {
    header('Location: index.php?p=home');
    exit;
}

$logo = (string) set('logo');
$logo = $logo !== '' ? $logo : 'src/images/logo.svg';
http_response_code(503);
header('Retry-After: 300');
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#f4f7fb">
    <title>Bakım Çalışması | AYDINOĞULLARI</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&amp;display=swap" rel="stylesheet">
    <style>
        :root {
            color-scheme: light;
            --surface: rgba(255, 255, 255, .9);
            --surface-solid: #fff;
            --text: #101828;
            --muted: #667085;
            --blue: #d71920;
            --blue-dark: #a90f15;
            --blue-soft: #fff1f2;
            --line: #e4e7ec;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            overflow-x: hidden;
            padding: 36px 20px;
            font-family: "Outfit", -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: var(--text);
            background: #f5f6f8;
        }
        .ambient { position: fixed; z-index: -1; border-radius: 999px; filter: blur(4px); pointer-events: none; }
        .ambient-one { width: 460px; height: 460px; left: -160px; top: -190px; background: radial-gradient(circle, rgba(215,25,32,.13), transparent 68%); }
        .ambient-two { width: 560px; height: 560px; right: -200px; bottom: -250px; background: radial-gradient(circle, rgba(15,23,42,.1), transparent 68%); }
        .card {
            position: relative;
            width: min(100%, 980px);
            overflow: hidden;
            background: var(--surface);
            border: 1px solid rgba(255,255,255,.9);
            border-radius: 30px;
            box-shadow: 0 30px 80px rgba(16,24,40,.12), 0 2px 8px rgba(16,24,40,.04);
            backdrop-filter: blur(18px);
        }
        .card::before { content:""; position:absolute; z-index:3; inset:0 0 auto; height:4px; background:linear-gradient(90deg,#8b0b10,#ef4444,#f97316); }
        .layout { display:grid; grid-template-columns:1.08fr .92fr; min-height:600px; }
        .content { display:flex; flex-direction:column; padding:48px 48px 30px; }
        .brand { align-self:flex-start; display:inline-flex; align-items:center; justify-content:center; min-height:56px; margin-bottom:auto; padding:7px 12px; border-radius:13px; background:#fff; }
        .logo { display:block; max-width:225px; max-height:58px; }
        .copy { padding:48px 0 42px; }
        .eyebrow { display:flex; align-items:center; gap:9px; margin:0 0 14px; color:var(--blue); font-size:11px; font-weight:850; letter-spacing:.15em; text-transform:uppercase; }
        .eyebrow::before { content:""; width:25px; height:2px; border-radius:2px; background:var(--blue); }
        h1 { max-width:470px; margin:0 0 18px; font-size:clamp(34px,4.6vw,49px); line-height:1.08; letter-spacing:-.045em; }
        .lead { max-width:465px; margin:0; color:var(--muted); line-height:1.72; font-size:15px; }
        .status { display:inline-flex; align-items:center; gap:10px; margin:28px 0 0; padding:9px 14px; border:1px solid #fecdd3; border-radius:999px; background:var(--blue-soft); color:var(--blue-dark); font-size:12px; font-weight:750; }
        .pulse { position:relative; width:8px; height:8px; border-radius:50%; background:#ef4444; }
        .pulse::after { content:""; position:absolute; inset:-4px; border:1px solid #fb7185; border-radius:50%; animation:pulse 2s ease-out infinite; }
        @keyframes pulse { 0%{transform:scale(.65);opacity:1} 80%,100%{transform:scale(1.65);opacity:0} }
        .meta { margin-top:auto; padding-top:20px; border-top:1px solid var(--line); }
        .session-area { display:flex; align-items:center; flex-wrap:wrap; gap:8px; color:#98a2b3; font-size:11px; }
        .session-area a { color:#667085; font-weight:700; text-decoration:none; }
        .session-area a:hover { color:var(--blue); }
        footer { display:flex; align-items:center; gap:8px; margin-top:14px; color:#98a2b3; font-size:11px; }
        footer span { width:3px; height:3px; border-radius:50%; background:#cbd5e1; }
        .visual { position:relative; display:flex; align-items:flex-end; justify-content:center; min-width:0; overflow:hidden; isolation:isolate; background:linear-gradient(155deg,#151820 0%,#2a1214 48%,#610f13 100%); }
        .visual::before { content:""; position:absolute; width:390px; height:390px; top:42px; left:50%; z-index:-1; border:1px solid rgba(255,255,255,.09); border-radius:50%; transform:translateX(-50%); box-shadow:0 0 0 42px rgba(255,255,255,.025),0 0 0 84px rgba(255,255,255,.018); }
        .visual::after { content:""; position:absolute; inset:auto -15% -28% -15%; z-index:-1; height:52%; background:radial-gradient(ellipse at center,rgba(249,115,22,.48),rgba(215,25,32,.15) 48%,transparent 72%); filter:blur(10px); animation:fireGlow 2.4s ease-in-out infinite alternate; }
        .visual-label { position:absolute; z-index:2; top:32px; left:30px; display:flex; align-items:center; gap:8px; color:rgba(255,255,255,.72); font-size:10px; font-weight:800; letter-spacing:.13em; text-transform:uppercase; }
        .visual-label i { width:7px; height:7px; border-radius:50%; background:#f97316; box-shadow:0 0 0 5px rgba(249,115,22,.15); }
        .technician { position:relative; z-index:1; width:min(112%,470px); max-height:570px; object-fit:contain; object-position:center bottom; filter:drop-shadow(0 24px 24px rgba(0,0,0,.34)); transform-origin:center bottom; animation:technicianFloat 3.6s ease-in-out infinite; }
        @keyframes technicianFloat { 0%,100%{transform:translateY(3px) rotate(-.25deg)} 50%{transform:translateY(-5px) rotate(.25deg)} }
        @keyframes fireGlow { from{opacity:.7;transform:scale(.96)} to{opacity:1;transform:scale(1.05)} }
        @media (prefers-reduced-motion: reduce) { *,*::before,*::after { animation:none!important; transition:none!important; } }
        @media (prefers-color-scheme: dark) {
            :root { color-scheme:dark; --surface:rgba(17,24,39,.92); --surface-solid:#111827; --text:#f8fafc; --muted:#a5b4c7; --blue-soft:rgba(37,99,235,.13); --line:#344054; }
            body { background:#0b1120; }
            .card { border-color:rgba(255,255,255,.08); box-shadow:0 30px 90px rgba(0,0,0,.38); }
            .brand { background:#fff; }
            .session-area a { color:#cbd5e1; }
        }
        @media (max-width:820px) {
            body { padding:18px 12px; }
            .card { border-radius:22px; }
            .layout { grid-template-columns:1fr; }
            .content { padding:34px 26px 24px; text-align:center; }
            .brand { align-self:center; margin-bottom:34px; }
            .logo { max-width:205px; }
            .copy { padding:0 0 28px; }
            .eyebrow { justify-content:center; }
            h1,.lead { margin-left:auto; margin-right:auto; }
            h1 { font-size:clamp(31px,10vw,42px); }
            .meta { margin-top:0; }
            .session-area,footer { justify-content:center; }
            .visual { min-height:390px; }
            .visual-label { top:24px; left:24px; }
            .technician { width:min(88%,360px); max-height:380px; }
        }
        @media (max-width:430px) {
            .visual { min-height:330px; }
            .technician { max-height:325px; }
            .session-area span:first-child { width:100%; }
        }
    </style>
</head>
<body>
    <div class="ambient ambient-one"></div>
    <div class="ambient ambient-two"></div>
    <main class="card">
        <div class="layout">
            <section class="content">
                <div class="brand"><img class="logo" src="<?php echo htmlspecialchars($logo, ENT_QUOTES, 'UTF-8'); ?>" alt="Aydınoğulları"></div>
                <div class="copy">
                    <p class="eyebrow">Ekibimiz iş başında</p>
                    <h1>Kontrol bizde, kısa süre sonra buradayız.</h1>
                    <p class="lead">Sistemimizi daha güvenli ve güçlü hale getirmek için planlı bir bakım çalışması yürütüyoruz. Çalışma tamamlandığında kaldığınız yerden devam edebilirsiniz.</p>
                    <div class="status"><span class="pulse"></span> Bakım çalışması devam ediyor</div>
                </div>

                <div class="meta">
                    <div class="session-area">
                        <span><?php echo htmlspecialchars((string) ($_SESSION['username'] ?? 'Kullanıcı'), ENT_QUOTES, 'UTF-8'); ?> hesabıyla oturum açık</span>
                        <span>·</span>
                        <a href="logout.php">Farklı hesapla giriş yap</a>
                    </div>
                    <footer>AYDINOĞULLARI YSC <span></span> Güvenliğiniz için çalışıyoruz.</footer>
                </div>
            </section>
            <aside class="visual" aria-label="Yangın güvenliği teknisyeni bakım çalışması illüstrasyonu">
                <div class="visual-label"><i></i> Teknik ekip çalışıyor</div>
                <img class="technician" src="src/images/maintenance-fire-technician.png" alt="Yangın söndürme ekipmanlarıyla çalışan teknisyen">
            </aside>
        </div>
    </main>
</body>
</html>
