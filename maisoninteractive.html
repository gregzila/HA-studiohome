<?php
session_start();
if (!isset($_SESSION['auth_maison'])) { header("Location: index.php"); exit; }

// --- CONFIGURATION DES PIÈCES ---
$pieces = [
    'salon'   => ['nom' => 'Salon', 'img' => 'Salon.jpg'],
    'cuisine' => ['nom' => 'Cuisine', 'img' => 'Cuisine.jpg'],
    'parents' => ['nom' => 'Chambre Parents', 'img' => 'Chambreparents.jpg'],
    'elisa'   => ['nom' => 'Chambre Elisa', 'img' => 'Elisa.jpg'],
    'justine' => ['nom' => 'Chambre Justine', 'img' => 'Justine.jpg'],
    'sdb'     => ['nom' => 'Salle de Bain', 'img' => 'SDB.jpg']
];

$p_id = $_GET['piece'] ?? 'salon';
$current = $pieces[$p_id];
$config_file = "config_$p_id.json";

// --- SAUVEGARDE / MISE À JOUR ---
if (isset($_POST['save_poly'])) {
    $data = file_exists($config_file) ? json_decode(file_get_contents($config_file), true) : [];
    $new_zone = [
        'name'   => $_POST['name'],
        'entity' => $_POST['entity'],
        'url'    => $_POST['url'], 
        'points' => $_POST['points']
    ];

    if (isset($_POST['edit_id']) && $_POST['edit_id'] !== "") {
        $data[$_POST['edit_id']] = $new_zone;
    } else {
        $data[] = $new_zone;
    }
    file_put_contents($config_file, json_encode($data));
    exit("success");
}

// --- SUPPRESSION ---
if (isset($_POST['delete_id'])) {
    $data = json_decode(file_get_contents($config_file), true);
    array_splice($data, (int)$_POST['delete_id'], 1);
    file_put_contents($config_file, json_encode($data));
    exit("deleted");
}

$hotspots = file_exists($config_file) ? json_decode(file_get_contents($config_file), true) : [];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maison Interactive - <?=$current['nom']?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body { background: #0f0f0f; color: white; font-family: 'Segoe UI', sans-serif; overflow-x: hidden; text-align: center; }
        .nav-pills .nav-link { color: #aaa; background: rgba(255,255,255,0.05); margin: 0 5px; }
        .nav-pills .nav-link.active { background-color: #0d6efd; color: white; }
        .canvas-container { position: relative; display: inline-block; margin-top: 10px; border-radius: 15px; overflow: hidden; box-shadow: 0 10px 40px rgba(0,0,0,0.8); }
        .room-img { display: block; width: 100%; max-width: 1200px; -webkit-user-drag: none; }
        #svg-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; pointer-events: all; }
        .poly-btn { fill: rgba(255, 255, 255, 0.03); stroke: rgba(255, 255, 255, 0.1); stroke-width: 1; cursor: pointer; transition: 0.3s; }
        .poly-btn:hover { fill: rgba(13, 110, 253, 0.3); stroke: #0d6efd; stroke-width: 2; }
        .edit-mode .poly-btn { stroke: #ff4d4d; fill: rgba(255, 77, 77, 0.2); stroke-dasharray: 4; }
        #temp-poly { fill: rgba(255, 193, 7, 0.3); stroke: #ffc107; stroke-width: 2; }
        #ha-modal {
            display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%);
            width: 90%; max-width: 450px; height: 85vh;
            background: rgba(20, 20, 20, 0.85); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 30px; z-index: 3000; overflow: hidden;
            box-shadow: 0 0 50px rgba(0,0,0,0.9);
        }
        #ha-modal iframe { width: 100%; height: 100%; border: none; }
        .close-modal { position: absolute; top: 15px; right: 25px; font-size: 35px; color: white; cursor: pointer; z-index: 3001; opacity: 0.6; transition: 0.3s; }
        .close-modal:hover { opacity: 1; transform: scale(1.1); }
        #modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 2999; }
        .tools { position: fixed; top: 20px; right: 20px; z-index: 1000; background: rgba(0,0,0,0.85); padding: 15px; border-radius: 15px; border: 1px solid #333; width: 180px; }
    </style>
</head>
<body>

<div class="container-fluid p-4">
    <ul class="nav nav-pills justify-content-center mb-4">
        <?php foreach ($pieces as $id => $info): ?>
            <li class="nav-item">
                <a class="nav-link <?=($p_id == $id)?'active':''?>" href="?piece=<?=$id?>"><?=$info['nom']?></a>
            </li>
        <?php endforeach; ?>
    </ul>

    <div class="tools">
        <button class="btn btn-warning w-100 mb-2 fw-bold" onclick="toggleEdit()" id="btn-edit">
            <i class="bi bi-pencil"></i> ÉDITION
        </button>
        <div id="draw-tools" style="display:none;">
            <button class="btn btn-success btn-sm w-100 mb-2 fw-bold" onclick="finishShape()">✅ TERMINER</button>
            <button class="btn btn-outline-danger btn-sm w-100" onclick="resetPoints()">🗑️ ANNULER</button>
        </div>
    </div>

    <div class="canvas-container shadow" id="canvas">
        <img src="<?=$current['img']?>" class="room-img" draggable="false">
        <svg id="svg-overlay" viewBox="0 0 1000 600" preserveAspectRatio="none">
            <?php foreach ($hotspots as $idx => $h): ?>
                <polygon points="<?=$h['points']?>" class="poly-btn" 
                         onclick="handleClick(<?=$idx?>, event)" 
                         data-info='<?= htmlspecialchars(json_encode($h), ENT_QUOTES, 'UTF-8') ?>'></polygon>
            <?php endforeach; ?>
            <polyline id="temp-poly" points=""></polyline>
            <line id="temp-line" x1="0" y1="0" x2="0" y2="0" style="display:none; stroke:#ffc107; stroke-width:2;"></line>
        </svg>
    </div>
</div>

<div id="modal-overlay" onclick="closePopup()"></div>
<div id="ha-modal">
    <span class="close-modal" onclick="closePopup()">&times;</span>
    <iframe id="ha-frame" src=""></iframe>
</div>

<div class="modal fade" id="configModal" data-bs-backdrop="static" style="color: black;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white"><h5>⚙️ Configuration</h5></div>
            <div class="modal-body text-start">
                <input type="hidden" id="edit_id">
                <input type="hidden" id="poly_points">
                <label class="fw-bold">Nom</label>
                <input type="text" id="name" class="form-control mb-3">
                <label class="fw-bold">Entité HA (On/Off simple)</label>
                <input type="text" id="entity" class="form-control mb-3">
                <label class="fw-bold text-primary">URL Sous-vue (Lien Nabu Casa)</label>
                <input type="text" id="url" class="form-control mb-1">
                <small class="text-muted">Si URL remplie, le popup s'ouvrira.</small>
            </div>
            <div class="modal-footer">
                <button class="btn btn-danger me-auto" id="btn-delete" onclick="deleteZone()">Supprimer</button>
                <button class="btn btn-primary fw-bold" onclick="save()">ENREGISTRER</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
let editMode = false, points = [];
const svg = document.getElementById('svg-overlay'), tempPoly = document.getElementById('temp-poly'), tempLine = document.getElementById('temp-line');

function toggleEdit() {
    editMode = !editMode;
    document.body.classList.toggle('edit-mode');
    document.getElementById('draw-tools').style.display = editMode ? 'block' : 'none';
    document.getElementById('btn-edit').className = editMode ? "btn btn-danger w-100 fw-bold" : "btn btn-warning w-100 fw-bold";
    resetPoints();
}

svg.addEventListener('click', (e) => {
    if(!editMode || e.target.classList.contains('poly-btn')) return;
    const rect = svg.getBoundingClientRect();
    const x = (e.clientX - rect.left) / rect.width * 1000, y = (e.clientY - rect.top) / rect.height * 600;
    points.push(`${x},${y}`);
    tempPoly.setAttribute('points', points.join(' '));
    tempLine.setAttribute('x1', x); tempLine.setAttribute('y1', y);
    tempLine.style.display = 'block';
});

svg.addEventListener('mousemove', (e) => {
    if(!editMode || points.length === 0) return;
    const rect = svg.getBoundingClientRect();
    tempLine.setAttribute('x2', (e.clientX - rect.left) / rect.width * 1000);
    tempLine.setAttribute('y2', (e.clientY - rect.top) / rect.height * 600);
});

function handleClick(idx, e) {
    const data = JSON.parse(e.target.getAttribute('data-info'));
    if(editMode) {
        document.getElementById('edit_id').value = idx;
        document.getElementById('name').value = data.name;
        document.getElementById('entity').value = data.entity;
        document.getElementById('url').value = data.url || "";
        document.getElementById('poly_points').value = data.points;
        document.getElementById('btn-delete').style.display = "block";
        new bootstrap.Modal(document.getElementById('configModal')).show();
    } else {
        if (data.url && data.url.trim() !== "") {
            // AJOUT DE L'AUTHENTIFICATION EXTERNE POUR EVITER LE BLOCAGE
            let finalUrl = data.url.trim();
            finalUrl += (finalUrl.includes('?') ? '&' : '?') + "external_auth=1";
            
            document.getElementById('ha-frame').src = finalUrl;
            document.getElementById('ha-modal').style.display = 'block';
            document.getElementById('modal-overlay').style.display = 'block';
        } else {
            fetch(`api_ha.php?action=toggle&entity=${data.entity}`);
        }
    }
}

function finishShape() {
    if(points.length < 3) return;
    document.getElementById('edit_id').value = "";
    document.getElementById('name').value = "";
    document.getElementById('entity').value = "";
    document.getElementById('url').value = "";
    document.getElementById('poly_points').value = points.join(' ');
    document.getElementById('btn-delete').style.display = "none";
    new bootstrap.Modal(document.getElementById('configModal')).show();
}

function save() {
    const fd = new FormData();
    fd.append('save_poly', true);
    fd.append('edit_id', document.getElementById('edit_id').value);
    fd.append('name', document.getElementById('name').value);
    fd.append('entity', document.getElementById('entity').value);
    fd.append('url', document.getElementById('url').value);
    fd.append('points', document.getElementById('poly_points').value);
    fetch(window.location.href, {method:'POST', body:fd}).then(() => location.reload());
}

function deleteZone() {
    if(confirm("Supprimer cette zone ?")) {
        const fd = new FormData();
        fd.append('delete_id', document.getElementById('edit_id').value);
        fetch(window.location.href, {method:'POST', body:fd}).then(() => location.reload());
    }
}

function resetPoints() { points = []; if(tempPoly) tempPoly.setAttribute('points', ''); if(tempLine) tempLine.style.display = 'none'; }
function closePopup() { 
    document.getElementById('ha-modal').style.display = 'none'; 
    document.getElementById('modal-overlay').style.display = 'none'; 
    document.getElementById('ha-frame').src = ''; 
}
</script>
</body>
</html>