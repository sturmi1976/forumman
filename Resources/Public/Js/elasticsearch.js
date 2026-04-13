(function () {

    console.log('ELASTIC JS LOADED');

    function init() {

        const interval = setInterval(() => {

            const btn = document.getElementById('es-reindex-button');

            if (btn) {
                console.log('BUTTON FOUND');

                btn.addEventListener('click', function () {
                    openModal();
                });

                clearInterval(interval);
            }

        }, 300);
    }

    function openModal() {
        const overlay = document.createElement('div');
        overlay.style.cssText = `
            position:fixed;
            top:0;left:0;
            width:100%;height:100%;
            background:rgba(0,0,0,0.6);
            display:flex;
            align-items:center;
            justify-content:center;
            z-index:999999;
        `;

        overlay.innerHTML = `
            <div style="
                background:#ffffff;
                padding:24px;
                width:420px;
                border-radius:12px;
                box-shadow:0 10px 30px rgba(0,0,0,0.25);
                font-family:Arial, sans-serif;
            ">

                <h3 style="
                    margin:0 0 15px 0;
                    font-size:20px;
                    font-weight:600;
                    color:#333;
                ">
                    🔄 Elasticsearch Reindex
                </h3>

                <div id="es-status" style="
                    font-size:14px;
                    color:#666;
                    margin-bottom:10px;
                ">
                    Bereit...
                </div>

                <div style="
                    background:#f1f1f1;
                    border-radius:8px;
                    overflow:hidden;
                    height:16px;
                    margin-bottom:15px;
                ">
                    <div id="es-bar" style="
                        height:100%;
                        width:0%;
                        background:linear-gradient(90deg,#007acc,#00c6ff);
                        transition:width 0.3s ease;
                    "></div>
                </div>

                <div style="
                    display:flex;
                    justify-content:space-between;
                    gap:10px;
                ">
                    <button id="es-start" style="
                        flex:1;
                        padding:10px;
                        background:#28a745;
                        color:#fff;
                        border:none;
                        border-radius:6px;
                        cursor:pointer;
                        font-weight:500;
                    ">
                        ▶ Start
                    </button>

                    <button id="es-close" style="
                        flex:1;
                        padding:10px;
                        background:#e0e0e0;
                        color:#333;
                        border:none;
                        border-radius:6px;
                        cursor:pointer;
                        font-weight:500;
                    ">
                        ✖ Schließen
                    </button>
                </div>

            </div>
        `;

        document.body.appendChild(overlay);

        document.getElementById('es-close').onclick = () => overlay.remove();
        document.getElementById('es-start').onclick = startReindex;
    }

    function startReindex() {

        let offset = 0;

        const startBtn = document.getElementById('es-start');
    const status = document.getElementById('es-status');

    // ✅ Button deaktivieren + visuell ändern
    if (startBtn) {
        startBtn.disabled = true;
        startBtn.innerText = '⏳ Läuft...';
        startBtn.style.background = '#999';
        startBtn.style.cursor = 'not-allowed';
    }

    if (status) {
        status.innerText = 'Starte Indexierung...';
    }

    const url = TYPO3.settings.ajaxUrls['forumman_reindex'];

        function run() {

            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ offset })
            })
            .then(r => r.json())
            .then(data => {

                offset = data.nextOffset;

                const bar = document.getElementById('es-bar');
                const status = document.getElementById('es-status');

                // 👉 Fortschritt erhöhen (Fake +10%)
                let current = parseInt(bar.style.width) || 0;
                let next = Math.min(current + 10, 100);
                bar.style.width = next + '%';

                if (status) {
                    status.innerText = 'Indexiert: ' + offset;
                }

                if (!data.finished) {
                    run();
                } else {
                    
                    bar.style.width = '100%';

                    if (status) {
                        status.innerText = 'Fertig!';

                        if (startBtn) {
        startBtn.disabled = false;
        startBtn.innerText = '▶ Start';
        startBtn.style.background = '#28a745';
        startBtn.style.cursor = 'pointer';
    }
                    }
                }
            })
            .catch(err => {
                console.error(err);

                const status = document.getElementById('es-status');
                if (status) {
                    status.innerText = 'Fehler bei der Indexierung!';
                }
            });
        }

        run();
    }

    init();

})();