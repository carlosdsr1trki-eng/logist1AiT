<?php
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");

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

try {
    $stmt = $conn->prepare("
        UPDATE kilometraje
        SET km_inicio = ?
        WHERE id_ruta = ?
    ");

    $stmt->bind_param("di", $km_inicio, $id_ruta);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo json_encode([
            "status" => "ok",
            "msg" => "Kilometraje guardado"
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "msg" => "No se encontró registro en kilometros para esa ruta"
        ]);
    }

    $stmt->close();
    $conn->close();

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "msg" => $e->getMessage()
    ]);
}