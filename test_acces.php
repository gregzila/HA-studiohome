<?php
$dossier = "//192.168.1.52/media/";
if (is_writable($dossier)) {
    echo "✅ PHP peut écrire dans le dossier !";
} else {
    echo "❌ PHP ne peut toujours pas écrire dans $dossier";
}
?>