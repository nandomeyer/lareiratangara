<?php
// admin/auth.php — Verificação de sessão do admin

require_once '../config.php';

session_name(ADMIN_SESSION_NAME);
session_start();

function checkAdmin(): void {
    if (
        !isset($_SESSION['admin_id']) ||
        !isset($_SESSION['admin_ts']) ||
        (time() - $_SESSION['admin_ts']) > SESSION_LIFETIME
    ) {
        session_destroy();
        header('Location: login.php?expired=1');
        exit;
    }
    // Renovar timestamp
    $_SESSION['admin_ts'] = time();
}

function adminNome(): string {
    return $_SESSION['admin_nome'] ?? 'Admin';
}

function adminId(): int {
    return (int)($_SESSION['admin_id'] ?? 0);
}
