<!-- Floating Theme Toggle — for login/register pages without sidebar -->
<style>
.floating-theme-toggle {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 1000;
    width: 44px;
    height: 44px;
    border: none;
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.15);
    cursor: pointer;
    font-size: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}
.floating-theme-toggle:hover {
    transform: scale(1.1);
    background: rgba(255, 255, 255, 0.2);
}
.floating-theme-toggle .ft-icon-light,
.floating-theme-toggle .ft-icon-dark {
    transition: opacity 0.3s, transform 0.3s;
    position: absolute;
}
.floating-theme-toggle .ft-icon-dark {
    opacity: 0;
    transform: rotate(-90deg) scale(0.5);
}
body.dark .floating-theme-toggle {
    background: rgba(255, 255, 255, 0.08);
    border-color: rgba(255, 255, 255, 0.12);
}
body.dark .floating-theme-toggle:hover {
    background: rgba(255, 255, 255, 0.15);
}
body.dark .floating-theme-toggle .ft-icon-light {
    opacity: 0;
    transform: rotate(90deg) scale(0.5);
}
body.dark .floating-theme-toggle .ft-icon-dark {
    opacity: 1;
    transform: rotate(0deg) scale(1);
}
</style>

<button class="floating-theme-toggle" onclick="toggleTheme()" title="Toggle Theme">
    <span class="ft-icon-light">☀️</span>
    <span class="ft-icon-dark">🌙</span>
</button>

<script>
// Sync floating toggle with theme state
(function() {
    function syncFloating() {
        var isDark = document.body.classList.contains('dark');
        var btn = document.querySelector('.floating-theme-toggle');
        if (btn) btn.title = isDark ? 'Switch to Light Mode' : 'Switch to Dark Mode';
    }
    syncFloating();
    // Re-sync after dark_mode.php runs
    setTimeout(syncFloating, 100);
})();
</script>
