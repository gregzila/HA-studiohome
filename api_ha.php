<?php
session_start();
if (!isset($_SESSION['auth_maison'])) { exit("Non autorisé"); }

// --- CONFIGURATION ---
$config = json_decode(file_get_contents('config.json'), true);
$ha_token = $config['ha_token'];
$ha_url = $config['ha_url'];

$action = $_GET['action'] ?? null;
$entity = $_GET['entity'] ?? null;

// Fallback for saving config directly to HA if PHP storage is unavailable
if ($action === 'save_config') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || !isset($input['room'])) {
        exit("Invalid input");
    }

    $target_entity = $config['persistence_entity'] ?? 'input_text.maison_config';
    $room = strtolower($input['room']);

    // We use the HA API to update a state.
    // NOTE: Storing in state has a 255 char limit.
    // To be robust, we should store in an ATTRIBUTE.
    // The HA REST API /api/states/<entity_id> allows setting attributes.

    // First, get current state to preserve other attributes
    $ch = curl_init("$ha_url/api/states/$target_entity");
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $ha_token"]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $res = curl_exec($ch);
    $current = json_decode($res, true) ?: ['state' => 'OK', 'attributes' => []];
    curl_close($ch);

    $attributes = $current['attributes'];
    $attributes["config_$room"] = json_encode([
        'zones' => $input['zones'] ?? [],
        'hotspots' => $input['hotspots'] ?? []
    ]);

    $payload = [
        'state' => 'Updated ' . date('Y-m-d H:i:s'),
        'attributes' => $attributes
    ];

    $ch = curl_init("$ha_url/api/states/$target_entity");
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $ha_token",
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    curl_close($ch);

    echo $result;
    exit;
}

if ($entity) {
    $domain = explode('.', $entity)[0];
    $service = $action;
    
    if ($domain === 'button') { $service = 'press'; }
    elseif ($domain === 'script') { $service = 'turn_on'; }
    elseif ($domain === 'vacuum' && $action === 'toggle') { $service = 'start'; }

    $url = $ha_url . "/api/services/$domain/$service";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $ha_token,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(["entity_id" => $entity]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $result = curl_exec($ch);
    curl_close($ch);
}

if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    echo "OK";
} else {
    header("Location: index.php");
}
exit;
