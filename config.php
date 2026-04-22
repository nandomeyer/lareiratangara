<?php
// ============================================================
//  CONFIGURAÇÕES DO SISTEMA - Lareira Tangará da Serra
// ============================================================

// Chave secreta para tokens de download (MUDE ISTO!)
define('SECRET_KEY', 'lareira_tangara_MT_2026_xK9#mPqL');

// Banco de dados
define('DB_HOST', 'localhost');
define('DB_NAME', 'lareira_reunioes');
define('DB_USER', 'root');         // Altere para seu usuário
define('DB_PASS', '');             // Altere para sua senha
define('DB_CHARSET', 'utf8mb4');

// Diretório de uploads (FORA da pasta pública — configure no servidor!)
// Exemplo: '/var/www/storage/lareira_pdfs/' (fora de /public_html/)
define('UPLOAD_DIR', __DIR__ . '/storage/pdfs/');

// Tamanho máximo de upload: 20MB
define('MAX_FILE_SIZE', 20 * 1024 * 1024);

// Tipos permitidos
define('ALLOWED_MIME', ['application/pdf']);
define('ALLOWED_EXT', ['pdf']);

// Sessão do admin
define('ADMIN_SESSION_NAME', 'lareira_admin');
define('SESSION_LIFETIME', 3600); // 1 hora

// Modo debug (false em produção!)
define('DEBUG', false);

// Timezone
date_default_timezone_set('America/Cuiaba');

// Error handling
if (!DEBUG) {
    error_reporting(0);
    ini_set('display_errors', 0);
}
