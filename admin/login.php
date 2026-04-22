<?php
require_once '../config.php';

session_name(ADMIN_SESSION_NAME);
session_start();
require_once '../db.php';

// Se já logado, redirecionar
if (isset($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit;
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario'] ?? '');
    $senha    = trim($_POST['senha']   ?? '');

    if ($usuario && $senha) {
        $stmt = $db->prepare("SELECT * FROM admins WHERE usuario = ? AND ativo = 1 LIMIT 1");
        $stmt->execute([$usuario]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($senha, $admin['senha_hash'])) {
            // Login bem-sucedido
            session_regenerate_id(true);
            $_SESSION['admin_id']   = $admin['id'];
            $_SESSION['admin_nome'] = $admin['nome'];
            $_SESSION['admin_ts']   = time();

            // Log de acesso
            $db->prepare("UPDATE admins SET ultimo_login = NOW() WHERE id = ?")->execute([$admin['id']]);

            header('Location: dashboard.php');
            exit;
        } else {
            // Delay para dificultar brute-force
            sleep(1);
            $erro = 'Usuário ou senha incorretos.';
        }
    } else {
        $erro = 'Preencha todos os campos.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrativo · Lareira Tangará da Serra</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --vermelho: #C0392B;
            --vermelho-escuro: #922B21;
            --creme: #FDF6EE;
            --texto: #2C1810;
            --cinza: #8C7B6E;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'DM Sans', sans-serif;
            background: linear-gradient(135deg, #2C1810 0%, #4A1F10 50%, #C0392B 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-box {
            background: #fff;
            border-radius: 20px;
            padding: 48px 44px;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 24px 80px rgba(0,0,0,0.35);
        }
        .login-logo {
            text-align: center;
            margin-bottom: 32px;
        }
        .login-logo .icon {
            width: 64px;
            height: 64px;
            background: var(--vermelho);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
        }
        .login-logo .icon i { color: #fff; font-size: 1.8rem; }
        .login-logo h1 {
            font-family: 'Playfair Display', serif;
            font-size: 1.4rem;
            color: var(--texto);
            font-weight: 700;
        }
        .login-logo p { font-size: 0.82rem; color: var(--cinza); margin-top: 4px; }
        .form-label {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--texto);
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-bottom: 6px;
        }
        .form-control {
            border: 1.5px solid #DDD3C8;
            border-radius: 10px;
            padding: 12px 16px;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.92rem;
            color: var(--texto);
            background: #FAFAF9;
            transition: border 0.2s, box-shadow 0.2s;
        }
        .form-control:focus {
            border-color: var(--vermelho);
            box-shadow: 0 0 0 3px rgba(192,57,43,0.12);
            background: #fff;
            outline: none;
        }
        .btn-entrar {
            background: var(--vermelho);
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 13px;
            width: 100%;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.92rem;
            font-weight: 600;
            letter-spacing: 0.04em;
            cursor: pointer;
            transition: background 0.2s, transform 0.15s;
            margin-top: 8px;
        }
        .btn-entrar:hover { background: var(--vermelho-escuro); transform: scale(1.01); }
        .alert-erro {
            background: #FEF2F2;
            border: 1px solid #FECACA;
            border-radius: 10px;
            padding: 12px 16px;
            color: #991B1B;
            font-size: 0.85rem;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            font-size: 0.82rem;
            color: var(--cinza);
            text-decoration: none;
            transition: color 0.2s;
        }
        .back-link:hover { color: var(--vermelho); }
        .mb-4 { margin-bottom: 16px; }
    </style>
</head>
<body>
<div class="login-box">
    <div class="login-logo">
        <div class="icon"><i class="fa-solid fa-fire"></i></div>
        <h1>Área Administrativa</h1>
        <p>Lareira Tangará da Serra · MT</p>
    </div>

    <?php if ($erro): ?>
    <div class="alert-erro">
        <i class="fa-solid fa-circle-exclamation"></i>
        <?= htmlspecialchars($erro) ?>
    </div>
    <?php endif; ?>

    <form method="POST" autocomplete="off">
        <div class="mb-4">
            <label class="form-label">Usuário</label>
            <input type="text" name="usuario" class="form-control"
                   placeholder="seu.usuario"
                   value="<?= htmlspecialchars($_POST['usuario'] ?? '') ?>"
                   required autofocus>
        </div>
        <div class="mb-4">
            <label class="form-label">Senha</label>
            <input type="password" name="senha" class="form-control"
                   placeholder="••••••••" required>
        </div>
        <button type="submit" class="btn-entrar">
            <i class="fa-solid fa-arrow-right-to-bracket" style="margin-right:8px;"></i>
            Entrar
        </button>
    </form>
    <a href="../index.php" class="back-link">
        <i class="fa-solid fa-arrow-left" style="font-size:0.75rem;"></i> Voltar ao site
    </a>
</div>
</body>
</html>
