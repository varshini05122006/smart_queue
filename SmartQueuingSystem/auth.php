<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


function isLoggedIn() {
    return isset($_SESSION["user_id"]);
}


// For PAGES: user must be logged in, otherwise go to login

function requireLogin() {

    if (!isset($_SESSION["user_id"])) {
        header("Location: login.php");
        exit;
    }
}


// For PAGES: user must have this exact role

function requireRole($role) {

    requireLogin();

    if ($_SESSION["role"] !== $role) {
        http_response_code(403);
        echo "Access denied. This page is for " . htmlspecialchars($role) . " only.";
        exit;
    }
}


// For APIs (JSON): user must have this exact role

function requireRoleApi($role) {

    header("Content-Type: application/json");

    if (!isset($_SESSION["user_id"])) {
        http_response_code(401);
        echo json_encode([
            "success" => false,
            "message" => "Please log in first."
        ]);
        exit;
    }

    if ($_SESSION["role"] !== $role) {
        http_response_code(403);
        echo json_encode([
            "success" => false,
            "message" => "Access denied."
        ]);
        exit;
    }
}

?>
