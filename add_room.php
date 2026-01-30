<?php
header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => 'An unknown error occurred.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $icon = isset($_POST['icon']) ? trim($_POST['icon']) : '';
    $temp = isset($_POST['temp']) ? trim($_POST['temp']) : '';

    if (empty($name) || empty($icon)) {
        $response['message'] = 'Room name and icon are required.';
        echo json_encode($response);
        exit;
    }

    $uploadDir = './'; // Upload to the root directory
    $img2dPath = '';
    $img360Path = '';

    if (isset($_FILES['img2d']) && $_FILES['img2d']['error'] === UPLOAD_ERR_OK) {
        $img2dPath = $uploadDir . basename($_FILES['img2d']['name']);
        move_uploaded_file($_FILES['img2d']['tmp_name'], $img2dPath);
    } else {
        $response['message'] = '2D image is required.';
        echo json_encode($response);
        exit;
    }

    if (isset($_FILES['img360']) && $_FILES['img360']['error'] === UPLOAD_ERR_OK) {
        $img360Path = $uploadDir . basename($_FILES['img360']['name']);
        move_uploaded_file($_FILES['img360']['tmp_name'], $img360Path);
    } else {
        $response['message'] = '360 image is required.';
        echo json_encode($response);
        exit;
    }

    $configFile = 'config.json';
    $config = json_decode(file_get_contents($configFile), true);

    $newRoom = [
        'n' => $name,
        'icon' => $icon,
        'img' => basename($img2dPath),
        'img360' => basename($img360Path),
        'temp' => $temp
    ];

    $config['rooms'][] = $newRoom;

    if (file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT))) {
        // Also create the specific config file for the new room's hotspots
        $roomConfigFile = 'config_' . strtolower($name) . '.json';
        if (!file_exists($roomConfigFile)) {
            file_put_contents($roomConfigFile, json_encode(['hotspots' => []], JSON_PRETTY_PRINT));
        }
        $response = ['status' => 'success', 'message' => 'Room added successfully.'];
    } else {
        $response['message'] = 'Failed to update config file.';
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
?>