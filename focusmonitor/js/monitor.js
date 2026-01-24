window.addEventListener('load', () => {
    let tabSwitchWarnings = 0;
    let faceWarnings = 0;
    const MAX_TAB_WARNINGS = 5;
    let alreadyForced = false;
    let isSubmitting = false;
    let faceDetectionInterval = null;
    let consecutiveNoFaceFrames = 0;
    const MAX_NO_FACE_FRAMES = 15;
    let noFaceStartTime = null;

    const MODEL_URL = 'https://cdn.jsdelivr.net/npm/@vladmandic/face-api@1.7.12/model/';
    let modelsLoaded = false;

    showConsentModal();

    function showConsentModal() {
        const modal = document.createElement('div');
        modal.id = 'fm-consent';
        modal.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.75);
            backdrop-filter: blur(10px);
            z-index: 100000;
            display: flex;
            justify-content: center;
            align-items: center;
            animation: fadeIn 0.3s ease-out;
        `;
        modal.innerHTML = `
            <style>
                @keyframes fadeIn {
                    from { opacity: 0; }
                    to { opacity: 1; }
                }
                @keyframes slideUp {
                    from { transform: translateY(30px); opacity: 0; }
                    to { transform: translateY(0); opacity: 1; }
                }
                .fm-consent-box {
                    animation: slideUp 0.4s ease-out;
                }
                #fm-accept {
                    padding: 14px 32px;
                    margin-top: 24px;
                    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
                    color: white;
                    border: none;
                    border-radius: 12px;
                    font-size: 16px;
                    font-weight: 600;
                    cursor: pointer;
                    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
                    transition: all 0.2s ease;
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
                }
                #fm-accept:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 6px 16px rgba(59, 130, 246, 0.5);
                }
                #fm-accept:active {
                    transform: translateY(0);
                }
            </style>
            <div class="fm-consent-box" style="
                background: white;
                padding: 40px;
                border-radius: 24px;
                text-align: center;
                max-width: 480px;
                box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            ">
                <div style="
                    width: 80px;
                    height: 80px;
                    margin: 0 auto 24px;
                    background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 40px;
                ">🎯</div>
                <h3 style="
                    margin: 0 0 16px 0;
                    font-size: 28px;
                    font-weight: 700;
                    color: #1e293b;
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
                ">Focus Monitoring Active</h3>
                <div style="
                    text-align: left;
                    background: #f8fafc;
                    padding: 20px;
                    border-radius: 12px;
                    margin: 20px 0;
                    border-left: 4px solid #3b82f6;
                ">
                    <p style="margin: 0 0 12px 0; color: #475569; font-size: 15px; line-height: 1.6;">
                        <strong style="color: #1e293b;">📹 Camera Monitoring:</strong><br>
                        Your face must remain visible throughout the quiz
                    </p>
                    <p style="margin: 0; color: #475569; font-size: 15px; line-height: 1.6;">
                        <strong style="color: #1e293b;">⚠️ Tab Switching:</strong><br>
                        Switching tabs 3 times will auto-submit your attempt
                    </p>
                </div>
                <button id="fm-accept">I Understand & Continue</button>
            </div>
        `;
        document.body.appendChild(modal);

        document.getElementById('fm-accept').onclick = () => {
            modal.remove();
            loadFaceDetectionModels();
        };
    }

    async function loadFaceDetectionModels() {
        const loadingMsg = document.createElement('div');
        loadingMsg.style.cssText = `
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            color: #fff;
            padding: 32px 48px;
            border-radius: 20px;
            z-index: 10002;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3);
            text-align: center;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        `;
        loadingMsg.innerHTML = `
            <div style="font-size: 40px; margin-bottom: 16px;">🔄</div>
            <div style="font-size: 16px; font-weight: 600;">Loading face detection models...</div>
        `;
        document.body.appendChild(loadingMsg);

        try {
            await loadScript('https://cdn.jsdelivr.net/npm/@vladmandic/face-api@1.7.12/dist/face-api.js');
            await faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL);
            
            modelsLoaded = true;
            loadingMsg.remove();
            setTimeout(startMonitoring, 100);
        } catch (err) {
            console.error('Failed to load face detection models:', err);
            loadingMsg.innerHTML = `
                <div style="font-size: 40px; margin-bottom: 16px;">❌</div>
                <div style="font-size: 16px; font-weight: 600;">Error loading face detection</div>
                <div style="font-size: 14px; margin-top: 8px; opacity: 0.8;">Please refresh the page</div>
            `;
        }
    }

    function loadScript(src) {
        return new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = src;
            script.onload = resolve;
            script.onerror = reject;
            document.head.appendChild(script);
        });
    }

    function startMonitoring() {
        initCamera();
        initTabSwitchDetection();
    }

    function initCamera() {
        if (document.getElementById('fm-facebox')) return;

        const box = document.createElement('div');
        box.id = 'fm-facebox';
        box.style.cssText = `
            position: fixed;
            bottom: 200px;
            left: 24px;
            width: 340px;
            background: linear-gradient(145deg, rgba(15, 23, 42, 0.98) 0%, rgba(30, 41, 59, 0.98) 50%, rgba(51, 65, 85, 0.98) 100%);
            border-radius: 28px;
            padding: 24px;
            z-index: 999;
            box-shadow: 
                0 25px 50px -12px rgba(0, 0, 0, 0.6),
                0 0 0 1px rgba(255, 255, 255, 0.1) inset,
                0 0 40px rgba(59, 130, 246, 0.15);
            backdrop-filter: blur(24px);
            border: 1.5px solid rgba(255, 255, 255, 0.2);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        `;
        
        box.onmouseenter = () => {
            box.style.transform = 'translateY(-6px) scale(1.02)';
            box.style.boxShadow = `
                0 30px 60px -15px rgba(0, 0, 0, 0.7),
                0 0 0 1px rgba(255, 255, 255, 0.15) inset,
                0 0 50px rgba(59, 130, 246, 0.25)
            `;
        };
        box.onmouseleave = () => {
            box.style.transform = 'translateY(0) scale(1)';
            box.style.boxShadow = `
                0 25px 50px -12px rgba(0, 0, 0, 0.6),
                0 0 0 1px rgba(255, 255, 255, 0.1) inset,
                0 0 40px rgba(59, 130, 246, 0.15)
            `;
        };
        
        box.innerHTML = `
            <style>
                @keyframes pulse {
                    0%, 100% { opacity: 1; transform: scale(1); }
                    50% { opacity: 0.6; transform: scale(0.95); }
                }
                @keyframes shimmer {
                    0% { background-position: -200% 0; }
                    100% { background-position: 200% 0; }
                }
                @keyframes float {
                    0%, 100% { transform: translateY(0px); }
                    50% { transform: translateY(-3px); }
                }
            </style>
            <div style="
                position: relative;
                background: linear-gradient(135deg, rgba(59, 130, 246, 0.15) 0%, rgba(37, 99, 235, 0.15) 100%);
                border-radius: 20px;
                padding: 4px;
                margin-bottom: 18px;
                overflow: hidden;
            ">
                <div style="
                    position: absolute;
                    inset: 0;
                    background: linear-gradient(90deg, 
                        transparent 0%, 
                        rgba(59, 130, 246, 0.1) 50%, 
                        transparent 100%);
                    background-size: 200% 100%;
                    animation: shimmer 3s linear infinite;
                    border-radius: 20px;
                "></div>
                <div style="
                    position: relative;
                    background: rgba(0, 0, 0, 0.3);
                    border-radius: 18px;
                    padding: 14px;
                    border: 1px solid rgba(59, 130, 246, 0.3);
                ">
                    <div style="position: relative;">
                        <video autoplay muted playsinline id="fm-video"
                            style="
                                width: 100%; 
                                height: 200px; 
                                background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); 
                                border-radius: 14px; 
                                object-fit: cover;
                                box-shadow: 
                                    0 10px 25px rgba(0, 0, 0, 0.5),
                                    0 0 0 1px rgba(255, 255, 255, 0.05) inset;
                            "></video>
                        <canvas id="fm-canvas" 
                            style="
                                position: absolute; 
                                top: 0; 
                                left: 0; 
                                width: 100%; 
                                height: 200px; 
                                border-radius: 14px;
                            "></canvas>
                        <div style="
                            position: absolute;
                            top: 12px;
                            left: 12px;
                            background: linear-gradient(135deg, rgba(239, 68, 68, 0.95) 0%, rgba(220, 38, 38, 0.95) 100%);
                            backdrop-filter: blur(12px);
                            padding: 7px 14px;
                            border-radius: 10px;
                            font-size: 11px;
                            font-weight: 700;
                            color: #fff;
                            display: flex;
                            align-items: center;
                            gap: 7px;
                            box-shadow: 
                                0 4px 10px rgba(239, 68, 68, 0.4),
                                0 0 0 1px rgba(255, 255, 255, 0.2) inset;
                            animation: float 3s ease-in-out infinite;
                        ">
                            <span style="
                                width: 8px;
                                height: 8px;
                                background: #fff;
                                border-radius: 50%;
                                animation: pulse 2s ease-in-out infinite;
                                box-shadow: 0 0 12px rgba(255, 255, 255, 0.8);
                            "></span>
                            LIVE
                        </div>
                        <div style="
                            position: absolute;
                            top: 12px;
                            right: 12px;
                            background: linear-gradient(135deg, rgba(15, 23, 42, 0.95) 0%, rgba(30, 41, 59, 0.95) 100%);
                            backdrop-filter: blur(12px);
                            padding: 7px 12px;
                            border-radius: 10px;
                            font-size: 11px;
                            font-weight: 700;
                            color: #60a5fa;
                            box-shadow: 
                                0 4px 10px rgba(0, 0, 0, 0.4),
                                0 0 0 1px rgba(96, 165, 250, 0.3) inset;
                        ">
                            🎯 MONITORING
                        </div>
                    </div>
                </div>
            </div>
            <div style="
                background: linear-gradient(135deg, rgba(30, 41, 59, 0.6) 0%, rgba(15, 23, 42, 0.6) 100%);
                border-radius: 16px;
                padding: 14px 16px;
                border: 1px solid rgba(255, 255, 255, 0.08);
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2) inset;
            ">
                <div id="fm-status" style="
                    text-align: center;
                    font-size: 13px;
                    font-weight: 600;
                    color: #cbd5e1;
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
                    letter-spacing: 0.3px;
                ">
                    📹 Initializing camera...
                </div>
            </div>
        `;
        document.body.appendChild(box);

        const video = document.getElementById('fm-video');
        const canvas = document.getElementById('fm-canvas');
        const statusDiv = document.getElementById('fm-status');

        navigator.mediaDevices.getUserMedia({ 
            video: { 
                width: { ideal: 640 },
                height: { ideal: 480 }
            } 
        })
        .then(stream => {
            video.srcObject = stream;
            video.play();
            
            video.addEventListener('loadeddata', () => {
                statusDiv.innerHTML = '<span style="color: #22c55e;">✓</span> Camera active - Detecting face...';
                startFaceDetection(video, canvas, statusDiv);
            });
        })
        .catch(err => {
            console.error('Camera access denied:', err);
            statusDiv.innerHTML = '<span style="color: #ef4444;">❌</span> Camera access denied';
            triggerWarning('NO_CAMERA', 0);
        });
    }

    async function startFaceDetection(video, canvas, statusDiv) {
        if (!modelsLoaded) {
            console.error('Face detection models not loaded');
            return;
        }

        const displaySize = { width: video.offsetWidth, height: video.offsetHeight };
        faceapi.matchDimensions(canvas, displaySize);

        faceDetectionInterval = setInterval(async () => {
            try {
                const detections = await faceapi.detectAllFaces(
                    video, 
                    new faceapi.TinyFaceDetectorOptions({
                        inputSize: 224,
                        scoreThreshold: 0.5
                    })
                );

                const ctx = canvas.getContext('2d');
                ctx.clearRect(0, 0, canvas.width, canvas.height);

                if (detections.length > 0) {
                    if (noFaceStartTime !== null) {
                        noFaceStartTime = null;
                    }
                    consecutiveNoFaceFrames = 0;
                    statusDiv.innerHTML = `<span style="color: #22c55e;">✓</span> Face detected (${detections.length})`;

                    const resizedDetections = faceapi.resizeResults(detections, displaySize);
                    resizedDetections.forEach(detection => {
                        const box = detection.box;
                        ctx.strokeStyle = '#22c55e';
                        ctx.lineWidth = 3;
                        ctx.shadowColor = '#22c55e';
                        ctx.shadowBlur = 10;
                        ctx.strokeRect(box.x, box.y, box.width, box.height);
                        ctx.shadowBlur = 0;
                    });
                } else {
                    if (noFaceStartTime === null) {
                        noFaceStartTime = Date.now();
                    }
                    
                    consecutiveNoFaceFrames++;
                    const secondsNoFace = Math.floor(consecutiveNoFaceFrames / 3);
                    statusDiv.innerHTML = `<span style="color: #eab308;">⚠️</span> No face detected (${secondsNoFace}s)`;

                    if (consecutiveNoFaceFrames >= MAX_NO_FACE_FRAMES) {
                        const duration = Math.floor((Date.now() - noFaceStartTime) / 1000);
                        triggerWarning('NO_FACE', duration);
                        consecutiveNoFaceFrames = 0;
                        noFaceStartTime = null;
                    }
                }
            } catch (err) {
                console.error('Face detection error:', err);
            }
        }, 333);
    }

    function initTabSwitchDetection() {
    let lastSwitchTime = 0;
    let tabSwitchCount = 0;
    const DEBOUNCE_MS = 2000;

    document.addEventListener('visibilitychange', () => {
        const now = Date.now();

        // Count ONLY when tab becomes hidden
        if (document.visibilityState === 'hidden' &&
            !isSubmitting &&
            (now - lastSwitchTime) > DEBOUNCE_MS
        ) {
            lastSwitchTime = now;
            tabSwitchCount++;

            console.log(
                `Tab switch #${tabSwitchCount} detected at`,
                new Date(now).toISOString()
            );

            // Send EXACT total count to backend
            triggerWarning('TAB_SWITCH', tabSwitchCount);
        }
    });
}

    function triggerWarning(type, warningCount, duration = 0) {
        if (alreadyForced || isSubmitting) {
            console.log('Warning ignored - already forced or submitting');
            return;
        }

        if (type === 'TAB_SWITCH') {
            // ✅ USE THE EXACT COUNT PROVIDED - DO NOT INCREMENT
            tabSwitchWarnings = warningCount;
            
            console.log('========================================');
            console.log('Tab Switch Event Triggered');
            console.log('Exact count received:', tabSwitchWarnings);
            console.log('Will send to database:', tabSwitchWarnings);
            console.log('========================================');
            
            // Show warning with current count
            showWarning(type, tabSwitchWarnings, MAX_TAB_WARNINGS, tabSwitchWarnings >= MAX_TAB_WARNINGS);
            
            // Log the actual count to server
            logEvent(type, tabSwitchWarnings, duration);

            // Check if we've reached the limit (3 or more)
            if (tabSwitchWarnings >= MAX_TAB_WARNINGS) {
                console.log('Maximum tab switches reached - forcing submit');
                alreadyForced = true;
                if (faceDetectionInterval) {
                    clearInterval(faceDetectionInterval);
                }
                forceSubmit();
            }
        } else if (type === 'NO_CAMERA') {
            // NO_CAMERA is logged separately - does NOT affect tab switch count
            console.log('Camera access issue detected');
            showWarning(type, 1, MAX_TAB_WARNINGS, false);
            logEvent(type, 1, duration);
        } else if (type === 'NO_FACE') {
            faceWarnings++;
            console.log(`Face not detected warning #${faceWarnings} (${duration}s)`);
            showWarning(type, faceWarnings, null, false);
            logEvent(type, faceWarnings, duration);
        }
    }

    function showWarning(type, count, max, willSubmit) {
        const w = document.createElement('div');
        w.className = 'warning-notification';
        w.style.cssText = `
            position: fixed;
            top: 24px;
            left: 50%;
            transform: translateX(-50%);
            background: ${willSubmit ? 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)' : 'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)'};
            color: #fff;
            padding: 16px 28px;
            font-weight: 600;
            border-radius: 16px;
            z-index: 9999;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            animation: slideDown 0.3s ease-out;
            border: 1px solid rgba(255, 255, 255, 0.2);
        `;
        
        const icon = willSubmit ? '🚨' : '⚠️';
        
        if (type === 'TAB_SWITCH') {
            w.innerHTML = `
                <div style="display: flex; align-items: center; gap: 12px;">
                    <span style="font-size: 24px;">${icon}</span>
                    <div>
                        <div style="font-size: 15px; font-weight: 700;">Tab Switch Detected</div>
                        <div style="font-size: 13px; opacity: 0.95; margin-top: 2px;">
                            Warning ${count}/${max} - ${max - count} remaining before auto-submit
                        </div>
                    </div>
                </div>
            `;
        } else if (type === 'NO_FACE') {
            w.innerHTML = `
                <div style="display: flex; align-items: center; gap: 12px;">
                    <span style="font-size: 24px;">${icon}</span>
                    <div>
                        <div style="font-size: 15px; font-weight: 700;">Face Not Visible</div>
                        <div style="font-size: 13px; opacity: 0.95; margin-top: 2px;">
                            Warning #${count} - Ensure your face is visible
                        </div>
                    </div>
                </div>
            `;
        } else if (type === 'NO_CAMERA') {
            w.innerHTML = `
                <div style="display: flex; align-items: center; gap: 12px;">
                    <span style="font-size: 24px;">${icon}</span>
                    <div>
                        <div style="font-size: 15px; font-weight: 700;">Camera Access Required</div>
                        <div style="font-size: 13px; opacity: 0.95; margin-top: 2px;">
                            Please enable camera access to continue
                        </div>
                    </div>
                </div>
            `;
        }
        
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideDown {
                from { transform: translate(-50%, -20px); opacity: 0; }
                to { transform: translate(-50%, 0); opacity: 1; }
            }
        `;
        document.head.appendChild(style);
        
        document.body.appendChild(w);
        setTimeout(() => w.remove(), 4000);
    }

    function logEvent(type, warningCount, duration) {
        if (!window.M || !M.cfg) {
            console.error('Moodle config not available');
            return;
        }
        
        const params = new URLSearchParams(window.location.search);
        const attemptId = params.get('attempt');
        
        if (!attemptId) {
            console.error('No attempt ID found in URL for logging');
            return;
        }
        
        const logData = {
            eventtype: type,
            warningcount: warningCount,
            duration: duration
        };
        
        console.log('=== SENDING TO SERVER ===');
        console.log('Attempt ID:', attemptId);
        console.log('Event Type:', type);
        console.log('Warning Count:', warningCount);
        console.log('Duration:', duration);
        console.log('Full URL:', M.cfg.wwwroot + '/local/focusmonitor/save_event.php?attemptid=' + attemptId);
        
        fetch(M.cfg.wwwroot + '/local/focusmonitor/save_event.php?attemptid=' + attemptId, {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(logData)
        })
        .then(response => {
            console.log('Response status:', response.status);
            return response.text();
        })
        .then(text => {
            console.log('=== RAW RESPONSE ===');
            console.log(text);
            
            try {
                const data = JSON.parse(text);
                console.log('=== PARSED JSON ===');
                console.log(data);
            } catch (e) {
                console.error('=== NOT VALID JSON ===');
                console.error('Response was:', text);
            }
        })
        .catch(err => {
            console.log('=== EVENT LOGGING FAILED ===');
            console.error(err);
        });
    }

    function forceSubmit() {
        isSubmitting = true;
        
        const overlay = document.createElement('div');
        overlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(10px);
            z-index: 100001;
            display: flex;
            justify-content: center;
            align-items: center;
            animation: fadeIn 0.3s ease-out;
        `;
        overlay.innerHTML = `
            <style>
                @keyframes fadeIn {
                    from { opacity: 0; }
                    to { opacity: 1; }
                }
                @keyframes scaleIn {
                    from { transform: scale(0.9); opacity: 0; }
                    to { transform: scale(1); opacity: 1; }
                }
                @keyframes spin {
                    from { transform: rotate(0deg); }
                    to { transform: rotate(360deg); }
                }
            </style>
            <div style="
                text-align: center;
                background: white;
                padding: 48px;
                border-radius: 24px;
                max-width: 500px;
                box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3);
                animation: scaleIn 0.4s ease-out;
            ">
                <div style="
                    width: 80px;
                    height: 80px;
                    margin: 0 auto 24px;
                    background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 40px;
                ">🚨</div>
                <h2 style="
                    margin: 0 0 12px 0;
                    font-size: 24px;
                    font-weight: 700;
                    color: #1e293b;
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
                ">Quiz Attempt Ending</h2>
                <p style="
                    margin: 0 0 8px 0;
                    font-size: 16px;
                    color: #475569;
                    line-height: 1.6;
                ">Multiple violations detected</p>
                <p style="
                    margin: 0 0 32px 0;
                    font-size: 15px;
                    color: #64748b;
                ">Your quiz is being submitted automatically...</p>
                <div style="
                    width: 40px;
                    height: 40px;
                    margin: 0 auto;
                    border: 4px solid #e2e8f0;
                    border-top-color: #3b82f6;
                    border-radius: 50%;
                    animation: spin 1s linear infinite;
                "></div>
                <p style="
                    font-size: 13px;
                    color: #94a3b8;
                    margin-top: 24px;
                    margin-bottom: 0;
                ">Please wait, do not close this window</p>
            </div>
        `;
        document.body.appendChild(overlay);

        const params = new URLSearchParams(window.location.search);
        const attemptId = params.get('attempt');

        if (!attemptId) {
            alert('Error: Could not identify quiz attempt. Please contact your administrator.');
            overlay.remove();
            isSubmitting = false;
            return;
        }

        setTimeout(() => {
            window.location.href = M.cfg.wwwroot + '/local/focusmonitor/force_end.php?attemptid=' + attemptId;
        }, 2000);
    }
});