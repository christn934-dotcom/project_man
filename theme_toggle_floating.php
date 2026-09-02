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
    <span class="ft-icon-light"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg></span>
    <span class="ft-icon-dark"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg></span>
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
