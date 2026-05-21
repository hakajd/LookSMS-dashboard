<?php
/**
 * GET /api/commands/pending
 * Retourne les messages status=Pending pour un device Android.
 * Marque atomiquement les messages récupérés en Queued.
 *
 * Headers requis :
 *   X-Api-Key  : clé API utilisateur (dashboard → Profil → API Key)
 *   X-Device-Id: androidId du téléphone (tokenManager.deviceId côté Android)
 */
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../vendor/autoload.php";

date_default_timezone_set(TIMEZONE);
header("Content-Type: application/json");

// Compatibilité Apache et PHP-FPM
function getHeader(string $name): ?string {
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    foreach ($headers as $k => $v) {
        if (strcasecmp($k, $name) === 0) return $v;
    }
    // Fallback $_SERVER
    $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
    return $_SERVER[$key] ?? null;
}

try {
    $apiKey   = getHeader("X-Api-Key");
    $androidId = getHeader("X-Device-Id");

    if (!$apiKey || !$androidId) {
        http_response_code(401);
        echo json_encode(["error" => "Missing X-Api-Key or X-Device-Id"]);
        exit;
    }

    // Auth utilisateur par API key
    $user = new User();
    $user->setApiKey($apiKey);
    $user = $user->read();
    if (!$user) {
        http_response_code(401);
        echo json_encode(["error" => "Invalid API key"]);
        exit;
    }

    // Résolution du device par androidId + userId
    $device = new Device();
    $device->setAndroidID($androidId);
    $device->setUserID($user->getID());
    if (!$device->read()) {
        http_response_code(404);
        echo json_encode(["error" => "Device not found — call /api/register first"]);
        exit;
    }

    // Récupération atomique des messages Pending → Queued
    MysqliDb::getInstance()->startTransaction();

    $messages = Message::where("deviceID", $device->getID())
        ->where("status", "Pending")
        ->read_all(false);

    if (!empty($messages)) {
        $ids = array_map(fn(Message $m) => $m->getID(), $messages);
        Message::where("ID", $ids, "IN")
            ->update_all(["status" => "Queued"]);
    }

    MysqliDb::getInstance()->commit();

    // Format SmsCommand attendu par CommandDispatcher.kt
    $commands = array_map(fn(Message $m) => [
        "id"       => "looksms_" . $m->getID(),
        "action"   => "send",
        "to"       => $m->getNumber(),
        "message"  => $m->getMessage(),
        "priority" => $m->getPrioritize() ? 1 : 0,
    ], $messages);

    echo json_encode($commands);

} catch (Throwable $t) {
    try { MysqliDb::getInstance()->rollback(); } catch (Throwable $_) {}
    http_response_code(500);
    echo json_encode(["error" => $t->getMessage()]);
}
