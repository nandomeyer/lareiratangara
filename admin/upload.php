<?php
require_once 'auth.php';
require_once '../db.php';
checkAdmin();

// CSRF token
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

$sucesso = '';
$erro    = '';

$meses_nomes = [
    1=>'Janeiro',2=>'Fevereiro',3=>'Março',4=>'Abril',
    5=>'Maio',6=>'Junho',7=>'Julho',8=>'Agosto',
    9=>'Setembro',10=>'Outubro',11=>'Novembro',12=>'Dezembro'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF
    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
        $erro = 'Token de segurança inválido. Recarregue a página.';
    } else {
        $mes = intval($_POST['mes'] ?? 0);
        $ano = intval($_POST['ano'] ?? 0);

        // Validações básicas
        if ($mes < 1 || $mes > 12) {
            $erro = 'Selecione um mês válido.';
        } elseif ($ano < 2000 || $ano > 2100) {
            $erro = 'Informe um ano válido.';
        } elseif (empty($_FILES['pdf']) || $_FILES['pdf']['error'] !== UPLOAD_ERR_OK) {
            $erro = 'Selecione um arquivo PDF para enviar.';
        } else {
            $file = $_FILES['pdf'];

            // Verificar tamanho
            if ($file['size'] > MAX_FILE_SIZE) {
                $erro = 'Arquivo muito grande. Máximo: 20MB.';
            } else {
                // Verificar tipo real (MIME)
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime  = $finfo->file($file['tmp_name']);

                if (!in_array($mime, ALLOWED_MIME)) {
                    $erro = 'Apenas arquivos PDF são aceitos.';
                } else {
                    // Verificar duplicata
                    $dup = $db->prepare("SELECT id FROM reunioes WHERE ano = ? AND mes = ?");
                    $dup->execute([$ano, $mes]);
                    if ($dup->fetch()) {
                        $erro = 'Já existe uma reunião cadastrada para ' . $meses_nomes[$mes] . '/' . $ano . '.';
                    } else {
                        // Criar diretório se não existir
                        if (!is_dir(UPLOAD_DIR)) {
                            mkdir(UPLOAD_DIR, 0755, true);
                        }

                        // Nome seguro e aleatório (não previsível)
                        $nome_arquivo = 'reuniao_' . $ano . '_' . str_pad($mes, 2, '0', STR_PAD_LEFT)
                            . '_' . bin2hex(random_bytes(8)) . '.pdf';

                        $destino = UPLOAD_DIR . $nome_arquivo;

                        if (move_uploaded_file($file['tmp_name'], $destino)) {
                            // Salvar no banco
                            $stmt = $db->prepare(
                                "INSERT INTO reunioes (mes, ano, arquivo, downloads, criado_em, admin_id)
                                 VALUES (?, ?, ?, 0, NOW(), ?)"
                            );
                            $stmt->execute([$mes, $ano, $nome_arquivo, adminId()]);

                            // Renovar CSRF
                            $_SESSION['csrf'] = bin2hex(random_bytes(32));
                            $sucesso = 'Reunião de ' . $meses_nomes[$mes] . '/' . $ano . ' cadastrada com sucesso!';
                        } else {
                            $erro = 'Erro ao salvar o arquivo. Verifique as permissões do diretório.';
                        }
                    }
                }
            }
        }
    }
}

$anos_opcoes = range(date('Y'), 2020);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nova Reunião · Admin · Lareira</title>
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
            --sidebar-bg: #1E1008;
            --sidebar-w: 260px;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'DM Sans', sans-serif; background: #F5F0EB; color: var(--texto); min-height: 100vh; display: flex; }

        /* === SIDEBAR (mesma do dashboard) === */
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
        .user-avatar { width: 34px; height: 34px; background: var(--vermelho); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.85rem; color: #fff; flex-shrink: 0; }
        .user-info { flex: 1; overflow: hidden; }
        .user-info strong { color: #fff; font-size: 0.84rem; display: block; }
        .user-info span { color: rgba(255,255,255,0.4); font-size: 0.72rem; }
        .btn-logout { display: flex; align-items: center; gap: 8px; color: rgba(255,255,255,0.45); font-size: 0.8rem; text-decoration: none; transition: color 0.2s; }
        .btn-logout:hover { color: #E74C3C; }

        /* MAIN */
        .main { margin-left: var(--sidebar-w); flex: 1; padding: 32px 36px; min-height: 100vh; }
        .page-header { margin-bottom: 28px; }
        .page-header h1 { font-family: 'Playfair Display', serif; font-size: 1.6rem; font-weight: 700; }
        .page-header p { font-size: 0.85rem; color: var(--cinza); margin-top: 2px; }

        /* FORM CARD */
        .form-card { background: #fff; border-radius: 16px; padding: 36px; max-width: 600px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); border: 1px solid #EEE5DD; }
        .form-label { font-size: 0.8rem; font-weight: 600; color: var(--texto); text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 7px; display: block; }
        .form-control, .form-select {
            border: 1.5px solid #DDD3C8;
            border-radius: 10px;
            padding: 12px 16px;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.92rem;
            color: var(--texto);
            background: #FAFAF9;
            width: 100%;
            transition: border 0.2s, box-shadow 0.2s;
            appearance: none;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--vermelho);
            box-shadow: 0 0 0 3px rgba(192,57,43,0.1);
            background: #fff;
            outline: none;
        }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px; }
        .form-group { margin-bottom: 20px; }

        /* DROPZONE */
        .dropzone {
            border: 2px dashed #DDD3C8;
            border-radius: 12px;
            padding: 40px 24px;
            text-align: center;
            cursor: pointer;
            transition: all 0.25s;
            background: #FAFAF9;
            position: relative;
        }
        .dropzone:hover, .dropzone.dragover {
            border-color: var(--vermelho);
            background: #FFF8F7;
        }
        .dropzone input[type="file"] {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
            width: 100%;
            height: 100%;
        }
        .dropzone-icon { font-size: 2.5rem; color: #DDD3C8; margin-bottom: 12px; transition: color 0.25s; }
        .dropzone:hover .dropzone-icon, .dropzone.dragover .dropzone-icon { color: var(--vermelho); }
        .dropzone p { font-size: 0.9rem; color: var(--cinza); }
        .dropzone strong { color: var(--texto); }
        .file-selected { display: flex; align-items: center; gap: 10px; background: #FFF0EE; border-radius: 8px; padding: 10px 14px; margin-top: 12px; font-size: 0.85rem; color: var(--vermelho); }
        .file-selected i { font-size: 1.2rem; }

        .btn-salvar {
            background: var(--vermelho);
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 13px 28px;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.92rem;
            font-weight: 600;
            letter-spacing: 0.03em;
            cursor: pointer;
            transition: background 0.2s;
            width: 100%;
            margin-top: 8px;
        }
        .btn-salvar:hover { background: var(--vermelho-escuro); }

        .alert { border-radius: 10px; padding: 14px 18px; font-size: 0.88rem; margin-bottom: 24px; display: flex; align-items: center; gap: 10px; }
        .alert-success { background: #ECFDF5; border: 1px solid #A7F3D0; color: #065F46; }
        .alert-danger  { background: #FEF2F2; border: 1px solid #FECACA; color: #991B1B; }
    </style>
</head>
<body>

<aside class="sidebar">
    <div class="sidebar-logo">
        <div class="brand">
            <div class="icon"><i class="fa-solid fa-fire"></i></div>
            <div><h2>Lareira</h2><span>Tangará da Serra · MT</span></div>
        </div>
    </div>
    <nav class="sidebar-nav">
        <div class="nav-label">Principal</div>
        <a href="dashboard.php" class="nav-item"><i class="fa-solid fa-gauge"></i> Dashboard</a>
        <a href="upload.php" class="nav-item ativo"><i class="fa-solid fa-cloud-arrow-up"></i> Nova Reunião</a>
        <div class="nav-label">Sistema</div>
        <a href="../index.php" class="nav-item" target="_blank"><i class="fa-solid fa-arrow-up-right-from-square"></i> Ver Site</a>
        <a href="trocar-senha.php" class="nav-item"><i class="fa-solid fa-key"></i> Trocar Senha</a>
    </nav>
    <div class="sidebar-footer">
        <div class="user-badge">
            <div class="user-avatar"><?= strtoupper(substr(adminNome(), 0, 1)) ?></div>
            <div class="user-info"><strong><?= htmlspecialchars(adminNome()) ?></strong><span>Administrador</span></div>
        </div>
        <a href="logout.php" class="btn-logout"><i class="fa-solid fa-right-from-bracket"></i> Sair do sistema</a>
    </div>
</aside>

<main class="main">
    <div class="page-header">
        <h1>Nova Reunião</h1>
        <p>Faça o upload do PDF da ata de reunião</p>
    </div>

    <?php if ($sucesso): ?>
    <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($sucesso) ?></div>
    <?php endif; ?>
    <?php if ($erro): ?>
    <div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>

    <div class="form-card">
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf" value="<?= $_SESSION['csrf'] ?>">

            <div class="form-row">
                <div>
                    <label class="form-label">Mês da Reunião</label>
                    <select name="mes" class="form-select" required>
                        <option value="">Selecione...</option>
                        <?php foreach ($meses_nomes as $n => $nome): ?>
                        <option value="<?= $n ?>" <?= (($_POST['mes'] ?? '') == $n) ? 'selected' : '' ?>>
                            <?= str_pad($n, 2, '0', STR_PAD_LEFT) ?> · <?= $nome ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label">Ano</label>
                    <select name="ano" class="form-select" required>
                        <option value="">Selecione...</option>
                        <?php foreach ($anos_opcoes as $a): ?>
                        <option value="<?= $a ?>" <?= (($_POST['ano'] ?? date('Y')) == $a) ? 'selected' : '' ?>>
                            <?= $a ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Arquivo PDF</label>
                <div class="dropzone" id="dropzone">
                    <input type="file" name="pdf" accept=".pdf,application/pdf" required id="fileInput">
                    <div class="dropzone-icon"><i class="fa-solid fa-file-arrow-up"></i></div>
                    <p><strong>Clique para selecionar</strong> ou arraste o arquivo aqui</p>
                    <p style="margin-top:6px;font-size:0.78rem;">Somente PDF · Máximo 20MB</p>
                </div>
                <div class="file-selected" id="fileSelected" style="display:none;">
                    <i class="fa-solid fa-file-pdf"></i>
                    <span id="fileName"></span>
                </div>
            </div>

            <button type="submit" class="btn-salvar">
                <i class="fa-solid fa-cloud-arrow-up" style="margin-right:8px;"></i>
                Enviar Reunião
            </button>
        </form>
    </div>
</main>

<script>
const dz = document.getElementById('dropzone');
const fi = document.getElementById('fileInput');
const fs = document.getElementById('fileSelected');
const fn = document.getElementById('fileName');

fi.addEventListener('change', () => {
    if (fi.files[0]) {
        fn.textContent = fi.files[0].name + ' (' + (fi.files[0].size / 1024 / 1024).toFixed(2) + ' MB)';
        fs.style.display = 'flex';
    }
});
dz.addEventListener('dragover', e => { e.preventDefault(); dz.classList.add('dragover'); });
dz.addEventListener('dragleave', () => dz.classList.remove('dragover'));
dz.addEventListener('drop', e => {
    e.preventDefault();
    dz.classList.remove('dragover');
    if (e.dataTransfer.files[0]) {
        fi.files = e.dataTransfer.files;
        fn.textContent = e.dataTransfer.files[0].name;
        fs.style.display = 'flex';
    }
});
</script>
</body>
</html>
