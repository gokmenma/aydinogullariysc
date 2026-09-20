<?php

use App\Model\ServiceModel;
use App\Helper\Date;

$services = new ServiceModel();
$canViewHomeFinancialData = permtrue('home_financial_data_view');

// 1. KPI Metrikleri - Servisler
$waitingServicesQuery = $ac->prepare('SELECT COUNT(*) FROM projects WHERE pstatu = ?');
$waitingServicesQuery->execute([15]);
$waitingCount = (int) $waitingServicesQuery->fetchColumn();

$inProgressServicesQuery = $ac->prepare('SELECT COUNT(*) FROM projects WHERE pstatu = ?');
$inProgressServicesQuery->execute([16]);
$inProgressCount = (int) $inProgressServicesQuery->fetchColumn();

$activeServices = $waitingCount + $inProgressCount;

$completedServicesQuery = $ac->prepare('SELECT COUNT(*) FROM projects WHERE pstatu = ?');
$completedServicesQuery->execute([17]);
$completedCount = (int) $completedServicesQuery->fetchColumn();

$totalServicesQuery = $ac->query('SELECT COUNT(*) FROM projects');
$totalServices = (int) $totalServicesQuery->fetchColumn();
$serviceCompRate = $totalServices > 0 ? round(($completedCount / $totalServices) * 100, 1) : 0;

// 2. KPI Metrikleri - Teklifler
$pendingOffersQuery = $ac->query('SELECT COUNT(*) as cnt, COALESCE(SUM(total_price), 0) as total FROM offers WHERE statu = 1');
$pendingOffersData = $pendingOffersQuery->fetch(PDO::FETCH_ASSOC);
$pendingOffersCount = (int) ($pendingOffersData['cnt'] ?? 0);
$pendingOffersSum = (float) ($pendingOffersData['total'] ?? 0);

$wonOffersQuery = $ac->query('SELECT COUNT(*) as cnt, COALESCE(SUM(total_price), 0) as total FROM offers WHERE statu = 2');
$wonOffersData = $wonOffersQuery->fetch(PDO::FETCH_ASSOC);
$wonOffersCount = (int) ($wonOffersData['cnt'] ?? 0);
$wonOffersSum = (float) ($wonOffersData['total'] ?? 0);

$totalOffers = $pendingOffersCount + $wonOffersCount;
$offerWinRate = $totalOffers > 0 ? round(($wonOffersCount / $totalOffers) * 100, 1) : 0;

// 3. KPI Metrikleri - Müşteriler & Görevler
$customersQuery = $ac->query('SELECT COUNT(*) FROM customers WHERE deleted_at IS NULL');
$activeCustomers = (int) $customersQuery->fetchColumn();

$todoQuery = $ac->prepare('SELECT COUNT(*) FROM todolist WHERE okey = ?');
$todoQuery->execute([0]);
$openTasksCount = (int) $todoQuery->fetchColumn();

// Türkçe Gün & Ay İsimleri
$turkishMonths = [
    1 => 'Ocak', 2 => 'Şubat', 3 => 'Mart', 4 => 'Nisan', 5 => 'Mayıs', 6 => 'Haziran',
    7 => 'Temmuz', 8 => 'Ağustos', 9 => 'Eylül', 10 => 'Ekim', 11 => 'Kasım', 12 => 'Aralık'
];
$turkishDays = [
    'Monday' => 'Pazartesi', 'Tuesday' => 'Salı', 'Wednesday' => 'Çarşamba',
    'Thursday' => 'Perşembe', 'Friday' => 'Cuma', 'Saturday' => 'Cumartesi', 'Sunday' => 'Pazar'
];

$curDayName = $turkishDays[date('l')] ?? date('l');
$curDateFormatted = date('d') . ' ' . ($turkishMonths[(int)date('m')] ?? date('F')) . ' ' . date('Y') . ', ' . $curDayName;
$loggedUser = htmlspecialchars($_SESSION['username'] ?? 'Kullanıcı', ENT_QUOTES, 'UTF-8');

?>

<div class="main-container" id="content">
	<div id="maincontainer" class="content crm-dashboard-wrapper animate-fade-in">
		
		<!-- 1. CRM HERO / KARŞILAMA VE HIZLI AKSİYON ÇUBUĞU -->
		<div class="crm-hero-banner">
			<div class="row align-items-center">
				<div class="col-12">
					<div class="d-flex align-items-center mb-2">
						<span class="crm-date-chip">
							<i class="fa fa-calendar-o"></i> <?php echo $curDateFormatted; ?>
						</span>
					</div>
					<h2 class="crm-hero-title">Hoş Geldiniz, <?php echo $loggedUser; ?> 👋</h2>
					<p class="crm-hero-subtitle m-0">Operasyonel süreçler, servis takibi ve aktif tekliflerinize genel bakış.</p>
				</div>
			</div>
			<div class="crm-quick-actions">
				
				<div class="crm-quick-actions-list">
					<?php if (permtrue('offeradd')) : ?>
							<a href="index.php?p=offers/offer-manage" class="crm-quick-btn btn-primary-action">
								<i class="fa fa-file-text-o"></i> Yeni Teklif
							</a>
					<?php endif; ?>
					<?php if (permtrue('serviceAdd')) : ?>
							<a href="index.php?p=service/manage" class="crm-quick-btn ">
								<i class="fa fa-plus-circle"></i> Yeni Servis
							</a>
					<?php endif; ?>
					
					<?php if (permtrue('customeradd')) : ?>
							<a href="index.php?p=customers/manage" class="crm-quick-btn">
								<i class="fa fa-building-o"></i> Yeni Firma
							</a>
					<?php endif; ?>
					<?php if (permtrue('todoadd')) : ?>
							<a href="index.php?p=task-new" class="crm-quick-btn">
								<i class="fa fa-check-square-o"></i> Görev Ekle
							</a>
					<?php endif; ?>
				</div>
			</div>
		</div>

		<!-- 2. EXECUTIVE KPI CARDS -->
		<div class="crm-kpi-grid mb-4">
			<!-- Devam Eden Servisler -->
			<div class="crm-kpi-card">
				<div class="crm-kpi-header">
					<div>
						<span class="crm-kpi-label">Aktif Servisler</span>
						<div class="crm-kpi-value"><?php echo $activeServices; ?></div>
					</div>
					<div class="crm-kpi-icon icon-blue">
						<i class="fa fa-wrench"></i>
					</div>
				</div>
				<div class="crm-kpi-footer">
					<div class="d-flex align-items-center" style="gap: 4px;">
						<span class="crm-badge-soft soft-amber">Bekleyen: <?php echo $waitingCount; ?></span>
						<span class="crm-badge-soft soft-blue">Sahada: <?php echo $inProgressCount; ?></span>
					</div>
					<a href="index.php?p=service/list" class="crm-card-link">Tümü <i class="fa fa-angle-right"></i></a>
				</div>
			</div>

			<!-- Bekleyen Teklifler -->
			<div class="crm-kpi-card">
				<div class="crm-kpi-header">
					<div>
						<span class="crm-kpi-label">Bekleyen Teklifler</span>
						<div class="crm-kpi-value"><?php echo $pendingOffersCount; ?></div>
					</div>
					<div class="crm-kpi-icon icon-amber">
						<i class="fa fa-file-text-o"></i>
					</div>
				</div>
				<div class="crm-kpi-footer">
					<span class="weight-600 text-dark" style="font-size: 11px;">
						Hacim:
						<?php if ($canViewHomeFinancialData) : ?>
							<span class="text-primary"><?php echo tlFormat($pendingOffersSum); ?></span>
						<?php else : ?>
							<span class="crm-financial-hidden" title="Bu finansal veriyi görüntüleme yetkiniz bulunmuyor">
								<i class="fa fa-eye-slash" aria-hidden="true"></i>
								<span class="sr-only">Finansal veri gizli</span>
							</span>
						<?php endif; ?>
					</span>
					<span class="crm-badge-soft soft-amber">Pipeline</span>
				</div>
			</div>

			<!-- Kazanılan Teklifler & Ciro -->
			<div class="crm-kpi-card">
				<div class="crm-kpi-header">
					<div>
						<span class="crm-kpi-label">Kazanılan Teklifler</span>
						<div class="crm-kpi-value"><?php echo $wonOffersCount; ?></div>
					</div>
					<div class="crm-kpi-icon icon-emerald">
						<i class="fa fa-trophy"></i>
					</div>
				</div>
				<div class="crm-kpi-footer">
					<span class="weight-600 text-dark" style="font-size: 11px;">
						Ciro:
						<?php if ($canViewHomeFinancialData) : ?>
							<span class="text-success"><?php echo tlFormat($wonOffersSum); ?></span>
						<?php else : ?>
							<span class="crm-financial-hidden" title="Bu finansal veriyi görüntüleme yetkiniz bulunmuyor">
								<i class="fa fa-eye-slash" aria-hidden="true"></i>
								<span class="sr-only">Finansal veri gizli</span>
							</span>
						<?php endif; ?>
					</span>
					<span class="crm-badge-soft soft-emerald">%<?php echo $offerWinRate; ?> Başarı</span>
				</div>
			</div>

			<!-- Müşteri Portföyü & Görevler -->
			<div class="crm-kpi-card">
				<div class="crm-kpi-header">
					<div>
						<span class="crm-kpi-label">Kayıtlı Portföy</span>
						<div class="crm-kpi-value"><?php echo number_format($activeCustomers, 0, ',', '.'); ?></div>
					</div>
					<div class="crm-kpi-icon icon-purple">
						<i class="fa fa-building-o"></i>
					</div>
				</div>
				<div class="crm-kpi-footer">
					<span class="weight-600 text-dark" style="font-size: 11px;">
						<i class="fa fa-tasks text-muted mr-1"></i> <?php echo $openTasksCount; ?> Bekleyen Görev
					</span>
					<span class="crm-badge-soft soft-purple">Aktif CRM</span>
				</div>
			</div>
		</div>

		<!-- 3. 15 GÜNLÜK SERVİS PLANLAMA VE AYLIK TAKVİM PANOSU -->
		<?php
		$todayDate = date('Y-m-d');
		
		// 15 Günlük Tek Sıra Kolon Verisi Hazırlığı
		$startDate = new DateTime();
		$startDate->modify('-3 days'); // 3 gün öncesinden başla

		$planningDays = [];
		$totalPeriodServices = 0;

		$iter = clone $startDate;
		for ($i = 0; $i < 15; $i++) {
			$dateStr = $iter->format('Y-m-d');
			$dayEng = $iter->format('l');
			$dayTr = $turkishDays[$dayEng] ?? $dayEng;
			$displayDate = $iter->format('d.m.Y');
			$isToday = ($dateStr === $todayDate);

			$dailyServices = $services->getDailyServiceList($dateStr);
			$serviceCount = count($dailyServices);
			$totalPeriodServices += $serviceCount;

			$planningDays[] = [
				'dateStr' => $dateStr,
				'displayDate' => $displayDate,
				'dayTr' => $dayTr,
				'isToday' => $isToday,
				'services' => $dailyServices,
				'count' => $serviceCount
			];

			$iter->modify('+1 day');
		}

		// Aylık Takvim Verisi Hazırlığı (Geçerli Ay)
		$currentCalYear = (int) date('Y');
		$currentCalMonth = (int) date('n');
		$currentCalMonthName = $turkishMonths[$currentCalMonth] ?? date('F');
		$daysInMonth = (int) date('t', strtotime("$currentCalYear-$currentCalMonth-01"));
		$firstDayWeekday = (int) date('N', strtotime("$currentCalYear-$currentCalMonth-01")); // 1 (Pzt) - 7 (Paz)

		$monthDaysData = [];
		$monthDaysJson = [];
		$totalMonthServices = 0;
		for ($d = 1; $d <= $daysInMonth; $d++) {
			$dateStr = sprintf('%04d-%02d-%02d', $currentCalYear, $currentCalMonth, $d);
			$dServices = $services->getDailyServiceList($dateStr);
			$cnt = count($dServices);
			$totalMonthServices += $cnt;

			$clientMonthServices = [];
			foreach ($dServices as $ds) {
				$statusObj = $services->getServiceBackColour($ds->pstatu);
				$statusColor = !empty($statusObj->colour) ? $statusObj->colour : '#3b82f6';
				$statusTitle = !empty($statusObj->title) ? $statusObj->title : 'Durum Belirtilmemiş';

				$authorNames = [];
				if (!empty($ds->pauthors)) {
					$authorList = explode('|', $ds->pauthors);
					foreach ($authorList as $authId) {
						$name = getUsername($authId);
						if (!empty($name)) {
							$authorNames[] = $name;
						}
					}
				}

				$clientMonthServices[] = [
					'id' => (int) $ds->id,
					'service_number' => (string) ($ds->service_number ?? ''),
					'title' => (string) ($ds->title ?? ''),
					'company' => (string) ($ds->firma_adi ?? 'Firma Belirtilmemiş'),
					'authors' => implode(', ', $authorNames),
					'status_color' => $statusColor,
					'status_title' => $statusTitle,
				];
			}

			$dayEng = date('l', strtotime($dateStr));
			$dayTr = $turkishDays[$dayEng] ?? $dayEng;
			$displayDate = date('d.m.Y', strtotime($dateStr));

			$monthDaysData[$d] = [
				'dayNum' => $d,
				'dateStr' => $dateStr,
				'displayDate' => $displayDate,
				'dayTr' => $dayTr,
				'isToday' => ($dateStr === $todayDate),
				'services' => $dServices,
				'count' => $cnt
			];

			$monthDaysJson[$dateStr] = [
				'dayNum' => $d,
				'dateStr' => $dateStr,
				'displayDate' => $displayDate,
				'dayTr' => $dayTr,
				'isToday' => ($dateStr === $todayDate),
				'count' => $cnt,
				'services' => $clientMonthServices
			];
		}
		?>
		<div class="crm-card mb-4 position-relative">
			<div class="crm-card-header flex-wrap" style="gap: 12px;">
				<div class="d-flex align-items-center flex-wrap" style="gap: 10px;">
					<h3 class="crm-card-title m-0">
						<i class="fa fa-calendar text-primary"></i> Servis Planlama Panosu
					</h3>
					<span class="crm-badge-soft soft-blue font-12" id="crmHeaderCountBadge">
						<?php echo $totalPeriodServices; ?> Servis
					</span>
				</div>
				<div class="d-flex align-items-center flex-wrap" style="gap: 8px;">
					<!-- Görünüm Seçici (Tek Satır veya Aylık Takvim - İkisi Aynı Anda Gelmez) -->
					<div class="crm-view-switcher-group" role="group" aria-label="Görünüm Seçimi">
						<button type="button" class="btn-view-toggle active" id="btnViewSingleRow" title="15 Günlük Tek Sıra Kolon Görünümü">
							<i class="fa fa-columns"></i> 15 Günlük Liste
						</button>
						<button type="button" class="btn-view-toggle" id="btnViewMonthCal" title="Aylık Takvim Görünümü">
							<i class="fa fa-calendar-o"></i> Aylık Takvim
						</button>
					</div>

					<!-- Servisleri Genişlet Butonu (Sadece Aylık Takvimde Görünür) -->
					<button type="button" class="btn-toggle-expand d-none" id="btnToggleExpandServices" title="Tüm servisleri açık göster / kompakt takvim moduna dön">
						<i class="fa fa-arrows-v"></i> <span id="btnExpandText">Servisleri Genişlet</span>
					</button>

					<!-- Lejant -->
					<div class="d-none d-md-flex align-items-center" style="gap: 6px; font-size: 11px;">
						<span class="crm-badge-soft soft-amber"><i class="fa fa-circle"></i> Bekliyor</span>
						<span class="crm-badge-soft soft-blue"><i class="fa fa-circle"></i> Çalışıyor</span>
						<span class="crm-badge-soft soft-emerald"><i class="fa fa-circle"></i> Tamamlandı</span>
					</div>

					<a href="index.php?p=service/list" class="crm-card-link ml-1">Servis Listesi <i class="fa fa-arrow-right"></i></a>
				</div>
			</div>
			<div class="crm-card-body p-3 position-relative">
				
				<!-- GÖRÜNÜM 1: 15 GÜNLÜK TEK SIRA KOLON LİSTESİ -->
				<div id="crmSingleRowWrapper" class="crm-view-container position-relative">
					<!-- Sol Dikey Orta Buton -->
					<button type="button" class="crm-timeline-nav-btn btn-nav-left" id="crmTimelinePrev" title="Önceki Günler" aria-label="Önceki Günler">
						<i class="fa fa-chevron-left"></i>
					</button>

					<div class="crm-timeline-wrapper" id="crmTimelineScroll">
						<div class="crm-timeline-grid">
							<?php foreach ($planningDays as $day) : 
								$currentDateStr = $day['dateStr'];
								$dailyServices = $day['services'];
								$isToday = $day['isToday'];
							?>
								<div class="crm-day-column <?php echo $isToday ? 'is-today' : ''; ?>" data-column-date="<?php echo $currentDateStr; ?>" id="crmCol_<?php echo $currentDateStr; ?>">
									<div class="crm-day-header">
										<div>
											<div class="crm-day-title">
												<?php echo $day['dayTr']; ?>
												<?php if ($isToday) : ?>
													<span class="crm-badge-soft soft-blue ml-1" style="font-size: 9px; padding: 1px 5px;">Bugün</span>
												<?php endif; ?>
											</div>
											<div class="crm-day-date"><?php echo $day['displayDate']; ?></div>
										</div>
										<span class="badge badge-pill <?php echo count($dailyServices) > 0 ? 'badge-primary' : 'badge-light text-muted'; ?>" style="font-size: 11px;">
											<?php echo count($dailyServices); ?>
										</span>
									</div>
									<div class="crm-day-body">
										<?php if (empty($dailyServices)) : ?>
											<div class="crm-service-empty">
												<i class="fa fa-calendar-check-o d-block mb-1 font-16 text-muted" style="opacity: 0.5;"></i>
												Kayıt Yok
											</div>
										<?php else : ?>
												<?php foreach ($dailyServices as $item) : 
													$statusObj = $services->getServiceBackColour($item->pstatu);
													$statusColor = !empty($statusObj->colour) ? $statusObj->colour : '#3b82f6';
													$statusBg = $statusColor . '14';
													$statusBorder = $statusColor . '35';
													
													$itemDateFormatted = !empty($item->psecond_date) ? (new DateTime($item->psecond_date))->format('Y-m-d') : null;
													$isSecondary = ($itemDateFormatted === $currentDateStr);
												?>
													<a href="index.php?p=service/list&id=<?php echo $item->id; ?>" class="crm-service-item" style="background: <?php echo $statusBg; ?>; border-color: <?php echo $statusBorder; ?>; border-left: 4px solid <?php echo $statusColor; ?>; <?php echo $isSecondary ? 'box-shadow: 0 0 0 1px #8b5cf6;' : ''; ?>">
														<div class="d-flex justify-content-between align-items-center">
															<span class="crm-service-num" style="color: <?php echo $statusColor; ?>;"><?php echo htmlspecialchars($item->service_number, ENT_QUOTES, 'UTF-8'); ?></span>
															<?php if (!empty($item->title)) : ?>
																<span class="crm-badge-soft" style="background: <?php echo $statusColor; ?>22; color: <?php echo $statusColor; ?>; border: 1px solid <?php echo $statusColor; ?>40; font-size: 10px; padding: 1px 4px; max-width: 90px; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;">
																	<?php echo htmlspecialchars($item->title, ENT_QUOTES, 'UTF-8'); ?>
																</span>
															<?php endif; ?>
														</div>
														<div class="crm-service-company" title="<?php echo htmlspecialchars($item->firma_adi, ENT_QUOTES, 'UTF-8'); ?>">
															<?php echo htmlspecialchars(shorted($item->firma_adi, 28), ENT_QUOTES, 'UTF-8'); ?>
														</div>
														<?php if (!empty($item->pauthors)) : ?>
															<div class="crm-service-meta">
																<i class="fa fa-user-o"></i>
																<?php
																$authorList = explode('|', $item->pauthors);
																$authorNames = [];
																foreach ($authorList as $authId) {
																	$name = getUsername($authId);
																	if (!empty($name)) {
																		$authorNames[] = $name;
																	}
																}
																echo htmlspecialchars(shorted(implode(', ', $authorNames), 24), ENT_QUOTES, 'UTF-8');
																?>
															</div>
														<?php endif; ?>
													</a>
												<?php endforeach; ?>
										<?php endif; ?>
									</div>
								</div>
							<?php endforeach; ?>
						</div>
					</div>

					<!-- Sağ Dikey Orta Buton -->
					<button type="button" class="crm-timeline-nav-btn btn-nav-right" id="crmTimelineNext" title="Sonraki Günler" aria-label="Sonraki Günler">
						<i class="fa fa-chevron-right"></i>
					</button>
				</div>

				<!-- GÖRÜNÜM 2: AYLIK TAKVİM (RESİMDEKİ TASARIM) -->
				<div id="crmMonthCalWrapper" class="crm-view-container d-none">
					<div class="crm-month-calendar-layout" id="crmMonthCalLayout">
						<!-- Sol Dikey Ay & Yıl Bloğu (Görseldeki gibi) -->
						<div class="crm-month-cal-sidebar">
							<div class="crm-month-cal-year-circle">(<?php echo $currentCalYear; ?>)</div>
							<div class="crm-month-cal-v-month"><?php echo mb_strtoupper($currentCalMonthName, 'UTF-8'); ?></div>
							<div class="crm-month-cal-side-summary"><?php echo $totalMonthServices; ?> Servis</div>
						</div>

						<!-- Sağ Takvim Ana Alanı -->
						<div class="crm-month-cal-main">
							<!-- 7 Günlük Üst Başlık Şeridi -->
							<div class="crm-month-cal-header-row">
								<div class="crm-month-cal-header-cell">Pazartesi</div>
								<div class="crm-month-cal-header-cell">Salı</div>
								<div class="crm-month-cal-header-cell">Çarşamba</div>
								<div class="crm-month-cal-header-cell">Perşembe</div>
								<div class="crm-month-cal-header-cell">Cuma</div>
								<div class="crm-month-cal-header-cell">Cumartesi</div>
								<div class="crm-month-cal-header-cell">Pazar</div>
							</div>

							<!-- Günler Izgarası (Grid) -->
							<div class="crm-month-cal-grid">
								<?php
								// Ayın ilk gününden önceki boşluk hücreleri
								$emptyLeading = $firstDayWeekday - 1;
								for ($k = 0; $k < $emptyLeading; $k++) {
									echo '<div class="crm-month-cal-cell is-other-month"></div>';
								}

								// Ayın günleri
								for ($d = 1; $d <= $daysInMonth; $d++) {
									$dayData = $monthDaysData[$d];
									$dServices = $dayData['services'];
									$isToday = $dayData['isToday'];
									?>
									<div class="crm-month-cal-cell <?php echo $isToday ? 'is-today' : ''; ?>">
										<div class="crm-month-cal-day-num">
											<span class="crm-cal-num-badge"><?php echo $d; ?></span>
											<div class="d-flex align-items-center" style="gap: 4px;">
												<?php if ($isToday) : ?>
													<span class="crm-month-cal-today-pill">Bugün</span>
												<?php endif; ?>
												<?php if (count($dServices) > 0) : ?>
													<span class="crm-cal-count-pill" 
														  data-date="<?php echo $dayData['dateStr']; ?>" 
														  data-day-tr="<?php echo $dayData['dayTr']; ?>" 
														  data-display-date="<?php echo $dayData['displayDate']; ?>" 
														  title="Bu günün servislerini görüntüle">
														<i class="fa fa-wrench"></i> <?php echo count($dServices); ?> Servis
													</span>
												<?php endif; ?>
											</div>
										</div>
										<div class="crm-month-cal-services-list">
											<?php if (!empty($dServices)) : ?>
												<?php foreach ($dServices as $sItem) : 
													$statusObj = $services->getServiceBackColour($sItem->pstatu);
													$statusColor = !empty($statusObj->colour) ? $statusObj->colour : '#3b82f6';
													$statusBg = $statusColor . '15';
													$statusBorder = $statusColor . '35';
												?>
													<a href="index.php?p=service/list&id=<?php echo $sItem->id; ?>" 
													   class="crm-month-cal-service-badge" 
													   style="background: <?php echo $statusBg; ?>; border-color: <?php echo $statusBorder; ?>; border-left: 3.5px solid <?php echo $statusColor; ?>;" 
													   title="<?php echo htmlspecialchars($sItem->service_number . ' - ' . $sItem->firma_adi, ENT_QUOTES, 'UTF-8'); ?>">
														<span class="crm-cal-s-num" style="color: <?php echo $statusColor; ?>;"><?php echo htmlspecialchars($sItem->service_number, ENT_QUOTES, 'UTF-8'); ?></span>
														<span class="crm-cal-s-company"><?php echo htmlspecialchars(shorted($sItem->firma_adi, 18), ENT_QUOTES, 'UTF-8'); ?></span>
													</a>
												<?php endforeach; ?>
											<?php endif; ?>
										</div>
									</div>
									<?php
								}

								// Izgarayı 7'nin katına tamamlamak için bitiş boşlukları
								$totalCells = $emptyLeading + $daysInMonth;
								$trailingEmpty = (7 - ($totalCells % 7)) % 7;
								for ($k = 0; $k < $trailingEmpty; $k++) {
									echo '<div class="crm-month-cal-cell is-other-month"></div>';
								}
								?>
							</div>
						</div>
					</div>
				</div>

				<!-- Sayfa açılmadan önce kayıtlı tercihleri anında uygula (Flicker / zıplama önleme) -->
				<script>
				(function() {
					try {
						var savedView = localStorage.getItem('crm_service_board_view');
						if (savedView === 'month_cal') {
							var singleWrap = document.getElementById('crmSingleRowWrapper');
							var monthWrap = document.getElementById('crmMonthCalWrapper');
							var btnSR = document.getElementById('btnViewSingleRow');
							var btnMC = document.getElementById('btnViewMonthCal');
							var btnExp = document.getElementById('btnToggleExpandServices');
							if (singleWrap) singleWrap.classList.add('d-none');
							if (monthWrap) monthWrap.classList.remove('d-none');
							if (btnSR) btnSR.classList.remove('active');
							if (btnMC) btnMC.classList.add('active');
							if (btnExp) btnExp.classList.remove('d-none');
						}
						var savedExp = localStorage.getItem('crm_cal_expanded');
						if (savedExp === '1') {
							var calLayout = document.getElementById('crmMonthCalLayout');
							var btnExp = document.getElementById('btnToggleExpandServices');
							var btnExpText = document.getElementById('btnExpandText');
							if (calLayout) calLayout.classList.add('crm-cal-expanded');
							if (btnExp) btnExp.classList.add('is-expanded');
							if (btnExpText) btnExpText.textContent = 'Kompakt Takvim';
						}
					} catch(e) {}
				})();
				</script>

			</div>
		</div>

		<!-- 4. MODULAR FEEDS GRID (SON TEKLİFLER & SON SERVİSLER) -->
		<?php if (permtrue('offerview')) : ?>
			<div class="crm-two-col-grid mb-4">
				<!-- Son Teklifler -->
				<div class="crm-card h-100 mb-0">
					<div class="crm-card-header">
						<h3 class="crm-card-title">
							<i class="fa fa-file-text-o text-success"></i> Son Teklifler
						</h3>
						<a href="index.php?p=offers/list" class="crm-card-link">Tüm Teklifler <i class="fa fa-arrow-right"></i></a>
					</div>
					<div class="crm-card-body p-3">
						<div class="crm-feed-list">
							<?php
							$latestOffers = $ac->prepare('SELECT o.*, c.company as customer_company 
														 FROM offers o 
														 LEFT JOIN customers c ON o.cid = c.id 
														 ORDER BY o.id DESC LIMIT 4');
							$latestOffers->execute();
							$hasOffers = false;
							while ($offer = $latestOffers->fetch(PDO::FETCH_ASSOC)) {
								$hasOffers = true;
								$isWon = ($offer['statu'] == 2);
								?>
								<a href="index.php?p=offers/offer-manage&id=<?php echo $offer['id']; ?>" class="crm-feed-item border-left-accent-emerald">
									<div style="flex: 1; min-width: 0; padding-right: 12px;">
										<div class="crm-feed-title text-truncate">
											<?php echo htmlspecialchars($offer['customer_company'] ?: 'Müşteri Belirtilmemiş', ENT_QUOTES, 'UTF-8'); ?>
										</div>
										<div class="crm-feed-subtitle">
											<span><i class="fa fa-hashtag mr-1"></i><?php echo htmlspecialchars($offer['offerNumber'] ?: 'TK-' . $offer['id'], ENT_QUOTES, 'UTF-8'); ?></span>
											<span>•</span>
											<span><i class="fa fa-calendar mr-1"></i><?php echo htmlspecialchars($offer['reg_date'] ?: date('d.m.Y', strtotime($offer['created_at'] ?? 'now')), ENT_QUOTES, 'UTF-8'); ?></span>
										</div>
									</div>
									<div class="text-right flex-shrink-0">
										<div class="crm-feed-amount text-success">
											<?php echo tlFormat($offer['total_price']); ?>
										</div>
										<span class="crm-badge-soft <?php echo $isWon ? 'soft-emerald' : 'soft-amber'; ?> mt-1">
											<?php echo $isWon ? 'Kazanıldı' : 'Bekliyor'; ?>
										</span>
									</div>
								</a>
							<?php } 
							if (!$hasOffers) {
								echo '<div class="text-center py-4 text-muted font-13">Henüz teklif kaydı bulunmuyor.</div>';
							}
							?>
						</div>
					</div>
				</div>

				<!-- Son Eklenen Servisler -->
				<div class="crm-card h-100 mb-0">
					<div class="crm-card-header">
						<h3 class="crm-card-title">
							<i class="fa fa-wrench text-primary"></i> Son Eklenen Servisler
						</h3>
						<a href="index.php?p=service/list" class="crm-card-link">Tüm Servisler <i class="fa fa-arrow-right"></i></a>
					</div>
					<div class="crm-card-body p-3">
						<div class="crm-feed-list">
							<?php
							$latestProjects = $ac->prepare('SELECT p.*, c.company as customer_company, u.title as service_type_title, st.title as status_title, st.colour as status_colour
															FROM projects p 
															LEFT JOIN customers c ON p.pcid = c.id 
															LEFT JOIN units u ON p.servicestype = u.id 
															LEFT JOIN units st ON p.pstatu = st.id
															ORDER BY p.id DESC LIMIT 4');
							$latestProjects->execute();
							$hasProjects = false;
							while ($proj = $latestProjects->fetch(PDO::FETCH_ASSOC)) {
								$hasProjects = true;
								$stColour = !empty($proj['status_colour']) ? $proj['status_colour'] : '#3b82f6';
								?>
								<a href="index.php?p=service/list&id=<?php echo $proj['id']; ?>" class="crm-feed-item border-left-accent-blue">
									<div style="flex: 1; min-width: 0; padding-right: 12px;">
										<div class="crm-feed-title text-truncate">
											<?php echo htmlspecialchars($proj['customer_company'] ?: 'Müşteri Belirtilmemiş', ENT_QUOTES, 'UTF-8'); ?>
										</div>
										<div class="crm-feed-subtitle">
											<span class="text-primary weight-600"><?php echo htmlspecialchars($proj['service_number'], ENT_QUOTES, 'UTF-8'); ?></span>
											<span>•</span>
											<span><?php echo htmlspecialchars($proj['service_type_title'] ?: 'Genel Servis', ENT_QUOTES, 'UTF-8'); ?></span>
										</div>
									</div>
									<div class="text-right flex-shrink-0">
										<span class="crm-badge-soft" style="background: <?php echo $stColour; ?>18; color: <?php echo $stColour; ?>; border: 1px solid <?php echo $stColour; ?>30;">
											<?php echo htmlspecialchars($proj['status_title'] ?: 'Durum Belirtilmemiş', ENT_QUOTES, 'UTF-8'); ?>
										</span>
										<div class="text-muted mt-1" style="font-size: 11px;">
											<i class="fa fa-calendar mr-1"></i><?php echo htmlspecialchars($proj['pstart_date'] ?: '-', ENT_QUOTES, 'UTF-8'); ?>
										</div>
									</div>
								</a>
							<?php } 
							if (!$hasProjects) {
								echo '<div class="text-center py-4 text-muted font-13">Henüz servis kaydı bulunmuyor.</div>';
							}
							?>
						</div>
					</div>
				</div>
			</div>
		<?php endif; ?>

		<!-- 5. EKİP & YAPILACAKLAR (TO-DO) LİSTESİ -->
		<div class="crm-two-col-grid">
			<!-- Ekip Üyeleri ve Görev Durumu -->
			<div class="crm-card h-100 mb-0">
				<div class="crm-card-header">
					<h3 class="crm-card-title">
						<i class="fa fa-users text-primary"></i> Ekip ve Operasyon Durumu
					</h3>
					<a href="index.php?p=all-users" class="crm-card-link">Tüm Kullanıcılar <i class="fa fa-arrow-right"></i></a>
				</div>
				<div class="crm-card-body p-0">
					<div class="table-responsive">
						<table class="crm-team-table">
							<thead>
								<tr>
									<th>Kullanıcı / Departman</th>
									<th class="text-center">Aktif / Toplam Görev</th>
									<th class="text-right">Durum</th>
								</tr>
							</thead>
							<tbody>
								<?php
								$teamQuery = $ac->query('SELECT u.id, u.username, u.Unvan, p.p_title as role_title 
														FROM users u 
														LEFT JOIN perms p ON u.permission = p.id 
														WHERE u.statu = 1 
														ORDER BY u.id ASC LIMIT 6');
								while ($member = $teamQuery->fetch(PDO::FETCH_ASSOC)) {
									$mActive = $ac->prepare('SELECT COUNT(*) FROM missions WHERE authors = ? AND statu = 0');
									$mActive->execute([$member['id']]);
									$activeMissions = (int) $mActive->fetchColumn();

									$mTotal = $ac->prepare('SELECT COUNT(*) FROM missions WHERE authors = ?');
									$mTotal->execute([$member['id']]);
									$totalMissions = (int) $mTotal->fetchColumn();

									$initials = strtoupper(mb_substr($member['username'], 0, 2, 'UTF-8'));
									?>
									<tr>
										<td>
											<div class="d-flex align-items-center" style="gap: 10px;">
												<div class="crm-avatar-initials"><?php echo $initials; ?></div>
												<div>
													<div class="weight-600 text-dark" style="font-size: 13px;"><?php echo htmlspecialchars($member['username'], ENT_QUOTES, 'UTF-8'); ?></div>
													<div class="text-muted" style="font-size: 11px;"><?php echo htmlspecialchars($member['role_title'] ?: ($member['Unvan'] ?: 'Ekip Üyesi'), ENT_QUOTES, 'UTF-8'); ?></div>
												</div>
											</div>
										</td>
										<td class="text-center">
											<span class="badge badge-pill badge-light px-2 py-1 font-12 weight-600" style="background: #f1f5f9; color: #475569;">
												<?php echo $activeMissions . ' / ' . $totalMissions; ?>
											</span>
										</td>
										<td class="text-right">
											<?php if ($activeMissions > 0) : ?>
												<span class="crm-badge-soft soft-amber"><i class="fa fa-clock-o"></i> Görevde</span>
											<?php else : ?>
												<span class="crm-badge-soft soft-emerald"><i class="fa fa-check"></i> Müsait</span>
											<?php endif; ?>
										</td>
									</tr>
								<?php } ?>
							</tbody>
						</table>
					</div>
				</div>
			</div>

			<!-- Yapılacaklar (To-Do) & Notlar -->
			<div class="crm-card h-100 mb-0">
				<div class="crm-card-header">
					<h3 class="crm-card-title">
						<i class="fa fa-check-square-o text-purple"></i> Yapılacaklar & Hatırlatıcılar
					</h3>
					<a href="index.php?p=tasks" class="crm-card-link">Tüm Görevler <i class="fa fa-arrow-right"></i></a>
				</div>
				<div class="crm-card-body p-3">
					<div class="crm-feed-list">
						<?php
						$todosListQuery = $ac->prepare('SELECT t.*, u.username as creator_name 
														FROM todolist t 
														LEFT JOIN users u ON t.creativer = u.id 
														WHERE t.okey = 0 
														ORDER BY t.id DESC LIMIT 4');
						$todosListQuery->execute();
						$hasTodos = false;
						while ($todo = $todosListQuery->fetch(PDO::FETCH_ASSOC)) {
							$hasTodos = true;
							?>
							<a href="index.php?p=task-edit&reg=true&id=<?php echo $todo['id']; ?>" class="crm-feed-item border-left-accent-purple">
								<div style="flex: 1; min-width: 0; padding-right: 12px;">
									<div class="crm-feed-title text-truncate">
										<?php echo htmlspecialchars($todo['title'], ENT_QUOTES, 'UTF-8'); ?>
									</div>
									<div class="crm-feed-subtitle">
										<span><i class="fa fa-user-circle mr-1"></i><?php echo htmlspecialchars($todo['creator_name'] ?: 'Sistem', ENT_QUOTES, 'UTF-8'); ?></span>
									</div>
								</div>
								<div class="text-right flex-shrink-0">
									<span class="crm-badge-soft soft-purple">
										<i class="fa fa-clock-o mr-1"></i><?php echo htmlspecialchars($todo['last_date'] ?: 'Tarihsiz', ENT_QUOTES, 'UTF-8'); ?>
									</span>
								</div>
							</a>
						<?php } 
						if (!$hasTodos) {
							echo '<div class="text-center py-4 text-muted font-13"><i class="fa fa-check-circle-o font-20 text-success d-block mb-1"></i>Tüm yapılacaklar tamamlandı!</div>';
						}
						?>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<!-- GÜNLÜK SERVİSLER DETAY MODAL'I -->
<div class="modal fade crm-modal-day-services" id="crmDailyServicesModal" tabindex="-1" role="dialog" aria-labelledby="crmDailyModalTitle" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header d-flex align-items-center justify-content-between">
				<div class="d-flex align-items-center" style="gap: 10px;">
					<h5 class="modal-title font-16 weight-700 text-dark m-0" id="crmDailyModalTitle">
						<i class="fa fa-calendar-check-o text-primary mr-1"></i> Servis Listesi
					</h5>
					<span class="badge badge-pill badge-primary font-12 px-2 py-1" id="crmDailyModalCount">0 Servis</span>
				</div>
				<button type="button" class="close btn-crm-modal-close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Kapat" style="outline: none; cursor: pointer;">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body" id="crmDailyModalBody">
				<!-- Dinamik render edilir -->
			</div>
			<div class="modal-footer bg-light py-2 px-3 border-top d-flex justify-content-between align-items-center">
				<?php if (permtrue('servicenew')) : ?>
					<a href="index.php?p=service-new" class="btn btn-sm btn-outline-primary" style="font-size: 12px; border-radius: 6px;">
						<i class="fa fa-plus-circle mr-1"></i> Yeni Servis Ekle
					</a>
				<?php else: ?>
					<span></span>
				<?php endif; ?>
				<button type="button" class="btn btn-sm btn-secondary btn-crm-modal-close" data-dismiss="modal" data-bs-dismiss="modal" style="font-size: 12px; border-radius: 6px; cursor: pointer;">Kapat</button>
			</div>
		</div>
	</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
	var monthDaysJson = <?php echo json_encode($monthDaysJson, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE); ?> || {};
	var btnSingleRow = document.getElementById('btnViewSingleRow');
	var btnMonthCal = document.getElementById('btnViewMonthCal');
	var btnExpand = document.getElementById('btnToggleExpandServices');
	var btnExpandText = document.getElementById('btnExpandText');
	var calLayout = document.getElementById('crmMonthCalLayout');
	var singleRowWrapper = document.getElementById('crmSingleRowWrapper');
	var monthCalWrapper = document.getElementById('crmMonthCalWrapper');
	var headerBadge = document.getElementById('crmHeaderCountBadge');

	var timeline = document.getElementById('crmTimelineScroll');
	var prevBtn = document.getElementById('crmTimelinePrev');
	var nextBtn = document.getElementById('crmTimelineNext');

	var modalEl = document.getElementById('crmDailyServicesModal');
	var modalTitle = document.getElementById('crmDailyModalTitle');
	var modalCount = document.getElementById('crmDailyModalCount');
	var modalBody = document.getElementById('crmDailyModalBody');

	var periodCountText = '<?php echo $totalPeriodServices; ?> Servis';
	var monthCountText = '<?php echo $totalMonthServices; ?> Servis';

	function escapeHtml(str) {
		if (!str) return '';
		return String(str)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;')
			.replace(/'/g, '&#039;');
	}

	function setView(viewMode) {
		if (viewMode === 'month_cal') {
			if (btnMonthCal) btnMonthCal.classList.add('active');
			if (btnSingleRow) btnSingleRow.classList.remove('active');
			if (monthCalWrapper) monthCalWrapper.classList.remove('d-none');
			if (singleRowWrapper) singleRowWrapper.classList.add('d-none');
			if (btnExpand) btnExpand.classList.remove('d-none');
			if (headerBadge) headerBadge.textContent = monthCountText;
			localStorage.setItem('crm_service_board_view', 'month_cal');
		} else {
			if (btnSingleRow) btnSingleRow.classList.add('active');
			if (btnMonthCal) btnMonthCal.classList.remove('active');
			if (singleRowWrapper) singleRowWrapper.classList.remove('d-none');
			if (monthCalWrapper) monthCalWrapper.classList.add('d-none');
			if (btnExpand) btnExpand.classList.add('d-none');
			if (headerBadge) headerBadge.textContent = periodCountText;
			localStorage.setItem('crm_service_board_view', 'single_row');

			// Tek satır kolon görünümünde bugünün kolonuna odaklan
			scrollToToday();
		}
	}

	function toggleExpandServices() {
		if (!calLayout) return;
		var isExpanded = calLayout.classList.toggle('crm-cal-expanded');
		if (btnExpand) btnExpand.classList.toggle('is-expanded', isExpanded);
		if (btnExpandText) btnExpandText.textContent = isExpanded ? 'Kompakt Takvim' : 'Servisleri Genişlet';
		localStorage.setItem('crm_cal_expanded', isExpanded ? '1' : '0');
	}

	if (btnExpand) {
		btnExpand.addEventListener('click', function(e) {
			e.preventDefault();
			toggleExpandServices();
		});
	}

	function scrollToToday() {
		if (timeline) {
			var todayCol = timeline.querySelector('.crm-day-column.is-today');
			if (todayCol) {
				setTimeout(function() {
					var scrollLeftPos = todayCol.offsetLeft - (timeline.clientWidth / 2) + (todayCol.clientWidth / 2);
					if (scrollLeftPos > 0) {
						timeline.scrollTo({ left: scrollLeftPos, behavior: 'smooth' });
					}
				}, 100);
			}
		}
	}

	if (btnSingleRow && btnMonthCal) {
		btnSingleRow.addEventListener('click', function(e) {
			e.preventDefault();
			setView('single_row');
		});

		btnMonthCal.addEventListener('click', function(e) {
			e.preventDefault();
			setView('month_cal');
		});
	}

	// Kolonlar Sol/Sağ Kaydırma Okları
	if (timeline && prevBtn && nextBtn) {
		var scrollAmount = 450;

		prevBtn.addEventListener('click', function(e) {
			e.preventDefault();
			timeline.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
		});

		nextBtn.addEventListener('click', function(e) {
			e.preventDefault();
			timeline.scrollBy({ left: scrollAmount, behavior: 'smooth' });
		});
	}

	// Modal Kapatma Fonksiyonu (jQuery + Native JS güvenilir fallback)
	function hideDailyModal() {
		if (typeof $ !== 'undefined' && $('#crmDailyServicesModal').length) {
			try {
				$('#crmDailyServicesModal').modal('hide');
			} catch (err) {}
		}
		if (modalEl) {
			modalEl.classList.remove('show');
			modalEl.style.display = 'none';
			document.body.classList.remove('modal-open');
			document.body.style.paddingRight = '';
			var backdrops = document.querySelectorAll('.modal-backdrop');
			backdrops.forEach(function(b) {
				b.remove();
			});
		}
	}

	// Kapat Butonları Dinleyicileri
	document.querySelectorAll('.btn-crm-modal-close, [data-dismiss="modal"], [data-bs-dismiss="modal"]').forEach(function(btn) {
		btn.addEventListener('click', function(e) {
			e.preventDefault();
			hideDailyModal();
		});
	});

	// Modal Dışına Tıklama ile Kapatma
	if (modalEl) {
		modalEl.addEventListener('click', function(e) {
			if (e.target === modalEl) {
				hideDailyModal();
			}
		});
	}

	// ESC Tuşu ile Kapatma
	document.addEventListener('keydown', function(e) {
		if (e.key === 'Escape' || e.keyCode === 27) {
			if (modalEl && (modalEl.classList.contains('show') || modalEl.style.display === 'block')) {
				hideDailyModal();
			}
		}
	});

	// Günlük Servis Sayısı Rozetine Tıklama -> Modalı Aç
	document.querySelectorAll('.crm-cal-count-pill').forEach(function(pill) {
		pill.addEventListener('click', function(e) {
			e.preventDefault();
			e.stopPropagation();

			var dateStr = this.getAttribute('data-date');
			var dayData = monthDaysJson[dateStr];

			if (!dayData) return;

			if (modalTitle) {
				modalTitle.innerHTML = '<i class="fa fa-calendar text-primary mr-2"></i>' + escapeHtml(dayData.dayTr) + ', ' + escapeHtml(dayData.displayDate) + (dayData.isToday ? ' <span class="crm-badge-soft soft-blue font-11 ml-1">Bugün</span>' : '');
			}

			if (modalCount) {
				modalCount.textContent = (dayData.count || 0) + ' Servis';
			}

			if (modalBody) {
				modalBody.innerHTML = '';

				if (!dayData.services || dayData.services.length === 0) {
					modalBody.innerHTML = '<div class="text-center py-5 text-muted font-13"><i class="fa fa-calendar-check-o font-30 d-block mb-2 text-muted" style="opacity: 0.5;"></i>Bu tarihte planlanmış servis bulunmuyor.</div>';
				} else {
					var listHtml = '<div class="row">';
					dayData.services.forEach(function(item) {
						var stCol = item.status_color || '#3b82f6';
						listHtml += `
							<div class="col-md-6 col-12 mb-3">
								<a href="index.php?p=service/list&id=${item.id}" class="crm-modal-service-card" style="background: ${escapeHtml(stCol)}14; border-color: ${escapeHtml(stCol)}35; border-left: 4px solid ${escapeHtml(stCol)};">
									<div class="d-flex justify-content-between align-items-center mb-2">
										<span class="crm-service-num" style="color: ${escapeHtml(stCol)}; font-weight: 700; font-size: 12px;">${escapeHtml(item.service_number)}</span>
										<span class="crm-badge-soft" style="background: ${escapeHtml(stCol)}22; color: ${escapeHtml(stCol)}; border: 1px solid ${escapeHtml(stCol)}40; font-size: 10px; padding: 2px 7px;">
											${escapeHtml(item.status_title)}
										</span>
									</div>
									<div class="crm-service-company font-13 weight-600 mb-2 text-dark" title="${escapeHtml(item.company)}">
										${escapeHtml(item.company)}
									</div>
									${item.title ? `<div class="text-muted font-11 mb-2"><i class="fa fa-tag text-primary mr-1"></i>${escapeHtml(item.title)}</div>` : ''}
									${item.authors ? `
										<div class="crm-service-meta pt-2 border-top" style="border-color: ${escapeHtml(stCol)}25 !important; font-size: 11px;">
											<i class="fa fa-user-o mr-1"></i> ${escapeHtml(item.authors)}
										</div>
									` : ''}
								</a>
							</div>
						`;
					});
					listHtml += '</div>';
					modalBody.innerHTML = listHtml;
				}
			}

			// Bootstrap modal göster
			if (typeof $ !== 'undefined' && $('#crmDailyServicesModal').length) {
				try {
					$('#crmDailyServicesModal').modal('show');
				} catch (err) {
					if (modalEl) {
						modalEl.classList.add('show');
						modalEl.style.display = 'block';
						document.body.classList.add('modal-open');
					}
				}
			} else if (modalEl) {
				modalEl.classList.add('show');
				modalEl.style.display = 'block';
				document.body.classList.add('modal-open');
			}
		});
	});

	// Genişletilmiş takvim tercihini yükle (Varsayılan: 0 - Kompakt)
	var savedExpanded = localStorage.getItem('crm_cal_expanded');
	if (savedExpanded === '1') {
		if (calLayout) calLayout.classList.add('crm-cal-expanded');
		if (btnExpand) btnExpand.classList.add('is-expanded');
		if (btnExpandText) btnExpandText.textContent = 'Kompakt Takvim';
	} else {
		if (calLayout) calLayout.classList.remove('crm-cal-expanded');
		if (btnExpand) btnExpand.classList.remove('is-expanded');
		if (btnExpandText) btnExpandText.textContent = 'Servisleri Genişlet';
	}

	// Kayıtlı görünüm tercihini yükle (Varsayılan: 15 günlük tek satır liste)
	var savedView = localStorage.getItem('crm_service_board_view') || 'single_row';
	setView(savedView);
});
</script>
