<?php
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
$mount = getenv("RAILWAY_VOLUME_MOUNT_PATH");
error_log("MOUNT PATH: " . $mount);

if (!$mount) {
    http_response_code(500);
    echo json_encode([
        "status" => "error", 
        "msg" => "RAILWAY_VOLUME_MOUNT_PATH no definida - valor: " . var_export($mount, true)
    ]);
    exit;
}
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

$id_ruta = $_POST["id_ruta"] ?? null;
$comentario = $_POST["comentario"] ?? "";

if (!$id_ruta) {
    http_response_code(400);
    echo json_encode(["status" => "error", "msg" => "Falta id_ruta"]);
    exit;
}

if (!isset($_FILES["foto"]) || $_FILES["foto"]["error"] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(["status" => "error", "msg" => "Falta foto o error al subir"]);
    exit;
}

// Validación básica de tipo
$allowed = [
    "image/jpeg" => "jpg",
    "image/png"  => "png",
    "image/webp" => "webp"
];

$mime = mime_content_type($_FILES["foto"]["tmp_name"]);
if (!isset($allowed[$mime])) {
    http_response_code(400);
    echo json_encode(["status" => "error", "msg" => "Formato no permitido"]);
    exit;
}

$ext = $allowed[$mime];

// Carpeta donde guardar
$dir = getenv("RAILWAY_VOLUME_MOUNT_PATH") . "/uploads/evidencias/";
if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
    http_response_code(500);
    echo json_encode(["status" => "error", "msg" => "No se pudo crear carpeta"]);
    exit;
}

$filename = "ruta_" . intval($id_ruta) . "_" . date("Ymd_His") . "." . $ext;
$dest = $dir . $filename;

if (!move_uploaded_file($_FILES["foto"]["tmp_name"], $dest)) {
    http_response_code(500);
    echo json_encode(["status" => "error", "msg" => "No se pudo guardar la foto"]);
    exit;
}

$foto_url = "uploads/evidencias/" . $filename;

// UPSERT por id_ruta
$stmt = $conn->prepare(
    "INSERT INTO evidencia_unidad (id_ruta, comentario, foto_url)
     VALUES (?, ?, ?)
     ON DUPLICATE KEY UPDATE
        comentario = VALUES(comentario),
        foto_url = VALUES(foto_url)"
);

$stmt->bind_param("iss", $id_ruta, $comentario, $foto_url);

if ($stmt->execute()) {
    echo json_encode([
        "status" => "success",
        "msg" => "Evidencia guardada",
        "foto_url" => $foto_url
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "msg" => $stmt->error
    ]);
}

$stmt->close();
$conn->close();
?>
