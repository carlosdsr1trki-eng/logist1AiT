<?php
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
date_default_timezone_set('America/Mexico_City');

require_once __DIR__ . "/db.php";

try {
    $conn = db_conn();
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "msg" => "DB fail",
        "error" => $e->getMessage()
    ]);
    exit;
}

$id_ruta   = $_POST["id_ruta"] ?? null;
$km_inicio = $_POST["km_inicio"] ?? null;

if (!$id_ruta || $km_inicio === null || $km_inicio === "") {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "msg" => "Faltan datos"
    ]);
    exit;
}

$stmt = $conn->prepare("
    UPDATE rutas
    SET km_inicio = ?
    WHERE id_ruta = ?
");

$stmt->bind_param("di", $km_inicio, $id_ruta);

if ($stmt->execute()) {
    echo json_encode([
        "status" => "ok",
        "msg" => "Kilometraje guardado"
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "msg" => "No se pudo actualizar",
        "error" => $stmt->error
    ]);
}

$stmt->close();
$conn->close();