<?php
// 1. CONFIGURATION DES COOKIES
session_set_cookie_params(['samesite' => 'None', 'secure' => true, 'httponly' => true]);
session_start();

// 2. CONFIGURATION GÉNÉRALE (PLEX ET HA)
$config = json_decode(file_get_contents('config.json'), true);
$password_valide = $config['dashboard_password'];
$plex_ip = $config['plex_ip'];
$plex_port = $config['plex_port'];
$plex_token = $config['plex_token'];
$mon_server_id = $config['plex_server_id'];

// --- CONFIGURATION HOME ASSISTANT ---
$ha_token = $config['ha_token'];
$ha_url = $config['ha_url'];

function getHaState($entity_id, $url, $token) {
    $ch = curl_init($url . "/api/states/" . $entity_id);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token, 'Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 2);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}

// 3. Gestion de la déconnexion
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}

// 4. Authentification
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['pass'])) {
    if (trim($_POST['pass']) === $password_valide) {
        $_SESSION['auth_maison'] = true;
        header("Location: index.php");
        exit;
    }
}

// 5. RÉCUPÉRATION DES DONNÉES
$films = []; $series = []; $divers = [];
$lights_states = [];
$thermostats = [];
$temp_ext = "--";
$temp_int = "--";
$eve_status = "Inconnu";
$eve_battery = 0;
$scapin_status = "Inconnu";
$scapin_batt = 0;

if (isset($_SESSION['auth_maison']) && $_SESSION['auth_maison'] === true) {
    
    // Météo et Températures
    $ha_temp_ext = getHaState('sensor.temperature_a_izon', $ha_url, $ha_token);
    $temp_ext = $ha_temp_ext['state'] ?? '--';
    $ha_temp_int = getHaState('sensor.moyenne_temperature_maison', $ha_url, $ha_token);
    $temp_int = $ha_temp_int['state'] ?? '--';

    // --- EVE ASPIRATEUR ---
    $ha_eve = getHaState('vacuum.eve', $ha_url, $ha_token);
    $eve_status = $ha_eve['state'] ?? 'Inconnu';
    $ha_eve_batt = getHaState('sensor.eve_battery_level', $ha_url, $ha_token);
    $eve_battery = $ha_eve_batt['state'] ?? 0;

    // --- SCAPIN ASPIRATEUR ---
    $ha_scapin = getHaState('vacuum.rockrobo_vacuum_v1', $ha_url, $ha_token);
    $scapin_status = $ha_scapin['state'] ?? 'Inconnu';
    $scapin_batt = $ha_scapin['attributes']['battery_level'] ?? 0;

    // Lumières
    $lum_entities = [
        'switch.0x048727fffe7b5724' => 'Bureau',
        'light.lampe_salon' => 'Salon',
        'light.lampe_chevet_chambre_parentale' => 'Chevet',
        'light.lumiere_cuisine' => 'Cuisine',
        'light.lumiere_terrasse' => 'Terrasse',
        'light.0xa81710b7b7e50000' => 'Toilette'
    ];
    foreach($lum_entities as $id => $nom) {
        $st = getHaState($id, $ha_url, $ha_token);
        $lights_states[$id] = ['state' => ($st['state'] ?? 'off'), 'name' => $nom];
    }

    // Thermostats
    $climates = [
        'parents' => ['id' => 'climate.thermostat_chambre_parents_2', 'name' => 'Parents', 'img' => 'Chambreparents.jpg'],
        'elisa' => ['id' => 'climate.thermostat_chambre_elisa_2', 'name' => 'Elisa', 'img' => 'Elisa.jpg'],
        'justine' => ['id' => 'climate.thermostat_bureau', 'name' => 'Justine', 'img' => 'Justine.jpg'],
        'sdb' => ['id' => 'climate.thermostat_salle_de_bain_2', 'name' => 'Sdb', 'img' => 'SDB.jpg']
    ];
    foreach($climates as $key => $c) {
        $st = getHaState($c['id'], $ha_url, $ha_token);
        $thermostats[$key] = [
            'name' => $c['name'], 'img' => $c['img'],
            'current' => $st['attributes']['current_temperature'] ?? '--',
            'target' => $st['attributes']['temperature'] ?? '--',
            'state' => $st['state'] ?? 'off'
        ];
    }

    // --- PLEX PERSO ---
    $url_f = "http://$plex_ip:$plex_port/library/sections/1/recentlyAdded?X-Plex-Token=$plex_token&X-Plex-Container-Size=8";
    $data_f = @simplexml_load_file($url_f);
    if ($data_f) { foreach (($data_f->Video ?? $data_f->Directory ?? []) as $v) { $films[] = ['title' => (string)$v['title'], 'thumb' => (string)$v['thumb'], 'key' => (string)$v['ratingKey']]; } }

    $url_s = "http://$plex_ip:$plex_port/library/recentlyAdded?X-Plex-Token=$plex_token&X-Plex-Container-Size=8&type=2";
    $data_s = @simplexml_load_file($url_s);
    if ($data_s && isset($data_s->Directory)) { foreach ($data_s->Directory as $s) { $series[] = ['title' => (string)$s['title'], 'thumb' => (string)$s['thumb'], 'key' => (string)$s['ratingKey']]; } }

    $url_d = "http://$plex_ip:$plex_port/library/sections/6/recentlyAdded?X-Plex-Token=$plex_token&X-Plex-Container-Size=8";
    $data_d = @simplexml_load_file($url_d);
    if ($data_d) { foreach (($data_d->Video ?? $data_d->Directory ?? []) as $d) { $divers[] = ['title' => (string)$d['title'], 'thumb' => (string)$d['thumb'], 'key' => (string)$d['ratingKey']]; } }
}

if (!isset($_SESSION['auth_maison'])) : ?>
<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"><style>body{background:#1a1a1a;height:100vh;display:flex;align-items:center;justify-content:center;color:white;text-align:center;}.login-box{background:#2d2d2d;padding:40px;border-radius:20px;width:350px;}.form-control{background:#3d3d3d;border:none;color:white;text-align:center;margin-bottom:20px;}</style></head><body><div class="login-box"><h3>Maison Connectée</h3><form method="POST"><input type="password" name="pass" class="form-control form-control-lg" placeholder="Mot de passe" required autofocus><button type="submit" class="btn btn-primary w-100">Se connecter</button></form></div></body></html>
<?php exit; endif; ?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Intranet Maison</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body { background-color: #f4f7f6; font-family: 'Segoe UI', sans-serif; }
        .card { border: none; border-radius: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin-bottom: 25px; }
        .sidebar { background: #2c3e50; min-height: 100vh; color: white; padding-top: 20px; position: fixed; width: 16.66%; }
        .sidebar a { color: white; text-decoration: none; display: block; padding: 10px 20px; }
        .sidebar a:hover { background: #34495e; border-radius: 5px; }
        .scroll-x { overflow-x: auto; white-space: nowrap; padding-bottom: 15px; display: flex; gap: 15px; }
        .movie-card { width: 130px; text-align: center; flex: 0 0 auto; text-decoration: none !important; color: inherit; }
        .movie-card img { width: 100%; height: 180px; object-fit: cover; border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.2); transition: 0.2s; }
        .movie-card:hover img { transform: scale(1.05); }
        .section-title { font-weight: bold; border-left: 5px solid #0d6efd; padding-left: 12px; margin-bottom: 20px; }
        .ha-weather-card { background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); border-radius: 20px; padding: 15px; color: white; }
        .light-badge { display: flex; flex-direction: column; align-items: center; width: 85px; }
        .light-btn { width: 45px; height: 45px; border-radius: 12px; border: none; display: flex; align-items: center; justify-content: center; transition: 0.3s; }
        .light-on { background: #ffc107; color: #fff; }
        .light-off { background: #e9ecef; color: #6c757d; }
        .thermostat-card { width: 160px; height: 100px; border-radius: 15px; background-size: cover; background-position: center; position: relative; overflow: hidden; color: white; flex: 0 0 auto; }
        .thermostat-overlay { position: absolute; inset:0; background: rgba(0,0,0,0.4); padding: 10px; display: flex; flex-direction: column; justify-content: space-between; }
        .upload-zone { background: #fff5f5; border: 2px dashed #dc3545; border-radius: 15px; }
        .eve-card { background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); color: white; }
        .scapin-card { background: linear-gradient(135deg, #2c3e50 0%, #000000 100%); color: white; }
        .eve-btn { background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); color: white; border-radius: 10px; transition: 0.3s; padding: 10px; text-align: center; font-size: 0.8rem; min-width: 80px; }
        .eve-btn:hover { background: rgba(255,255,255,0.3); transform: translateY(-2px); }
        .eve-btn i { font-size: 1.2rem; display: block; margin-bottom: 5px; }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-2 d-none d-md-block sidebar">
            <h4 class="text-center mb-4">🏠 Mon Intra</h4>
            <a href="#"><i class="bi bi-house-door"></i> Accueil</a>
            <a href="#robots_zone"><i class="bi bi-robot"></i> Aspirateurs</a>
            <a href="#domotique"><i class="bi bi-lightning-charge"></i> Lumières</a>
            <a href="#thermostats_zone"><i class="bi bi-thermometer-half"></i> Chauffage</a>
            <a href="#upload_zone"><i class="bi bi-cloud-arrow-up"></i> Upload</a>
            <a href="#films_zone"><i class="bi bi-film"></i> Plex</a>
            <hr>
            <div class="px-3"><a href="?logout=1" class="btn btn-sm btn-outline-warning w-100">Déconnexion</a></div>
        </nav>

        <main class="col-md-10 offset-md-2 p-4">
            <div class="d-flex justify-content-between align-items-start mb-4 border-bottom pb-3">
                <div><h1>Bonjour la famille !</h1><p class="text-muted">Ravi de vous voir.</p></div>
                <div class="ha-weather-card text-center" style="min-width: 250px;">
                    <div class="d-flex justify-content-around align-items-center">
                        <div class="text-start"><h2 id="live-clock" class="mb-0 fw-bold">--:--</h2><div class="small opacity-75">Izon</div></div>
                        <div class="text-end"><div class="fs-3 fw-bold"><?php echo $temp_ext; ?>°C</div><div class="small">Maison: <?php echo $temp_int; ?>°C</div></div>
                    </div>
                </div>
            </div>

            <div id="robots_zone" class="row">
                <div class="col-md-6">
                    <div class="card p-4 eve-card shadow-lg mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0 fw-bold"><i class="bi bi-robot"></i> EVE (Aspirateur & Laveur)</h5>
                            <span class="badge <?php echo ($eve_battery > 20) ? 'bg-success' : 'bg-danger'; ?>">
                                <i class="bi bi-battery-full"></i> <?php echo $eve_battery; ?>%
                            </span>
                        </div>
                        <div class="mb-3">
                            <small class="opacity-75">Statut :</small> <span class="fw-bold text-capitalize"><?php echo $eve_status; ?></span>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <button onclick="callHA('vacuum.eve', 'return_to_base')" class="btn btn-sm btn-outline-light"><i class="bi bi-house"></i> Base</button>
                            <button onclick="callHA('script.laver_toutes_les_chambres', 'turn_on')" class="eve-btn"><i class="bi bi-stars"></i>Toutes</button>
                            <button onclick="callHA('button.eve_shortcut_1', 'press')" class="eve-btn"><i class="bi bi-bed"></i>Parents</button>
                            <button onclick="callHA('button.eve_shortcut_2', 'press')" class="eve-btn"><i class="bi bi-person"></i>Elisa</button>
                            <button onclick="callHA('button.eve_shortcut_3', 'press')" class="eve-btn"><i class="bi bi-baby"></i>Justine</button>
                            <button onclick="callHA('button.eve_shortcut_6', 'press')" class="eve-btn"><i class="bi bi-tv"></i>Salon</button>
                            <button onclick="callHA('button.eve_shortcut_5', 'press')" class="eve-btn"><i class="bi bi-egg-fried"></i>Cuisine</button>
                            <button onclick="callHA('button.eve_shortcut_4', 'press')" class="eve-btn text-warning fw-bold"><i class="bi bi-droplet-fill"></i>Entrée/SdB</button>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card p-4 scapin-card shadow-lg mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0 fw-bold"><i class="bi bi-robot"></i> SCAPIN (Aspirateur & Zones)</h5>
                            <span class="badge <?php echo ($scapin_batt > 20) ? 'bg-info' : 'bg-danger'; ?>">
                                <i class="bi bi-battery-full"></i> <?php echo $scapin_batt; ?>%
                            </span>
                        </div>
                        <div class="mb-3">
                            <small class="opacity-75">Statut :</small> <span class="fw-bold text-capitalize"><?php echo $scapin_status; ?></span>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <button onclick="callHA('vacuum.rockrobo_vacuum_v1', 'return_to_base')" class="btn btn-sm btn-outline-light"><i class="bi bi-house"></i> Base</button>
                            <button onclick="sendVacuumSegment('1')" class="eve-btn"><i class="bi bi-tree"></i>Terrasse</button>
                            <button onclick="sendVacuumSegment('11')" class="eve-btn"><i class="bi bi-egg-fried"></i>Cuisine</button>
                            <button onclick="sendVacuumSegment('15')" class="eve-btn"><i class="bi bi-tv"></i>Salon</button>
                            <button onclick="sendVacuumSegment('12')" class="eve-btn"><i class="bi bi-bed"></i>Parents</button>
                            <button onclick="sendVacuumSegment('9')" class="eve-btn"><i class="bi bi-person"></i>Elisa</button>
                            <button onclick="sendVacuumSegment('2')" class="eve-btn"><i class="bi bi-baby"></i>Justine</button>
                            <button onclick="sendVacuumSegment('10')" class="eve-btn"><i class="bi bi-droplet"></i>SdB</button>
                        </div>
                    </div>
                </div>
            </div>

            <div id="thermostats_zone" class="mb-4">
                <h5 class="section-title"><i class="bi bi-thermometer-half"></i> Chauffage</h5>
                <div class="scroll-x">
                    <?php foreach($thermostats as $t): ?>
                    <div class="thermostat-card" style="background-image: url('./<?php echo $t['img']; ?>');">
                        <div class="thermostat-overlay">
                            <div class="fw-bold"><?php echo $t['name']; ?></div>
                            <div><span class="fs-5 fw-bold"><?php echo $t['current']; ?>°</span> <small>Cible: <?php echo $t['target']; ?>°</small></div>
                            <?php if($t['state'] == 'heat'): ?><i class="bi bi-fire text-danger"></i><?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div id="domotique" class="card p-4">
                <h5 class="section-title"><i class="bi bi-lightning-charge"></i> Lumières</h5>
                <div class="d-flex flex-wrap gap-3">
                    <?php foreach($lights_states as $id => $info): ?>
                    <div class="light-badge">
                        <small class="fw-bold mb-1"><?php echo $info['name']; ?></small>
                        <button id="btn-<?php echo str_replace('.', '-', $id); ?>" onclick="toggleLight('<?php echo $id; ?>', this)" 
                                class="light-btn <?php echo ($info['state'] == 'on') ? 'light-on' : 'light-off'; ?>">
                            <i class="bi bi-lightbulb"></i>
                        </button>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div id="upload_zone" class="card p-4 upload-zone">
                <h5 class="section-title text-danger"><i class="bi bi-cloud-arrow-up"></i> Ajouter à Plex</h5>
                <form id="uploadForm" enctype="multipart/form-data" class="row g-3">
                    <div class="col-md-4"><input type="file" name="fileToUpload" id="fileInput" class="form-control" required></div>
                    <div class="col-md-6">
                        <select name="targetDir" class="form-select" required>
                            <option value="films">📁 Dossier Films</option>
                            <option value="series">📁 Dossier Séries</option>
                            <option value="films_elisa">📁 Dossier Films Elisa</option>
                            <option value="series_elisa">📁 Dossier Séries Elisa</option>
                            <option value="divers">📁 Divers Vidéo</option>
                        </select>
                    </div>
                    <div class="col-md-2"><button type="submit" id="btnUpload" class="btn btn-danger w-100">Uploader</button></div>
                </form>
                <div id="progressContainer" class="progress mt-3" style="display:none; height:25px;"><div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-danger" role="progressbar" style="width: 0%">0%</div></div>
                <div id="uploadStatus" class="mt-2 fw-bold text-center"></div>
            </div>

            <div id="films_zone" class="card p-4">
                <h5 class="section-title text-primary"><i class="bi bi-film"></i> Films récents</h5>
                <div class="scroll-x">
                    <?php foreach ($films as $f): ?>
                        <a href="https://app.plex.tv/desktop/#!/server/<?php echo $mon_server_id; ?>/details?key=%2Flibrary%2Fmetadata%2F<?php echo $f['key']; ?>" target="_blank" class="movie-card">
                            <img src="image_proxy.php?url=<?php echo urlencode("http://$plex_ip:$plex_port" . $f['thumb']); ?>&token=<?php echo $plex_token; ?>">
                            <div class="small fw-bold text-truncate mt-2"><?php echo $f['title']; ?></div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <div id="series_zone" class="card p-4">
                <h5 class="section-title text-success"><i class="bi bi-tv"></i> Séries récentes</h5>
                <div class="scroll-x">
                    <?php foreach ($series as $s): ?>
                        <a href="https://app.plex.tv/desktop/#!/server/<?php echo $mon_server_id; ?>/details?key=%2Flibrary%2Fmetadata%2F<?php echo $s['key']; ?>" target="_blank" class="movie-card">
                            <img src="image_proxy.php?url=<?php echo urlencode("http://$plex_ip:$plex_port" . $s['thumb']); ?>&token=<?php echo $plex_token; ?>">
                            <div class="small fw-bold text-truncate mt-2"><?php echo $s['title']; ?></div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

        </main>
    </div>
</div>

<script>
function updateClock() {
    const now = new Date();
    document.getElementById('live-clock').textContent = now.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
}
setInterval(updateClock, 1000); updateClock();

function callHA(entity, action) {
    fetch(`api_ha.php?action=${action}&entity=${entity}`)
    .then(() => { if(action === 'press' || action === 'turn_on') alert('Commande envoyée !'); });
}

function sendVacuumSegment(segmentId) {
    fetch(`api_ha.php?action=segment&entity=vacuum.rockrobo_vacuum_v1&segment=${segmentId}`)
    .then(() => alert('Scapin démarre le nettoyage !'));
}

function toggleLight(entity, btn) {
    fetch(`api_ha.php?action=toggle&entity=${entity}`)
    .then(() => {
        if (btn.classList.contains('light-on')) { btn.classList.replace('light-on', 'light-off'); } 
        else { btn.classList.replace('light-off', 'light-on'); }
    });
}

document.getElementById('uploadForm').onsubmit = function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const xhr = new XMLHttpRequest();
    const pContainer = document.getElementById('progressContainer');
    const pBar = document.getElementById('progressBar');
    const status = document.getElementById('uploadStatus');
    const btn = document.getElementById('btnUpload');

    pContainer.style.display = 'flex'; btn.disabled = true; status.innerHTML = "Transfert...";
    xhr.upload.onprogress = function(e) { if (e.lengthComputable) { const percent = Math.round((e.loaded / e.total) * 100); pBar.style.width = percent + '%'; pBar.innerHTML = percent + '%'; } };
    xhr.onload = function() { if (xhr.status === 200 && xhr.responseText.trim() === "OK") { status.innerHTML = "✅ Terminé !"; setTimeout(function(){ window.location.reload(); }, 2000); } else { status.innerHTML = "❌ Erreur: " + xhr.responseText; btn.disabled = false; } };
    xhr.open('POST', 'upload.php', true);
    xhr.send(formData);
};
</script>
</body>
</html>