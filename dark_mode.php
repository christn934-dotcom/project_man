<!-- Dark Mode Toggle JS — included before </body> in all pages -->
<script>
(function() {
    var key = 'promasy-theme';
    var body = document.body;

    // Apply saved theme immediately on load (no flash)
    var saved = localStorage.getItem(key);
    if (saved === 'dark') {
        body.classList.add('dark');
    } else if (saved === 'light') {
        body.classList.remove('dark');
    }

    // Sync all toggle buttons (sidebar + topbar)
    function syncToggles() {
        var isDark = body.classList.contains('dark');

        // Update sidebar toggle
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

        // Update topbar toggle
        var topbarToggle = document.querySelector('.theme-toggle-btn');
        if (topbarToggle) {
            topbarToggle.title = isDark ? 'Switch to Light Mode' : 'Switch to Dark Mode';
        }
    }

    syncToggles();

    // Sidebar toggle function
    window.toggleDarkMode = function() {
        body.classList.toggle('dark');
        localStorage.setItem(key, body.classList.contains('dark') ? 'dark' : 'light');
        syncToggles();
    };

    // Topbar toggle function (same behavior)
    window.toggleTheme = function() {
        body.classList.toggle('dark');
        localStorage.setItem(key, body.classList.contains('dark') ? 'dark' : 'light');
        syncToggles();
    };
})();
</script>
