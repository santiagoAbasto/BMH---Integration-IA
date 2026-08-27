<?php
$host='127.0.0.1'; $port=3306; $user='root'; $pass='';
$pdo = new PDO("mysql:host=$host;port=$port", $user, $pass, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$dump='bmh_dump_tmp'; $legacy='bmh_legacy';

// Tablas que NO deben sincronizarse (la nueva BD ya las gestiona / son de framework / son cuentas)
$exclude = [
    'equivalencias',               // colisión: el dump trae la equivalencias LEGACY, la nueva BD tiene la reestructurada
    'migrations','failed_jobs','password_reset_tokens','personal_access_tokens', // framework
    'users','admins',              // cuentas (pendiente de confirmación)
];

$dumpTables = $pdo->query("SELECT table_name FROM information_schema.tables WHERE table_schema='$dump' ORDER BY table_name")->fetchAll(PDO::FETCH_COLUMN);

$lines = [];
$lines[] = "-- Sync de datos legacy: bmh_dump_tmp -> bmh_legacy";
$lines[] = "-- Generado: ".date('Y-m-d H:i:s');
$lines[] = "-- No altera estructura. No crea tablas. No toca las 3 tablas nuevas ni las normalizadas.";
$lines[] = "SET FOREIGN_KEY_CHECKS=0;";
$lines[] = "SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';";
$lines[] = "";

foreach ($dumpTables as $t) {
    if (in_array($t, $exclude, true)) { $lines[] = "-- SKIP (excluida): $t"; $lines[] = ""; continue; }
    $exists = $pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$legacy' AND table_name=".$pdo->quote($t))->fetchColumn();
    if (!$exists) { $lines[] = "-- SKIP (no existe en $legacy): $t"; $lines[] = ""; continue; }
    $cols = $pdo->query("SELECT COLUMN_NAME FROM information_schema.columns WHERE table_schema='$legacy' AND table_name=".$pdo->quote($t)." ORDER BY ORDINAL_POSITION")->fetchAll(PDO::FETCH_COLUMN);
    if (empty($cols)) { $lines[] = "-- SKIP (sin columnas): $t"; $lines[] = ""; continue; }
    $colList = implode(', ', array_map(fn($c) => "`$c`", $cols));
    $src = $pdo->query("SELECT COUNT(*) FROM `$dump`.`$t`")->fetchColumn();
    $tgt = $pdo->query("SELECT COUNT(*) FROM `$legacy`.`$t`")->fetchColumn();
    $lines[] = "-- $t  | dump=$src  legacy=$tgt";
    $lines[] = "REPLACE INTO `$legacy`.`$t` ($colList) SELECT $colList FROM `$dump`.`$t`;";
    $lines[] = "";
}
$lines[] = "SET FOREIGN_KEY_CHECKS=1;";
$sql = implode("\n", $lines);
file_put_contents('database/sync_legacy_from_dump.sql', $sql);
echo $sql;
