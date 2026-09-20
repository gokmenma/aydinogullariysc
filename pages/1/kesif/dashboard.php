<?php
use App\Model\KesifModel;

if (!permtrue('kesif_dashboard') && !permtrue('kesifView')) {
    echo '<div class="alert alert-danger m-4"><i class="fa fa-exclamation-triangle"></i> Bu sayfayı görüntüleme yetkiniz bulunmamaktadır.</div>';
    return;
}

if (function_exists('audit_log')) {
    audit_log('view', 'kesif', 'Keşif Dashboard sayfası görüntülendi', 'dashboard', 0);
}

$period = $_GET['period'] ?? 'all';
$startDate = $_GET['start_date'] ?? null;
$endDate = $_GET['end_date'] ?? null;
$filterLabel = 'Tüm Zamanlar';

switch ($period) {
    case 'this_month':
        $startDate = date('Y-m-01'); $endDate = date('Y-m-t'); $filterLabel = 'Bu Ay'; break;
    case 'last_month':
        $startDate = date('Y-m-01', strtotime('-1 month')); $endDate = date('Y-m-t', strtotime('-1 month')); $filterLabel = 'Geçen Ay'; break;
    case 'last_30_days':
        $startDate = date('Y-m-d', strtotime('-29 days')); $endDate = date('Y-m-d'); $filterLabel = 'Son 30 Gün'; break;
    case 'last_90_days':
        $startDate = date('Y-m-d', strtotime('-89 days')); $endDate = date('Y-m-d'); $filterLabel = 'Son 90 Gün'; break;
    case 'this_year':
        $startDate = date('Y-01-01'); $endDate = date('Y-12-31'); $filterLabel = 'Bu Yıl'; break;
    case 'custom':
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $startDate) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $endDate)) {
            $period = 'all'; $startDate = null; $endDate = null;
        } else {
            $filterLabel = date('d.m.Y', strtotime($startDate)) . ' – ' . date('d.m.Y', strtotime($endDate));
        }
        break;
    default:
        $period = 'all'; $startDate = null; $endDate = null;
}

$model = new KesifModel();
$summary = $model->getDashboardSummary($startDate, $endDate);
$statusRows = $model->getDashboardStatusDistribution($startDate, $endDate);
$monthlyRows = $model->getDashboardMonthlyTrend(12);
$topPeople = $model->getDashboardTopPeople(7, $startDate, $endDate);
$topCompanies = $model->getDashboardTopCompanies(7, $startDate, $endDate);
$recentRows = $model->getDashboardRecent(8, $startDate, $endDate);

$statusLabels = [
    'bekliyor' => 'Bekliyor', 'kesif_tamamlandi' => 'Keşif Tamamlandı',
    'teklif_hazirlandi' => 'Teklif Hazırlandı', 'teklif_gonderildi' => 'Teklif Gönderildi',
    'iptal_edildi' => 'İptal Edildi'
];
$statusClasses = [
    'bekliyor' => 'warning', 'kesif_tamamlandi' => 'primary',
    'teklif_hazirlandi' => 'info', 'teklif_gonderildi' => 'success', 'iptal_edildi' => 'danger'
];
$statusColors = [
    'bekliyor' => '#f59e0b', 'kesif_tamamlandi' => '#3b82f6',
    'teklif_hazirlandi' => '#06b6d4', 'teklif_gonderildi' => '#10b981', 'iptal_edildi' => '#ef4444'
];

$monthlyMap = [];
foreach ($monthlyRows as $row) $monthlyMap[$row['month_key']] = (int) $row['total'];
$monthLabels = []; $monthValues = [];
$trMonths = [1=>'Oca',2=>'Şub',3=>'Mar',4=>'Nis',5=>'May',6=>'Haz',7=>'Tem',8=>'Ağu',9=>'Eyl',10=>'Eki',11=>'Kas',12=>'Ara'];
for ($i = 11; $i >= 0; $i--) {
    $stamp = strtotime("-{$i} months");
    $key = date('Y-m', $stamp);
    $monthLabels[] = $trMonths[(int) date('n', $stamp)] . ' ' . date('y', $stamp);
    $monthValues[] = $monthlyMap[$key] ?? 0;
}
$statusChartLabels = []; $statusChartValues = []; $statusChartColors = [];
foreach ($statusRows as $row) {
    $key = $row['durum'] ?: 'bekliyor';
    $statusChartLabels[] = $statusLabels[$key] ?? ucfirst(str_replace('_', ' ', $key));
    $statusChartValues[] = (int) $row['total'];
    $statusChartColors[] = $statusColors[$key] ?? '#64748b';
}
$total = (int) ($summary['total_count'] ?? 0);
$successful = (int) ($summary['completed_count'] ?? 0) + (int) ($summary['offer_count'] ?? 0);
$successRate = $total > 0 ? round(($successful / $total) * 100, 1) : 0;
$fullMonthNames = [1=>'Ocak',2=>'Şubat',3=>'Mart',4=>'Nisan',5=>'Mayıs',6=>'Haziran',7=>'Temmuz',8=>'Ağustos',9=>'Eylül',10=>'Ekim',11=>'Kasım',12=>'Aralık'];
$dayNames = ['Monday'=>'Pazartesi','Tuesday'=>'Salı','Wednesday'=>'Çarşamba','Thursday'=>'Perşembe','Friday'=>'Cuma','Saturday'=>'Cumartesi','Sunday'=>'Pazar'];
$currentDateLabel = date('d') . ' ' . $fullMonthNames[(int)date('n')] . ' ' . date('Y') . ', ' . $dayNames[date('l')];
?>

<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<style>
.kesif-dashboard{--kd:#4f46e5;color:#1e293b;width:100%}.kesif-dashboard>.row{margin-left:-8px;margin-right:-8px}.kesif-dashboard>.row>[class*="col-"]{padding-left:8px;padding-right:8px}.kd-hero,.kd-card,.kd-kpi{background:#fff;border:1px solid #e2e8f0;border-radius:14px;box-shadow:0 4px 12px -2px rgba(0,0,0,.04)}
.kd-hero{padding:20px 24px;border-left:4px solid var(--kd)}.kd-badges{display:flex;align-items:center;gap:8px;margin-bottom:9px;flex-wrap:wrap}.kd-badge{font-size:12px;font-weight:600;padding:5px 10px;border-radius:6px;background:#f8fafc;color:#475569;border:1px solid #e2e8f0}.kd-badge-filter{background:#eef2ff;color:#4f46e5;border-color:#c7d2fe}.kd-title-icon{width:42px;height:42px;border-radius:10px;background:#eef2ff;border:1px solid #e0e7ff;color:#4f46e5;display:flex;align-items:center;justify-content:center;font-size:19px;margin-right:14px}.kd-title{font-size:20px;font-weight:700;letter-spacing:-.3px;margin:0;color:#1e293b}.kd-subtitle{font-size:13px;color:#64748b;margin-top:2px}.kd-actions{display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end}.kd-btn{display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border:1px solid #cbd5e1;border-radius:8px;background:#fff;color:#334155;font-size:13px;font-weight:600;box-shadow:0 1px 2px rgba(0,0,0,.03)}.kd-btn:hover{color:#0f172a;border-color:#94a3b8;background:#f8fafc}.kd-btn-primary{background:#10b981;border-color:#059669;color:#fff!important;box-shadow:0 2px 6px rgba(16,185,129,.25)}.kd-btn-primary:hover{background:#059669}.kd-filter-row{margin:20px 0}.kd-pills{display:flex;gap:6px;flex-wrap:wrap;background:#fff;padding:8px 12px;border-radius:12px;border:1px solid #e2e8f0;box-shadow:0 2px 4px rgba(0,0,0,.02);align-items:center}.kd-pill{padding:6px 14px;border-radius:8px;color:#64748b;font-size:13px;font-weight:500;border:1px solid transparent}.kd-pill.active{background:#4f46e5;color:#fff;box-shadow:0 2px 6px rgba(79,70,229,.35)}.kd-pill:hover{background:#f1f5f9;color:#1e293b}.kd-pill.active:hover{background:#4f46e5;color:#fff}.kd-date-form{display:flex;align-items:center}.kd-date-form .input-group{width:auto}.kd-date-form input{height:32px;width:115px!important;border:1px solid #ced4da;padding:5px 9px;font-size:13px}.kd-date-form .btn{font-size:12px;font-weight:700}.kd-kpi{padding:22px 20px;height:100%;position:relative;overflow:hidden;display:flex;flex-direction:column;justify-content:space-between;transition:.25s}.kd-kpi:hover{transform:translateY(-3px);box-shadow:0 12px 20px -4px rgba(0,0,0,.08)}.kd-kpi:before{content:"";position:absolute;left:0;right:0;top:0;height:4px;background:linear-gradient(90deg,var(--accent),var(--accent2))}.kd-kpi-label{font-size:13px;text-transform:uppercase;letter-spacing:.4px;font-weight:600;color:#64748b}.kd-kpi-value{font-size:26px;font-weight:700;color:#0f172a;line-height:1.2;margin-top:4px}.kd-kpi-meta{font-size:13px;color:#64748b;margin-top:10px}.kd-kpi-icon{width:46px;height:46px;border-radius:12px;background:var(--soft);color:var(--accent);display:flex;align-items:center;justify-content:center;font-size:20px}.kd-card{height:100%;overflow:hidden}.kd-card-head{padding:22px 24px 10px;display:flex;align-items:center;justify-content:space-between}.kd-card-title{font-size:18px;font-weight:700;color:#272b31;margin:0}.kd-card-title i{margin-right:8px}.kd-card-sub{font-size:13px;color:#64748b;margin-top:3px}.kd-card-body{padding:10px 24px 22px}.kd-chart{min-height:300px}.kd-rank{display:flex;align-items:center;padding:10px 0;border-bottom:1px solid #f1f5f9}.kd-rank:last-child{border:0}.kd-rank-no{width:28px;height:28px;border-radius:8px;background:#f8fafc;color:#64748b;border:1px solid #e2e8f0;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;margin-right:10px}.kd-rank-name{flex:1;min-width:0;font-size:12px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.kd-rank-count{font-size:11px;font-weight:700;color:#4f46e5;background:#eef2ff;padding:4px 8px;border-radius:10px}.kd-table{margin:0;font-size:12px}.kd-table th{border-top:0!important;border-bottom:2px solid #e2e8f0!important;background:#f8fafc;color:#475569;font-size:11px;text-transform:uppercase;padding:10px 12px}.kd-table td{vertical-align:middle;border-color:#f1f5f9;padding:10px 12px}.kd-company{max-width:210px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;font-weight:600}.kd-status{font-size:10px;padding:4px 7px;border-radius:10px;white-space:nowrap}.kd-empty{padding:30px;text-align:center;color:#94a3b8;font-size:12px}
.dark-mode .kd-hero,.dark-mode .kd-card,.dark-mode .kd-kpi,.dark-mode .kd-pills{background:#1e293b;border-color:#334155}.dark-mode .kd-title,.dark-mode .kd-card-title,.dark-mode .kd-kpi-value,.dark-mode .kd-rank-name{color:#f8fafc}.dark-mode .kd-subtitle,.dark-mode .kd-card-sub,.dark-mode .kd-kpi-meta{color:#94a3b8}.dark-mode .kd-btn,.dark-mode .kd-pill,.dark-mode .kd-date-form input,.dark-mode .kd-date-form .input-group-text{background:#0f172a!important;border-color:#475569!important;color:#cbd5e1!important}.dark-mode .kd-pill.active{background:#4f46e5!important;border-color:#4f46e5!important;color:#fff!important}.dark-mode .kd-card-head,.dark-mode .kd-rank,.dark-mode .kd-table td{border-color:#334155}.dark-mode .kd-table th{background:#0f172a;color:#94a3b8}.dark-mode .kd-table{color:#cbd5e1}.dark-mode .kd-card .badge-light{background:#0f172a;color:#cbd5e1;border-color:#475569!important}.dark-mode .kd-rank-no{background:#0f172a;border-color:#475569;color:#cbd5e1}.dark-mode .kd-rank-count{background:#312e81;color:#c7d2fe}.dark-mode .kd-company{color:#f1f5f9}
@media(max-width:767px){.kd-hero{padding:16px}.kd-actions{width:100%;justify-content:flex-start;margin-top:14px}.kd-filter-row>.d-flex{display:block!important}.kd-date-form{margin-top:10px}.kd-date-form input{max-width:105px}.kd-chart{min-height:240px}.kd-card-head{padding:18px 18px 8px}.kd-card-body{padding:8px 18px 18px}}
</style>

<div class="kesif-dashboard">
    <div class="row mb-3">
        <div class="col-12"><div class="kd-hero">
        <div class="row align-items-center">
            <div class="col-lg-7 col-md-12 mb-3 mb-lg-0">
                <div class="kd-badges">
                    <span class="kd-badge"><i class="fa fa-calendar mr-1 text-muted"></i><?php echo htmlspecialchars($currentDateLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                    <span class="kd-badge kd-badge-filter"><i class="fa fa-filter mr-1"></i><?php echo htmlspecialchars($filterLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
                <div class="d-flex align-items-center">
                    <div class="kd-title-icon"><i class="fa fa-compass"></i></div>
                    <div><h1 class="kd-title">Keşif Yönetimi & Performans Paneli</h1><div class="kd-subtitle">Saha keşifleri, ekip iş yükü, durum dağılımları ve teklif dönüşüm analizi.</div></div>
                </div>
            </div>
            <div class="col-lg-5 col-md-12"><div class="kd-actions">
                <?php if (permtrue('kesifCreate')) { ?><a href="index.php?p=kesif/list&action=new" class="kd-btn kd-btn-primary"><i class="fa fa-plus"></i> Yeni Keşif</a><?php } ?>
                <a href="index.php?p=kesif/list" class="kd-btn"><i class="fa fa-list"></i> Keşif Listesi</a>
            </div></div>
        </div>
        </div></div>
    </div>

    <div class="row kd-filter-row"><div class="col-12"><div class="d-flex flex-wrap justify-content-between align-items-center" style="gap:12px">
            <div class="kd-pills">
                <span class="font-12 font-weight-bold text-muted mr-1"><i class="fa fa-sliders mr-1"></i> Dönem:</span>
                <?php foreach (['all'=>'Tümü','this_year'=>'Bu Yıl ('.date('Y').')','this_month'=>'Bu Ay','last_month'=>'Geçen Ay','last_30_days'=>'Son 30 Gün','last_90_days'=>'Son 90 Gün'] as $key=>$label) { ?>
                    <a class="kd-pill <?php echo $period === $key ? 'active' : ''; ?>" href="index.php?p=kesif/dashboard&period=<?php echo $key; ?>"><?php echo $label; ?></a>
                <?php } ?>
            </div>
            <form class="kd-date-form" method="get">
                <input type="hidden" name="p" value="kesif/dashboard"><input type="hidden" name="period" value="custom">
                <div class="input-group input-group-sm"><div class="input-group-prepend"><span class="input-group-text bg-white border-right-0"><i class="fa fa-calendar text-primary"></i></span></div>
                <input type="date" name="start_date" value="<?php echo htmlspecialchars((string)$startDate, ENT_QUOTES, 'UTF-8'); ?>" required>
                <div class="input-group-prepend input-group-append"><span class="input-group-text bg-light">-</span></div><input type="date" name="end_date" value="<?php echo htmlspecialchars((string)$endDate, ENT_QUOTES, 'UTF-8'); ?>" required>
                <div class="input-group-append"><button class="btn btn-primary px-3" type="submit"><i class="fa fa-search mr-1"></i> Filtrele</button></div></div>
                <?php if ($period !== 'all') { ?><a href="index.php?p=kesif/dashboard" class="btn btn-outline-secondary btn-sm ml-2"><i class="fa fa-times"></i></a><?php } ?>
            </form>
    </div></div></div>

    <div class="row mb-3">
        <?php $kpis = [
            ['Toplam Keşif',$total,'fa-map-o','#0284c7','#e0f2fe','Seçili dönemdeki aktif kayıt'],
            ['Bekleyen',(int)($summary['waiting_count']??0),'fa-hourglass-half','#f59e0b','#fef3c7',(int)($summary['overdue_count']??0).' gecikmiş · '.(int)($summary['today_count']??0).' bugün'],
            ['Teklif Aşaması',(int)($summary['offer_count']??0),'fa-file-text-o','#10b981','#d1fae5','Hazırlanan veya gönderilen teklifler'],
            ['Başarı Oranı','%'.str_replace('.', ',', (string)$successRate),'fa-line-chart','#8b5cf6','#ede9fe',$successful.' sonuçlanan kayıt']
        ]; foreach($kpis as $kpi) { ?>
        <div class="col-xl-3 col-md-6 mb-3 mb-xl-0"><div class="kd-kpi" style="--accent:<?php echo $kpi[3]; ?>;--accent2:<?php echo $kpi[3]; ?>bb;--soft:<?php echo $kpi[4]; ?>"><div class="d-flex justify-content-between align-items-start"><div><div class="kd-kpi-label"><?php echo $kpi[0]; ?></div><div class="kd-kpi-value"><?php echo is_int($kpi[1]) ? number_format($kpi[1],0,',','.') : $kpi[1]; ?></div></div><div class="kd-kpi-icon"><i class="fa <?php echo $kpi[2]; ?>"></i></div></div><div class="kd-kpi-meta font-weight-bold"><?php echo $kpi[5]; ?></div></div></div>
        <?php } ?>
    </div>

    <div class="row mb-4">
        <div class="col-lg-8 mb-3 mb-lg-0"><div class="kd-card"><div class="kd-card-head"><div><h3 class="kd-card-title"><i class="fa fa-bar-chart text-primary"></i>Aylık Keşif Trendi (Son 12 Ay)</h3><div class="kd-card-sub">Aylara göre planlanan toplam saha keşfi dağılımı</div></div><span class="badge badge-light p-2 font-12 border"><i class="fa fa-info-circle text-info mr-1"></i> İnteraktif Grafiktir</span></div><div class="kd-card-body"><div id="kesifTrendChart" class="kd-chart"></div></div></div></div>
        <div class="col-lg-4"><div class="kd-card"><div class="kd-card-head"><div><h3 class="kd-card-title"><i class="fa fa-pie-chart text-warning"></i>Keşif Durum Dağılımı</h3><div class="kd-card-sub">Keşiflerin süreç ve sonuç oranları</div></div></div><div class="kd-card-body"><div id="kesifStatusChart" class="kd-chart"></div></div></div></div>
    </div>

    <div class="row mb-4">
        <div class="col-lg-6 mb-3 mb-lg-0"><div class="kd-card"><div class="kd-card-head"><div><h3 class="kd-card-title"><i class="fa fa-users text-primary"></i>Personel İş Yükü</h3><div class="kd-card-sub">En çok keşife atanan ekip üyeleri</div></div></div><div class="kd-card-body"><?php if (!$topPeople) { ?><div class="kd-empty">Atanmış personel verisi bulunamadı.</div><?php } foreach($topPeople as $i=>$row) { ?><div class="kd-rank"><span class="kd-rank-no"><?php echo $i+1; ?></span><span class="kd-rank-name" title="<?php echo htmlspecialchars($row['person_name'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($row['person_name'], ENT_QUOTES, 'UTF-8'); ?></span><span class="kd-rank-count"><?php echo (int)$row['total']; ?> keşif</span></div><?php } ?></div></div></div>
        <div class="col-lg-6"><div class="kd-card"><div class="kd-card-head"><div><h3 class="kd-card-title"><i class="fa fa-building text-primary"></i>Öne Çıkan Firmalar</h3><div class="kd-card-sub">Keşif adedine göre ilk firmalar</div></div></div><div class="kd-card-body"><?php if (!$topCompanies) { ?><div class="kd-empty">Firma verisi bulunamadı.</div><?php } foreach($topCompanies as $i=>$row) { ?><div class="kd-rank"><span class="kd-rank-no"><?php echo $i+1; ?></span><span class="kd-rank-name" title="<?php echo htmlspecialchars($row['company_name'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($row['company_name'], ENT_QUOTES, 'UTF-8'); ?></span><span class="kd-rank-count"><?php echo (int)$row['total']; ?> keşif</span></div><?php } ?></div></div></div>
    </div>

    <div class="kd-card mb-3"><div class="kd-card-head"><div><h3 class="kd-card-title">Son Keşifler</h3><div class="kd-card-sub">Seçili dönemde keşif tarihine göre son kayıtlar</div></div><a href="index.php?p=kesif/list" class="kd-btn">Tümünü Gör <i class="fa fa-angle-right"></i></a></div><div class="table-responsive"><table class="table kd-table no-filter"><thead><tr><th>Tarih</th><th>Firma</th><th>Personel</th><th>Konum</th><th>Durum</th></tr></thead><tbody><?php if (!$recentRows) { ?><tr><td colspan="5" class="kd-empty">Bu dönemde keşif kaydı bulunamadı.</td></tr><?php } foreach($recentRows as $row) { $status=$row['durum']?:'bekliyor'; ?><tr><td><?php echo date('d.m.Y H:i', strtotime($row['kesif_tarihi'])); ?></td><td><div class="kd-company" title="<?php echo htmlspecialchars($row['firma'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($row['firma'], ENT_QUOTES, 'UTF-8'); ?></div></td><td><?php echo htmlspecialchars(($row['gidecek_kisi'] && trim($row['gidecek_kisi'])!=='.')?$row['gidecek_kisi']:'Atanmadı', ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo htmlspecialchars($row['konum'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></td><td><span class="badge badge-<?php echo $statusClasses[$status]??'secondary'; ?> kd-status"><?php echo htmlspecialchars($statusLabels[$status]??$status, ENT_QUOTES, 'UTF-8'); ?></span></td></tr><?php } ?></tbody></table></div></div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof ApexCharts === 'undefined') return;
    var dark = document.body.classList.contains('dark-mode');
    var textColor = dark ? '#cbd5e1' : '#64748b';
    new ApexCharts(document.querySelector('#kesifTrendChart'), {chart:{type:'area',height:270,toolbar:{show:false},fontFamily:'inherit',foreColor:textColor},theme:{mode:dark?'dark':'light'},series:[{name:'Keşif',data:<?php echo json_encode($monthValues); ?>}],xaxis:{categories:<?php echo json_encode($monthLabels, JSON_UNESCAPED_UNICODE); ?>,labels:{style:{colors:textColor,fontSize:'10px'}}},yaxis:{min:0,forceNiceScale:true,labels:{style:{colors:textColor},formatter:function(v){return Math.round(v)}}},colors:['#0ea5e9'],stroke:{curve:'smooth',width:2.5},fill:{type:'gradient',gradient:{shadeIntensity:1,opacityFrom:.28,opacityTo:.03}},dataLabels:{enabled:false},grid:{borderColor:dark?'#334155':'#eef2f7'},tooltip:{theme:dark?'dark':'light'}}).render();
    new ApexCharts(document.querySelector('#kesifStatusChart'), {chart:{type:'donut',height:270,fontFamily:'inherit',foreColor:textColor},theme:{mode:dark?'dark':'light'},series:<?php echo json_encode($statusChartValues); ?>,labels:<?php echo json_encode($statusChartLabels, JSON_UNESCAPED_UNICODE); ?>,colors:<?php echo json_encode($statusChartColors); ?>,legend:{position:'bottom',fontSize:'10px',labels:{colors:textColor}},dataLabels:{enabled:true,formatter:function(v){return Math.round(v)+'%'}},plotOptions:{pie:{donut:{size:'64%',labels:{show:true,name:{color:textColor},value:{color:dark?'#f8fafc':'#1e293b'},total:{show:true,label:'Toplam',color:textColor,formatter:function(){return '<?php echo $total; ?>'}}}}}},stroke:{colors:[dark?'#1e293b':'#ffffff']},noData:{text:'Veri bulunamadı'}}).render();
});
</script>
