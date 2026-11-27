<?php

use App\Model\ReportAlarmModel;

if (!function_exists('permtrue') || !permtrue('reportview') || !permtrue('report_alarm')) {
    return;
}

$alarmBannerUserId = (int) sesset('id');
if ($alarmBannerUserId < 1) {
    return;
}

try {
    $alarmBannerCache = $_SESSION['report_alarm_banner_cache'] ?? null;
    if (is_array($alarmBannerCache) && ($alarmBannerCache['user'] ?? 0) === $alarmBannerUserId && ($alarmBannerCache['expires'] ?? 0) > time()) {
        $alarmBannerItems = $alarmBannerCache['items'];
    } else {
        $alarmBannerItems = (new ReportAlarmModel($ac))->summaryForUser($alarmBannerUserId);
        $_SESSION['report_alarm_banner_cache'] = ['user' => $alarmBannerUserId, 'expires' => time() + 300, 'items' => $alarmBannerItems];
    }
} catch (\Throwable $e) {
    error_log('Rapor alarm banner hatası: ' . $e->getMessage());
    return;
}

if (!$alarmBannerItems) {
    return;
}

$alarmBannerHasOverdue = array_sum(array_column($alarmBannerItems, 'overdue')) > 0;
?>
<style>
    .report-alarm-banner { display: flex; align-items: flex-start; gap: 12px; padding: 10px 14px; margin-bottom: 14px; border-radius: 10px; border: 1px solid #fde68a; background: #fffbeb; color: #78350f; font-size: 13px; }
    .report-alarm-banner.has-overdue { border-color: #fecaca; background: #fef2f2; color: #7f1d1d; }
    .report-alarm-banner .ra-icon { font-size: 18px; line-height: 1.6; }
    .report-alarm-banner .ra-body { flex: 1; min-width: 0; }
    .report-alarm-banner .ra-title { font-weight: 700; margin-bottom: 4px; }
    .report-alarm-banner .ra-items { display: flex; flex-wrap: wrap; gap: 6px; }
    .report-alarm-banner .ra-item { display: inline-flex; align-items: center; gap: 6px; padding: 2px 10px; border-radius: 999px; background: rgba(255,255,255,.75); border: 1px solid rgba(0,0,0,.08); color: inherit; text-decoration: none; font-weight: 600; }
    .report-alarm-banner .ra-item:hover { background: #fff; color: inherit; text-decoration: none; }
    .report-alarm-banner .ra-count-over { color: #b91c1c; }
    .report-alarm-banner .ra-count-soon { color: #b45309; }
    .report-alarm-banner .ra-actions { display: flex; align-items: center; gap: 8px; flex-shrink: 0; }
    .report-alarm-banner .ra-btn { background: rgba(255,255,255,.75); border: 1px solid rgba(0,0,0,.12); border-radius: 6px; padding: 2px 10px; font-size: 12px; font-weight: 600; color: inherit; cursor: pointer; white-space: nowrap; }
    .report-alarm-banner .ra-btn:hover { background: #fff; }
    .dark-mode .report-alarm-banner .ra-btn { background: rgba(0,0,0,.25); border-color: rgba(255,255,255,.15); }
    .report-alarm-banner .ra-close { background: transparent; border: 0; font-size: 18px; line-height: 1; color: inherit; opacity: .6; cursor: pointer; }
    .report-alarm-banner .ra-close:hover { opacity: 1; }
    .dark-mode .report-alarm-banner { background: #3b2f12; border-color: #78510f; color: #fde68a; }
    .dark-mode .report-alarm-banner.has-overdue { background: #3f1d1d; border-color: #7f1d1d; color: #fecaca; }
    .dark-mode .report-alarm-banner .ra-item { background: rgba(0,0,0,.25); border-color: rgba(255,255,255,.12); }
</style>
<div class="report-alarm-banner<?php echo $alarmBannerHasOverdue ? ' has-overdue' : ''; ?>" id="reportAlarmBanner" style="display:none;">
    <i class="fa fa-bell ra-icon"></i>
    <div class="ra-body">
        <div class="ra-title">Rapor hatırlatma alarmlarınız tetiklendi</div>
        <div class="ra-items">
            <?php foreach ($alarmBannerItems as $alarmBannerItem) { ?>
                <a class="ra-item" href="index.php?p=reports/reports&amp;alarm=<?php echo (int) $alarmBannerItem['id']; ?>" title="Bu alarmın raporlarını listele">
                    <?php echo htmlspecialchars($alarmBannerItem['name'], ENT_QUOTES, 'UTF-8'); ?>
                    <?php if ($alarmBannerItem['overdue'] > 0) { ?><span class="ra-count-over"><?php echo (int) $alarmBannerItem['overdue']; ?> süresi geçmiş</span><?php } ?>
                    <?php if ($alarmBannerItem['upcoming'] > 0) { ?><span class="ra-count-soon"><?php echo (int) $alarmBannerItem['upcoming']; ?> yaklaşan</span><?php } ?>
                </a>
            <?php } ?>
        </div>
    </div>
    <div class="ra-actions">
        <button type="button" class="ra-btn" id="reportAlarmBannerSnooze" title="Bugün bir daha gösterme"><i class="fa fa-bell-slash-o"></i> Bugün gösterme</button>
        <button type="button" class="ra-close" id="reportAlarmBannerClose" title="Kapat" aria-label="Kapat">&times;</button>
    </div>
</div>
<script>
    (function () {
        var el = document.getElementById('reportAlarmBanner');
        if (!el) { return; }
        var key = 'report_alarm_banner_hidden_<?php echo (int) $alarmBannerUserId; ?>';
        var today = <?php echo json_encode(date('Y-m-d')); ?>;
        var hidden = false;
        try { hidden = localStorage.getItem(key) === today; } catch (e) {}
        if (!hidden) { el.style.display = 'flex'; }
        document.getElementById('reportAlarmBannerClose').addEventListener('click', function () {
            el.style.display = 'none';
        });
        document.getElementById('reportAlarmBannerSnooze').addEventListener('click', function () {
            el.style.display = 'none';
            try { localStorage.setItem(key, today); } catch (e) {}
        });
    })();
</script>
