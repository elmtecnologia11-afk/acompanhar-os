<?php
require __DIR__ . '/config.php';
$db = db();

try {
    $abertas_det = $db->query("
        SELECT CODIGO, DATA_INICIO, HORA_INICIO, NOME, DESCRICAO, SITUACAO, TOTAL_GERAL, MARCA, MODELO, PREVISAO_ENTREGA
        FROM OS_MASTER 
        WHERE SITUACAO IN ('A','E','P')
        ORDER BY CODIGO DESC
    ")->fetchAll();

} catch (Exception $e) {
    $erro = $e->getMessage();
}

$abertas_por_cat = ['Computadores' => [], 'Notebooks' => [], 'Impressoras' => [], 'Outros' => []];
if (empty($erro)) {
    foreach ($abertas_det as $row) {
        $cat = categoria_dispositivo($row['DESCRICAO']);
        if (isset($abertas_por_cat[$cat])) {
            $abertas_por_cat[$cat][] = $row;
        } else {
            $abertas_por_cat['Outros'][] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="10">
    <title>Acompanhar OS - Inicio</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .cat-grid { display:grid; grid-template-columns:repeat(4, 1fr); gap:16px; margin-top:20px; }
        .cat-col { background:var(--card); border-radius:10px; box-shadow:0 1px 4px rgba(0,0,0,.08); overflow:hidden; }
        .cat-header { padding:14px 16px; font-weight:700; font-size:14px; display:flex; align-items:center; gap:8px; border-bottom:1px solid #f0f0f0; }
        .cat-count { border-radius:50%; width:20px; height:20px; display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:700; color:#fff; margin-left:auto; }
        .cat-body { padding:8px; max-height:800px; overflow-y:auto; display:flex; flex-direction:column; gap:8px; }
        .cli-card { background:#fff; border-radius:10px; padding:16px; cursor:pointer; transition:all .2s; box-shadow:0 1px 4px rgba(0,0,0,.08); border-top:3px solid var(--accent); }
        .cli-card:hover { transform:translateY(-2px); box-shadow:0 4px 12px rgba(0,0,0,.12); }
        .cli-top { display:flex; align-items:center; gap:8px; margin-bottom:6px; }
        .cli-os { font-weight:700; font-size:15px; color:var(--accent); }
        .cli-nome { font-size:14px; font-weight:600; color:var(--text); }
        .cli-desc { font-size:12px; color:var(--text-light); margin-bottom:6px; }
        .cli-bottom { display:flex; justify-content:space-between; align-items:center; margin-top:6px; font-size:11px; color:var(--text-light); }
        .cat-empty { padding:20px; text-align:center; color:var(--text-light); font-size:12px; }
        @media(max-width:1100px) { .cat-grid { grid-template-columns:repeat(2, 1fr); } }
        @media(max-width:600px) { .cat-grid { grid-template-columns:1fr; } }
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
        <?php if (isset($erro)): ?>
            <div class="card" style="border-left:4px solid var(--danger)">
                <p>Erro ao conectar com o banco: <?= htmlspecialchars($erro) ?></p>
            </div>
        <?php else: ?>

        <?php
        $cores = [
            'Computadores' => '#3498db',
            'Notebooks' => '#2ecc71',
            'Impressoras' => '#e67e22',
            'Outros' => '#95a5a6',
        ];
        $icones = [
            'Computadores' => '&#128187;',
            'Notebooks' => '&#128187;',
            'Impressoras' => '&#128424;',
            'Outros' => '&#128196;',
        ];
        ?>
        <div class="cat-grid">
            <?php foreach ($abertas_por_cat as $cat => $lista):
                $cor = $cores[$cat];
            ?>
            <div class="cat-col">
                <div class="cat-header" style="border-top:3px solid <?= $cor ?>">
                    <span><?= $icones[$cat] ?? '' ?></span> <?= $cat ?>
                    <div class="cat-count" style="background:<?= $cor ?>"><?= count($lista) ?></div>
                </div>
                <div class="cat-body">
                    <?php if (empty($lista)): ?>
                        <div class="cat-empty">Nenhuma OS aberta</div>
                    <?php else: ?>
                        <?php foreach ($lista as $os):
                            $sit = situacao_os($os['SITUACAO']);
                        ?>
                        <div class="cli-card" style="border-top-color:<?= $cor ?>" onclick="location.href='os_detalhe.php?cod=<?= $os['CODIGO'] ?>'">
                            <div class="cli-top">
                                <span class="cli-os">#<?= $os['CODIGO'] ?></span>
                                <span class="cli-nome"><?= htmlspecialchars(utf($os['NOME'] ?? '-')) ?></span>
                                <span class="badge badge-<?= strtolower(str_replace(' ','',$sit[0])) ?>" style="margin-left:auto;font-size:10px;padding:2px 8px"><?= $sit[0] ?></span>
                            </div>
                            <div class="cli-desc"><?= htmlspecialchars(utf($os['DESCRICAO'] ?? '')) ?></div>
                            <div class="cli-bottom">
                                <span><?= htmlspecialchars(utf($os['MARCA'] ?? '')) ?> <?= htmlspecialchars(utf($os['MODELO'] ?? '')) ?></span>
                                <span><?= formatar_data($os['PREVISAO_ENTREGA']) ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
