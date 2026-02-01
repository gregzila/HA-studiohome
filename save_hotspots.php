<?php
// Script de sauvegarde locale pour Mon Intra
// Permet de contourner la limite de 255 caractères de Home Assistant

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !isset($data['file']) || !isset($data['content'])) {
    http_response_code(400);
    exit(json_encode(["error" => "Données invalides"]));
}

$filename = basename($data['file']); // Sécurité : on garde juste le nom du fichier

// Protection : Autoriser uniquement l'écriture des fichiers de configuration spécifiques
// pour éviter d'écraser config.json ou d'autres fichiers sensibles.
if (!str_starts_with($filename, 'config_') || !str_ends_with($filename, '.json')) {
    http_response_code(403);
    exit(json_encode(["error" => "Écriture interdite pour ce fichier (autorisé: config_*.json uniquement)"]));
}

$content = json_encode($data['content'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

if ($content === false) {
    http_response_code(500);
    exit(json_encode(["error" => "Erreur d'encodage JSON : " . json_last_error_msg()]));
}

// Vérifier si le fichier existe et s'il est accessible en écriture
if (file_exists($filename) && !is_writable($filename)) {
    http_response_code(500);
    exit(json_encode(["error" => "Le fichier $filename existe mais n'est pas modifiable. Vérifiez les permissions CHMOD."]));
}

// On vérifie le dossier si le fichier n'existe pas
if (!file_exists($filename) && !is_writable('.')) {
    http_response_code(500);
    exit(json_encode(["error" => "Impossible de créer $filename. Le dossier n'est pas modifiable par PHP."]));
}

if (file_put_contents($filename, $content) !== false) {
    echo json_encode(["success" => true, "message" => "Fichier $filename sauvegardé avec succès"]);
} else {
    $lastError = error_get_last();
    http_response_code(500);
    echo json_encode(["error" => "Erreur système lors de l'écriture : " . ($lastError['message'] ?? 'inconnue')]);
}
