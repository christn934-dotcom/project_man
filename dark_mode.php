// Dark Mode Toggle JS — included before </body> in all pages
(function() {
    var key = 'promasy-theme';
    var body = document.body;

    /* Apply saved theme immediately from localStorage */
    var saved = localStorage.getItem(key);
    if (saved === 'dark') {
        body.classList.add('dark');
        body.classList.remove('light');
    } else if (saved === 'light') {
        body.classList.add('light');
        body.classList.remove('dark');
    } else {
        /* No saved preference — let system decide, but add no class */
        body.classList.remove('dark', 'light');
    }

    /* Sync theme from DB for logged-in users */
    fetch('api/user_theme.php')
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.theme) {
                applyTheme(data.theme === 'dark');
            }
        })
        .catch(function() {});

    function applyTheme(isDark) {
        if (isDark) {
            body.classList.add('dark');
            body.classList.remove('light');
        } else {
            body.classList.add('light');
            body.classList.remove('dark');
        }
        syncToggles();
        syncSettingsToggles();
    }

    /* Sync all toggle buttons (sidebar + topbar) */
    function syncToggles() {
        var isDark = body.classList.contains('dark');

        // Sidebar toggle
        var sidebarToggle = document.querySelector('.dark-mode-toggle');
        if (sidebarToggle) {
            var icon = sidebarToggle.querySelector('.toggle-icon');
            var label = sidebarToggle.querySelector('span:not(.toggle-icon):not(.toggle-track)');
            if (icon) icon.textContent = isDark ? '\u2600\uFE0F' : '\uD83C\uDF19';
            if (label) {
                var text = label.textContent.trim();
                if (text === 'Dark Mode' || text === 'Light Mode') {
                    label.textContent = isDark ? 'Light Mode' : 'Dark Mode';
                }
            }
        }

        // Topbar toggle
        var topbarToggle = document.querySelector('.theme-toggle-btn');
        if (topbarToggle) {
            topbarToggle.title = isDark ? 'Switch to Light Mode' : 'Switch to Dark Mode';
        }
    }

    /* Sync settings page theme toggle if present */
    function syncSettingsToggles() {
        var settingsTheme = document.getElementById('settingsThemeToggle');
        if (settingsTheme) {
            settingsTheme.checked = body.classList.contains('dark');
        }
    }

    syncToggles();
    syncSettingsToggles();

    /* Save theme to DB */
    function saveThemeToDB(isDark) {
        fetch('api/user_theme.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ theme: isDark ? 'dark' : 'light' })
        }).catch(function() {});
    }

    /* Toggle — switches between dark and light (never removes both classes) */
    function toggle() {
        var isDark = body.classList.contains('dark');
        applyTheme(!isDark);
        localStorage.setItem(key, isDark ? 'light' : 'dark');
        saveThemeToDB(!isDark);
    }

    /* Sidebar toggle function */
    window.toggleDarkMode = toggle;

    /* Topbar toggle function */
    window.toggleTheme = toggle;

    /* Settings page toggle function */
    window.toggleSettingsTheme = toggle;
})();
