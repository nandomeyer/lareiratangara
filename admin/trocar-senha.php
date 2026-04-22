<?php
require_once 'auth.php';
require_once '../db.php';
checkAdmin();

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

$sucesso = $erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
        $erro = 'Token de segurança inválido.';
    } else {
        $atual   = $_POST['senha_atual']   ?? '';
        $nova    = $_POST['senha_nova']     ?? '';
        $confirm = $_POST['senha_confirm']  ?? '';

        if (strlen($nova) < 8) {
            $erro = 'A nova senha deve ter pelo menos 8 caracteres.';
        } elseif ($nova !== $confirm) {
            $erro = 'A confirmação não corresponde à nova senha.';
        } else {
            $stmt = $db->prepare("SELECT senha_hash FROM admins WHERE id = ?");
            $stmt->execute([adminId()]);
            $admin = $stmt->fetch();

            if (!$admin || !password_verify($atual, $admin['senha_hash'])) {
                $erro = 'Senha atual incorreta.';
            } else {
                $novo_hash = password_hash($nova, PASSWORD_BCRYPT, ['cost' => 12]);
                $db->prepare("UPDATE admins SET senha_hash = ? WHERE id = ?")->execute([$novo_hash, adminId()]);
                $_SESSION['csrf'] = bin2hex(random_bytes(32));
                $sucesso = 'Senha alterada com sucesso!';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trocar Senha · Admin · Lareira</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        :root { --vermelho: #C0392B; --vermelho-escuro: #922B21; --texto: #2C1810; --cinza: #8C7B6E; --sidebar-bg: #1E1008; --sidebar-w: 260px; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'DM Sans', sans-serif; background: #F5F0EB; color: var(--texto); min-height: 100vh; display: flex; }
        .sidebar { width: var(--sidebar-w); background: var(--sidebar-bg); min-height: 100vh; position: fixed; top: 0; left: 0; display: flex; flex-direction: column; z-index: 200; }
        .sidebar-logo { padding: 28px 24px 20px; border-bottom: 1px solid rgba(255,255,255,0.06); }
        .sidebar-logo .brand { display: flex; align-items: center; gap: 12px; }
        .sidebar-logo .icon { width: 40px; height: 40px; background: var(--vermelho); border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .sidebar-logo .icon i { color: #fff; font-size: 1.1rem; }
        .sidebar-logo h2 { font-family: 'Playfair Display', serif; font-size: 0.95rem; color: #fff; line-height: 1.3; }
        .sidebar-logo span { font-size: 0.7rem; color: rgba(255,255,255,0.4); }
        .sidebar-nav { padding: 20px 0; flex: 1; }
        .nav-label { font-size: 0.65rem; font-weight: 700; color: rgba(255,255,255,0.3); letter-spacing: 0.1em; text-transform: uppercase; padding: 0 24px; margin: 16px 0 6px; }
        .nav-item { display: flex; align-items: center; gap: 12px; padding: 11px 24px; color: rgba(255,255,255,0.55); text-decoration: none; font-size: 0.88rem; font-weight: 500; transition: all 0.2s; border-left: 3px solid transparent; }
        .nav-item:hover, .nav-item.ativo { color: #fff; background: rgba(255,255,255,0.06); border-left-color: var(--vermelho); }
        .nav-item i { width: 18px; text-align: center; font-size: 0.9rem; }
        .sidebar-footer { padding: 20px 24px; border-top: 1px solid rgba(255,255,255,0.06); }
        .user-badge { display: flex; align-items: center; gap: 10px; margin-bottom: 12px; }
        .user-avatar { width: 34px; height: 34px; background: var(--vermelho); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.85rem; color: #fff; }
        .user-info strong { color: #fff; font-size: 0.84rem; display: block; }
        .user-info span { color: rgba(255,255,255,0.4); font-size: 0.72rem; }
        .btn-logout { display: flex; align-items: center; gap: 8px; color: rgba(255,255,255,0.45); font-size: 0.8rem; text-decoration: none; transition: color 0.2s; }
        .btn-logout:hover { color: #E74C3C; }
        .main { margin-left: var(--sidebar-w); flex: 1; padding: 32px 36px; min-height: 100vh; }
        .page-header { margin-bottom: 28px; }
        .page-header h1 { font-family: 'Playfair Display', serif; font-size: 1.6rem; font-weight: 700; }
        .page-header p { font-size: 0.85rem; color: var(--cinza); margin-top: 2px; }
        .form-card { background: #fff; border-radius: 16px; padding: 36px; max-width: 460px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); border: 1px solid #EEE5DD; }
        .form-label { font-size: 0.8rem; font-weight: 600; color: var(--texto); text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 7px; display: block; }
        .form-control { border: 1.5px solid #DDD3C8; border-radius: 10px; padding: 12px 16px; font-family: 'DM Sans', sans-serif; font-size: 0.92rem; color: var(--texto); background: #FAFAF9; width: 100%; transition: border 0.2s, box-shadow 0.2s; }
        .form-control:focus { border-color: var(--vermelho); box-shadow: 0 0 0 3px rgba(192,57,43,0.1); background: #fff; outline: none; }
        .form-group { margin-bottom: 18px; }
        .btn-salvar { background: var(--vermelho); color: #fff; border: none; border-radius: 10px; padding: 13px 28px; font-family: 'DM Sans', sans-serif; font-size: 0.92rem; font-weight: 600; cursor: pointer; transition: background 0.2s; width: 100%; margin-top: 8px; }
        .btn-salvar:hover { background: var(--vermelho-escuro); }
        .alert { border-radius: 10px; padding: 14px 18px; font-size: 0.88rem; margin-bottom: 24px; display: flex; align-items: center; gap: 10px; }
        .alert-success { background: #ECFDF5; border: 1px solid #A7F3D0; color: #065F46; }
        .alert-danger { background: #FEF2F2; border: 1px solid #FECACA; color: #991B1B; }
    </style>
</head>
<body>
<aside class="sidebar">
    <div class="sidebar-logo"><div class="brand"><div class="icon"><i class="fa-solid fa-fire"></i></div><div><h2>Lareira</h2><span>Tangará da Serra · MT</span></div></div></div>
    <nav class="sidebar-nav">
        <div class="nav-label">Principal</div>
        <a href="dashboard.php" class="nav-item"><i class="fa-solid fa-gauge"></i> Dashboard</a>
        <a href="upload.php" class="nav-item"><i class="fa-solid fa-cloud-arrow-up"></i> Nova Reunião</a>
        <div class="nav-label">Sistema</div>
        <a href="../index.php" class="nav-item" target="_blank"><i class="fa-solid fa-arrow-up-right-from-square"></i> Ver Site</a>
        <a href="trocar-senha.php" class="nav-item ativo"><i class="fa-solid fa-key"></i> Trocar Senha</a>
    </nav>
    <div class="sidebar-footer">
        <div class="user-badge"><div class="user-avatar"><?= strtoupper(substr(adminNome(), 0, 1)) ?></div><div class="user-info"><strong><?= htmlspecialchars(adminNome()) ?></strong><span>Administrador</span></div></div>
        <a href="logout.php" class="btn-logout"><i class="fa-solid fa-right-from-bracket"></i> Sair</a>
    </div>
</aside>
<main class="main">
    <div class="page-header">
        <h1>Trocar Senha</h1>
        <p>Altere a senha da sua conta administrativa</p>
    </div>
    <?php if ($sucesso): ?><div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?= $sucesso ?></div><?php endif; ?>
    <?php if ($erro): ?><div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($erro) ?></div><?php endif; ?>
    <div class="form-card">
        <form method="POST">
            <input type="hidden" name="csrf" value="<?= $_SESSION['csrf'] ?>">
            <div class="form-group">
                <label class="form-label">Senha Atual</label>
                <input type="password" name="senha_atual" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Nova Senha</label>
                <input type="password" name="senha_nova" class="form-control" required minlength="8">
            </div>
            <div class="form-group">
                <label class="form-label">Confirmar Nova Senha</label>
                <input type="password" name="senha_confirm" class="form-control" required minlength="8">
            </div>
            <button type="submit" class="btn-salvar"><i class="fa-solid fa-key" style="margin-right:8px;"></i>Alterar Senha</button>
        </form>
    </div>
</main>
</body>
</html>
