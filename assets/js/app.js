(function () {
    'use strict';

    const ChoQ = {
        async fetchJson(url, options) {
            const res = await fetch(url, {
                headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
                ...options,
            });
            const data = await res.json();
            if (!res.ok) {
                throw new Error(data.error || '요청에 실패했습니다.');
            }
            return data;
        },

        formatTime(iso) {
            try {
                const d = new Date(iso);
                return d.toLocaleString('ko-KR', {
                    month: 'short',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit',
                });
            } catch {
                return '';
            }
        },

        updateStatusUI(display) {
            const icon = document.getElementById('statusIcon');
            const label = document.getElementById('statusLabel');
            const message = document.getElementById('statusMessage');
            if (!icon || !label || !message || !display) return;
            icon.textContent = display.icon || '✏️';
            label.textContent = display.label || '';
            if (display.is_html && display.message_html) {
                message.innerHTML = display.message_html;
            } else {
                message.textContent = display.message || '';
            }
        },

        appendMessages(listEl, messages) {
            if (!listEl || !messages || !messages.length) return;
            messages.forEach((msg) => {
                if (msg.id && ChoQ.seenMessageIds.has(msg.id)) return;
                if (msg.id) ChoQ.seenMessageIds.add(msg.id);

                const li = document.createElement('li');
                const text = document.createElement('span');
                text.textContent = msg.message;
                const time = document.createElement('span');
                time.className = 'time';
                time.textContent = ChoQ.formatTime(msg.created_at);
                li.appendChild(text);
                li.appendChild(time);
                listEl.insertBefore(li, listEl.firstChild);
            });
        },

        updateSinceFromMessages(messages) {
            if (!messages || !messages.length) return;
            messages.forEach((msg) => {
                if (msg.created_at && msg.created_at > ChoQ.since) {
                    ChoQ.since = msg.created_at;
                }
            });
        },

        async loadMessages(carCode) {
            const listEl = document.getElementById('messageList');
            if (!listEl) return;
            const data = await ChoQ.fetchJson(`/api/messages.php?car=${encodeURIComponent(carCode)}&limit=20`);
            listEl.innerHTML = '';
            ChoQ.seenMessageIds.clear();
            ChoQ.appendMessages(listEl, data.messages || []);
            ChoQ.updateSinceFromMessages(data.messages || []);
        },

        pollTimer: null,
        since: '',
        seenMessageIds: new Set(),

        startPolling(carCode, intervalMs) {
            ChoQ.since = window.ChoQPage?.since || new Date().toISOString();

            const tick = async () => {
                try {
                    const url = `/api/poll.php?car=${encodeURIComponent(carCode)}&since=${encodeURIComponent(ChoQ.since)}`;
                    const data = await ChoQ.fetchJson(url);
                    if (!data.changed) return;

                    if (data.status) {
                        ChoQ.updateStatusUI(data.status.display);
                        ChoQ.since = data.status.updated_at;
                    }
                    if (data.messages && data.messages.length) {
                        ChoQ.appendMessages(document.getElementById('messageList'), data.messages);
                        ChoQ.updateSinceFromMessages(data.messages);
                    }
                } catch {
                    /* 폴링 실패는 조용히 무시 */
                }
            };

            ChoQ.pollTimer = setInterval(tick, intervalMs);
        },

        initTheme() {
            const root = document.documentElement;
            const btn = document.getElementById('themeToggle');
            const stored = localStorage.getItem('choq-theme');
            if (stored) root.setAttribute('data-theme', stored);

            const cycle = { auto: 'light', light: 'dark', dark: 'auto' };

            btn?.addEventListener('click', () => {
                const current = root.getAttribute('data-theme') || 'auto';
                const next = cycle[current] || 'auto';
                root.setAttribute('data-theme', next);
                localStorage.setItem('choq-theme', next);
                btn.textContent = next === 'dark' ? '☀️' : '🌙';
            });

            const theme = root.getAttribute('data-theme') || 'auto';
            btn.textContent = theme === 'dark' ? '☀️' : '🌙';
        },

        async sendGuestMessage(carCode, message) {
            const trimmed = message.trim();
            if (!trimmed) return null;

            const result = await ChoQ.fetchJson('/api/messages.php', {
                method: 'POST',
                body: JSON.stringify({ car: carCode, message: trimmed }),
            });

            if (result.message) {
                const listEl = document.getElementById('messageList');
                ChoQ.appendMessages(listEl, [result.message]);
                ChoQ.updateSinceFromMessages([result.message]);
            }

            return result;
        },

        setGuestFormBusy(form, busy) {
            form?.querySelector('button[type="submit"]')?.toggleAttribute('disabled', busy);
            form?.querySelector('textarea')?.toggleAttribute('disabled', busy);
            document.querySelectorAll('.emoji-btn').forEach((btn) => {
                btn.disabled = busy;
            });
        },

        initCarPage() {
            const page = window.ChoQPage;
            if (!page || page.mode !== 'car') return;

            ChoQ.loadMessages(page.carCode).catch(() => {});
            ChoQ.startPolling(page.carCode, page.pollInterval || 3000);

            const form = document.getElementById('messageForm');

            document.querySelectorAll('.emoji-btn').forEach((btn) => {
                btn.addEventListener('click', async () => {
                    const message = btn.dataset.message || '';
                    if (!message) return;

                    ChoQ.setGuestFormBusy(form, true);
                    try {
                        await ChoQ.sendGuestMessage(page.carCode, message);
                    } catch (err) {
                        alert(err.message);
                    } finally {
                        ChoQ.setGuestFormBusy(form, false);
                    }
                });
            });

            form?.addEventListener('submit', async (e) => {
                e.preventDefault();
                const textarea = form.querySelector('textarea');
                const message = textarea?.value.trim();
                if (!message) return;

                ChoQ.setGuestFormBusy(form, true);
                try {
                    await ChoQ.sendGuestMessage(page.carCode, message);
                    textarea.value = '';
                } catch (err) {
                    alert(err.message);
                } finally {
                    ChoQ.setGuestFormBusy(form, false);
                }
            });

            ChoQ.initReportForm(page.carCode);
        },

        initReportForm(carCode) {
            const toggle = document.getElementById('reportToggle');
            const panel = document.getElementById('reportPanel');
            const form = document.getElementById('reportForm');
            const hint = document.getElementById('reportHint');
            if (!toggle || !panel || !form) return;

            toggle.addEventListener('click', () => {
                const open = panel.hidden;
                panel.hidden = !open;
                toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            });

            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                const reason = form.querySelector('[name="reason"]')?.value || '';
                const detail = form.querySelector('[name="detail"]')?.value?.trim() || '';
                if (!reason) return;

                const btn = form.querySelector('button[type="submit"]');
                btn.disabled = true;
                if (hint) {
                    hint.hidden = true;
                    hint.className = 'form-hint';
                }

                try {
                    const result = await ChoQ.fetchJson('/api/reports.php', {
                        method: 'POST',
                        body: JSON.stringify({ car: carCode, reason, detail }),
                    });
                    form.reset();
                    panel.hidden = true;
                    toggle.setAttribute('aria-expanded', 'false');
                    if (hint) {
                        hint.textContent = result.message || '신고가 접수되었습니다.';
                        hint.className = 'form-hint ok';
                        hint.hidden = false;
                    }
                } catch (err) {
                    if (hint) {
                        hint.textContent = err.message;
                        hint.className = 'form-hint err';
                        hint.hidden = false;
                    } else {
                        alert(err.message);
                    }
                } finally {
                    btn.disabled = false;
                }
            });
        },

        initConsolePage() {
            const page = window.ChoQPage;
            if (!page || page.mode !== 'console') return;

            const form = document.getElementById('consoleForm');
            const hint = document.getElementById('consoleHint');

            form?.addEventListener('submit', async (e) => {
                e.preventDefault();
                const fd = new FormData(form);
                const customMessage = window.ChoQEditor
                    ? window.ChoQEditor.getValue()
                    : (fd.get('custom_message') || '');

                const payload = {
                    car: page.carCode,
                    status_key: fd.get('status_key'),
                    custom_message: customMessage,
                };

                const btn = form.querySelector('button[type="submit"]');
                btn.disabled = true;
                hint.textContent = '업데이트 중…';
                hint.className = 'form-hint';

                try {
                    await ChoQ.fetchJson('/api/status.php', {
                        method: 'POST',
                        body: JSON.stringify(payload),
                    });
                    hint.textContent = '상태가 업데이트됐어요! ✅';
                    hint.className = 'form-hint ok';
                } catch (err) {
                    hint.textContent = err.message;
                    hint.className = 'form-hint err';
                } finally {
                    btn.disabled = false;
                }
            });
        },

        init() {
            ChoQ.initTheme();
            ChoQ.initCarPage();
            ChoQ.initConsolePage();
        },
    };

    window.ChoQ = ChoQ;
    document.addEventListener('DOMContentLoaded', ChoQ.init);
})();
