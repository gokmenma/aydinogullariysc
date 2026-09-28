<?php

use App\Helper\Security;
use App\Service\MailboxService;

if (!MailboxService::isSuperAdmin()) {
    http_response_code(403);
    echo '<div class="alert alert-danger">Bu modül yalnızca Süper Admin tarafından kullanılabilir.</div>';
    return;
}

$mailboxService = new MailboxService($ac);
$accounts = $mailboxService->accounts();
$accountId = (int) ($_GET['account'] ?? ($accounts[0]['id'] ?? 0));
$folder = ($_GET['folder'] ?? 'inbox') === 'sent' ? 'sent' : 'inbox';
$feedback = '';
$feedbackType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::checkCsrfToken()) {
        $feedback = 'Oturum doğrulaması başarısız oldu.';
        $feedbackType = 'danger';
    } else {
        try {
            $action = (string) ($_POST['action'] ?? '');
            $accountId = (int) ($_POST['account_id'] ?? 0);
            if ($action === 'sync') {
                $count = $mailboxService->syncAccount($accountId, 100);
                $feedback = $count > 0 ? "$count yeni mail alındı." : 'Posta kutusu güncel.';
            } elseif ($action === 'send') {
                $mailboxService->send($accountId, trim((string) ($_POST['to'] ?? '')), trim((string) ($_POST['subject'] ?? '')), trim((string) ($_POST['body'] ?? '')));
                $feedback = 'Mail başarıyla gönderildi.';
                $folder = 'sent';
            }
        } catch (Throwable $e) {
            $feedback = $e->getMessage();
            $feedbackType = 'danger';
        }
    }
}

$showRemoteImages = ($_GET['show_images'] ?? '') === '1';
$selected = !empty($_GET['message']) ? $mailboxService->message((int) $_GET['message'], $accountId, $showRemoteImages) : null;
$messages = $accountId > 0 ? $mailboxService->messages($accountId, $folder) : [];
$activeAccount = null;
foreach ($accounts as $account) {
    if ((int) $account['id'] === $accountId) {
        $activeAccount = $account;
        break;
    }
}
$unreadCount = count(array_filter($messages, static fn(array $message): bool => empty($message['is_read_local'])));
$formatMailDate = static function (?string $date): string {
    if (!$date) return '-';
    $timestamp = strtotime($date);
    if (!$timestamp) return $date;
    return date('Y-m-d', $timestamp) === date('Y-m-d') ? date('H:i', $timestamp) : date('d.m.Y', $timestamp);
};
$initials = static function (string $value): string {
    $value = trim($value);
    if ($value === '') return '?';
    $parts = preg_split('/\s+/u', $value) ?: [];
    $letters = mb_substr((string) ($parts[0] ?? ''), 0, 1, 'UTF-8');
    if (count($parts) > 1) $letters .= mb_substr((string) end($parts), 0, 1, 'UTF-8');
    return mb_strtoupper($letters, 'UTF-8');
};
$formatBytes = static function (int $bytes): string {
    if ($bytes < 1024) return $bytes . ' B';
    if ($bytes < 1048576) return number_format($bytes / 1024, 1, ',', '.') . ' KB';
    return number_format($bytes / 1048576, 1, ',', '.') . ' MB';
};
?>

<style>
.mailbox-page{--mail-border:#e6eaf0;--mail-muted:#64748b;--mail-bg:#f8fafc}
.mailbox-page .mail-hero,
.mailbox-page .mail-hero .header-content,
.mailbox-page .mail-hero .header-left,
.mailbox-page .mail-hero .header-title{overflow:visible!important;position:relative}
.mailbox-page .mail-hero{padding:18px 22px;z-index:30}
.mail-hero-meta{display:flex;align-items:center;gap:8px;margin-top:7px;flex-wrap:wrap}
.mail-status-dot{width:8px;height:8px;border-radius:50%;background:#10b981;box-shadow:0 0 0 4px rgba(16,185,129,.12);flex-shrink:0}
.mail-account-dropdown{position:relative;display:inline-block}
.mail-account-dropdown .mail-account-btn{display:inline-flex;align-items:center;gap:6px;padding:0;border:0;background:transparent;color:var(--mail-muted,#64748b);font-size:12px;cursor:pointer;box-shadow:none!important;line-height:1.4;text-decoration:none}
.mail-account-dropdown .mail-account-btn:hover,.mail-account-dropdown .mail-account-btn:focus,.mail-account-dropdown.show .mail-account-btn{color:var(--theme-primary,#2563eb);background:transparent;border:0;box-shadow:none!important}
.mail-account-dropdown .mail-account-address{color:#1e293b;font-weight:700}
.mail-account-dropdown .mail-account-btn:hover .mail-account-address,.mail-account-dropdown.show .mail-account-btn .mail-account-address{color:var(--theme-primary,#2563eb)}
.mail-account-dropdown .mail-account-btn i.fa-angle-down{font-size:12px;margin-left:4px;transition:transform .15s ease}
.mail-account-dropdown.show .mail-account-btn i.fa-angle-down{transform:rotate(180deg)}
.mail-account-dropdown .dropdown-menu{min-width:280px;padding:6px 0;margin-top:6px;border-radius:10px;border:1px solid var(--mail-border);box-shadow:0 12px 30px rgba(15,23,42,.15)!important;z-index:1060}
.mail-account-dropdown .dropdown-item{padding:8px 14px;font-size:12px;font-weight:600;color:#334155;transition:all .12s ease}
.mail-account-dropdown .dropdown-item:hover{background:#eef4ff;color:var(--theme-primary,#2563eb)}
.mail-account-dropdown .dropdown-item.active{background:var(--theme-primary,#2563eb);color:#fff}
.mailbox-shell{display:flex;flex-direction:row;height:calc(100vh - 290px);min-height:590px;max-height:calc(100vh - 290px);border:1px solid var(--mail-border);border-radius:14px;background:#fff;overflow:hidden;box-shadow:0 8px 28px rgba(15,23,42,.06);position:relative}
.mailbox-sidebar{width:220px;min-width:220px;max-width:220px;padding:18px 14px;border-right:1px solid var(--mail-border);background:var(--mail-bg);min-height:0;overflow-y:auto;flex-shrink:0;transition:width .2s cubic-bezier(.4,0,.2,1),min-width .2s cubic-bezier(.4,0,.2,1),max-width .2s cubic-bezier(.4,0,.2,1),padding .2s cubic-bezier(.4,0,.2,1),opacity .15s ease}
.mailbox-shell.sidebar-collapsed .mailbox-sidebar{width:0!important;min-width:0!important;max-width:0!important;padding:0!important;border-right:0!important;opacity:0!important;pointer-events:none;overflow:hidden}
.btn-mail-sidebar-toggle{width:30px;height:30px;padding:0;display:inline-flex;align-items:center;justify-content:center;border-radius:8px;border:1px solid var(--mail-border);background:#fff;color:#64748b;font-size:13px;cursor:pointer;transition:all .15s ease;flex-shrink:0}
.btn-mail-sidebar-toggle:hover{color:var(--theme-primary,#2563eb);background:#eef4ff;border-color:#cbd5e1}
.mail-compose-btn{width:100%;height:42px;border:0;border-radius:10px;background:var(--theme-primary,#2563eb);color:#fff;font-weight:700;font-size:13px;box-shadow:0 5px 14px rgba(37,99,235,.2)}
.mail-compose-btn:hover{filter:brightness(.95)}
.mail-account-select{height:38px!important;font-size:12px!important;border-color:var(--mail-border)!important;background:#fff!important}
.mail-folder-list{display:flex;flex-direction:column;gap:5px;margin-top:18px}
.mail-folder-link{display:flex;align-items:center;gap:10px;padding:10px 11px;border-radius:9px;color:#475569;font-size:13px;font-weight:600}
.mail-folder-link:hover{color:var(--theme-primary,#2563eb);background:#eef4ff}
.mail-folder-link.active{color:var(--theme-primary,#2563eb);background:#eaf1ff}
.mail-folder-link i{width:18px;text-align:center;font-size:15px}
.mail-folder-count{margin-left:auto;min-width:23px;padding:2px 7px;border-radius:12px;background:#fff;color:#64748b;text-align:center;font-size:11px}
.mail-folder-link.active .mail-folder-count{background:var(--theme-primary,#2563eb);color:#fff}
.mail-sync-card{margin-top:22px;padding:12px;border:1px solid var(--mail-border);border-radius:10px;background:#fff}
.mail-sync-card strong{display:block;font-size:11px;color:#334155}
.mail-sync-card span{display:block;margin-top:3px;font-size:10px;color:#94a3b8}
.mail-sync-button{margin-top:10px;width:100%;border:1px solid var(--mail-border);border-radius:8px;background:#fff;color:#475569;padding:7px;font-size:11px;font-weight:700}
.mail-list-panel{width:var(--mail-list-w,380px);min-width:260px;max-width:750px;display:flex;flex-direction:column;border-right:1px solid var(--mail-border);min-height:0;height:100%;overflow:hidden;flex-shrink:0}
.mail-list-toolbar{padding:14px 15px;border-bottom:1px solid var(--mail-border);flex-shrink:0}
.mail-list-title{display:flex;align-items:center;justify-content:space-between;margin-bottom:11px}
.mail-list-title h5{margin:0;color:#172033;font-size:15px;font-weight:800}
.mail-list-title span{font-size:11px;color:#94a3b8}
.mail-search{position:relative}
.mail-search i{position:absolute;left:12px;top:11px;color:#94a3b8}
.mail-search input{height:38px;padding-left:34px;border:1px solid var(--mail-border);border-radius:9px;background:var(--mail-bg);font-size:12px}
.mail-list{overflow-y:auto;overflow-x:hidden;flex:1 1 0%;min-height:0;-webkit-overflow-scrolling:touch}
.mail-list-empty{display:flex;align-items:center;justify-content:center;height:100%;color:#94a3b8;font-size:13px}
.mail-item{position:relative;display:grid;grid-template-columns:38px minmax(0,1fr);gap:11px;padding:14px 15px;border-bottom:1px solid #edf0f4;color:inherit;background:#fff;transition:.16s ease}
.mail-item:hover{background:#f8fbff;color:inherit}
.mail-item.active{background:#edf4ff}
.mail-item.unread:before{content:"";position:absolute;left:0;top:0;bottom:0;width:3px;background:var(--theme-primary,#2563eb)}
.mail-avatar{width:38px;height:38px;border-radius:11px;display:flex;align-items:center;justify-content:center;background:#e8eef8;color:#475569;font-size:12px;font-weight:800}
.mail-item.active .mail-avatar{background:var(--theme-primary,#2563eb);color:#fff}
.mail-item-head{display:flex;align-items:center;gap:8px}
.mail-sender{overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#293548;font-size:12px;font-weight:700}
.mail-date{margin-left:auto;white-space:nowrap;color:#94a3b8;font-size:10px}
.mail-subject{display:flex;align-items:center;gap:6px;margin-top:4px;color:#536174;font-size:12px;font-weight:600;overflow:hidden}
.mail-subject span{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.mail-item.unread .mail-sender,.mail-item.unread .mail-subject{color:#172033;font-weight:800}
@keyframes mailNewPulse{0%{background:#e0e7ff;transform:translateY(-6px);opacity:0}60%{background:#dbeafe}100%{background:#fff;transform:translateY(0);opacity:1}}
.mail-item.mail-item-new-pulse{animation:mailNewPulse 1.2s ease-out}
.mail-resizer{width:9px;margin-left:-5px;margin-right:-4px;z-index:20;cursor:col-resize;position:relative;user-select:none;-webkit-user-select:none;display:flex;align-items:center;justify-content:center;background:transparent;flex-shrink:0}
.mail-resizer-line{width:2px;height:32px;border-radius:2px;background:#cbd5e1;transition:all .15s ease}
.mail-resizer:hover .mail-resizer-line,.mail-resizer.active .mail-resizer-line,body.is-resizing-mailbox .mail-resizer .mail-resizer-line{height:100%;width:3px;background:var(--theme-primary,#2563eb)}
.mail-reader{flex:1 1 0%;min-width:320px;display:flex;flex-direction:column;min-height:0;height:100%;overflow:hidden;background:#fff}
.mail-reader-empty{display:flex;flex:1 1 0%;min-height:0;flex-direction:column;align-items:center;justify-content:center;text-align:center;color:#94a3b8;padding:30px}
.mail-reader-empty .empty-icon{width:66px;height:66px;border-radius:20px;background:#f1f5f9;display:flex;align-items:center;justify-content:center;margin-bottom:15px;color:#64748b;font-size:26px}
.mail-reader-empty h5{color:#334155;font-size:15px}
.mail-reader-header{padding:20px 22px 16px;border-bottom:1px solid var(--mail-border);flex-shrink:0}
.mail-reader-header h4{margin:0 0 14px;color:#172033;font-size:18px;line-height:1.4;font-weight:800}
.mail-reader-person{display:flex;align-items:center;gap:11px}
.mail-reader-person .mail-avatar{width:42px;height:42px}
.mail-reader-address{min-width:0}
.mail-reader-address strong{display:block;overflow:hidden;text-overflow:ellipsis;color:#334155;font-size:12px}
.mail-reader-address span{display:block;margin-top:2px;color:#94a3b8;font-size:10px}
.mail-reader-time{margin-left:auto;color:#94a3b8;font-size:11px;white-space:nowrap}
.mail-reader-body{flex:1 1 0%;min-height:0;overflow-y:auto;overflow-x:hidden;-webkit-overflow-scrolling:touch;padding:24px;color:#374151;font-family:Arial,sans-serif;font-size:13px;line-height:1.75;white-space:pre-wrap;overflow-wrap:anywhere}
.mail-reader-body.is-html{white-space:normal}.mail-reader-body.is-html img{max-width:100%;height:auto}.mail-reader-body.is-html table{max-width:100%}
.mail-reader-frame{flex:1 1 0%;min-height:0;width:100%;border:0;background:#fff}
.mail-remote-warning{display:flex;align-items:center;gap:10px;padding:10px 18px;border-bottom:1px solid #fde68a;background:#fffbeb;color:#92400e;font-size:11px;flex-shrink:0}.mail-remote-warning i{font-size:15px}.mail-remote-warning span{flex:1}.mail-remote-warning button{border:1px solid #f59e0b;border-radius:7px;background:#fff;color:#92400e;padding:5px 9px;font-size:10px;font-weight:800}
.mail-reader-actions{display:flex;gap:8px;padding:13px 20px;border-top:1px solid var(--mail-border);background:#fbfcfe;flex-shrink:0}
.mail-reader-actions .btn{border-radius:8px;font-size:11px;font-weight:700}
.mail-attachments{padding:0 22px 18px;flex-shrink:0}
.mail-attachments-title{margin-bottom:8px;color:#64748b;font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.04em}
.mail-attachment-list{display:flex;flex-wrap:wrap;gap:8px}
.mail-attachment{display:flex;align-items:center;gap:9px;min-width:210px;max-width:310px;padding:9px 11px;border:1px solid var(--mail-border);border-radius:9px;background:#f8fafc;color:#334155}
.mail-attachment:hover{border-color:var(--theme-primary,#2563eb);color:var(--theme-primary,#2563eb)}
.mail-attachment i{font-size:17px}.mail-attachment-info{min-width:0}.mail-attachment-name{display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:11px;font-weight:700}.mail-attachment-size{display:block;color:#94a3b8;font-size:9px}
.compose-modal .modal-content{border:0;border-radius:14px;overflow:hidden;box-shadow:0 24px 70px rgba(15,23,42,.25)}
.compose-modal .modal-header{background:#f8fafc;border-bottom:1px solid var(--mail-border);padding:17px 20px}
.compose-modal .modal-title{font-size:16px;font-weight:800;color:#172033}
.compose-modal label{font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.04em}
.compose-modal .form-control{border-color:#e2e8f0;border-radius:8px;font-size:13px}
.mail-list, .mail-reader-body, .mailbox-sidebar{scrollbar-width:thin;scrollbar-color:#cbd5e1 transparent}
.mail-list::-webkit-scrollbar, .mail-reader-body::-webkit-scrollbar, .mailbox-sidebar::-webkit-scrollbar{width:6px;height:6px}
.mail-list::-webkit-scrollbar-track, .mail-reader-body::-webkit-scrollbar-track, .mailbox-sidebar::-webkit-scrollbar-track{background:transparent}
.mail-list::-webkit-scrollbar-thumb, .mail-reader-body::-webkit-scrollbar-thumb, .mailbox-sidebar::-webkit-scrollbar-thumb{background:#cbd5e1;border-radius:4px}
.mail-list::-webkit-scrollbar-thumb:hover, .mail-reader-body::-webkit-scrollbar-thumb:hover, .mailbox-sidebar::-webkit-scrollbar-thumb:hover{background:#94a3b8}
body.is-resizing-mailbox{cursor:col-resize!important;user-select:none!important;-webkit-user-select:none!important}
body.is-resizing-mailbox *{user-select:none!important;-webkit-user-select:none!important}
.dark-mode .mailbox-shell,.dark-mode .mail-list-panel,.dark-mode .mail-reader,.dark-mode .mail-item,.dark-mode .mail-sync-card,.dark-mode .mailbox-sidebar{background:#18202f;color:#d7deea;border-color:#2b3547}
.dark-mode .mailbox-sidebar,.dark-mode .mail-list-toolbar,.dark-mode .mail-reader-actions{background:#151d2b}
.dark-mode .mail-item:hover,.dark-mode .mail-item.active{background:#202c40}
.dark-mode .mail-sender,.dark-mode .mail-item.unread .mail-sender,.dark-mode .mail-item.unread .mail-subject,.dark-mode .mail-reader-header h4,.dark-mode .mail-reader-address strong,.dark-mode .mail-list-title h5{color:#edf2f7}
.dark-mode .mail-search input,.dark-mode .mail-sync-button{background:#111827!important;color:#d7deea!important;border-color:#344054!important}
.dark-mode .mail-account-dropdown .mail-account-btn{background:#1e293b;border-color:#334155;color:#f1f5f9}
.dark-mode .mail-account-dropdown .mail-account-btn:hover,.dark-mode .mail-account-dropdown.show .mail-account-btn{background:#334155;border-color:#475569}
.dark-mode .mail-status-badge{background:rgba(16,185,129,.15);border-color:rgba(16,185,129,.3);color:#34d399}
.dark-mode .mail-account-dropdown .dropdown-menu{background:#18202f;border-color:#2b3547}
.dark-mode .mail-account-dropdown .dropdown-item{color:#cbd5e1}
.dark-mode .mail-account-dropdown .dropdown-item:hover{background:#202c40;color:#60a5fa}
.dark-mode .mail-account-dropdown .dropdown-item.active{background:var(--theme-primary,#2563eb);color:#fff}
.dark-mode .btn-mail-sidebar-toggle{background:#18202f;color:#cbd5e1;border-color:#2b3547}
.dark-mode .btn-mail-sidebar-toggle:hover{background:#202c40;color:#60a5fa;border-color:#3b82f6}
.dark-mode .mail-resizer-line{background:#334155}
.dark-mode .mail-resizer:hover .mail-resizer-line,.dark-mode .mail-resizer.active .mail-resizer-line{background:#3b82f6}
.dark-mode .mail-reader-body{color:#d7deea}.dark-mode .mail-reader-frame{background:#fff}
.dark-mode .mail-item,.dark-mode .mail-list-toolbar,.dark-mode .mail-reader-header,.dark-mode .mail-reader-actions{border-color:#2b3547}
.dark-mode .mail-attachment{background:#111827;color:#d7deea;border-color:#344054}
.dark-mode .mail-list, .dark-mode .mail-reader-body, .dark-mode .mailbox-sidebar{scrollbar-color:#334155 transparent}
.dark-mode .mail-list::-webkit-scrollbar-thumb, .dark-mode .mail-reader-body::-webkit-scrollbar-thumb, .dark-mode .mailbox-sidebar::-webkit-scrollbar-thumb{background:#334155}
@media(max-width:991px){
    .mailbox-shell{flex-direction:column;height:auto;max-height:none;min-height:620px}
    .mailbox-sidebar{width:100%;min-width:100%;max-width:100%;border-right:0;border-bottom:1px solid var(--mail-border)}
    .mailbox-shell.sidebar-collapsed .mailbox-sidebar{width:100%!important;min-width:100%!important;max-width:100%!important;height:0;min-height:0;padding:0!important;border-bottom:0!important}
    .mail-list-panel{width:100%!important;min-width:100%!important;max-width:100%!important;border-right:0;border-bottom:1px solid var(--mail-border);height:420px}
    .mail-resizer{display:none}
    .mail-reader{min-height:430px;min-width:100%}
}
@media(max-width:650px){
    .mail-folder-list{flex-direction:row;margin-top:12px}
    .mail-folder-link{flex:1}
    .mail-sync-card{display:none}
    .mail-list-panel{height:500px}
    .mail-reader{min-height:400px}
    .mail-hero .header-content{gap:14px}
    .mail-hero .header-actions{width:100%}
    .mail-hero .header-actions button{width:100%}
}
</style>

<div class="mailbox-page">
    <div class="premium-header-card mail-hero animate-fade-in mb-3">
        <div class="header-content">
            <div class="header-left">
                <div class="header-icon"><i class="fa fa-envelope-open-o"></i></div>
                <div class="header-title">
                    <h4>Gelen / Giden Mail</h4>
                    <div class="mail-hero-meta">
                        <span class="mail-status-dot"></span>
                        <div class="dropdown mail-account-dropdown">
                            <button class="btn mail-account-btn" type="button" id="mailAccountDropdownBtn" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span class="mail-account-address"><?php echo htmlspecialchars((string) ($activeAccount['mail_address'] ?? 'Hesap Seçin'), ENT_QUOTES, 'UTF-8'); ?></span>
                                <span>bağlı ve eşitleniyor</span>
                                <i class="fa fa-angle-down"></i>
                            </button>
                            <div class="dropdown-menu shadow-sm" aria-labelledby="mailAccountDropdownBtn">
                                <div class="dropdown-header text-uppercase font-10 font-weight-bold text-muted">Genel Posta Hesapları</div>
                                <?php foreach ($accounts as $account): 
                                    $isActive = (int) $account['id'] === $accountId;
                                ?>
                                    <a class="dropdown-item d-flex align-items-center justify-content-between <?php echo $isActive ? 'active' : ''; ?>" href="gelen-giden-mail?account=<?php echo (int) $account['id']; ?>&folder=<?php echo $folder; ?>">
                                        <div class="d-flex align-items-center">
                                            <i class="fa fa-envelope-o mr-2 <?php echo $isActive ? 'text-white' : 'text-primary'; ?>"></i>
                                            <div>
                                                <div class="font-weight-bold"><?php echo htmlspecialchars($account['mail_address'], ENT_QUOTES, 'UTF-8'); ?></div>
                                                <?php if (!empty($account['description'])): ?>
                                                    <div class="<?php echo $isActive ? 'text-light' : 'text-muted'; ?>" style="font-size:10px;"><?php echo htmlspecialchars($account['description'], ENT_QUOTES, 'UTF-8'); ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <?php if ($isActive): ?>
                                            <i class="fa fa-check text-white ml-3 font-12"></i>
                                        <?php endif; ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="header-actions"><button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#composeMailModal"><i class="fa fa-pencil mr-1"></i> Yeni Mail</button></div>
        </div>
    </div>

    <?php if ($feedback !== ''): ?><div class="alert alert-<?php echo $feedbackType; ?> alert-dismissible fade show"><i class="fa <?php echo $feedbackType === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mr-2"></i><?php echo htmlspecialchars($feedback, ENT_QUOTES, 'UTF-8'); ?><button type="button" class="close" data-dismiss="alert">&times;</button></div><?php endif; ?>

    <div class="mailbox-shell animate-fade-in" id="mailboxShell">
        <aside class="mailbox-sidebar" id="mailboxSidebar">
            <button type="button" class="mail-compose-btn" data-toggle="modal" data-target="#composeMailModal"><i class="fa fa-pencil-square-o mr-2"></i>Yeni Mail Oluştur</button>
            <nav class="mail-folder-list">
                <a class="mail-folder-link <?php echo $folder === 'inbox' ? 'active' : ''; ?>" href="gelen-giden-mail?account=<?php echo $accountId; ?>&folder=inbox"><i class="fa fa-inbox"></i><span>Gelen Kutusu</span><?php if ($unreadCount > 0 && $folder === 'inbox'): ?><span class="mail-folder-count" id="mailUnreadCount"><?php echo $unreadCount; ?></span><?php endif; ?></a>
                <a class="mail-folder-link <?php echo $folder === 'sent' ? 'active' : ''; ?>" href="gelen-giden-mail?account=<?php echo $accountId; ?>&folder=sent"><i class="fa fa-paper-plane-o"></i><span>Gönderilenler</span></a>
            </nav>
            <div class="mail-sync-card">
                <strong><i class="fa fa-refresh mr-1 text-success"></i> Otomatik eşitleme açık</strong>
                <span id="mailLastSyncTime">Son kontrol: <?php echo htmlspecialchars((string) ($activeAccount['last_sync_at'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></span>
                <form id="mailSyncForm" method="post"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(Security::csrf(), ENT_QUOTES, 'UTF-8'); ?>"><input type="hidden" name="action" value="sync"><input type="hidden" name="account_id" value="<?php echo $accountId; ?>"><button type="submit" id="mailSyncButton" class="mail-sync-button"><i class="fa fa-refresh mr-1"></i> Şimdi Eşitle</button></form>
            </div>
        </aside>

        <section class="mail-list-panel" id="mailListPanel">
            <div class="mail-list-toolbar">
                <div class="mail-list-title">
                    <div class="d-flex align-items-center">
                        <button type="button" class="btn-mail-sidebar-toggle mr-2" id="toggleMailboxSidebar" title="Sol Menüyü Gizle / Göster">
                            <i class="fa fa-bars"></i>
                        </button>
                        <h5 class="mb-0"><?php echo $folder === 'sent' ? 'Gönderilenler' : 'Gelen Kutusu'; ?></h5>
                    </div>
                    <span id="mailListCountText"><?php echo count($messages); ?> ileti</span>
                </div>
                <div class="mail-search"><i class="fa fa-search"></i><input type="search" id="mailboxSearch" class="form-control" placeholder="Gönderen veya konuda ara..."></div>
            </div>
            <div class="mail-list" id="mailboxMessageList">
                <?php if (!$messages): ?><div class="mail-list-empty"><div><i class="fa fa-envelope-o mr-2"></i>Henüz mail bulunmuyor.</div></div><?php endif; ?>
                <?php foreach ($messages as $message):
                    $party = $folder === 'sent' ? (string) $message['to_addresses'] : (string) ($message['from_name'] ?: $message['from_address']);
                    $searchText = mb_strtolower($party . ' ' . $message['subject'], 'UTF-8');
                ?>
                    <a class="mail-item <?php echo !$message['is_read_local'] && $folder === 'inbox' ? 'unread' : ''; ?> <?php echo $selected && (int) $selected['id'] === (int) $message['id'] ? 'active' : ''; ?>" data-search="<?php echo htmlspecialchars($searchText, ENT_QUOTES, 'UTF-8'); ?>" data-message-id="<?php echo (int) $message['id']; ?>" href="gelen-giden-mail?account=<?php echo $accountId; ?>&folder=<?php echo $folder; ?>&message=<?php echo (int) $message['id']; ?>">
                        <span class="mail-avatar"><?php echo htmlspecialchars($initials($party), ENT_QUOTES, 'UTF-8'); ?></span>
                        <span class="min-w-0"><span class="mail-item-head"><span class="mail-sender"><?php echo htmlspecialchars($party, ENT_QUOTES, 'UTF-8'); ?></span><span class="mail-date"><?php echo htmlspecialchars($formatMailDate($message['received_at']), ENT_QUOTES, 'UTF-8'); ?></span></span><span class="mail-subject"><span><?php echo htmlspecialchars($message['subject'], ENT_QUOTES, 'UTF-8'); ?></span><?php if ($message['has_attachments']): ?><i class="fa fa-paperclip"></i><?php endif; ?></span></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>

        <div class="mail-resizer" id="mailListResizer" title="Sürükleyerek genişliği ayarlayın">
            <div class="mail-resizer-line"></div>
        </div>

        <section class="mail-reader" id="mailReaderPanel" data-account-id="<?php echo $accountId; ?>" data-folder="<?php echo htmlspecialchars($folder, ENT_QUOTES, 'UTF-8'); ?>">
            <?php if (!$selected): ?>
                <div class="mail-reader-empty"><div class="empty-icon"><i class="fa fa-envelope-open-o"></i></div><h5>Okumak için bir mail seçin</h5><p>Mail içeriği bu panelde görüntülenecek.</p></div>
            <?php else:
                $readerParty = $folder === 'sent' ? (string) $selected['to_addresses'] : (string) ($selected['from_name'] ?: $selected['from_address']);
            ?>
                <div class="mail-reader-header">
                    <h4><?php echo htmlspecialchars($selected['subject'], ENT_QUOTES, 'UTF-8'); ?></h4>
                    <div class="mail-reader-person"><span class="mail-avatar"><?php echo htmlspecialchars($initials($readerParty), ENT_QUOTES, 'UTF-8'); ?></span><div class="mail-reader-address"><strong><?php echo htmlspecialchars($readerParty, ENT_QUOTES, 'UTF-8'); ?></strong><span><?php echo htmlspecialchars($selected['from_address'] . ' → ' . $selected['to_addresses'], ENT_QUOTES, 'UTF-8'); ?></span></div><span class="mail-reader-time"><?php echo htmlspecialchars((string) $selected['received_at'], ENT_QUOTES, 'UTF-8'); ?></span></div>
                </div>
                <?php if (!$showRemoteImages && !empty($selected['remote_image_count'])): ?><div class="mail-remote-warning"><i class="fa fa-shield"></i><span><?php echo (int) $selected['remote_image_count']; ?> harici görsel gizlilik için engellendi.</span><button type="button" class="btn-show-remote-images" data-message-id="<?php echo (int) $selected['id']; ?>">Görselleri Göster</button></div><?php endif; ?>
                <?php
                    $initialMailContent = !empty($selected['safe_html'])
                        ? (string) $selected['safe_html']
                        : '<pre style="white-space:pre-wrap;font:13px/1.75 Arial,sans-serif;color:#374151">' . htmlspecialchars((string) ($selected['body_text'] ?: strip_tags((string) $selected['body_html'])), ENT_QUOTES, 'UTF-8') . '</pre>';
                    $initialMailDocument = '<!doctype html><html><head><meta charset="UTF-8"><meta http-equiv="Content-Security-Policy" content="default-src \'none\'; img-src data:; style-src \'unsafe-inline\'; font-src \'none\'; media-src \'none\'; connect-src \'none\'; frame-src \'none\'; form-action \'none\'; base-uri \'none\'"><style>html,body{margin:0;padding:0;background:#fff;color:#374151;font:13px/1.65 system-ui,-apple-system,"Segoe UI",sans-serif;overflow-wrap:anywhere}body{padding:24px}body *{font-family:inherit!important}img{max-width:100%;height:auto}table{max-width:100%}a{color:#2563eb}</style></head><body>' . $initialMailContent . '</body></html>';
                ?>
                <iframe class="mail-reader-frame" sandbox="" referrerpolicy="no-referrer" title="Mail içeriği" srcdoc="<?php echo htmlspecialchars($initialMailDocument, ENT_QUOTES, 'UTF-8'); ?>"></iframe>
                <?php if (!empty($selected['attachments'])): ?><div class="mail-attachments"><div class="mail-attachments-title"><i class="fa fa-paperclip mr-1"></i> Ekler (<?php echo count($selected['attachments']); ?>)</div><div class="mail-attachment-list"><?php foreach ($selected['attachments'] as $attachment): ?><a class="mail-attachment" href="api/mailbox_attachment.php?id=<?php echo (int) $attachment['id']; ?>"><i class="fa fa-file-o"></i><span class="mail-attachment-info"><span class="mail-attachment-name"><?php echo htmlspecialchars($attachment['file_name'], ENT_QUOTES, 'UTF-8'); ?></span><span class="mail-attachment-size"><?php echo htmlspecialchars($formatBytes((int) $attachment['file_size']), ENT_QUOTES, 'UTF-8'); ?> · İndir</span></span></a><?php endforeach; ?></div></div><?php endif; ?>
                <div class="mail-reader-actions"><button type="button" class="btn btn-outline-primary" data-toggle="modal" data-target="#composeMailModal" data-mail-to="<?php echo htmlspecialchars($selected['from_address'], ENT_QUOTES, 'UTF-8'); ?>" data-mail-subject="Ynt: <?php echo htmlspecialchars($selected['subject'], ENT_QUOTES, 'UTF-8'); ?>"><i class="fa fa-reply mr-1"></i> Yanıtla</button><button type="button" class="btn btn-outline-secondary" data-toggle="modal" data-target="#composeMailModal" data-mail-subject="İlt: <?php echo htmlspecialchars($selected['subject'], ENT_QUOTES, 'UTF-8'); ?>"><i class="fa fa-share mr-1"></i> Yönlendir</button></div>
            <?php endif; ?>
        </section>
    </div>
</div>

<div class="modal fade compose-modal" id="composeMailModal" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-centered"><form method="post" class="modal-content">
    <div class="modal-header"><h5 class="modal-title"><i class="fa fa-pencil-square-o mr-2 text-primary"></i>Yeni Mail</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
    <div class="modal-body p-4"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(Security::csrf(), ENT_QUOTES, 'UTF-8'); ?>"><input type="hidden" name="action" value="send"><input type="hidden" name="account_id" value="<?php echo $accountId; ?>"><div class="form-group"><label>Gönderen Hesap</label><input class="form-control" value="<?php echo htmlspecialchars((string) ($activeAccount['mail_address'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" readonly></div><div class="form-group"><label>Alıcı</label><input type="email" class="form-control" id="composeMailTo" name="to" placeholder="ornek@firma.com" required></div><div class="form-group"><label>Konu</label><input type="text" class="form-control" id="composeMailSubject" name="subject" maxlength="1000" required></div><div class="form-group mb-0"><label>Mesaj</label><textarea class="form-control" name="body" rows="11" placeholder="Mesajınızı yazın..." required></textarea></div></div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-dismiss="modal">Vazgeç</button><button class="btn btn-primary px-4"><i class="fa fa-paper-plane mr-1"></i> Gönder</button></div>
</form></div></div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Sidebar Collapse Logic
    var shell = document.getElementById('mailboxShell');
    var toggleSidebarBtn = document.getElementById('toggleMailboxSidebar');
    var SIDEBAR_KEY = 'aydinogullari_mailbox_sidebar_collapsed';
    var LIST_W_KEY = 'aydinogullari_mailbox_list_w';

    if (shell && toggleSidebarBtn) {
        if (localStorage.getItem(SIDEBAR_KEY) === '1') {
            shell.classList.add('sidebar-collapsed');
        }
        toggleSidebarBtn.addEventListener('click', function () {
            shell.classList.toggle('sidebar-collapsed');
            localStorage.setItem(SIDEBAR_KEY, shell.classList.contains('sidebar-collapsed') ? '1' : '0');
        });
    }

    // Mail List Panel Resizer Logic
    var resizer = document.getElementById('mailListResizer');
    var listPanel = document.getElementById('mailListPanel');

    if (resizer && listPanel && shell) {
        var savedW = localStorage.getItem(LIST_W_KEY);
        if (savedW) {
            var parsedW = parseInt(savedW, 10);
            if (!isNaN(parsedW) && parsedW >= 260 && parsedW <= 800) {
                listPanel.style.width = parsedW + 'px';
            }
        }

        var isResizing = false;
        var startX = 0;
        var startWidth = 0;

        function startResize(clientX) {
            isResizing = true;
            startX = clientX;
            startWidth = listPanel.getBoundingClientRect().width;
            document.body.classList.add('is-resizing-mailbox');
            resizer.classList.add('active');
        }

        function handleResize(clientX) {
            if (!isResizing) return;
            var diff = clientX - startX;
            var containerWidth = shell.getBoundingClientRect().width;
            var sidebarWidth = shell.classList.contains('sidebar-collapsed') ? 0 : 220;
            var maxAllowed = Math.max(280, containerWidth - sidebarWidth - 320);
            var newWidth = Math.min(Math.max(260, startWidth + diff), Math.min(750, maxAllowed));
            listPanel.style.width = newWidth + 'px';
        }

        function stopResize() {
            if (!isResizing) return;
            isResizing = false;
            document.body.classList.remove('is-resizing-mailbox');
            resizer.classList.remove('active');
            var finalWidth = parseInt(listPanel.style.width, 10);
            if (finalWidth) {
                localStorage.setItem(LIST_W_KEY, String(finalWidth));
            }
        }

        resizer.addEventListener('mousedown', function (e) {
            if (e.button !== 0) return;
            e.preventDefault();
            startResize(e.clientX);
        });

        document.addEventListener('mousemove', function (e) {
            handleResize(e.clientX);
        });

        document.addEventListener('mouseup', stopResize);

        // Touch support
        resizer.addEventListener('touchstart', function (e) {
            if (e.touches && e.touches.length === 1) {
                startResize(e.touches[0].clientX);
            }
        }, {passive: true});

        document.addEventListener('touchmove', function (e) {
            if (isResizing && e.touches && e.touches.length === 1) {
                handleResize(e.touches[0].clientX);
            }
        }, {passive: true});

        document.addEventListener('touchend', stopResize);
    }

    var search = document.getElementById('mailboxSearch');
    function applySearchFilter() {
        if (!search) return;
        var query = search.value.toLocaleLowerCase('tr-TR').trim();
        document.querySelectorAll('#mailboxMessageList .mail-item').forEach(function (item) {
            item.style.display = !query || (item.getAttribute('data-search') || '').indexOf(query) !== -1 ? '' : 'none';
        });
    }
    if (search) search.addEventListener('input', applySearchFilter);

    $('#composeMailModal').on('show.bs.modal', function (event) {
        var trigger = $(event.relatedTarget);
        $('#composeMailTo').val(trigger.data('mail-to') || '');
        $('#composeMailSubject').val(trigger.data('mail-subject') || '');
    });

    var reader = document.getElementById('mailReaderPanel');
    var list = document.getElementById('mailboxMessageList');
    var requestController = null;
    var currentAccountId = <?php echo (int) $accountId; ?>;
    var currentFolder = '<?php echo $folder; ?>';

    // Track existing message IDs to detect new incoming mails
    var knownMessageIds = new Set();
    if (list) {
        list.querySelectorAll('.mail-item[data-message-id]').forEach(function (el) {
            var id = parseInt(el.getAttribute('data-message-id'), 10);
            if (id) knownMessageIds.add(id);
        });
    }

    function escapeHtml(value) {
        var node = document.createElement('div');
        node.textContent = value == null ? '' : String(value);
        return node.innerHTML;
    }
    function escapeAttr(value) {
        return String(value == null ? '' : value).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }
    function initials(value) {
        var parts = String(value || '?').trim().split(/\s+/);
        return ((parts[0] || '?').charAt(0) + (parts.length > 1 ? parts[parts.length - 1].charAt(0) : '')).toLocaleUpperCase('tr-TR');
    }
    function formatBytes(bytes) {
        bytes = Number(bytes || 0);
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1048576) return (bytes / 1024).toLocaleString('tr-TR', {maximumFractionDigits:1}) + ' KB';
        return (bytes / 1048576).toLocaleString('tr-TR', {maximumFractionDigits:1}) + ' MB';
    }
    function buildMailDocument(message) {
        var content = message.safe_html || '<pre style="white-space:pre-wrap;font:13px/1.75 Arial,sans-serif;color:#374151">' + escapeHtml(message.body) + '</pre>';
        return '<!doctype html><html><head><meta charset="UTF-8"><meta http-equiv="Content-Security-Policy" content="default-src \'none\'; img-src data:; style-src \'unsafe-inline\'; font-src \'none\'; media-src \'none\'; connect-src \'none\'; frame-src \'none\'; form-action \'none\'; base-uri \'none\'"><style>html,body{margin:0;padding:0;background:#fff;color:#374151;font:13px/1.65 system-ui,-apple-system,"Segoe UI",sans-serif;overflow-wrap:anywhere}body{padding:24px}body *{font-family:inherit!important}img{max-width:100%;height:auto}table{max-width:100%}a{color:#2563eb}</style></head><body>' + content + '</body></html>';
    }
    function showReaderMessage(message, remoteImagesLoaded) {
        var folder = reader.getAttribute('data-folder');
        var party = folder === 'sent' ? message.to_addresses : (message.from_name || message.from_address);
        var replyTo = folder === 'sent' ? message.to_addresses : message.from_address;
        var attachments = Array.isArray(message.attachments) && message.attachments.length ? '<div class="mail-attachments"><div class="mail-attachments-title"><i class="fa fa-paperclip mr-1"></i> Ekler (' + message.attachments.length + ')</div><div class="mail-attachment-list">' + message.attachments.map(function (attachment) { return '<a class="mail-attachment" href="' + escapeAttr(attachment.download_url) + '"><i class="fa fa-file-o"></i><span class="mail-attachment-info"><span class="mail-attachment-name">' + escapeHtml(attachment.file_name) + '</span><span class="mail-attachment-size">' + escapeHtml(formatBytes(attachment.file_size)) + ' · İndir</span></span></a>'; }).join('') + '</div></div>' : '';
        var remoteWarning = !remoteImagesLoaded && Number(message.remote_image_count || 0) > 0 ? '<div class="mail-remote-warning"><i class="fa fa-shield"></i><span>' + Number(message.remote_image_count) + ' harici görsel gizlilik için engellendi.</span><button type="button" class="btn-show-remote-images" data-message-id="' + Number(message.id) + '">Görselleri Göster</button></div>' : '';
        reader.innerHTML = '<div class="mail-reader-header">' +
            '<h4>' + escapeHtml(message.subject) + '</h4>' +
            '<div class="mail-reader-person"><span class="mail-avatar">' + escapeHtml(initials(party)) + '</span>' +
            '<div class="mail-reader-address"><strong>' + escapeHtml(party) + '</strong><span>' + escapeHtml(message.from_address + ' → ' + message.to_addresses) + '</span></div>' +
            '<span class="mail-reader-time">' + escapeHtml(message.received_at) + '</span></div></div>' + remoteWarning +
            '<iframe class="mail-reader-frame" sandbox="" referrerpolicy="no-referrer" title="Mail içeriği" srcdoc="' + escapeAttr(buildMailDocument(message)) + '"></iframe>' + attachments +
            '<div class="mail-reader-actions"><button type="button" class="btn btn-outline-primary" data-toggle="modal" data-target="#composeMailModal" data-mail-to="' + escapeAttr(replyTo) + '" data-mail-subject="' + escapeAttr('Ynt: ' + message.subject) + '"><i class="fa fa-reply mr-1"></i> Yanıtla</button>' +
            '<button type="button" class="btn btn-outline-secondary" data-toggle="modal" data-target="#composeMailModal" data-mail-subject="' + escapeAttr('İlt: ' + message.subject) + '"><i class="fa fa-share mr-1"></i> Yönlendir</button></div>';
    }

    var isRefreshingList = false;
    function refreshMessageList(quiet, onDone) {
        if (isRefreshingList) return;
        isRefreshingList = true;

        fetch('api/mailbox_list.php?account=' + encodeURIComponent(currentAccountId) + '&folder=' + encodeURIComponent(currentFolder), {
            credentials: 'same-origin',
            cache: 'no-store'
        })
        .then(function (response) {
            if (!response.ok) throw new Error('Mail listesi alınamadı.');
            return response.json();
        })
        .then(function (data) {
            isRefreshingList = false;
            if (!data || data.status !== 'success' || !Array.isArray(data.messages)) return;

            // Find currently active message ID in the list
            var activeItem = list ? list.querySelector('.mail-item.active') : null;
            var activeMessageId = activeItem ? parseInt(activeItem.getAttribute('data-message-id'), 10) : 0;

            // Update Total Count
            var countEl = document.getElementById('mailListCountText');
            if (countEl) countEl.textContent = (data.total || data.messages.length) + ' ileti';

            // Update Unread Count Badge in Sidebar
            var inboxLink = document.querySelector('.mail-folder-link[href*="folder=inbox"]');
            var unreadBadge = document.getElementById('mailUnreadCount');
            if (currentFolder === 'inbox' && inboxLink) {
                if (data.unread_count > 0) {
                    if (unreadBadge) {
                        unreadBadge.textContent = String(data.unread_count);
                    } else {
                        var newBadge = document.createElement('span');
                        newBadge.className = 'mail-folder-count';
                        newBadge.id = 'mailUnreadCount';
                        newBadge.textContent = String(data.unread_count);
                        inboxLink.appendChild(newBadge);
                    }
                } else if (unreadBadge) {
                    unreadBadge.remove();
                }
            }

            if (!list) return;

            if (data.messages.length === 0) {
                list.innerHTML = '<div class="mail-list-empty"><div><i class="fa fa-envelope-o mr-2"></i>Henüz mail bulunmuyor.</div></div>';
                knownMessageIds.clear();
                return;
            }

            var newKnownSet = new Set();
            var html = '';

            data.messages.forEach(function (m) {
                newKnownSet.add(m.id);
                var isNew = !knownMessageIds.has(m.id);
                var isUnread = !m.is_read && currentFolder === 'inbox';
                var isActive = activeMessageId === m.id;
                var unreadClass = isUnread ? ' unread' : '';
                var activeClass = isActive ? ' active' : '';
                var newClass = isNew ? ' mail-item-new-pulse' : '';
                var attachIcon = m.has_attachments ? '<i class="fa fa-paperclip"></i>' : '';

                html += '<a class="mail-item' + unreadClass + activeClass + newClass + '" data-search="' + escapeAttr(m.search) + '" data-message-id="' + m.id + '" href="gelen-giden-mail?account=' + currentAccountId + '&folder=' + currentFolder + '&message=' + m.id + '">' +
                    '<span class="mail-avatar">' + escapeHtml(m.initials) + '</span>' +
                    '<span class="min-w-0">' +
                        '<span class="mail-item-head">' +
                            '<span class="mail-sender">' + escapeHtml(m.party) + '</span>' +
                            '<span class="mail-date">' + escapeHtml(m.formatted_date) + '</span>' +
                        '</span>' +
                        '<span class="mail-subject"><span>' + escapeHtml(m.subject) + '</span>' + attachIcon + '</span>' +
                    '</span>' +
                '</a>';
            });

            list.innerHTML = html;
            knownMessageIds = newKnownSet;
            applySearchFilter();

            if (typeof onDone === 'function') onDone(data);
        })
        .catch(function (err) {
            isRefreshingList = false;
            if (!quiet) console.error('Mail list refresh error:', err);
        });
    }

    // Listen for global new mail event from header.php
    window.addEventListener('mailbox:new_mail', function () {
        refreshMessageList(false);
    });

    // Polling every 15s to keep list fresh
    setInterval(function () {
        if (!document.hidden) {
            refreshMessageList(true);
        }
    }, 15000);

    // AJAX Form Sync
    var syncForm = document.getElementById('mailSyncForm');
    var syncBtn = document.getElementById('mailSyncButton');
    var lastSyncText = document.getElementById('mailLastSyncTime');

    if (syncForm) {
        syncForm.addEventListener('submit', function (e) {
            e.preventDefault();
            if (syncBtn) {
                syncBtn.disabled = true;
                syncBtn.innerHTML = '<i class="fa fa-spinner fa-spin mr-1"></i> Eşitleniyor...';
            }
            var formData = new FormData(syncForm);

            fetch('api/mailbox_sync.php', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (syncBtn) {
                    syncBtn.disabled = false;
                    syncBtn.innerHTML = '<i class="fa fa-refresh mr-1"></i> Şimdi Eşitle';
                }
                if (data && data.status === 'success') {
                    if (lastSyncText && data.last_sync_at) {
                        lastSyncText.textContent = 'Son kontrol: ' + data.last_sync_at;
                    }
                    refreshMessageList(false);
                }
            })
            .catch(function () {
                if (syncBtn) {
                    syncBtn.disabled = false;
                    syncBtn.innerHTML = '<i class="fa fa-refresh mr-1"></i> Şimdi Eşitle';
                }
            });
        });
    }

    if (list && reader) list.addEventListener('click', function (event) {
        var item = event.target.closest('.mail-item');
        if (!item) return;
        event.preventDefault();
        var messageId = item.getAttribute('data-message-id');
        if (!messageId) return;
        if (requestController) requestController.abort();
        requestController = new AbortController();
        reader.innerHTML = '<div class="mail-reader-empty"><div class="empty-icon"><i class="fa fa-spinner fa-spin"></i></div><h5>Mail yükleniyor</h5></div>';
        fetch('api/mailbox_message.php?account=' + encodeURIComponent(reader.getAttribute('data-account-id')) + '&message=' + encodeURIComponent(messageId), {credentials:'same-origin', cache:'no-store', signal:requestController.signal})
            .then(function (response) { if (!response.ok) throw new Error('Mail yüklenemedi.'); return response.json(); })
            .then(function (data) {
                if (!data || data.status !== 'success') throw new Error((data && data.message) || 'Mail yüklenemedi.');
                document.querySelectorAll('#mailboxMessageList .mail-item').forEach(function (row) { row.classList.remove('active'); });
                var wasUnread = item.classList.contains('unread');
                item.classList.add('active');
                item.classList.remove('unread');
                if (wasUnread) {
                    var unreadBadge = document.getElementById('mailUnreadCount');
                    if (unreadBadge) {
                        var remaining = Math.max(0, parseInt(unreadBadge.textContent || '0', 10) - 1);
                        if (remaining) unreadBadge.textContent = String(remaining); else unreadBadge.remove();
                    }
                }
                showReaderMessage(data.message, false);
                window.history.replaceState({}, '', item.getAttribute('href'));
            })
            .catch(function (error) {
                if (error.name === 'AbortError') return;
                reader.innerHTML = '<div class="mail-reader-empty"><div class="empty-icon"><i class="fa fa-exclamation-triangle"></i></div><h5>' + escapeHtml(error.message) + '</h5><p>Lütfen tekrar deneyin.</p></div>';
            });
    });
    if (reader) reader.addEventListener('click', function (event) {
        var button = event.target.closest('.btn-show-remote-images');
        if (!button) return;
        var messageId = button.getAttribute('data-message-id');
        button.disabled = true;
        button.textContent = 'Yükleniyor...';
        fetch('api/mailbox_message.php?account=' + encodeURIComponent(reader.getAttribute('data-account-id')) + '&message=' + encodeURIComponent(messageId) + '&show_remote_images=1', {credentials:'same-origin', cache:'no-store'})
            .then(function (response) { if (!response.ok) throw new Error('Görseller yüklenemedi.'); return response.json(); })
            .then(function (data) { if (!data || data.status !== 'success') throw new Error('Görseller yüklenemedi.'); showReaderMessage(data.message, true); })
            .catch(function () { button.disabled = false; button.textContent = 'Tekrar Dene'; });
    });
});
</script>
