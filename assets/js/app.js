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
            message.textContent = display.message || '';
        },

        appendMessages(listEl, messages) {
            if (!listEl || !messages || !messages.length) return;
            messages.forEach((msg) => {
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

        async loadMessages(carCode) {
            const listEl = document.getElementById('messageList');
            if (!listEl) return;
            const data = await ChoQ.fetchJson(`/api/messages.php?car=${encodeURIComponent(carCode)}&limit=20`);
            listEl.innerHTML = '';
            ChoQ.appendMessages(listEl, data.messages || []);
        },

        pollTimer: null,
        since: '',

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
                        const last = data.messages[data.messages.length - 1];
                        if (last && last.created_at > ChoQ.since) {
                            ChoQ.since = last.created_at;
                        }
                    }
                    if (data.polled_at) {
                        ChoQ.since = data.polled_at;
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

        initCarPage() {
            const page = window.ChoQPage;
            if (!page || page.mode !== 'car') return;

            ChoQ.loadMessages(page.carCode).catch(() => {});
            ChoQ.startPolling(page.carCode, page.pollInterval || 3000);

            const form = document.getElementById('messageForm');
            form?.addEventListener('submit', async (e) => {
                e.preventDefault();
                const textarea = form.querySelector('textarea');
                const message = textarea?.value.trim();
                if (!message) return;

                const btn = form.querySelector('button[type="submit"]');
                btn.disabled = true;
                try {
                    await ChoQ.fetchJson('/api/messages.php', {
                        method: 'POST',
                        body: JSON.stringify({ car: page.carCode, message }),
                    });
                    textarea.value = '';
                    await ChoQ.loadMessages(page.carCode);
                } catch (err) {
                    alert(err.message);
                } finally {
                    btn.disabled = false;
                }
            });
        },

        initConsolePage() {
            const page = window.ChoQPage;
            if (!page || page.mode !== 'console') return;

            const form = document.getElementById('consoleForm');
            const customField = document.getElementById('customField');
            const hint = document.getElementById('consoleHint');

            const toggleCustom = () => {
                const selected = form?.querySelector('input[name="status_key"]:checked');
                const isCustom = selected?.value === 'custom';
                if (customField) customField.hidden = !isCustom;
            };

            form?.querySelectorAll('input[name="status_key"]').forEach((radio) => {
                radio.addEventListener('change', toggleCustom);
            });
            toggleCustom();

            form?.addEventListener('submit', async (e) => {
                e.preventDefault();
                const fd = new FormData(form);
                const payload = {
                    car: page.carCode,
                    pin: fd.get('pin'),
                    status_key: fd.get('status_key'),
                    custom_message: fd.get('custom_message') || '',
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
