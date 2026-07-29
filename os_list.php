<?php
require __DIR__ . '/config.php';
$db = db();

$filtro_situacao = $_GET['situacao'] ?? '';
$filtro_texto = $_GET['texto'] ?? '';
$filtro_data_ini = $_GET['data_ini'] ?? '';
$filtro_data_fim = $_GET['data_fim'] ?? '';

$where = [];
$params = [];

if ($filtro_situacao !== '' && $filtro_situacao !== 'ALL') {
    $where[] = "OS.SITUACAO = ?";
    $params[] = $filtro_situacao;
} elseif ($filtro_situacao !== 'ALL') {
    $where[] = "OS.SITUACAO IN ('A','E','P','I')";
}

if ($filtro_texto !== '') {
    $where[] = "(OS.NOME LIKE ? OR OS.DESCRICAO LIKE ? OR OS.CODIGO = ? OR OS.NUMERO_SERIE LIKE ?)";
    $params[] = "%$filtro_texto%";
    $params[] = "%$filtro_texto%";
    $params[] = $filtro_texto;
    $params[] = "%$filtro_texto%";
}
if ($filtro_data_ini !== '') {
    $where[] = "OS.DATA_INICIO >= ?";
    $params[] = $filtro_data_ini;
}
if ($filtro_data_fim !== '') {
    $where[] = "OS.DATA_INICIO <= ?";
    $params[] = $filtro_data_fim;
}

$whereSQL = 'WHERE ' . implode(' AND ', $where);

$sql = "
    SELECT OS.CODIGO, OS.DATA_INICIO, OS.HORA_INICIO, OS.NOME, OS.DESCRICAO, 
           OS.SITUACAO, OS.TOTAL_GERAL, OS.TIPO_SERVICO, OS.MARCA, OS.MODELO,
           OS.PREVISAO_ENTREGA, OS.NUMERO_SERIE, OS.PLACA,
           (SELECT COUNT(*) FROM OS_DETALHE D WHERE D.FK_OS_MASTER = OS.CODIGO) AS ITENS,
           (SELECT COUNT(*) FROM OS_IMAGEM I WHERE I.FK_OS_MASTER = OS.CODIGO) AS FOTOS
    FROM OS_MASTER OS
    $whereSQL
    ORDER BY OS.CODIGO DESC
";
$sql = limit_query($sql, 100);

try {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $os_list = $stmt->fetchAll();
} catch (Exception $e) {
    $erro = $e->getMessage();
    $os_list = [];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="30">
    <title>Acompanhar OS - Ordens de Servico</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .cat-computadores { border-left:4px solid #3498db; }
        .cat-notebooks { border-left:4px solid #2ecc71; }
        .cat-impressoras { border-left:4px solid #e67e22; }
        .cat-monitores { border-left:4px solid #9b59b6; }
        .cat-celulares { border-left:4px solid #1abc9c; }
        .cat-estabilizadores { border-left:4px solid #f1c40f; }
        .cat-outros { border-left:4px solid #95a5a6; }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>Acompanhar OS</h1>
        <div class="links">
            <a href="index.php">Inicio</a>
            <a href="os_list.php">Ordens de Servico</a>
        </div>
    </div>
    <div class="container">
        <div class="card">
            <h2>Filtros</h2>
            <form method="GET" class="filtros">
                <input type="text" name="texto" placeholder="Buscar por cliente, descricao, numero..." value="<?= htmlspecialchars($filtro_texto) ?>">
                <select name="situacao">
                    <option value="">Somente Abertas</option>
                    <option value="A" <?= $filtro_situacao === 'A' ? 'selected' : '' ?>>Aberta</option>
                    <option value="E" <?= $filtro_situacao === 'E' ? 'selected' : '' ?>>Em Andamento</option>
                    <option value="P" <?= $filtro_situacao === 'P' ? 'selected' : '' ?>>Aguardando Peca</option>
                    <option value="I" <?= $filtro_situacao === 'I' ? 'selected' : '' ?>>Em Andamento (I)</option>
                    <option value="F" <?= $filtro_situacao === 'F' ? 'selected' : '' ?>>Finalizada</option>
                    <option value="D" <?= $filtro_situacao === 'D' ? 'selected' : '' ?>>Entregue</option>
                    <option value="C" <?= $filtro_situacao === 'C' ? 'selected' : '' ?>>Cancelada</option>
                    <option value="ALL" <?= $filtro_situacao === 'ALL' ? 'selected' : '' ?>>Todas</option>
                </select>
                <input type="date" name="data_ini" value="<?= htmlspecialchars($filtro_data_ini) ?>" title="Data inicio">
                <input type="date" name="data_fim" value="<?= htmlspecialchars($filtro_data_fim) ?>" title="Data fim">
                <button type="submit">Filtrar</button>
                <a href="os_list.php" style="padding:8px 12px;color:var(--text-light);font-size:14px">Limpar</a>
            </form>
        </div>

        <?php if (isset($erro)): ?>
            <div class="card" style="border-left:4px solid var(--danger)">
                <p>Erro: <?= htmlspecialchars($erro) ?></p>
            </div>
        <?php endif; ?>

        <div class="card">
            <h2>Ordens de Servico (<?= count($os_list) ?>)</h2>
            <?php if (empty($os_list)): ?>
                <div class="empty">
                    <div class="icon">&#128269;</div>
                    <p>Nenhuma OS encontrada com os filtros selecionados.</p>
                </div>
            <?php else: ?>
            <table>
                <tr>
                    <th>OS</th>
                    <th>Cliente</th>
                    <th>Descricao</th>
                    <th>Marca/Modelo</th>
                    <th>Situacao</th>
                    <th>Previsao</th>
                    <th>Itens</th>
                    <th>Total</th>
                </tr>
                <?php foreach ($os_list as $os): 
                    $sit = situacao_os($os['SITUACAO']);
                    $classe = strtolower(str_replace(' ','-',$sit[0]));
                    $cat = categoria_dispositivo($os['DESCRICAO']);
                    $catClasse = 'cat-' . strtolower(str_replace('/','-',$cat));
                ?>
                <tr class="row-click <?= $catClasse ?>" onclick="location.href='os_detalhe.php?cod=<?= $os['CODIGO'] ?>'">
                    <td><strong><?= $os['CODIGO'] ?></strong></td>
                    <td><?= htmlspecialchars(utf($os['NOME'] ?? '-')) ?></td>
                    <td title="<?= htmlspecialchars(utf($os['DESCRICAO'] ?? '')) ?>"><?= htmlspecialchars(utf(substr($os['DESCRICAO'] ?? '-', 0, 35))) ?></td>
                    <td><?= htmlspecialchars(utf(($os['MARCA'] ?? '').' '.$os['MODELO'] ?? '-')) ?></td>
                    <td><span class="badge badge-<?= $classe ?>"><?= $sit[0] ?></span></td>
                    <td><?= formatar_data($os['PREVISAO_ENTREGA']) ?></td>
                    <td><?= $os['ITENS'] ?><?= $os['FOTOS'] > 0 ? ' / '.$os['FOTOS'].'ft' : '' ?></td>
                    <td><?= formatar_valor($os['TOTAL_GERAL']) ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
