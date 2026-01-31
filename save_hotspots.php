<?php
header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => 'An unknown error occurred.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        $response['message'] = 'Invalid JSON input: ' . json_last_error_msg();
        http_response_code(400);
        echo json_encode($response);
        exit;
    }

    if (!isset($input['room']) || !isset($input['jsonFile'])) {
        $response['message'] = 'Invalid input: room or jsonFile missing.';
        http_response_code(400);
        echo json_encode($response);
        exit;
    }

    $roomName = $input['room'];
    $configFile = $input['jsonFile'];

    // Whitelist check to prevent arbitrary file writing
    if (strpos($configFile, 'config_') !== 0 || strpos($configFile, '.json') === false) {
        if ($configFile !== 'config.json') {
            $response['message'] = "Filename '{$configFile}' is not allowed.";
            http_response_code(400);
            echo json_encode($response);
            exit;
        }
    }

    if (file_exists($configFile) && !is_writable($configFile)) {
        $response['message'] = "File '{$configFile}' exists but is not writable. Check permissions.";
        http_response_code(500);
        echo json_encode($response);
        exit;
    }

    $dataToSave = [
        'room' => $roomName,
        'zones' => $input['zones'] ?? [],
        'hotspots' => $input['hotspots'] ?? []
    ];

    $jsonContent = json_encode($dataToSave, JSON_PRETTY_PRINT);

    $oldMtime = file_exists($configFile) ? filemtime($configFile) : 0;

    if (file_put_contents($configFile, $jsonContent)) {
        clearstatcache();
        $newMtime = filemtime($configFile);

        if ($newMtime > $oldMtime || (file_exists($configFile) && $oldMtime == 0)) {
            $response = ['status' => 'success', 'message' => 'Save OK'];
        } else {
            $response['message'] = "File '{$configFile}' was not updated (mtime did not change).";
            http_response_code(500);
        }
    } else {
        $lastError = error_get_last();
        $response['message'] = "Failed to write to '{$configFile}'. " . ($lastError['message'] ?? '');
        http_response_code(500);
    }

} else {
    $response['message'] = 'Invalid request method: ' . $_SERVER['REQUEST_METHOD'];
    http_response_code(405);
}

echo json_encode($response);
?>