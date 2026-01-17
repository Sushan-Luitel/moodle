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
        box.innerHTML = '⏱ Video time: <strong id="video-timer">00:00</strong>';
        box.style.cssText =
            'position:fixed;top:150px;right:20px;background:#f5f5f5;' +
            'border:1px solid #ccc;padding:8px 12px;border-radius:6px;z-index:9999';

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
            position:fixed;
            top:0;left:0;right:0;bottom:0;
            background:rgba(0,0,0,0.5);
            display:flex;
            align-items:center;
            justify-content:center;
            z-index:10000;
        `;

        modal.innerHTML = `
            <div style="background:#fff;padding:20px;border-radius:8px;width:300px;text-align:center">
                <p style="margin-bottom:15px;font-size:16px">⏸ Are you awake?</p>
                <button id="awake-yes" style="margin-right:10px">Yes</button>
                <button id="awake-no">No</button>
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
