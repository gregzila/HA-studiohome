<?php
session_start();
// Authentification check - compatible with both login methods
if (!isset($_SESSION['auth_maison']) && !isset($_SESSION['auth'])) {
    header('HTTP/1.0 401 Unauthorized');
    exit("Non autorisé");
}

$config = json_decode(file_get_contents('config.json'), true);
$ha_token = $config['ha_token'] ?? '';
$ha_url = rtrim($config['ha_url'] ?? '', '/');

$action = $_GET['action'] ?? null;
$entity = $_GET['entity'] ?? null;

if (empty($ha_token) || empty($ha_url)) {
    header('HTTP/1.1 500 Internal Server Error');
    exit("Configuration Home Assistant manquante");
}

// 1. Action de sauvegarde (Persistence Fallback)
if ($action === 'save_config') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || !isset($input['room'])) {
        header('HTTP/1.1 400 Bad Request');
        exit("Invalid input");
    }

    $target_entity = $config['persistence_entity'] ?? 'input_text.maison_config';
    $room = strtolower($input['room']);

    // Récupérer l'état actuel pour préserver les autres attributs
    $ch = curl_init("$ha_url/api/states/$target_entity");
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $ha_token"]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $res = curl_exec($ch);
    $current = json_decode($res, true) ?: ['state' => 'OK', 'attributes' => []];
    curl_close($ch);

    $attributes = $current['attributes'] ?? [];
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

    header('Content-Type: application/json');
    echo $result;
    exit;
}

// 2. Proxy GET States
if ($action === 'get_state' && $entity) {
    if ($entity === 'persistence') {
        $entity = $config['persistence_entity'] ?? 'input_text.maison_config';
    }
    $ch = curl_init("$ha_url/api/states/$entity");
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $ha_token"]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    curl_close($ch);
    header('Content-Type: application/json');
    echo $result;
    exit;
}

// 3. Proxy Services
if ($entity) {
    $domain = explode('.', $entity)[0];
    $service = $action;
    
    // Mappings par défaut pour simplifier les appels frontend
    if ($domain === 'button') { $service = 'press'; }
    elseif ($domain === 'script') { $service = 'turn_on'; }
    elseif ($domain === 'vacuum' && $action === 'toggle') { $service = 'start'; }
    elseif ($action === 'set_temperature') { $service = 'set_temperature'; $domain = 'climate'; }
    elseif ($action === 'toggle') { $service = 'toggle'; $domain = 'homeassistant'; }

    $url = $ha_url . "/api/services/$domain/$service";
    
    // Données de service (priorité au corps du POST, sinon entity_id par défaut)
    $postData = json_decode(file_get_contents('php://input'), true) ?: ["entity_id" => $entity];
    if (!isset($postData['entity_id'])) $postData['entity_id'] = $entity;

    // Cas spécial Scapin (segments)
    if ($action === 'segment' && isset($_GET['segment'])) {
        $url = $ha_url . "/api/services/vacuum/send_command";
        $postData = [
            "entity_id" => $entity,
            "command" => "app_segment_clean",
            "params" => [(int)$_GET['segment']]
        ];
    }

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $ha_token,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    curl_close($ch);

    header('Content-Type: application/json');
    echo $result;
    exit;
}

header('HTTP/1.1 400 Bad Request');
echo json_encode(["error" => "Requête invalide"]);
exit;
