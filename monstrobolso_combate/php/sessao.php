<?php

if (session_status() === PHP_SESSION_NONE) {
    ini_set("session.use_strict_mode", "1");
    ini_set("session.use_only_cookies", "1");
    ini_set("session.use_trans_sid", "0");

    session_set_cookie_params([
        "lifetime" => 0,
        "path" => "/Maremon/monstrobolso_combate/",
        "httponly" => true,
        "secure" => !empty($_SERVER["HTTPS"])
            && $_SERVER["HTTPS"] !== "off",
        "samesite" => "Lax"
    ]);

    session_start();
}
