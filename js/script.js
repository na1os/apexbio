document.addEventListener('DOMContentLoaded', () => {
    const toastContainer = document.getElementById('toast-container');

    window.showToast = function (message, type = 'success') {
        if (!toastContainer) return;
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.textContent = message;
        toastContainer.appendChild(toast);
        setTimeout(() => {
            toast.style.opacity = '0';
            setTimeout(() => toast.remove(), 250);
        }, 2400);
    };

    const navItems = document.querySelectorAll('.sidebar-menu .nav-item[data-tab]');
    const tabContents = document.querySelectorAll('.tab-content');
    navItems.forEach(item => {
        item.addEventListener('click', () => {
            const targetTab = item.dataset.tab;
            navItems.forEach(node => node.classList.remove('active'));
            tabContents.forEach(node => node.classList.remove('active'));
            item.classList.add('active');
            const tab = document.getElementById(`tab-${targetTab}`);
            if (tab) tab.classList.add('active');
        });
    });

    const ajaxForms = document.querySelectorAll('.ajax-form');
    ajaxForms.forEach(form => {
        form.addEventListener('submit', async event => {
            event.preventDefault();
            try {
                const response = await fetch('backend/api.php', {
                    method: 'POST',
                    body: new FormData(form),
                });
                const result = await response.json();

                if (response.ok && result.success) {
                    showToast(result.message || 'Saved successfully.');
                    setTimeout(() => location.reload(), 450);
                } else {
                    showToast(result.error || 'Something went wrong.', 'danger');
                }
            } catch (err) {
                showToast('Network error occurred.', 'danger');
            }
        });
    });

    const templateCards = document.querySelectorAll('.template-card');
    templateCards.forEach(card => {
        card.addEventListener('click', () => {
            const hidden = document.querySelector('input[name="template_key"]');
            const themeSelect = document.querySelector('select[name="theme"]');
            const accentInput = document.querySelector('input[name="accent_color"]');

            if (hidden) hidden.value = card.dataset.templateKey || 'glass';
            if (themeSelect) {
                themeSelect.value = card.dataset.theme || themeSelect.value;
            }
            if (accentInput) {
                accentInput.value = card.dataset.accent || accentInput.value;
            }

            showToast('Template selected. Save changes to apply it.');
        });
    });

    document.addEventListener('click', async event => {
        if (event.target.classList.contains('delete-link-btn')) {
            if (!confirm('Delete this link?')) return;
            const fd = new FormData();
            fd.append('action', 'delete_link');
            fd.append('link_id', event.target.dataset.id);
            fd.append('csrf_token', document.querySelector('input[name="csrf_token"]')?.value || '');
            const response = await fetch('backend/api.php', { method: 'POST', body: fd });
            if (response.ok) location.reload();
        }

        if (event.target.classList.contains('delete-social-btn')) {
            if (!confirm('Remove this social link?')) return;
            const fd = new FormData();
            fd.append('action', 'delete_social');
            fd.append('social_id', event.target.dataset.id);
            fd.append('csrf_token', document.querySelector('input[name="csrf_token"]')?.value || '');
            const response = await fetch('backend/api.php', { method: 'POST', body: fd });
            if (response.ok) location.reload();
        }

        if (event.target.classList.contains('admin-reset-btn')) {
            if (!confirm('Are you sure you want to delete this media permanently?')) return;
            const fd = new FormData();
            fd.append('action', 'admin_reset_media');
            fd.append('target_id', event.target.dataset.id);
            fd.append('media_type', event.target.dataset.type);
            fd.append('csrf_token', document.querySelector('input[name="csrf_token"]')?.value || '');
            const response = await fetch('backend/api.php', { method: 'POST', body: fd });
            const result = await response.json();
            if (response.ok && result.success) {
                showToast(result.message || 'Media reset.');
                setTimeout(() => location.reload(), 450);
            } else {
                showToast(result.error || 'Could not reset media.', 'danger');
            }
        }

        if (event.target.classList.contains('admin-del-link-btn')) {
            if (!confirm('Delete this user link?')) return;
            const fd = new FormData();
            fd.append('action', 'admin_delete_link');
            fd.append('link_id', event.target.dataset.id);
            fd.append('csrf_token', document.querySelector('input[name="csrf_token"]')?.value || '');
            const response = await fetch('backend/api.php', { method: 'POST', body: fd });
            if (response.ok) location.reload();
        }

        if (event.target.classList.contains('toggle-ban-btn')) {
            const fd = new FormData();
            fd.append('action', 'admin_ban');
            fd.append('target_id', event.target.dataset.id);
            fd.append('ban_status', event.target.dataset.status);
            fd.append('csrf_token', document.querySelector('input[name="csrf_token"]')?.value || '');
            const response = await fetch('backend/api.php', { method: 'POST', body: fd });
            const result = await response.json();
            if (response.ok && result.success) {
                showToast(result.message || 'Updated.');
                setTimeout(() => location.reload(), 450);
            } else {
                showToast(result.error || 'Action failed.', 'danger');
            }
        }

        if (event.target.classList.contains('delete-user-btn')) {
            const conf = prompt('Type "DELETE" to permanently erase this user and all their data.');
            if (conf !== 'DELETE') return;
            const fd = new FormData();
            fd.append('action', 'admin_delete_user');
            fd.append('target_id', event.target.dataset.id);
            fd.append('csrf_token', document.querySelector('input[name="csrf_token"]')?.value || '');
            const response = await fetch('backend/api.php', { method: 'POST', body: fd });
            const result = await response.json();
            if (response.ok && result.success) {
                window.location.href = 'admin.php';
            } else {
                showToast(result.error || 'Delete failed.', 'danger');
            }
        }
    });

    const linksList = document.getElementById('links-list');
    if (linksList) {
        let dragged = null;

        const persistOrder = async () => {
            const order = [...linksList.querySelectorAll('.link-item-row')].map(row => row.dataset.id);
            const fd = new FormData();
            fd.append('action', 'reorder_links');
            fd.append('csrf_token', document.querySelector('input[name="csrf_token"]')?.value || '');
            order.forEach(id => fd.append('order[]', id));
            const response = await fetch('backend/api.php', { method: 'POST', body: fd });
            if (response.ok) showToast('Link order saved.');
        };

        linksList.querySelectorAll('.link-item-row').forEach(row => {
            row.setAttribute('draggable', 'true');
            row.addEventListener('dragstart', () => {
                dragged = row;
                row.classList.add('dragging');
            });
            row.addEventListener('dragend', () => {
                row.classList.remove('dragging');
                dragged = null;
                persistOrder();
            });
            row.addEventListener('dragover', event => {
                event.preventDefault();
                if (!dragged || dragged === row) return;
                const after = getDragAfterElement(linksList, event.clientY);
                if (after == null) {
                    linksList.appendChild(dragged);
                } else {
                    linksList.insertBefore(dragged, after);
                }
            });
        });
    }

    function getDragAfterElement(container, y) {
        const elements = [...container.querySelectorAll('.link-item-row:not(.dragging)')];
        return elements.reduce((closest, child) => {
            const box = child.getBoundingClientRect();
            const offset = y - box.top - box.height / 2;
            if (offset < 0 && offset > closest.offset) {
                return { offset, element: child };
            }
            return closest;
        }, { offset: Number.NEGATIVE_INFINITY }).element;
    }

    document.querySelectorAll('.profile-link-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const fd = new FormData();
            fd.append('action', 'track_click');
            fd.append('link_id', btn.dataset.linkId || '');
            navigator.sendBeacon('backend/api.php', fd);
        });
    });

    const audio = document.getElementById('bg-audio');
    const audioBtn = document.getElementById('audio-toggle-btn');
    if (audio && audioBtn) {
        let isPlaying = false;
        audioBtn.addEventListener('click', async () => {
            if (isPlaying) {
                audio.pause();
                audioBtn.classList.remove('playing');
            } else {
                try {
                    await audio.play();
                    audioBtn.classList.add('playing');
                } catch (err) {
                    showToast('Audio could not start.', 'danger');
                    return;
                }
            }
            isPlaying = !isPlaying;
        });
    }
});
