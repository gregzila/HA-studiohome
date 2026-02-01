<?php
header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => 'Invalid Request'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $room_idx = $input['room_idx'];
    $entities = $input['entities'];

    if (isset($room_idx) && isset($entities)) {
        $configFile = 'config.json';
        $config = json_decode(file_get_contents($configFile), true);

        if (isset($config['rooms'][$room_idx])) {
            $config['rooms'][$room_idx]['temp'] = $entities['temp'];
            $config['rooms'][$room_idx]['vacuum_entity'] = $entities['vacuum_entity'];
            $config['rooms'][$room_idx]['window_entity'] = $entities['window_entity'];

            if (file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT))) {
                $response = ['status' => 'success', 'message' => 'Entities updated successfully.'];
            } else {
                $response['message'] = 'Failed to write to config file.';
            }
        } else {
            $response['message'] = 'Room index not found.';
        }
    } else {
        $response['message'] = 'Missing room_idx or entities.';
    }
}

echo json_encode($response);
?>