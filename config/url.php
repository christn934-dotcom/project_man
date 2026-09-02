<?php

/**
 * Auto-detect the site base URL.
 * Works on both localhost and 000webhost.
 */
function site_url($path = "") {
    $protocol = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") ? "https" : "http";
    $host = $_SERVER["HTTP_HOST"] ?? "localhost";
    $base = $protocol . "://" . $host;
    if (!empty($path)) {
        $base .= "/" . ltrim($path, "/");
    }
    return $base;
}

/**
 * Get just the base URL (no trailing slash)
 */
function base_url() {
    $protocol = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") ? "https" : "http";
    $host = $_SERVER["HTTP_HOST"] ?? "localhost";
    return $protocol . "://" . $host;
}

?>
