<?php
require_once 'auth.php';
require_once '../db.php';
checkAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}

// CSRF
if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
    header('Location: dashboard.php?erro=csrf');
    exit;
}

$id = intval($_POST['id'] ?? 0);
if (!$id) {
    header('Location: dashboard.php');
    exit;
}

$stmt = $db->prepare("SELECT * FROM reunioes WHERE id = ?");
$stmt->execute([$id]);
$reuniao = $stmt->fetch();

if ($reuniao) {
    // Remover arquivo físico
    $caminho = UPLOAD_DIR . $reuniao['arquivo'];
    if (file_exists($caminho)) {
        unlink($caminho);
    }
    // Remover do banco
    $db->prepare("DELETE FROM reunioes WHERE id = ?")->execute([$id]);
}

header('Location: dashboard.php?ok=excluido');
exit;
