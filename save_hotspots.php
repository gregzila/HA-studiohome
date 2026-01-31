<?php
header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => 'An unknown error occurred.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawData = file_get_contents('php://input');
    $input = json_decode($rawData, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        $response['message'] = 'Erreur lors du décodage du JSON envoyé : ' . json_last_error_msg();
        http_response_code(400);
        echo json_encode($response);
        exit;
    }

    if (!isset($input['room'])) {
        $response['message'] = 'Donnée "room" manquante dans la requête.';
        http_response_code(400);
        echo json_encode($response);
        exit;
    }

    // Sanitize room name to prevent path traversal
    $roomName = preg_replace('/[^a-zA-Z0-9_\-]/', '', $input['room']);
    if (empty($roomName)) {
        $response['message'] = 'Invalid room name.';
        http_response_code(400);
        echo json_encode($response);
        exit;
    }

    $configFile = 'config_' . strtolower($roomName) . '.json';

    // Check if directory is writable
    if (!is_writable('.')) {
        $response['message'] = "Le serveur PHP n'a pas les droits d'écriture dans le dossier courant (" . getcwd() . "). Assurez-vous que l'utilisateur du serveur web (ex: www-data) a les permissions d'écriture.";
        http_response_code(500);
        echo json_encode($response);
        exit;
    }

    // Check if file exists and is writable, or if it doesn't exist and dir is writable
    if (file_exists($configFile) && !is_writable($configFile)) {
        $response['message'] = "Le fichier de configuration $configFile existe mais n'est pas modifiable (droits d'écriture manquants).";
        http_response_code(500);
        echo json_encode($response);
        exit;
    }

    $hotspots = isset($input['hotspots']) ? $input['hotspots'] : null;
    $zones = isset($input['zones']) ? $input['zones'] : null;

    // Attempt to open/create the file
    $fileHandle = fopen($configFile, 'c+');
    if ($fileHandle && flock($fileHandle, LOCK_EX)) {
        // Clear stat cache to get accurate filesize
        clearstatcache(true, $configFile);
        $filesize = filesize($configFile);

        $currentData = [];
        if ($filesize > 0) {
            $content = fread($fileHandle, $filesize);
            $currentData = json_decode($content, true) ?: [];
        }

        // Update data
        if ($hotspots !== null) {
            $currentData['hotspots'] = $hotspots;
        }
        if ($zones !== null) {
            $currentData['zones'] = $zones;
        }

        $newJsonData = json_encode($currentData, JSON_PRETTY_PRINT);

        // Go to the beginning, truncate and write
        ftruncate($fileHandle, 0);
        rewind($fileHandle);
        
        if (fwrite($fileHandle, $newJsonData)) {
            $response = ['status' => 'success', 'message' => 'Configuration saved successfully.'];
        } else {
            $response['message'] = 'Failed to write to config file.';
            http_response_code(500);
        }
        
        fflush($fileHandle);
        flock($fileHandle, LOCK_UN);
    } else {
        $response['message'] = 'Could not get a lock on the config file or open it.';
        http_response_code(500);
    }
    if ($fileHandle) fclose($fileHandle);

} else {
    $response['message'] = 'Invalid request method.';
    http_response_code(405);
}

echo json_encode($response);
?>