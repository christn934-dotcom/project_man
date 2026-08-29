<?php

/*|--------------------------------------------------------------------------| COOKIE CONSENT|--------------------------------------------------------------------------|
|
| Include this file at the bottom of every page (before </body>).
| It shows a consent banner if the user hasn't made a choice yet.
|
| Usage: include "cookie_consent.php";  (before </body>)
|
|--------------------------------------------------------------------------|*/

?>

<!-- COOKIE CONSENT BANNER -->
<div
    id="cookieConsentBanner"
    style="
        display: none;
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        background: #111827;
        color: #f3f4f6;
        padding: 18px 30px;
        z-index: 99999;
        box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.3);
    "
>
    <div style="
        max-width: 1200px;
        margin: 0 auto;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        flex-wrap: wrap;
    ">
        <div style="flex: 1; min-width: 280px;">
            <strong style="font-size: 14px; display: block; margin-bottom: 4px;">
                🍪 We use cookies
            </strong>
            <span style="font-size: 12px; color: #9ca3af; line-height: 1.6;">
                This system uses a <strong style="color: #e5e7eb;">Remember Me</strong> cookie to keep you signed in
                after you close your browser. No tracking cookies are used.
                You can accept or decline this feature below.
            </span>
        </div>
        <div style="display: flex; gap: 10px; flex-shrink: 0;">
            <button
                type="button"
                id="cookieAcceptBtn"
                style="
                    background: #4f46e5;
                    color: #ffffff;
                    border: none;
                    padding: 10px 22px;
                    border-radius: 8px;
                    font-size: 13px;
                    font-weight: 600;
                    cursor: pointer;
                "
            >
                Accept
            </button>
            <button
                type="button"
                id="cookieDeclineBtn"
                style="
                    background: transparent;
                    color: #9ca3af;
                    border: 1px solid #4b5563;
                    padding: 10px 22px;
                    border-radius: 8px;
                    font-size: 13px;
                    font-weight: 600;
                    cursor: pointer;
                "
            >
                Decline
            </button>
        </div>
    </div>
</div>


<script>

(function () {

    var CONSENT_KEY = "cookie_consent";
    var CONSENT_DAYS = 365;

    /**
     * Read a cookie value by name.
     */
    function getCookie(name) {

        var nameEQ = name + "=";
        var parts = document.cookie.split(";");

        for (var i = 0; i < parts.length; i++) {

            var c = parts[i].trim();

            if (c.indexOf(nameEQ) === 0) {
                return c.substring(nameEQ.length);
            }

        }

        return "";

    }


    /**
     * Set a cookie with expiration in days.
     */
    function setCookie(name, value, days) {

        var expires = "";
        var date = new Date();
        date.setTime(date.getTime() + days * 24 * 60 * 60 * 1000);
        expires = "; expires=" + date.toUTCString();

        document.cookie = name + "=" + value + expires + "; path=/; SameSite=Lax";

    }


    /**
     * Show the consent banner.
     */
    function showBanner() {

        var banner = document.getElementById("cookieConsentBanner");

        if (banner) {
            banner.style.display = "block";
        }

    }


    /**
     * Hide the consent banner.
     */
    function hideBanner() {

        var banner = document.getElementById("cookieConsentBanner");

        if (banner) {
            banner.style.display = "none";
        }

    }


    /**
     * Check if user has already given consent.
     * If not, show the banner.
     */
    var consent = getCookie(CONSENT_KEY);

    if (!consent) {
        showBanner();
    }


    /**
     * Accept button handler.
     */
    var acceptBtn = document.getElementById("cookieAcceptBtn");

    if (acceptBtn) {

        acceptBtn.addEventListener("click", function () {

            setCookie(CONSENT_KEY, "accepted", CONSENT_DAYS);
            hideBanner();

        });

    }


    /**
     * Decline button handler.
     */
    var declineBtn = document.getElementById("cookieDeclineBtn");

    if (declineBtn) {

        declineBtn.addEventListener("click", function () {

            setCookie(CONSENT_KEY, "declined", CONSENT_DAYS);
            hideBanner();

        });

    }


})();

</script>
