<?php
session_start();
require_once 'config.php';
require_once 'db.php';

// Filtros
$ano = isset($_GET['ano']) ? intval($_GET['ano']) : null;
$mes = isset($_GET['mes']) ? intval($_GET['mes']) : null;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 12;

// Buscar anos disponíveis
$anos = $db->query("SELECT DISTINCT ano FROM reunioes ORDER BY ano DESC")->fetchAll(PDO::FETCH_COLUMN);

// Buscar último arquivo em destaque
$destaque = $db->query("SELECT * FROM reunioes ORDER BY ano DESC, mes DESC, id DESC LIMIT 1")->fetch();

// Construir query com filtros
$where = [];
$params = [];
if ($ano) { $where[] = "ano = ?"; $params[] = $ano; }
if ($mes) { $where[] = "mes = ?"; $params[] = $mes; }

// Excluir destaque da listagem principal
if ($destaque) { $where[] = "id != ?"; $params[] = $destaque['id']; }

$where_sql = $where ? "WHERE " . implode(" AND ", $where) : "";

// Count total
$count_stmt = $db->prepare("SELECT COUNT(*) FROM reunioes $where_sql");
$count_stmt->execute($params);
$total = $count_stmt->fetchColumn();
$total_pages = ceil($total / $per_page);
$offset = ($page - 1) * $per_page;

// Buscar reuniões
$stmt = $db->prepare("SELECT * FROM reunioes $where_sql ORDER BY ano DESC, mes DESC, id DESC LIMIT $per_page OFFSET $offset");
$stmt->execute($params);
$reunioes = $stmt->fetchAll();

$meses_nomes = [
    1=>'Janeiro',2=>'Fevereiro',3=>'Março',4=>'Abril',
    5=>'Maio',6=>'Junho',7=>'Julho',8=>'Agosto',
    9=>'Setembro',10=>'Outubro',11=>'Novembro',12=>'Dezembro'
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lareira Tangará da Serra - MT | Reuniões</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --vermelho: #C0392B;
            --vermelho-escuro: #922B21;
            --vermelho-claro: #E74C3C;
            --creme: #FDF6EE;
            --creme-escuro: #F5EBD8;
            --marrom: #6B4226;
            --cinza-quente: #8C7B6E;
            --texto: #2C1810;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--creme);
            color: var(--texto);
            min-height: 100vh;
        }

        /* HEADER */
        .site-header {
            background: #fff;
            border-bottom: 3px solid var(--vermelho);
            box-shadow: 0 2px 20px rgba(192,57,43,0.08);
            padding: 0;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .header-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 40px;
            max-width: 1200px;
            margin: 0 auto;
        }
        .logo-area { display: flex; align-items: center; gap: 14px; }
        .logo-area img { height: 54px; width: auto; object-fit: contain; }
        .logo-text h1 {
            font-family: 'Playfair Display', serif;
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--vermelho);
            line-height: 1.2;
        }
        .logo-text span {
            font-size: 0.72rem;
            color: var(--cinza-quente);
            letter-spacing: 0.05em;
            font-weight: 500;
            text-transform: uppercase;
        }
        .header-nav a {
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--cinza-quente);
            text-decoration: none;
            margin-left: 20px;
            transition: color 0.2s;
        }
        .header-nav a:hover { color: var(--vermelho); }
        .btn-admin {
            background: var(--vermelho);
            color: #fff !important;
            padding: 8px 18px;
            border-radius: 6px;
            font-weight: 600 !important;
            font-size: 0.8rem !important;
            letter-spacing: 0.03em;
            transition: background 0.2s !important;
        }
        .btn-admin:hover { background: var(--vermelho-escuro) !important; }

        /* HERO */
        .hero {
            background: linear-gradient(135deg, var(--vermelho) 0%, var(--vermelho-escuro) 100%);
            padding: 60px 40px 50px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.04'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }
        .hero-img {
            width: 110px;
            height: 110px;
            border-radius: 50%;
            border: 4px solid rgba(255,255,255,0.3);
            margin: 0 auto 20px;
            object-fit: cover;
            background: rgba(255,255,255,0.15);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
        }
        .hero h2 {
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            color: #fff;
            font-weight: 700;
            margin-bottom: 8px;
        }
        .hero p {
            color: rgba(255,255,255,0.75);
            font-size: 1rem;
            font-weight: 300;
        }

        /* DESTAQUE */
        .destaque-section { padding: 40px 0 20px; }
        .destaque-card {
            background: linear-gradient(135deg, #fff 0%, #FFF8F5 100%);
            border: 2px solid var(--vermelho);
            border-radius: 16px;
            padding: 32px 36px;
            display: flex;
            align-items: center;
            gap: 28px;
            box-shadow: 0 8px 32px rgba(192,57,43,0.12);
            position: relative;
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .destaque-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 40px rgba(192,57,43,0.18);
        }
        .destaque-card::before {
            content: 'MAIS RECENTE';
            position: absolute;
            top: 16px;
            right: -28px;
            background: var(--vermelho);
            color: #fff;
            font-size: 0.65rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            padding: 4px 40px;
            transform: rotate(45deg);
        }
        .destaque-icon {
            width: 72px;
            height: 72px;
            background: var(--vermelho);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .destaque-icon i { font-size: 2rem; color: #fff; }
        .destaque-info h3 {
            font-family: 'Playfair Display', serif;
            font-size: 1.4rem;
            color: var(--vermelho);
            font-weight: 700;
            margin-bottom: 4px;
        }
        .destaque-info .meta {
            font-size: 0.8rem;
            color: var(--cinza-quente);
            margin-bottom: 16px;
        }
        .destaque-info .meta i { margin-right: 4px; }
        .btn-download-destaque {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--vermelho);
            color: #fff;
            padding: 10px 22px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.88rem;
            text-decoration: none;
            transition: background 0.2s, transform 0.15s;
            letter-spacing: 0.02em;
        }
        .btn-download-destaque:hover {
            background: var(--vermelho-escuro);
            color: #fff;
            transform: scale(1.03);
        }

        /* FILTROS */
        .filtros-section {
            background: #fff;
            border-radius: 12px;
            padding: 18px 22px;
            margin-bottom: 28px;
            box-shadow: 0 1px 6px rgba(0,0,0,0.05);
            border: 1px solid #EDE8E2;
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }
        .filtros-label {
            font-size: 0.75rem;
            font-weight: 600;
            color: #B0A49A;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            white-space: nowrap;
        }
        .filtro-select {
            appearance: none;
            -webkit-appearance: none;
            background: #F8F5F2 url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%238C7B6E' stroke-width='2.5'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E") no-repeat right 10px center;
            border: 1.5px solid #E5DDD6;
            border-radius: 8px;
            padding: 7px 30px 7px 12px;
            font-family: 'Poppins', sans-serif;
            font-size: 0.82rem;
            font-weight: 500;
            color: var(--texto);
            cursor: pointer;
            transition: border-color 0.2s, box-shadow 0.2s;
            min-width: 110px;
        }
        .filtro-select:focus {
            outline: none;
            border-color: var(--vermelho);
            box-shadow: 0 0 0 3px rgba(192,57,43,0.08);
        }
        .filtro-select.ativo {
            border-color: var(--vermelho);
            color: var(--vermelho);
            background-color: #FFF5F4;
        }
        .filtro-divider {
            width: 1px;
            height: 24px;
            background: #E5DDD6;
            display: inline-block;
        }
        .filtro-limpar {
            font-size: 0.78rem;
            font-weight: 500;
            color: #B0A49A;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: color 0.2s;
            white-space: nowrap;
            margin-left: auto;
        }
        .filtro-limpar:hover { color: var(--vermelho); }

        /* GRID REUNIÕES */
        .reunioes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }
        .reuniao-card {
            background: #fff;
            border-radius: 12px;
            padding: 22px 24px;
            border: 1px solid #EEE5DD;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            display: flex;
            flex-direction: column;
            gap: 12px;
            transition: all 0.25s;
            text-decoration: none;
            color: inherit;
        }
        .reuniao-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 24px rgba(192,57,43,0.12);
            border-color: #E8C5BF;
        }
        .card-top { display: flex; align-items: flex-start; gap: 14px; }
        .card-pdf-icon {
            width: 44px;
            height: 44px;
            background: #FFF0EE;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .card-pdf-icon i { color: var(--vermelho); font-size: 1.2rem; }
        .card-title {
            font-family: 'Playfair Display', serif;
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--texto);
            line-height: 1.35;
            flex: 1;
        }
        .card-meta {
            font-size: 0.75rem;
            color: var(--cinza-quente);
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .card-meta span { display: flex; align-items: center; gap: 4px; }
        .card-downloads {
            font-size: 0.72rem;
            background: #F5EBD8;
            color: var(--marrom);
            padding: 3px 10px;
            border-radius: 20px;
            font-weight: 600;
        }
        .btn-download-card {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            background: transparent;
            border: 1.5px solid var(--vermelho);
            color: var(--vermelho);
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 0.82rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
            letter-spacing: 0.02em;
        }
        .btn-download-card:hover {
            background: var(--vermelho);
            color: #fff;
        }

        /* PAGINAÇÃO */
        .paginacao { display: flex; justify-content: center; gap: 8px; margin-top: 40px; }
        .paginacao a, .paginacao span {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            font-size: 0.88rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
        }
        .paginacao a {
            border: 1.5px solid #DDD3C8;
            color: var(--cinza-quente);
            background: #fff;
        }
        .paginacao a:hover { border-color: var(--vermelho); color: var(--vermelho); }
        .paginacao span {
            background: var(--vermelho);
            color: #fff;
            border: 1.5px solid var(--vermelho);
        }

        /* FOOTER */
        .site-footer {
            background: var(--texto);
            color: rgba(255,255,255,0.65);
            padding: 30px 40px;
            text-align: center;
            margin-top: 60px;
        }
        .site-footer .footer-logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-bottom: 12px;
        }
        .site-footer img { height: 36px; filter: brightness(0) invert(1); opacity: 0.7; }
        .site-footer p { font-size: 0.8rem; }
        .site-footer strong { color: #fff; }

        /* VAZIO */
        .vazio {
            text-align: center;
            padding: 60px 20px;
            color: var(--cinza-quente);
        }
        .vazio i { font-size: 3rem; margin-bottom: 16px; opacity: 0.3; }
        .vazio p { font-size: 1rem; }

        /* CONTAINER */
        .main-container { max-width: 1160px; margin: 0 auto; padding: 0 24px; }

        /* SEÇÃO LABEL */
        .section-label {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
        }
        .section-label h3 {
            font-family: 'Playfair Display', serif;
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--texto);
        }
        .section-label .line {
            flex: 1;
            height: 1px;
            background: #DDD3C8;
        }
        .count-badge {
            background: var(--creme-escuro);
            color: var(--cinza-quente);
            font-size: 0.75rem;
            font-weight: 600;
            padding: 3px 10px;
            border-radius: 20px;
        }

        @media (max-width: 768px) {
            .header-inner { padding: 12px 20px; }
            .hero { padding: 40px 20px 35px; }
            .hero h2 { font-size: 1.5rem; }
            .destaque-card { flex-direction: column; padding: 24px; }
            .destaque-card::before { display: none; }
            .reunioes-grid { grid-template-columns: 1fr; }
            .main-container { padding: 0 16px; }
        }
    </style>
</head>
<body>

<!-- HEADER -->
<header class="site-header">
    <div class="header-inner">
        <div class="logo-area">
            <div style="width:48px;height:48px;background:var(--vermelho);border-radius:50%;display:flex;align-items:center;justify-content:center;">
                <i class="fa-solid fa-fire" style="color:#fff;font-size:1.4rem;"></i>
            </div>
            <div class="logo-text">
                <h1>Lareira Tangará da Serra</h1>
                <span>Diretoria de Educação · MT</span>
            </div>
        </div>
        <nav class="header-nav">
            <a href="index.php"><i class="fa-solid fa-house" style="font-size:0.75rem;"></i> Início</a>
            <a href="admin/login.php" class="btn-admin"><i class="fa-solid fa-lock" style="font-size:0.7rem;"></i> Administrativo</a>
        </nav>
    </div>
</header>

<!-- HERO -->
<div class="hero">
    <div class="hero-img">
        <i class="fa-solid fa-fire" style="color:rgba(255,255,255,0.6)"></i>
    </div>
    <h2>Reuniões da Diretoria</h2>
    <p>Acesse e baixe as atas das reuniões da Lareira Tangará da Serra - MT</p>
</div>

<div class="main-container">

    <!-- DESTAQUE -->
    <?php if ($destaque): ?>
    <div class="destaque-section">
        <div class="section-label">
            <h3><i class="fa-solid fa-star" style="color:var(--vermelho);font-size:0.9rem;"></i> Em Destaque</h3>
            <div class="line"></div>
        </div>
        <div class="destaque-card">
            <div class="destaque-icon">
                <i class="fa-solid fa-file-pdf"></i>
            </div>
            <div class="destaque-info" style="flex:1;">
                <h3>REUNIÃO de <?= strtoupper($meses_nomes[$destaque['mes']]) ?> de <?= $destaque['ano'] ?></h3>
                <div class="meta">
                    <i class="fa-regular fa-calendar"></i>
                    <?= $meses_nomes[$destaque['mes']] ?> / <?= $destaque['ano'] ?>
                    &nbsp;·&nbsp;
                    <i class="fa-solid fa-download"></i>
                    <?= number_format($destaque['downloads']) ?> downloads
                    &nbsp;·&nbsp;
                    <i class="fa-regular fa-clock"></i>
                    Adicionado em <?= date('d/m/Y', strtotime($destaque['criado_em'])) ?>
                </div>
                <a href="download.php?id=<?= $destaque['id'] ?>&token=<?= hash('sha256', $destaque['id'] . SECRET_KEY) ?>" class="btn-download-destaque">
                    <i class="fa-solid fa-download"></i> Baixar PDF
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- FILTROS -->
    <div class="filtros-section mt-4">
        <span class="filtros-label"><i class="fa-solid fa-sliders" style="margin-right:5px;"></i> Filtrar</span>

        <span class="filtro-divider"></span>

        <select class="filtro-select <?= $ano ? 'ativo' : '' ?>" onchange="aplicarFiltro('ano', this.value)">
            <option value="">Todos os anos</option>
            <?php foreach ($anos as $a): ?>
                <option value="<?= $a ?>" <?= $ano == $a ? 'selected' : '' ?>><?= $a ?></option>
            <?php endforeach; ?>
        </select>

        <select class="filtro-select <?= $mes ? 'ativo' : '' ?>" onchange="aplicarFiltro('mes', this.value)">
            <option value="">Todos os meses</option>
            <?php foreach ($meses_nomes as $num => $nome): ?>
                <option value="<?= $num ?>" <?= $mes == $num ? 'selected' : '' ?>><?= $nome ?></option>
            <?php endforeach; ?>
        </select>

        <?php if ($ano || $mes): ?>
        <a href="index.php" class="filtro-limpar">
            <i class="fa-solid fa-xmark" style="font-size:0.7rem;"></i> Limpar filtros
        </a>
        <?php endif; ?>
    </div>

    <!-- LISTAGEM -->
    <div class="section-label">
        <h3>Todas as Reuniões</h3>
        <div class="line"></div>
        <span class="count-badge"><?= $total ?> registros</span>
    </div>

    <?php if (empty($reunioes) && !$destaque): ?>
        <div class="vazio">
            <div><i class="fa-solid fa-folder-open"></i></div>
            <p>Nenhuma reunião encontrada com os filtros selecionados.</p>
        </div>
    <?php elseif (empty($reunioes) && $destaque): ?>
        <div class="vazio" style="padding:30px;">
            <p style="opacity:0.5;">Não há outras reuniões com os filtros selecionados.</p>
        </div>
    <?php else: ?>
    <div class="reunioes-grid">
        <?php foreach ($reunioes as $r): ?>
        <div class="reuniao-card">
            <div class="card-top">
                <div class="card-pdf-icon">
                    <i class="fa-solid fa-file-pdf"></i>
                </div>
                <div style="flex:1;">
                    <div class="card-title">REUNIÃO de <?= strtoupper($meses_nomes[$r['mes']]) ?> de <?= $r['ano'] ?></div>
                </div>
            </div>
            <div class="card-meta">
                <span><i class="fa-regular fa-calendar"></i> <?= $meses_nomes[$r['mes']] ?> <?= $r['ano'] ?></span>
                <span class="card-downloads"><i class="fa-solid fa-download"></i> <?= number_format($r['downloads']) ?></span>
            </div>
            <a href="download.php?id=<?= $r['id'] ?>&token=<?= hash('sha256', $r['id'] . SECRET_KEY) ?>" class="btn-download-card">
                <i class="fa-solid fa-download"></i> DOWNLOAD
            </a>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- PAGINAÇÃO -->
    <?php if ($total_pages > 1): ?>
    <div class="paginacao">
        <?php if ($page > 1): ?>
            <a href="<?= buildUrl(['page' => $page - 1]) ?>"><i class="fa-solid fa-chevron-left"></i></a>
        <?php endif; ?>
        <?php for ($i = max(1, $page-2); $i <= min($total_pages, $page+2); $i++): ?>
            <?php if ($i == $page): ?>
                <span><?= $i ?></span>
            <?php else: ?>
                <a href="<?= buildUrl(['page' => $i]) ?>"><?= $i ?></a>
            <?php endif; ?>
        <?php endfor; ?>
        <?php if ($page < $total_pages): ?>
            <a href="<?= buildUrl(['page' => $page + 1]) ?>"><i class="fa-solid fa-chevron-right"></i></a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<!-- FOOTER -->
<footer class="site-footer">
    <div class="footer-logo">
        <div style="width:32px;height:32px;background:rgba(255,255,255,0.15);border-radius:50%;display:flex;align-items:center;justify-content:center;">
            <i class="fa-solid fa-fire" style="color:rgba(255,255,255,0.6);font-size:0.9rem;"></i>
        </div>
        <strong style="color:rgba(255,255,255,0.85);font-family:'Playfair Display',serif;">Lareira Tangará da Serra · MT</strong>
    </div>
    <p>Diretoria de Educação &mdash; Todos os direitos reservados &copy; <?= date('Y') ?></p>
</footer>

<?php
function buildUrl($overrides = []) {
    $params = array_merge([
        'ano'  => $_GET['ano']  ?? null,
        'mes'  => $_GET['mes']  ?? null,
        'page' => $_GET['page'] ?? null,
    ], $overrides);
    $params = array_filter($params, fn($v) => $v !== null && $v !== '');
    return 'index.php' . ($params ? '?' . http_build_query($params) : '');
}
?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function aplicarFiltro(campo, valor) {
    const params = new URLSearchParams(window.location.search);
    if (valor) {
        params.set(campo, valor);
    } else {
        params.delete(campo);
    }
    params.delete('page'); // volta p/ página 1 ao filtrar
    window.location.href = 'index.php' + (params.toString() ? '?' + params.toString() : '');
}
</script>
</body>
</html>