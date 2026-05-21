<?php
/**
 * POST /api/send-direct
 * Envoi immédiat depuis l'app Android (template 1 clic).
 * Crée le message en DB et le retourne pour envoi immédiat par l'app.
 *
 * Headers : X-Api-Key, X-Device-Id
 * Body JSON : {"to": "+32...", "message": "Bonjour..."}
 * Réponse : {"id": "looksms_123", "action": "send", "to": "+32...", "message": "..."}
 */
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../vendor/autoload.php";

date_default_timezone_set(TIMEZONE);
header("Content-Type: application/json");

function getHeader(string $name): ?string {
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    foreach ($headers as $k => $v) {
        if (strcasecmp($k, $name) === 0) return $v;
    }
    return $_SERVER['HTTP_' . strtoupper(str_replace('-', '_', $name))] ?? null;
}

try {
    $apiKey   = getHeader("X-Api-Key");
    $androidId = getHeader("X-Device-Id");
    if (!$apiKey || !$androidId) {
        http_response_code(401);
        echo json_encode(["error" => "Missing X-Api-Key or X-Device-Id"]);
        exit;
    }

    $body = json_decode(file_get_contents("php://input"), true);
    $to      = trim($body["to"] ?? "");
    $message = trim($body["message"] ?? "");

    if (!$to || !$message) {
        http_response_code(400);
        echo json_encode(["error" => "Missing to or message"]);
        exit;
    }

    $user = new User();
    $user->setApiKey($apiKey);
    $user = $user->read();
    if (!$user) { http_response_code(401); echo json_encode(["error" => "Invalid API key"]); exit; }

    $device = new Device();
    $device->setAndroidID($androidId);
    $device->setUserID($user->getID());
    if (!$device->read()) {
        http_response_code(404);
        echo json_encode(["error" => "Device not found"]);
        exit;
    }

    // Créer le message en DB — statut Queued (envoi immédiat par l'app)
    $msg = new Message();
    $msg->setNumber($to);
    $msg->setMessage($message);
    $msg->setDeviceID($device->getID());
    $msg->setUserID($user->getID());
    $msg->setStatus("Queued");
    $msg->setGroupID(uniqid('direct_', true));
    $msg->save();

    echo json_encode([
        "id"       => "looksms_" . $msg->getID(),
        "action"   => "send",
        "to"       => $to,
        "message"  => $message,
        "priority" => 1,
    ]);

} catch (Throwable $t) {
    http_response_code(500);
    echo json_encode(["error" => $t->getMessage()]);
}
