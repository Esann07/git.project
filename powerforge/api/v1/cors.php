<?php
/**
 * CORS for the JSON API.
 *
 * EDIT THIS: add the origin(s) your new frontend runs on. In local dev
 * that's usually your dev server's URL (Vite default shown below, plus
 * a plain-HTML-via-VS-Code-Live-Server example). In production, replace
 * with your deployed frontend's real URL.
 */
$allowedOrigins = [
    'http://localhost:5173',  // Vite (React/Vue) dev server default
    'http://localhost:3000',  // Create React App / Next.js dev server default
    'http://127.0.0.1:5500',  // VS Code "Live Server" extension default
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowedOrigins, true)) {
    header("Access-Control-Allow-Origin: {$origin}");
}
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Vary: Origin');

// Preflight requests stop here.
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
}
