(function () {

    const KEY_TOTAL = 'notes_total_time';
    const KEY_START = 'notes_start_time';
    const KEY_DATE  = 'notes_date';
    const IDLE_LIMIT = 120; // seconds (2 minutes)

    let interval = null;
    let lastActivity = now();
    let idleInterval = null;
    let isIdle = false;


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
        const el = document.getElementById('notes-timer');
        if (el) el.textContent = format(totalSecondsNow());
    }

    function start() {
        if (!getStart()) setStart(now());
        if (!interval) interval = setInterval(updateDisplay, 1000);
    }

    function pause() {
        const elapsed = currentSessionSeconds();
        if (elapsed > 0) {
            setTotal(getTotal() + elapsed);
        }
        clearStart();
        clearInterval(interval);
        interval = null;
    }

    function saveToServer(seconds) {
        if (seconds <= 0) return;

        const data = new URLSearchParams();
        data.append('seconds', seconds);

        navigator.sendBeacon(
            M.cfg.wwwroot + '/local/consistencyscore/ajax/save_time.php',
            data
        );
    }

    function resetIfNewDay() {
        if (localStorage.getItem(KEY_DATE) !== today()) {
            localStorage.setItem(KEY_DATE, today());
            localStorage.removeItem(KEY_TOTAL);
            localStorage.removeItem(KEY_START);
        }
    }

    function fetchInitialTime() {
        return fetch(
            M.cfg.wwwroot + '/local/consistencyscore/ajax/get_time.php',
            { credentials: 'same-origin' }
        )
        .then(r => r.json())
        .then(data => {
            // ALWAYS trust DB
            setTotal(parseInt(data.seconds || 0, 10));
        })
        .catch(() => {});
    }

    function injectUI() {
        if (document.getElementById('notes-timer-container')) return;

        const box = document.createElement('div');
        box.id = 'notes-timer-container';
        box.innerHTML = '⏱ Notes time: <strong id="notes-timer">00:00</strong>';
        box.style.cssText =
            'position:fixed;top:100px;right:20px;background:#f5f5f5;' +
            'border:1px solid #ccc;padding:8px 12px;border-radius:6px;z-index:9999';

        document.body.appendChild(box);
    }
    function markActive() {
    lastActivity = now();
    }   

    ['mousemove', 'keydown', 'scroll', 'click'].forEach(e =>
    document.addEventListener(e, markActive, true)
    );

    function startIdleWatcher() {
    if (idleInterval) return;

    idleInterval = setInterval(() => {
        if (!isIdle && (now() - lastActivity) >= IDLE_LIMIT) {
            triggerIdleWarning();
        }
    }, 5000); // check every 5s
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
            <p style="margin-bottom:15px;font-size:16px">
                ⏸ Are you awake?
            </p>
            <button id="awake-yes" style="margin-right:10px">Yes, I'm awake</button>
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

    document.getElementById('awake-no').onclick = () => {
        modal.remove();
        // remain paused
    };
}

    document.addEventListener('visibilitychange', () => {
        document.hidden ? pause() : start();
    });

    window.addEventListener('beforeunload', () => {
        const elapsed = currentSessionSeconds();
        saveToServer(elapsed);
        pause();
    });

    resetIfNewDay();
    injectUI();

    fetchInitialTime().then(() => {
        updateDisplay();
        start();
    });
    startIdleWatcher();


})();
