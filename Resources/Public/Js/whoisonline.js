document.addEventListener("DOMContentLoaded", function() {
    const container = document.getElementById('who-is-online-box');
    if (!container) return;

    const wrapper = container.querySelector('.online-users-wrapper');
    const url = '/?type=446'; // dein Ajax Typoscript-Endpunkt
    const noUsersText = wrapper.dataset.text || 'No users online';

    function refreshOnlineUsers() {
        fetch(url, { cache: "no-store" })
            .then(response => response.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const usersList = doc.querySelector('.online-users');

                if (usersList && usersList.children.length > 0) {
                    wrapper.innerHTML = usersList.outerHTML;
                } else {
                    wrapper.innerHTML = `<div class="no-users">${noUsersText}</div>`;
                }
            })
            .catch(err => {
                console.error('Error fetching online users:', err);
                wrapper.innerHTML = `<div class="no-users">${noUsersText}</div>`;
            });
    }

    // alle 30 Sekunden aktualisieren
    setInterval(refreshOnlineUsers, 30000);

    // initial laden
    refreshOnlineUsers();
});