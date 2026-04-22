<?php
// download.php — Download seguro sem expor caminho físico
session_start();
require_once 'config.php';
require_once 'db.php';

// Validar parâmetros
$id    = isset($_GET['id'])    ? intval($_GET['id'])    : 0;
$token = isset($_GET['token']) ? trim($_GET['token'])   : '';

if (!$id || !$token) {
    http_response_code(400);
    die('Requisição inválida.');
}

// Buscar reunião no banco
$stmt = $db->prepare("SELECT * FROM reunioes WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$reuniao = $stmt->fetch();

if (!$reuniao) {
    http_response_code(404);
    die('Arquivo não encontrado.');
}

// Verificar token de segurança (HMAC sha256)
$token_esperado = hash('sha256', $reuniao['id'] . SECRET_KEY);
if (!hash_equals($token_esperado, $token)) {
    http_response_code(403);
    die('Acesso negado.');
}

// Verificar se arquivo existe no disco
$caminho = UPLOAD_DIR . $reuniao['arquivo'];
if (!file_exists($caminho) || !is_readable($caminho)) {
    http_response_code(404);
    die('Arquivo não disponível no momento.');
}

// Incrementar contador de downloads
$db->prepare("UPDATE reunioes SET downloads = downloads + 1 WHERE id = ?")->execute([$id]);

// Nome seguro para download (sem expor caminho real)
$nome_download = 'Reuniao_' . $reuniao['ano'] . '_' . str_pad($reuniao['mes'], 2, '0', STR_PAD_LEFT) . '.pdf';

// Enviar arquivo com headers corretos
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $nome_download . '"');
header('Content-Length: ' . filesize($caminho));
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');

// Limpar buffer de saída
if (ob_get_level()) ob_end_clean();

// Servir o arquivo
readfile($caminho);
exit;
