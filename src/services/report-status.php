<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../vendor/autoload.php";

date_default_timezone_set(TIMEZONE);
header("Content-Type: application/json");

// ── Format Android (POST JSON body depuis StatusReporter.kt) ─────────────────
// {"id":"looksms_123","status":"sent","timestamp":"...","device_id":"...","error":null}
$contentType = $_SERVER["CONTENT_TYPE"] ?? "";
if (str_contains($contentType, "application/json")) {
    try {
        $body = json_decode(file_get_contents("php://input"), true);
        if (!$body || !isset($body["id"]) || !isset($body["status"])) {
            http_response_code(400);
            echo json_encode(["success" => false, "error" => "Missing id or status"]);
            exit;
        }

        // "looksms_123" → 123
        if (!str_starts_with($body["id"], "looksms_")) {
            http_response_code(400);
            echo json_encode(["success" => false, "error" => "id must start with looksms_"]);
            exit;
        }
        $numericId = (int) substr($body["id"], strlen("looksms_"));

        // Mapping statuts Android → rbsoft
        $statusMap = [
            "sent"             => "Sent",
            "sent_no_report"   => "Sent",
            "delivered"        => "Delivered",
            "failed"           => "Failed",
            "failed_no_service"=> "Failed",
            "failed_radio_off" => "Failed",
            "failed_pdu"       => "Failed",
        ];
        $rbStatus = $statusMap[$body["status"]] ?? null;

        if (!$rbStatus) {
            // Statuts intermédiaires (pending, sending) ignorés
            echo json_encode(["success" => true, "data" => null, "error" => null]);
            exit;
        }

        $obj = new Message();
        $obj->setID($numericId);
        if ($obj->read()) {
            $obj->setStatus($rbStatus);
            if ($rbStatus === "Delivered") {
                $obj->setDeliveredDate(date("Y-m-d H:i:s"));
            }
            if (!empty($body["error"])) {
                $obj->setResultCode(null);
            }
            $obj->save();
        }
        echo json_encode(["success" => true, "data" => null, "error" => null]);
        exit;
    } catch (Throwable $t) {
        http_response_code(500);
        echo json_encode(["success" => false, "error" => $t->getMessage()]);
        exit;
    }
}

// ── Format rbsoft original (POST form, tableau messages) ─────────────────────
try {
    if (isset($_POST["messages"])) {
        $messages = json_decode($_POST["messages"], true);
        if (is_array($messages) && count($messages) > 0) {
            MysqliDb::getInstance()->startTransaction();
            foreach ($messages as $message) {
                if (isset($message["ID"]) && isset($message["status"])) {
                    $obj = new Message();
                    $obj->setID($message["ID"]);
                    if ($obj->read()) {
                        $obj->setStatus($message["status"]);
                        if (isset($message["deliveredDate"])) {
                            $time = new DateTime($message["deliveredDate"]);
                            $time->setTimezone(new DateTimeZone(TIMEZONE));
                            $obj->setDeliveredDate($time->format("Y-m-d H:i:s"));
                        }
                        if (isset($message["resultCode"])) {
                            $obj->setResultCode($message["resultCode"]);
                        }
                        if (isset($message["errorCode"])) {
                            $obj->setErrorCode($message["errorCode"]);
                        }
                        if (array_key_exists("simSlot", $message)) {
                            $obj->setSimSlot($message["simSlot"]);
                        }
                        $obj->save();
                    }
                } else {
                    throw new Exception(__("error_invalid_request_format"));
                }
            }
            /* Uncomment this block if you want to send webhook on message status change.
            $messageObjects = [];
            foreach ($messages as $message) {
                $obj = new Message();
                $obj->setID($message["ID"]);
                $obj->read();
                $messageObjects[$obj->userID] = $message;
            }
            foreach ($messageObjects as $userID => $data) {
                $user = new User();
                $user->setID($userID);
                if ($user->read()) {
                    $user->callWebhook('messages', $data);
                }
            }
            */
            MysqliDb::getInstance()->commit();
        } else {
            throw new Exception(__("error_invalid_request_format"));
        }
        echo json_encode(["success" => true, "data" => null, "error" => null]);
    }
} catch (Throwable $t) {
    echo json_encode(["success" => false, "data" => null, "error" => ["code" => 500, "message" => $t->getMessage()]]);
}
