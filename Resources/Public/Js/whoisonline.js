document.addEventListener("DOMContentLoaded", function() {
    const container = document.getElementById('who-is-online-box');
    const url = '/?type=446'; // dein Ajax Typoscript-Endpunkt

    function refreshOnlineUsers() {
        fetch(url, { cache: "no-store" })
            .then(response => response.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const usersList = doc.querySelector('.online-users');
                if (usersList) {
                    container.querySelector('.online-users-wrapper').innerHTML = usersList.outerHTML;
                }
            })
            .catch(err => console.error('Error fetching online users:', err));
    }

    // alle 30 Sekunden aktualisieren
    setInterval(refreshOnlineUsers, 30000);

    // initial laden
    refreshOnlineUsers();
});