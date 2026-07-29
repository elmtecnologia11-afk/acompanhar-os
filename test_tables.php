<?php
$dsn = 'firebird:dbname=C:\SISComercio\Dados\DADOS.FDB;charset=WIN1252';
$pdo = new PDO($dsn, 'SYSDBA', 'masterkey');

$tables = ['OS_MASTER', 'OS_DETALHE', 'OS_AVARIA', 'OS_IMAGEM', 'PRODUTO', 'USUARIOS'];

foreach ($tables as $table) {
    try {
        $r = $pdo->query("SELECT FIRST 1 * FROM $table")->fetchAll(PDO::FETCH_ASSOC);
        if ($r) {
            echo "=== $table ===\n";
            foreach ($r[0] as $k => $v) {
                echo "  $k: " . gettype($v) . " = " . var_export($v, true) . "\n";
            }
            echo "\n";
        }
    } catch (Exception $e) {
        echo "=== $table === ERRO: " . $e->getMessage() . "\n\n";
    }
}
