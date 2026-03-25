<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db.php';

try {
    $pdo = db_conn_pdo();
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => 'DB fail', 'error' => $e->getMessage()]);
    exit;
}

$id_ruta   = $_POST['id_ruta']   ?? '';
$traker_id = $_POST['traker_id'] ?? '';

if ($id_ruta === '' || $traker_id === '') {
    echo json_encode(['ok' => false, 'msg' => 'Faltan parámetros']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        UPDATE rutas 
        SET STAT_RUT = 'F' 
        WHERE id_ruta = :id_ruta 
        AND traker_id = :traker_id
    ");
    $stmt->execute([
        ':id_ruta'   => $id_ruta,
        ':traker_id' => $traker_id
    ]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['ok' => true, 'msg' => 'Ruta finalizada']);
    } else {
        echo json_encode(['ok' => false, 'msg' => 'Sin cambios, verifica id_ruta y traker_id']);
    }
} catch (PDOException $e) {
    echo json_encode(['ok' => false, 'msg' => 'Error: ' . $e->getMessage()]);
}