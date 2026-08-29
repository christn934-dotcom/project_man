/*
|--------------------------------------------------------------------------|
| MOBILE SIDEBAR + OVERLAY
|--------------------------------------------------------------------------|
*/
(function () {
    var sidebar = document.querySelector(".sidebar");
    var menuBtn = document.querySelector(".mobile-menu");

    if (!sidebar || !menuBtn) return;

    // Create overlay if it doesn't exist
    var overlay = document.querySelector(".sidebar-overlay");
    if (!overlay) {
        overlay = document.createElement("div");
        overlay.className = "sidebar-overlay";
        document.body.appendChild(overlay);
    }

    function openSidebar() {
        sidebar.classList.add("mobile-open");
        overlay.classList.add("active");
        document.body.classList.add("mobile-menu-open");
    }

    function closeSidebar() {
        sidebar.classList.remove("mobile-open");
        overlay.classList.remove("active");
        document.body.classList.remove("mobile-menu-open");
    }

    // Toggle on hamburger click
    menuBtn.addEventListener("click", function (e) {
        e.stopPropagation();
        if (sidebar.classList.contains("mobile-open")) {
            closeSidebar();
        } else {
            openSidebar();
        }
    });

    // Close when clicking overlay
    overlay.addEventListener("click", closeSidebar);

    // Close on Escape key
    document.addEventListener("keydown", function (e) {
        if (e.key === "Escape" && sidebar.classList.contains("mobile-open")) {
            closeSidebar();
        }
    });

    // Close sidebar when clicking outside on mobile
    document.addEventListener("click", function (e) {
        if (
            sidebar.classList.contains("mobile-open") &&
            !sidebar.contains(e.target) &&
            e.target !== menuBtn &&
            !menuBtn.contains(e.target)
        ) {
            closeSidebar();
        }
    });

    // Close sidebar on window resize to desktop
    var resizeTimer;
    window.addEventListener("resize", function () {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function () {
            if (window.innerWidth > 850) {
                closeSidebar();
            }
        }, 100);
    });
})();
