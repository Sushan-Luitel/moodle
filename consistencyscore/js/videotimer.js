(function () {

    const KEY_TOTAL = 'video_total_time';
    const KEY_START = 'video_start_time';
    const KEY_DATE  = 'video_date';
    const IDLE_LIMIT = 120; // 2 minutes
    const SAVE_INTERVAL = 5; // seconds

    let interval = null;
    let saveInterval = null;
    let lastActivity = now();
    let idleInterval = null;
    let isIdle = false;
    let video = null;

    // ---------------------- Helpers ----------------------
    function now() {
        return Math.floor(Date.now() / 1000);
    }

    function today() {
        const d = new Date();
        return d.getFullYear() + '-' + (d.getMonth()+1) + '-' + d.getDate();
    }

    function format(sec) {
        const m = Math.floor(sec / 60);
        const s = sec % 60;
        return String(m).padStart(2,'0') + ':' + String(s).padStart(2,'0');
    }

    function getTotal() {
        return parseInt(localStorage.getItem(KEY_TOTAL) || '0', 10);
    }

    function setTotal(v) {
        localStorage.setItem(KEY_TOTAL, v);
    }

    function getStart() {
        return parseInt(localStorage.getItem(KEY_START) || '0', 10);
    }

    function setStart(v) {
        localStorage.setItem(KEY_START, v);
    }

    function clearStart() {
        localStorage.removeItem(KEY_START);
    }

    function currentSessionSeconds() {
        const start = getStart();
        return start ? (now() - start) : 0;
    }

    function totalSecondsNow() {
        return getTotal() + currentSessionSeconds();
    }

    function updateDisplay() {
        const el = document.getElementById('video-timer');
        if (el) el.textContent = format(totalSecondsNow());
    }

    // ---------------------- Timer ----------------------
    function start() {
        // Only run if video exists, playing, tab visible, and user not idle
        if (!video || video.paused || document.hidden || isIdle) return;

        if (!getStart()) setStart(now());

        if (!interval) interval = setInterval(updateDisplay, 1000);
        if (!saveInterval) saveInterval = setInterval(saveToServerPeriodically, SAVE_INTERVAL * 1000);
    }

    function pause() {
        const elapsed = currentSessionSeconds();
        if (elapsed > 0) {
            setTotal(getTotal() + elapsed);
            saveToServer(elapsed);
        }
        clearStart();
        clearInterval(interval);
        clearInterval(saveInterval);
        interval = null;
        saveInterval = null;
    }

    function saveToServer(seconds) {
        if (seconds <= 0) return;
        const data = new URLSearchParams();
        data.append('seconds', seconds);
        navigator.sendBeacon(
            M.cfg.wwwroot + '/local/consistencyscore/ajax/save_video_time.php',
            data
        );
    }

    function saveToServerPeriodically() {
        const elapsed = currentSessionSeconds();
        if (elapsed > 0) {
            setTotal(getTotal() + elapsed);
            saveToServer(elapsed);
            setStart(now()); // reset start after saving
        }
    }

    // ---------------------- UI ----------------------
    function injectUI() {
        if (document.getElementById('video-timer-container')) return;

        const box = document.createElement('div');
        box.id = 'video-timer-container';
        box.innerHTML = `
            <div style="display: flex; align-items: center; gap: 10px;">
                <span style="font-size: 24px; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));">🎥</span>
                <div style="display: flex; flex-direction: column; gap: 2px;">
                    <span style="font-size: 11px; font-weight: 500; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">Watch Time</span>
                    <strong id="video-timer" style="font-size: 22px; font-weight: 700; color: #1e293b; font-family: 'SF Mono', 'Monaco', 'Consolas', monospace; letter-spacing: 1px;">00:00</strong>
                </div>
            </div>
        `;
        box.style.cssText = `
            position: fixed;
            top: 70px;
            right: 60px;
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border: 1px solid rgba(148, 163, 184, 0.2);
            padding: 16px 20px;
            border-radius: 16px;
            z-index: 9999;
            box-shadow: 
                0 4px 6px -1px rgba(0, 0, 0, 0.1),
                0 2px 4px -1px rgba(0, 0, 0, 0.06),
                0 0 0 1px rgba(255, 255, 255, 0.1) inset;
            backdrop-filter: blur(10px);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        `;

        // Add hover effect
        box.onmouseenter = () => {
            box.style.transform = 'translateY(-2px)';
            box.style.boxShadow = `
                0 10px 15px -3px rgba(0, 0, 0, 0.1),
                0 4px 6px -2px rgba(0, 0, 0, 0.05),
                0 0 0 1px rgba(255, 255, 255, 0.1) inset
            `;
        };
        box.onmouseleave = () => {
            box.style.transform = 'translateY(0)';
            box.style.boxShadow = `
                0 4px 6px -1px rgba(0, 0, 0, 0.1),
                0 2px 4px -1px rgba(0, 0, 0, 0.06),
                0 0 0 1px rgba(255, 255, 255, 0.1) inset
            `;
        };

        document.body.appendChild(box);
    }

    // ---------------------- Idle Detection ----------------------
    function markActive() { lastActivity = now(); }
    ['mousemove','keydown','scroll','click'].forEach(e =>
        document.addEventListener(e, markActive, true)
    );

    function startIdleWatcher() {
        if (idleInterval) return;
        idleInterval = setInterval(() => {
            if (!isIdle && (now() - lastActivity) >= IDLE_LIMIT) {
                triggerIdleWarning();
            }
        }, 5000);
    }

    function triggerIdleWarning() {
        isIdle = true;
        pause();

        if (document.getElementById('awake-modal')) return;

        const modal = document.createElement('div');
        modal.id = 'awake-modal';
        modal.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(8px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10000;
            animation: fadeIn 0.2s ease-out;
        `;

        modal.innerHTML = `
            <style>
                @keyframes fadeIn {
                    from { opacity: 0; }
                    to { opacity: 1; }
                }
                @keyframes slideUp {
                    from { transform: translateY(20px); opacity: 0; }
                    to { transform: translateY(0); opacity: 1; }
                }
                .awake-modal-content {
                    animation: slideUp 0.3s ease-out;
                }
                .awake-btn {
                    padding: 12px 24px;
                    border: none;
                    border-radius: 10px;
                    font-size: 15px;
                    font-weight: 600;
                    cursor: pointer;
                    transition: all 0.2s ease;
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
                }
                .awake-btn:hover {
                    transform: translateY(-1px);
                }
                .awake-btn:active {
                    transform: translateY(0);
                }
                #awake-yes {
                    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
                    color: white;
                    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
                }
                #awake-yes:hover {
                    box-shadow: 0 6px 16px rgba(59, 130, 246, 0.5);
                }
                #awake-no {
                    background: #f1f5f9;
                    color: #475569;
                }
                #awake-no:hover {
                    background: #e2e8f0;
                }
            </style>
            <div class="awake-modal-content" style="
                background: white;
                padding: 32px;
                border-radius: 20px;
                width: 380px;
                text-align: center;
                box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            ">
                <div style="
                    width: 64px;
                    height: 64px;
                    margin: 0 auto 20px;
                    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 32px;
                ">⏸️</div>
                <h3 style="
                    margin: 0 0 8px 0;
                    font-size: 22px;
                    font-weight: 700;
                    color: #1e293b;
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
                ">Still watching?</h3>
                <p style="
                    margin: 0 0 24px 0;
                    font-size: 15px;
                    color: #64748b;
                    line-height: 1.6;
                ">We noticed you've been inactive for a while. Are you still watching this video?</p>
                <div style="display: flex; gap: 12px; justify-content: center;">
                    <button id="awake-yes" class="awake-btn">Yes, I'm watching</button>
                    <button id="awake-no" class="awake-btn">No, take a break</button>
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        document.getElementById('awake-yes').onclick = () => {
            lastActivity = now();
            isIdle = false;
            modal.remove();
            start();
        };
        document.getElementById('awake-no').onclick = () => modal.remove();
    }

    // ---------------------- Events ----------------------
    document.addEventListener('visibilitychange', () => {
        document.hidden ? pause() : start();
    });

    window.addEventListener('beforeunload', () => pause());

    // ---------------------- Daily reset ----------------------
    function resetIfNewDay() {
        if (localStorage.getItem(KEY_DATE) !== today()) {
            localStorage.setItem(KEY_DATE, today());
            localStorage.removeItem(KEY_TOTAL);
            localStorage.removeItem(KEY_START);
        }
    }

    // ---------------------- Fetch initial DB time ----------------------
    function fetchInitialTime() {
        return fetch(
            M.cfg.wwwroot + '/local/consistencyscore/ajax/get_video_time.php',
            { credentials: 'same-origin' }
        )
        .then(r => r.json())
        .then(data => {
            setTotal(parseInt(data.seconds || 0, 10));
        })
        .catch(() => {});
    }

    // ---------------------- Main ----------------------
    document.addEventListener('DOMContentLoaded', () => {
        video = document.querySelector('video');
        if (!video) return;

        resetIfNewDay();
        injectUI();

        // 1️⃣ Fetch DB time first
        fetchInitialTime().then(() => {
            updateDisplay();

            // 2️⃣ Set start time after restoring DB total
            if (!getStart() && !video.paused) {
                setStart(now());
            }

            // 3️⃣ Only start timer if video is playing
            if (!video.paused) start();
        });

        // Event listeners for play/pause
        video.addEventListener('play', start);
        video.addEventListener('pause', pause);
        video.addEventListener('ended', pause);

        startIdleWatcher();
    });

})();