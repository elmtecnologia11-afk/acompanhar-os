<?php
require __DIR__ . '/config.php';
$db = db();

$codigo = (int)($_GET['cod'] ?? 0);
if ($codigo <= 0) {
    header('Location: os_list.php');
    exit;
}

try {
    $os = $db->prepare("
        SELECT * FROM OS_MASTER WHERE CODIGO = ?
    ");
    $os->execute([$codigo]);
    $os = $os->fetch();

    if (!$os) {
        header('Location: os_list.php');
        exit;
    }

    $detalhes = $db->prepare("
        SELECT D.*, P.DESCRICAO AS PRODUTO_NOME, U.LOGIN AS FUNCIONARIO_NOME
        FROM OS_DETALHE D
        LEFT JOIN PRODUTO P ON P.CODIGO = D.FK_PRODUTO
        LEFT JOIN USUARIOS U ON U.CODIGO = D.FK_FUNCIONARIO
        WHERE D.FK_OS_MASTER = ?
        ORDER BY D.CODIGO
    ");
    $detalhes->execute([$codigo]);
    $detalhes = $detalhes->fetchAll();

    $avarias = $db->prepare("SELECT * FROM OS_AVARIA WHERE FK_OS_MASTER = ? ORDER BY CODIGO");
    $avarias->execute([$codigo]);
    $avarias = $avarias->fetchAll();

    $imagens = $db->prepare("SELECT * FROM OS_IMAGEM WHERE FK_OS_MASTER = ? ORDER BY ITEM");
    $imagens->execute([$codigo]);
    $imagens = $imagens->fetchAll();

    $sit = situacao_os($os['SITUACAO']);

} catch (Exception $e) {
    $erro = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="10">
    <script>setTimeout(function(){ location.reload(); }, 10000);</script>
    <title>OS #<?= $codigo ?> - <?= htmlspecialchars(utf($os['NOME'] ?? '')) ?></title>
    <link rel="stylesheet" href="style.css">
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
                <p>Erro: <?= htmlspecialchars($erro) ?></p>
            </div>
        <?php else: ?>

        <div class="card" style="display:flex;align-items:center;gap:16px;flex-wrap:wrap">
            <div>
                <a href="os_list.php" style="font-size:13px">&larr; Voltar</a>
                <h2 style="margin:4px 0">OS #<?= $os['CODIGO'] ?> <?= $os['NUMERO'] ? ' ('.$os['NUMERO'].')' : '' ?></h2>
            </div>
            <span class="badge badge-<?= strtolower(str_replace(' ','-',$sit[0])) ?>" style="font-size:14px;padding:6px 16px"><?= $sit[0] ?></span>
            <span style="margin-left:auto;font-size:13px;color:var(--text-light)">
                Criada em <?= formatar_data($os['DATA_INICIO']) ?> <?= formatar_hora($os['HORA_INICIO']) ?>
            </span>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
            <div class="card">
                <h2>Dados do Cliente</h2>
                <div class="detail-grid">
                    <div class="detail-item"><label>Nome</label><span><?= htmlspecialchars(utf($os['NOME'] ?? '-')) ?></span></div>
                    <div class="detail-item"><label>Documento</label><span><?= htmlspecialchars(utf($os['DOCUMENTO'] ?? '-')) ?></span></div>
                    <div class="detail-item"><label>Fone 1</label><span><?= htmlspecialchars(utf($os['FONE1'] ?? '-')) ?></span></div>
                    <div class="detail-item"><label>Fone 2</label><span><?= htmlspecialchars(utf($os['FONE2'] ?? '-')) ?></span></div>
                    <div class="detail-item"><label>Endereco</label><span><?= htmlspecialchars(utf($os['ENDERECO'] ?? '-')) ?></span></div>
                    <div class="detail-item"><label>Bairro</label><span><?= htmlspecialchars(utf($os['BAIRRO'] ?? '-')) ?></span></div>
                    <div class="detail-item"><label>Cidade/UF</label><span><?= htmlspecialchars(utf(($os['CIDADE'] ?? '-').'/'.$os['UF'] ?? '')) ?></span></div>
                </div>
            </div>

            <div class="card">
                <h2>Dados do Aparelho</h2>
                <div class="detail-grid">
                    <div class="detail-item"><label>Descricao</label><span><?= htmlspecialchars(utf($os['DESCRICAO'] ?? '-')) ?></span></div>
                    <div class="detail-item"><label>Marca</label><span><?= htmlspecialchars(utf($os['MARCA'] ?? '-')) ?></span></div>
                    <div class="detail-item"><label>Modelo</label><span><?= htmlspecialchars(utf($os['MODELO'] ?? '-')) ?></span></div>
                    <div class="detail-item"><label>Cor</label><span><?= htmlspecialchars(utf($os['COR'] ?? '-')) ?></span></div>
                    <div class="detail-item"><label>Numero Serie</label><span><?= htmlspecialchars(utf($os['NUMERO_SERIE'] ?? '-')) ?></span></div>
                    <div class="detail-item"><label>IMEI</label><span><?= htmlspecialchars(utf($os['IMEI'] ?? '-')) ?></span></div>
                    <div class="detail-item"><label>Placa</label><span><?= htmlspecialchars(utf($os['PLACA'] ?? '-')) ?></span></div>
                    <div class="detail-item"><label>KM</label><span><?= $os['KM'] ? number_format($os['KM'],0,',','.') : '-' ?></span></div>
                </div>
            </div>
        </div>

        <div class="card">
            <h2>Problema Relatado</h2>
            <p style="line-height:1.6"><?= nl2br(htmlspecialchars(utf($os['PROBLEMA'] ?? 'Nenhum problema relatado.'))) ?></p>
        </div>

        <?php
        $laudo_checks = [
            'ARRANHADO'=>'Arranhado','TRINCADO'=>'Trincado','REPRODUZ'=>'Reproduz','CARREGANDO'=>'Carregando',
            'APARELHO'=>'Aparelho','PELICULA'=>'Pelicular','CHIP'=>'Chip','BATERIA'=>'Bateria',
            'CARTAO'=>'Cartao','CAIXA'=>'Caixa',
            'MARCAS_USO'=>'Marcas Uso','MANCHA_TELA'=>'Mancha Tela','PARAFUSOS_EXT'=>'Parafusos Ext',
            'CAMERA_TRINCADA'=>'Camera Trincada','TOUCH_FUNCIONANDO'=>'Touch OK','DISPLAY_SEM_AVARIAS'=>'Display OK',
            'VIDRO_DANIFICADO'=>'Vidro Danificado','BOTAO_HOME_FUNCIONANDO'=>'Home OK',
            'TOUCH_ID'=>'Touch ID','FACE_ID'=>'Face ID',
            'GRAVA_AUDIO'=>'Grava Audio','LEITURA_CHIP'=>'Leitura Chip','TESTE_WIFI'=>'WiFi',
            'BLUETOOTH_FUNCIONA'=>'Bluetooth','FONE_AURICULAR'=>'Fone','CHAVINHA_VIBRA'=>'Vibra',
            'TESTE_CAMERA_TRASEIRA'=>'Camera Traseira','TESTE_CAMERA_FRONTAL'=>'Camera Frontal',
            'SENSOR_PROXIMIDADE'=>'Sensor Proximidade',
        ];
        $checks_ativos = [];
        foreach ($laudo_checks as $k => $v) {
            if (isset($os[$k]) && $os[$k] === 'S') {
                $checks_ativos[] = $v;
            }
        }
        ?>
        <?php if (!empty($checks_ativos)): ?>
        <div class="card">
            <h2>Laudo Tecnico Visual</h2>
            <div style="display:flex;flex-wrap:wrap;gap:8px">
                <?php foreach ($checks_ativos as $c): ?>
                    <span style="background:#e8f5e9;padding:4px 12px;border-radius:12px;font-size:13px">&#10003; <?= $c ?></span>
                <?php endforeach; ?>
            </div>
            <?php if ($os['LAUDO_TECNICO_VISUAL']): ?>
                <p style="margin-top:12px;font-size:13px;color:var(--text-light)"><?= htmlspecialchars(utf($os['LAUDO_TECNICO_VISUAL'])) ?></p>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($avarias)): ?>
        <div class="card">
            <h2>Avarias (<?= count($avarias) ?>)</h2>
            <table>
                <tr><th>Tipo</th><th>Posicao X</th><th>Posicao Y</th><th>Observacao</th></tr>
                <?php foreach ($avarias as $av): ?>
                <tr>
                    <td><?= htmlspecialchars(utf($av['TIPO_AVARIA'])) ?></td>
                    <td><?= $av['POSICAO_X'] ?></td>
                    <td><?= $av['POSICAO_Y'] ?></td>
                    <td><?= htmlspecialchars(utf($av['OBSERVACAO'] ?? '-')) ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
        <?php endif; ?>

        <?php if (!empty($detalhes)): ?>
        <div class="card">
            <h2>Servicos e Pecas (<?= count($detalhes) ?>)</h2>
            <table>
                <tr>
                    <th>Tipo</th>
                    <th>Descricao</th>
                    <th>Funcionario</th>
                    <th>Qtd</th>
                    <th>Preco</th>
                    <th>Total</th>
                    <th>Situacao</th>
                    <th>Inicio</th>
                    <th>Fim</th>
                </tr>
                <?php foreach ($detalhes as $d):
                    $sdt = situacao_detalhe($d['SITUACAO'] ?? '');
                ?>
                <tr>
                    <td><span class="badge" style="background:<?= $d['TIPO'] === 'S' ? 'var(--accent)' : 'var(--info)' ?>"><?= $d['TIPO'] === 'S' ? 'Servico' : 'Peca' ?></span></td>
                    <td><?= htmlspecialchars(utf($d['DISCRIMINACAO'] ?? $d['PRODUTO_NOME'] ?? '-')) ?></td>
                    <td><?= htmlspecialchars(utf($d['FUNCIONARIO_NOME'] ?? '-')) ?></td>
                    <td><?= $d['QTD'] ?></td>
                    <td><?= formatar_valor($d['PRECO']) ?></td>
                    <td><?= formatar_valor($d['TOTAL']) ?></td>
                    <td><span class="badge" style="background:<?= $sdt[1] ?>"><?= $sdt[0] ?></span></td>
                    <td><?= formatar_data($d['DATA_INICIO']) ?> <?= formatar_hora($d['HORA_INICIO']) ?></td>
                    <td><?= formatar_data($d['DATA_TERMINO']) ?> <?= formatar_hora($d['HORA_TERMINO']) ?></td>
                </tr>
                <?php endforeach; ?>
            </table>

            <div style="margin-top:16px;text-align:right;font-size:14px">
                <strong>Subtotal Pecas:</strong> <?= formatar_valor($os['SUBTOTAL_PECAS']) ?> &nbsp;
                <strong>Servicos:</strong> <?= formatar_valor($os['TOTAL_SERVICOS']) ?> &nbsp;
                <strong style="font-size:16px;color:var(--accent)">Total Geral:</strong> <?= formatar_valor($os['TOTAL_GERAL']) ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="card">
            <h2>Timeline</h2>
            <div class="timeline">
                <div class="timeline-item">
                    <div class="t-date"><?= formatar_data($os['DATA_INICIO']) ?> <?= formatar_hora($os['HORA_INICIO']) ?></div>
                    <div class="t-title">OS Aberta</div>
                    <div class="t-desc"><?= htmlspecialchars(utf($os['TIPO_SERVICO'] ?? 'Servico')) ?> - <?= htmlspecialchars(utf($os['NOME'] ?? '')) ?></div>
                </div>

                <?php foreach ($detalhes as $d): ?>
                    <?php if ($d['DATA_INICIO']): ?>
                    <div class="timeline-item <?= ($d['DATA_TERMINO'] && $d['HORA_TERMINO']) ? 'ok' : '' ?>">
                        <div class="t-date"><?= formatar_data($d['DATA_INICIO']) ?> <?= formatar_hora($d['HORA_INICIO']) ?></div>
                        <div class="t-title"><?= $d['TIPO'] === 'S' ? 'Servico' : 'Peca' ?>: <?= htmlspecialchars(utf($d['DISCRIMINACAO'] ?? '')) ?></div>
                        <div class="t-desc">
                            <?= htmlspecialchars(utf($d['FUNCIONARIO_NOME'] ?? '')) ?>
                            <?= $d['DATA_TERMINO'] ? ' - Concluido em '.formatar_data($d['DATA_TERMINO']).' '.formatar_hora($d['HORA_TERMINO']) : ' - Em andamento' ?>
                        </div>
                    </div>
                    <?php endif; ?>
                <?php endforeach; ?>

                <?php if ($os['DATA_TERMINO']): ?>
                <div class="timeline-item ok">
                    <div class="t-date"><?= formatar_data($os['DATA_TERMINO']) ?> <?= formatar_hora($os['HORA_TERMINO']) ?></div>
                    <div class="t-title">Servico Finalizado</div>
                </div>
                <?php endif; ?>

                <?php if ($os['DATA_ENTREGA']): ?>
                <div class="timeline-item ok">
                    <div class="t-date"><?= formatar_data($os['DATA_ENTREGA']) ?> <?= formatar_hora($os['HORA_ENTREGA']) ?></div>
                    <div class="t-title">Equipamento Entregue</div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($os['OBSERVACOES']): ?>
        <div class="card">
            <h2>Observacoes</h2>
            <p style="line-height:1.6"><?= nl2br(htmlspecialchars(utf($os['OBSERVACOES']))) ?></p>
        </div>
        <?php endif; ?>

        <?php if ($os['JUSTIFICATIVA']): ?>
        <div class="card">
            <h2>Justificativa</h2>
            <p style="line-height:1.6"><?= nl2br(htmlspecialchars(utf($os['JUSTIFICATIVA']))) ?></p>
        </div>
        <?php endif; ?>

        <?php endif; ?>
    </div>
</body>
</html>
