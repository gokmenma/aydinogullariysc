/**
 * Menü Sıralama (Drag & Drop) ve Menü Ayarları Yönetimi
 */
(function () {
    'use strict';

    function initMenuSortable() {
        if (typeof Sortable === 'undefined') {
            console.warn('SortableJS yüklenemedi.');
            return;
        }

        var accordionMenu = document.getElementById('accordion-menu');
        if (!accordionMenu) return;

        // 1. Ana Menüleri Kendi Arasında Sıralama
        Sortable.create(accordionMenu, {
            animation: 150,
            draggable: '> li.dropdown',
            ghostClass: 'menu-sort-ghost',
            chosenClass: 'menu-sort-chosen',
            dragClass: 'menu-sort-drag',
            delay: 100,
            delayOnTouchOnly: true,
            touchStartThreshold: 5,
            onEnd: function () {
                saveCurrentMenuOrder();
            }
        });

        // 2. Alt Menüleri Kendi Üst Menüsü Altında Sıralama
        var submenus = accordionMenu.querySelectorAll('ul.submenu');
        submenus.forEach(function (submenu) {
            var parentKey = submenu.getAttribute('data-parent-key') || 'default';

            Sortable.create(submenu, {
                group: {
                    name: 'submenu-' + parentKey,
                    pull: false,
                    put: false
                },
                animation: 150,
                draggable: '> li',
                ghostClass: 'submenu-sort-ghost',
                chosenClass: 'submenu-sort-chosen',
                dragClass: 'submenu-sort-drag',
                delay: 100,
                delayOnTouchOnly: true,
                touchStartThreshold: 5,
                onEnd: function () {
                    saveCurrentMenuOrder();
                }
            });
        });
    }

    var saveTimeout = null;

    function saveCurrentMenuOrder() {
        if (saveTimeout) clearTimeout(saveTimeout);

        saveTimeout = setTimeout(function () {
            var accordionMenu = document.getElementById('accordion-menu');
            if (!accordionMenu) return;

            var mainOrder = [];
            var mainItems = accordionMenu.querySelectorAll(':scope > li.dropdown');
            mainItems.forEach(function (li) {
                var key = li.getAttribute('data-menu-key');
                if (key) {
                    mainOrder.push(key);
                }
            });

            var subOrder = {};
            var submenus = accordionMenu.querySelectorAll('ul.submenu');
            submenus.forEach(function (submenu) {
                var parentKey = submenu.getAttribute('data-parent-key');
                if (!parentKey) return;

                var items = [];
                var subLis = submenu.querySelectorAll(':scope > li');
                subLis.forEach(function (li) {
                    var itemKey = li.getAttribute('data-item-key');
                    if (itemKey) {
                        items.push(itemKey);
                    }
                });

                if (items.length > 0) {
                    subOrder[parentKey] = items;
                }
            });

            fetch('api/menu_order.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    action: 'save',
                    main_order: mainOrder,
                    sub_order: subOrder
                })
            })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data.status === 'success') {
                    showToast('Menü sıralaması kaydedildi', 'success');
                } else {
                    showToast(data.message || 'Sıralama kaydedilemedi', 'error');
                }
            })
            .catch(function (err) {
                console.error('Menü kaydetme hatası:', err);
                showToast('Bağlantı hatası oluştu', 'error');
            });
        }, 300);
    }

    function resetMenuOrder() {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Varsayılan Menü Sırası',
                text: 'Menü sıralamasını varsayılana sıfırlamak istediğinize emin misiniz?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Evet, Sıfırla',
                cancelButtonText: 'Vazgeç'
            }).then(function (result) {
                if (result.isConfirmed) {
                    executeResetOrder();
                }
            });
        } else {
            if (confirm('Menü sıralamasını varsayılana sıfırlamak istediğinize emin misiniz?')) {
                executeResetOrder();
            }
        }
    }

    function executeResetOrder() {
        fetch('api/menu_order.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ action: 'reset' })
        })
        .then(function (res) { return res.json(); })
        .then(function (data) {
            if (data.status === 'success') {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Sıfırlandı!',
                        text: 'Menü sırası varsayılana döndürüldü.',
                        timer: 1200,
                        showConfirmButton: false
                    }).then(function () {
                        window.location.reload();
                    });
                } else {
                    alert('Menü sırası varsayılana döndürüldü.');
                    window.location.reload();
                }
            } else {
                showToast(data.message || 'Sıfırlama başarısız oldu.', 'error');
            }
        })
        .catch(function (err) {
            console.error('Menü sıfırlama hatası:', err);
            showToast('Bağlantı hatası oluştu', 'error');
        });
    }

    function showToast(title, icon) {
        if (typeof Swal !== 'undefined') {
            var Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 2000,
                timerProgressBar: false
            });
            Toast.fire({
                icon: icon || 'success',
                title: title
            });
        }
    }

    // Event Dinleyicileri
    document.addEventListener('DOMContentLoaded', function () {
        initMenuSortable();

        var resetBtn = document.getElementById('btn-reset-menu-order');
        if (resetBtn) {
            resetBtn.addEventListener('click', function (e) {
                e.preventDefault();
                resetMenuOrder();
            });
        }

        // Dropdown toggle fallback (Bootstrap dropdown çalışmazsa)
        var settingsBtn = document.getElementById('sidebarMenuSettingsDropdown');
        if (settingsBtn) {
            settingsBtn.addEventListener('click', function (e) {
                var parent = settingsBtn.closest('.dropdown');
                if (parent) {
                    var menu = parent.querySelector('.dropdown-menu');
                    if (menu && typeof $ !== 'undefined' && typeof $.fn.dropdown === 'undefined') {
                        menu.classList.toggle('show');
                    }
                }
            });
        }
    });

    // jQuery hazır olduğunda da tetikle
    if (typeof $ !== 'undefined') {
        $(document).ready(function () {
            initMenuSortable();
        });
    }
})();
