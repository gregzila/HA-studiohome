<?php
session_start();
if (!isset($_SESSION['auth_maison'])) {
    http_response_code(403);
    exit(json_encode(["error" => "Non autorisé"]));
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !isset($data['file']) || !isset($data['content'])) {
    http_response_code(400);
    exit(json_encode(["error" => "Données invalides"]));
}

$filename = basename($data['file']); // Sécurité : on garde juste le nom du fichier
if (!str_ends_with($filename, '.json')) {
    http_response_code(400);
    exit(json_encode(["error" => "Format de fichier invalide"]));
}

$content = json_encode($data['content'], JSON_PRETTY_PRINT);

if (file_put_contents($filename, $content) !== false) {
    echo json_encode(["success" => true, "message" => "Fichier $filename sauvegardé"]);
} else {
    http_response_code(500);
    echo json_encode(["error" => "Impossible d'écrire dans le fichier"]);
}
