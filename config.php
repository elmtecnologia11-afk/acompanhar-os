<?php
function carregar_config() {
    $arquivo = __DIR__ . '/config.json';
    if (!file_exists($arquivo)) {
        die('Arquivo config.json nao encontrado. Copie config.json.example e preencha os dados.');
    }
    $json = file_get_contents($arquivo);
    $config = json_decode($json, true);
    if (!$config || empty($config['banco'])) {
        die('Erro ao ler config.json. Verifique o formato.');
    }
    return $config;
}

function db() {
    static $pdo = null;
    if ($pdo === null) {
        $config = carregar_config();
        $dsn = "firebird:dbname={$config['banco']};charset=WIN1252";
        $usuario = $config['usuario'] ?? 'SYSDBA';
        $senha = $config['senha'] ?? 'masterkey';
        $pdo = new PDO($dsn, $usuario, $senha);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }
    return $pdo;
}

function utf($texto) {
    if (!$texto) return '';
    return iconv('WINDOWS-1252', 'UTF-8', $texto);
}

function situacao_os($s) {
    $map = [
        'A' => ['Aberta', '#e74c3c'],
        'E' => ['Em Andamento', '#f39c12'],
        'P' => ['Aguardando Peca', '#9b59b6'],
        'I' => ['Importada', '#2980b9'],
        'F' => ['Finalizada', '#27ae60'],
        'C' => ['Cancelada', '#95a5a6'],
        'D' => ['Entregue', '#2980b9'],
    ];
    return $map[$s] ?? ['Desconhecido', '#7f8c8d'];
}

function situacao_detalhe($s) {
    $map = [
        'PENDENTE' => ['Pendente', '#f39c12'],
        'EM ANDAMENTO' => ['Em Andamento', '#3498db'],
        'FINALIZADO' => ['Finalizado', '#27ae60'],
        'AGUARDANDO' => ['Aguardando', '#9b59b6'],
    ];
    return $map[strtoupper($s)] ?? [$s, '#7f8c8d'];
}

function formatar_data($d) {
    if (!$d) return '-';
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) {
        $parts = explode('-', $d);
        return "{$parts[2]}/{$parts[1]}/{$parts[0]}";
    }
    if (is_numeric($d)) {
        $ts = mktime(0,0,0, substr($d,5,2), substr($d,8,2), substr($d,0,4));
        return date('d/m/Y', $ts);
    }
    return $d;
}

function formatar_hora($h) {
    if (!$h) return '-';
    return substr($h, 0, 5);
}

function formatar_valor($v) {
    if (!$v) return 'R$ 0,00';
    return 'R$ ' . number_format((float)$v, 2, ',', '.');
}

function categoria_dispositivo($descricao) {
    $d = strtoupper(trim($descricao ?? ''));
    if (strpos($d, 'NOTEBOOK') !== false || strpos($d, 'NOTE') !== false || strpos($d, 'NB') !== false) return 'Notebooks';
    if (strpos($d, 'COMPUTADOR') !== false || strpos($d, 'PC') !== false || strpos($d, 'DESKTOP') !== false) return 'Computadores';
    if (strpos($d, 'IMPRESSORA') !== false || strpos($d, 'IMP') !== false) return 'Impressoras';
    if (strpos($d, 'MONITOR') !== false) return 'Monitores';
    if (strpos($d, 'CELULAR') !== false || strpos($d, 'CEL') !== false || strpos($d, 'TABLET') !== false) return 'Celulares/Tablets';
    if (strpos($d, 'ESTABILIZADOR') !== false || strpos($d, 'NOBREAK') !== false || strpos($d, 'UPS') !== false) return 'Estabilizadores';
    return 'Outros';
}

function icone_dispositivo($cat) {
    $map = [
        'Computadores' => '&#128187;',
        'Notebooks' => '&#128187;',
        'Impressoras' => '&#128424;',
        'Monitores' => '&#128424;',
        'Celulares/Tablets' => '&#128241;',
        'Estabilizadores' => '&#9889;',
        'Outros' => '&#128196;',
    ];
    return $map[$cat] ?? '&#128196;';
}
