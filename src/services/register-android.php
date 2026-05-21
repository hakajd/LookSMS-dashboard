<?php
/**
 * POST /api/register
 * Enregistre ou met à jour un device Android et son token FCM.
 *
 * Header : X-Api-Key  (clé API utilisateur)
 * Body JSON :
 *   {
 *     "deviceId"  : "uuid-android",   // tokenManager.deviceId
 *     "model"     : "Redmi Note 12",  // Build.MODEL (optionnel)
 *     "fcmToken"  : "firebase-token"  // LookSmsFcmService.onNewToken (optionnel)
 *   }
 *
 * Réponse :
 *   { "success": true, "data": { "deviceNumericId": 42, "userId": 7 } }
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
    $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
    return $_SERVER[$key] ?? null;
}

try {
    $input    = json_decode(file_get_contents("php://input"), true) ?? [];
    $apiKey   = getHeader("X-Api-Key") ?? ($input["apiKey"] ?? null);
    $androidId = $input["deviceId"] ?? null;
    $model    = $input["model"]    ?? "Android";
    $fcmToken = $input["fcmToken"] ?? null;

    if (!$apiKey || !$androidId) {
        http_response_code(400);
        echo json_encode(["success" => false, "error" => "Missing X-Api-Key header or deviceId in body"]);
        exit;
    }

    $user = new User();
    $user->setApiKey($apiKey);
    $user = $user->read();
    if (!$user) {
        http_response_code(401);
        echo json_encode(["success" => false, "error" => "Invalid API key"]);
        exit;
    }

    MysqliDb::getInstance()->startTransaction();

    $device = new Device();
    $device->setAndroidID($androidId);
    $device->setUserID($user->getID());
    $device->read(); // no-op si inexistant (ID reste null → INSERT)

    $device->setModel($model);
    $device->setEnabled(1);
    if ($fcmToken) {
        $device->setToken($fcmToken);
    }
    $device->save();

    // Lien device ↔ user (table DeviceUser) — upsert via onDuplicate
    $deviceUser = DeviceUser::getById($device->getID(), $user->getID());
    if (!$deviceUser) {
        $deviceUser = new DeviceUser();
        $deviceUser->setDeviceID($device->getID());
        $deviceUser->setUserID($user->getID());
        $deviceUser->setActive(true);
    } else {
        $deviceUser->setActive(true);
    }
    $deviceUser->save(true, ['active' => 1]);

    MysqliDb::getInstance()->commit();

    echo json_encode([
        "success" => true,
        "data"    => [
            "deviceNumericId" => $device->getID(),
            "userId"          => $user->getID(),
        ]
    ]);

} catch (Throwable $t) {
    try { MysqliDb::getInstance()->rollback(); } catch (Throwable $_) {}
    http_response_code(500);
    echo json_encode(["success" => false, "error" => $t->getMessage()]);
}
