<?php
require_once 'auth.php';
require_once '../db.php';
checkAdmin();

$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 15;
$offset = ($page - 1) * $per_page;

$total = $db->query("SELECT COUNT(*) FROM reunioes")->fetchColumn();
$total_pages = ceil($total / $per_page);

$reunioes = $db->query(
    "SELECT * FROM reunioes ORDER BY ano DESC, mes DESC, id DESC LIMIT $per_page OFFSET $offset"
)->fetchAll();

$meses_nomes = [
    1=>'Janeiro',2=>'Fevereiro',3=>'Março',4=>'Abril',
    5=>'Maio',6=>'Junho',7=>'Julho',8=>'Agosto',
    9=>'Setembro',10=>'Outubro',11=>'Novembro',12=>'Dezembro'
];

// Stats
$total_downloads = $db->query("SELECT SUM(downloads) FROM reunioes")->fetchColumn() ?: 0;
$total_arquivos  = $total;
$ultimo = $db->query("SELECT * FROM reunioes ORDER BY id DESC LIMIT 1")->fetch();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard · Admin · Lareira</title>
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
        body {
            font-family: 'DM Sans', sans-serif;
            background: #F5F0EB;
            color: var(--texto);
            min-height: 100vh;
            display: flex;
        }

        /* SIDEBAR */
        .sidebar {
            width: var(--sidebar-w);
            background: var(--sidebar-bg);
            min-height: 100vh;
            position: fixed;
            top: 0; left: 0;
            display: flex;
            flex-direction: column;
            z-index: 200;
        }
        .sidebar-logo {
            padding: 28px 24px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.06);
        }
        .sidebar-logo .brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .sidebar-logo .icon {
            width: 40px;
            height: 40px;
            background: var(--vermelho);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .sidebar-logo .icon i { color: #fff; font-size: 1.1rem; }
        .sidebar-logo h2 {
            font-family: 'Playfair Display', serif;
            font-size: 0.95rem;
            color: #fff;
            line-height: 1.3;
        }
        .sidebar-logo span { font-size: 0.7rem; color: rgba(255,255,255,0.4); }

        .sidebar-nav { padding: 20px 0; flex: 1; }
        .nav-label {
            font-size: 0.65rem;
            font-weight: 700;
            color: rgba(255,255,255,0.3);
            letter-spacing: 0.1em;
            text-transform: uppercase;
            padding: 0 24px;
            margin: 16px 0 6px;
        }
        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 11px 24px;
            color: rgba(255,255,255,0.55);
            text-decoration: none;
            font-size: 0.88rem;
            font-weight: 500;
            transition: all 0.2s;
            border-left: 3px solid transparent;
        }
        .nav-item:hover, .nav-item.ativo {
            color: #fff;
            background: rgba(255,255,255,0.06);
            border-left-color: var(--vermelho);
        }
        .nav-item i { width: 18px; text-align: center; font-size: 0.9rem; }

        .sidebar-footer {
            padding: 20px 24px;
            border-top: 1px solid rgba(255,255,255,0.06);
        }
        .user-badge {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
        }
        .user-avatar {
            width: 34px;
            height: 34px;
            background: var(--vermelho);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.85rem;
            color: #fff;
            flex-shrink: 0;
        }
        .user-info { flex: 1; overflow: hidden; }
        .user-info strong { color: #fff; font-size: 0.84rem; display: block; }
        .user-info span { color: rgba(255,255,255,0.4); font-size: 0.72rem; }
        .btn-logout {
            display: flex;
            align-items: center;
            gap: 8px;
            color: rgba(255,255,255,0.45);
            font-size: 0.8rem;
            text-decoration: none;
            transition: color 0.2s;
        }
        .btn-logout:hover { color: #E74C3C; }

        /* MAIN */
        .main {
            margin-left: var(--sidebar-w);
            flex: 1;
            padding: 32px 36px;
            min-height: 100vh;
        }
        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 28px;
        }
        .page-header h1 {
            font-family: 'Playfair Display', serif;
            font-size: 1.6rem;
            font-weight: 700;
        }
        .page-header p { font-size: 0.85rem; color: var(--cinza); margin-top: 2px; }

        /* STATS */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
            margin-bottom: 28px;
        }
        .stat-card {
            background: #fff;
            border-radius: 14px;
            padding: 22px 24px;
            display: flex;
            align-items: center;
            gap: 18px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border: 1px solid #EEE5DD;
        }
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            flex-shrink: 0;
        }
        .stat-icon.vermelho { background: #FFF0EE; color: var(--vermelho); }
        .stat-icon.verde    { background: #ECFDF5; color: #10B981; }
        .stat-icon.azul     { background: #EFF6FF; color: #3B82F6; }
        .stat-label { font-size: 0.78rem; color: var(--cinza); font-weight: 500; margin-bottom: 4px; }
        .stat-value { font-size: 1.7rem; font-weight: 700; color: var(--texto); line-height: 1; }

        /* TABELA */
        .card-table {
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border: 1px solid #EEE5DD;
            overflow: hidden;
        }
        .card-table-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px 24px;
            border-bottom: 1px solid #EEE5DD;
        }
        .card-table-header h3 {
            font-family: 'Playfair Display', serif;
            font-size: 1.05rem;
            font-weight: 700;
        }
        .btn-novo {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--vermelho);
            color: #fff;
            padding: 9px 20px;
            border-radius: 8px;
            font-size: 0.84rem;
            font-weight: 600;
            text-decoration: none;
            transition: background 0.2s;
        }
        .btn-novo:hover { background: var(--vermelho-escuro); color: #fff; }

        table { width: 100%; border-collapse: collapse; }
        thead th {
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--cinza);
            padding: 12px 20px;
            text-align: left;
            background: #FAF7F4;
            border-bottom: 1px solid #EEE5DD;
        }
        tbody tr { border-bottom: 1px solid #F2EDE8; transition: background 0.15s; }
        tbody tr:last-child { border-bottom: none; }
        tbody tr:hover { background: #FDF8F5; }
        tbody td { padding: 14px 20px; font-size: 0.88rem; vertical-align: middle; }

        .mes-badge {
            background: #FFF0EE;
            color: var(--vermelho);
            font-weight: 700;
            font-size: 0.75rem;
            padding: 3px 10px;
            border-radius: 20px;
        }
        .downloads-count {
            font-weight: 600;
            color: var(--texto);
        }
        .action-btns { display: flex; gap: 8px; }
        .btn-action {
            width: 32px;
            height: 32px;
            border-radius: 7px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            text-decoration: none;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
        }
        .btn-action.delete { background: #FEF2F2; color: #DC2626; }
        .btn-action.delete:hover { background: #DC2626; color: #fff; }
        .btn-action.download { background: #EFF6FF; color: #3B82F6; }
        .btn-action.download:hover { background: #3B82F6; color: #fff; }

        /* PAGINAÇÃO */
        .paginacao {
            display: flex;
            justify-content: flex-end;
            gap: 6px;
            padding: 16px 20px;
            border-top: 1px solid #EEE5DD;
        }
        .paginacao a, .paginacao span {
            width: 34px;
            height: 34px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 7px;
            font-size: 0.84rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
        }
        .paginacao a { border: 1px solid #DDD3C8; color: var(--cinza); background: #fff; }
        .paginacao a:hover { border-color: var(--vermelho); color: var(--vermelho); }
        .paginacao span { background: var(--vermelho); color: #fff; border: 1px solid var(--vermelho); }

        @media (max-width: 900px) {
            .sidebar { transform: translateX(-100%); }
            .main { margin-left: 0; padding: 20px; }
            .stats-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar">
    <div class="sidebar-logo">
        <div class="brand">
            <div class="icon"><i class="fa-solid fa-fire"></i></div>
            <div>
                <h2>Lareira</h2>
                <span>Tangará da Serra · MT</span>
            </div>
        </div>
    </div>
    <nav class="sidebar-nav">
        <div class="nav-label">Principal</div>
        <a href="dashboard.php" class="nav-item ativo">
            <i class="fa-solid fa-gauge"></i> Dashboard
        </a>
        <a href="upload.php" class="nav-item">
            <i class="fa-solid fa-cloud-arrow-up"></i> Nova Reunião
        </a>
        <div class="nav-label">Sistema</div>
        <a href="../index.php" class="nav-item" target="_blank">
            <i class="fa-solid fa-arrow-up-right-from-square"></i> Ver Site
        </a>
        <a href="trocar-senha.php" class="nav-item">
            <i class="fa-solid fa-key"></i> Trocar Senha
        </a>
    </nav>
    <div class="sidebar-footer">
        <div class="user-badge">
            <div class="user-avatar"><?= strtoupper(substr(adminNome(), 0, 1)) ?></div>
            <div class="user-info">
                <strong><?= htmlspecialchars(adminNome()) ?></strong>
                <span>Administrador</span>
            </div>
        </div>
        <a href="logout.php" class="btn-logout">
            <i class="fa-solid fa-right-from-bracket"></i> Sair do sistema
        </a>
    </div>
</aside>

<!-- MAIN -->
<main class="main">
    <div class="page-header">
        <div>
            <h1>Dashboard</h1>
            <p>Gerencie as reuniões da Lareira Tangará da Serra</p>
        </div>
        <a href="upload.php" class="btn-novo">
            <i class="fa-solid fa-plus"></i> Nova Reunião
        </a>
    </div>

    <!-- STATS -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon vermelho"><i class="fa-solid fa-file-pdf"></i></div>
            <div>
                <div class="stat-label">Total de Reuniões</div>
                <div class="stat-value"><?= $total_arquivos ?></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon verde"><i class="fa-solid fa-download"></i></div>
            <div>
                <div class="stat-label">Total de Downloads</div>
                <div class="stat-value"><?= number_format($total_downloads) ?></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon azul"><i class="fa-solid fa-calendar-check"></i></div>
            <div>
                <div class="stat-label">Última Reunião</div>
                <div class="stat-value" style="font-size:1.1rem;">
                    <?php if ($ultimo): ?>
                        <?= $meses_nomes[$ultimo['mes']] ?> <?= $ultimo['ano'] ?>
                    <?php else: ?>—<?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- TABELA -->
    <div class="card-table">
        <div class="card-table-header">
            <h3>Reuniões Cadastradas</h3>
            <a href="upload.php" class="btn-novo">
                <i class="fa-solid fa-plus"></i> Adicionar
            </a>
        </div>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Reunião</th>
                    <th>Arquivo</th>
                    <th>Downloads</th>
                    <th>Cadastrado em</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($reunioes)): ?>
                <tr><td colspan="6" style="text-align:center;padding:40px;color:var(--cinza);">
                    <i class="fa-solid fa-folder-open" style="font-size:1.5rem;margin-bottom:8px;display:block;opacity:0.3;"></i>
                    Nenhuma reunião cadastrada.
                </td></tr>
                <?php endif; ?>
                <?php foreach ($reunioes as $r): ?>
                <tr>
                    <td style="color:var(--cinza);font-size:0.8rem;"><?= $r['id'] ?></td>
                    <td>
                        <strong><?= $meses_nomes[$r['mes']] ?> de <?= $r['ano'] ?></strong>
                        <br><span class="mes-badge"><?= $r['ano'] ?> · <?= str_pad($r['mes'], 2, '0', STR_PAD_LEFT) ?></span>
                    </td>
                    <td style="color:var(--cinza);font-size:0.82rem;font-family:monospace;">
                        <?= htmlspecialchars(substr($r['arquivo'], 0, 30)) ?>...
                    </td>
                    <td><span class="downloads-count"><i class="fa-solid fa-download" style="font-size:0.7rem;color:var(--cinza);margin-right:4px;"></i><?= number_format($r['downloads']) ?></span></td>
                    <td style="font-size:0.82rem;color:var(--cinza);"><?= date('d/m/Y H:i', strtotime($r['criado_em'])) ?></td>
                    <td>
                        <div class="action-btns">
                            <a href="../download.php?id=<?= $r['id'] ?>&token=<?= hash('sha256', $r['id'] . SECRET_KEY) ?>"
                               class="btn-action download" title="Baixar" target="_blank">
                                <i class="fa-solid fa-download"></i>
                            </a>
                            <form method="POST" action="excluir.php" onsubmit="return confirm('Excluir esta reunião?');" style="display:inline;">
                                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                <input type="hidden" name="csrf" value="<?= $_SESSION['csrf'] ?? '' ?>">
                                <button type="submit" class="btn-action delete" title="Excluir">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php if ($total_pages > 1): ?>
        <div class="paginacao">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <?php if ($i == $page): ?>
                    <span><?= $i ?></span>
                <?php else: ?>
                    <a href="dashboard.php?page=<?= $i ?>"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>
</main>

<?php
// CSRF token
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
