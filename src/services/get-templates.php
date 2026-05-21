<?php
/**
 * GET /api/templates
 * Retourne les templates SMS de l'utilisateur.
 *
 * Header : X-Api-Key
 * Réponse : [{"id":1,"name":"Bienvenue","message":"Bonjour {{prénom}}..."}]
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
    $apiKey = getHeader("X-Api-Key");
    if (!$apiKey) { http_response_code(401); echo json_encode(["error" => "Missing X-Api-Key"]); exit; }

    $user = new User();
    $user->setApiKey($apiKey);
    $user = $user->read();
    if (!$user) { http_response_code(401); echo json_encode(["error" => "Invalid API key"]); exit; }

    $templates = Template::where("userID", $user->getID())->read_all(false);

    $result = array_map(fn(Template $t) => [
        "id"      => $t->getID(),
        "name"    => html_entity_decode($t->getName(), ENT_QUOTES),
        "message" => html_entity_decode($t->getMessage(), ENT_QUOTES),
    ], $templates);

    echo json_encode($result);

} catch (Throwable $t) {
    http_response_code(500);
    echo json_encode(["error" => $t->getMessage()]);
}
