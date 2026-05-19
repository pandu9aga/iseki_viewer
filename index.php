<?php
$baseURL = 'http://localhost/iseki_viewer/'; // 192.168.173.201
$slidesDir = 'slides/';
$photosDir = $slidesDir.'photos/';
$videosDir = $slidesDir.'videos/';

// Pastikan direktori utama ada
if (! is_dir($photosDir)) {
    mkdir($photosDir, 0775, true);
}
if (! is_dir($videosDir)) {
    mkdir($videosDir, 0775, true);
}

$currentTab = isset($_GET['tab']) ? $_GET['tab'] : 'photos';
$activeDir = ($currentTab === 'videos') ? $videosDir : $photosDir;

// Tangani permintaan pembuatan folder
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_folder'])) {
    $folderName = trim($_POST['new_folder']);
    $tab = isset($_POST['tab']) ? $_POST['tab'] : 'photos';
    $targetDir = ($tab === 'videos') ? $videosDir : $photosDir;

    if (preg_match('/^[\w\- ]{1,50}$/', $folderName)) {
        $newPath = $targetDir.$folderName;
        if (! file_exists($newPath)) {
            mkdir($newPath, 0775, true);
            header('Location: '.$baseURL.'?tab='.$tab);
            exit;
        } else {
            $message = "Folder '$folderName' sudah ada di tab ini.";
        }
    } else {
        $message = 'Nama folder tidak valid.';
    }
}

function deleteFolder($folderPath)
{
    if (! is_dir($folderPath)) {
        return;
    }
    $items = scandir($folderPath);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        $path = $folderPath.'/'.$item;
        if (is_dir($path)) {
            deleteFolder($path);
        } else {
            unlink($path);
        }
    }
    rmdir($folderPath);
}

// Tangani request hapus folder
if (isset($_GET['delete']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $folderToDelete = basename($_GET['delete']);
    $tab = isset($_GET['tab']) ? $_GET['tab'] : 'photos';
    $targetDir = ($tab === 'videos') ? $videosDir : $photosDir;
    $pathToDelete = $targetDir.$folderToDelete;

    if (is_dir($pathToDelete)) {
        deleteFolder($pathToDelete);
        header('Location: '.$baseURL.'?tab='.$tab);
        exit;
    } else {
        $message = 'Folder tidak ditemukan.';
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Iseki Viewer - Daftar Folder</title>
    <link rel="icon" href="/iseki_viewer/logo-iseki.png" type="image/x-icon">
    <link rel="shortcut icon" href="/iseki_viewer/logo-iseki.png" type="image/x-icon">
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background-color: #f8f9fa; }
        h2 { margin-bottom: 20px; color: #333; }
        .folder {
            padding: 15px;
            background: white;
            border-radius: 8px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .folder:hover { background-color: #f0f0f0; }
        .play-btn { background-color: #28a745; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer; text-decoration: none; }
        .upload-btn { background-color: #007bff; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer; text-decoration: none; }
        .delete-btn { background-color: #dc3545; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer; }
        .tabs { display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 2px solid #dee2e6; padding-bottom: 10px; }
        .tab-btn { padding: 10px 20px; border: none; background: none; cursor: pointer; font-size: 16px; font-weight: bold; color: #6c757d; border-radius: 6px; }
        .tab-btn.active { background-color: #e91e63; color: white; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
    </style>
    <script src="/iseki_pro_app/js/dynamic-favicon.js"></script>
    <script>document.addEventListener("DOMContentLoaded", function() { setDynamicFavicon("connected_tv", "Viewer"); });</script>
    <link rel="stylesheet" href="/iseki_pro_app/css/icon.css">
</head>
<body>
<form method="POST" style="margin: 20px 0; display: flex; gap: 10px; align-items: center;">
    <input type="hidden" name="tab" id="currentTabInput" value="<?= htmlspecialchars($currentTab) ?>">
    <input type="text" name="new_folder" placeholder="Nama folder baru" required
           pattern="[\w\- ]{1,50}" title="Hanya huruf, angka, -, _, dan spasi" style="padding: 10px; border: 1px solid #ccc; border-radius: 6px; width: 250px;">
    <button type="submit" style="padding: 10px 20px; background: #333; color: white; border: none; border-radius: 6px; cursor: pointer;">➕ Buat Folder</button>
</form>

<?php if (isset($message)) { ?>
    <p style="margin-bottom: 20px; color: white; background: #e91e63; display: inline-block; padding: 10px 20px; border-radius: 8px;">
        <?= htmlspecialchars($message) ?>
    </p>
<?php } ?>

<div class="tabs">
    <button class="tab-btn <?= $currentTab === 'photos' ? 'active' : '' ?>" onclick="openTab('photos')">🖼 Photos</button>
    <button class="tab-btn <?= $currentTab === 'videos' ? 'active' : '' ?>" onclick="openTab('videos')">🎬 Videos</button>
</div>

<div id="photos" class="tab-content <?= $currentTab === 'photos' ? 'active' : '' ?>">
    <h2>📁 Photo Folders</h2>
    <?php
    $folders = array_filter(scandir($photosDir), function ($item) use ($photosDir) {
        return is_dir($photosDir.$item) && ! in_array($item, ['.', '..']);
    });
foreach ($folders as $folder) {
    echo "<div class='folder'><strong>$folder</strong><div style='display: flex; gap: 10px;'>";
    echo "<a href='viewer.php?parent=photos&dir=".urlencode($folder)."' class='play-btn'>▶ Play</a>";
    echo "<a href='upload.php?parent=photos&dir=".urlencode($folder)."' class='upload-btn'>📤 Upload</a>";
    echo "<form method='POST' action='?delete=".urlencode($folder)."&tab=photos' onsubmit='return confirm(\"Yakin?\")' style='margin:0;'><button type='submit' class='delete-btn'>🗑 Hapus</button></form>";
    echo '</div></div>';
}
?>
</div>

<div id="videos" class="tab-content <?= $currentTab === 'videos' ? 'active' : '' ?>">
    <h2>📁 Video Folders</h2>
    <?php
$folders = array_filter(scandir($videosDir), function ($item) use ($videosDir) {
    return is_dir($videosDir.$item) && ! in_array($item, ['.', '..']);
});
foreach ($folders as $folder) {
    echo "<div class='folder'><strong>$folder</strong><div style='display: flex; gap: 10px;'>";
    echo "<a href='viewer.php?parent=videos&dir=".urlencode($folder)."' class='play-btn'>▶ Play</a>";
    echo "<a href='upload.php?parent=videos&dir=".urlencode($folder)."' class='upload-btn'>📤 Upload</a>";
    echo "<form method='POST' action='?delete=".urlencode($folder)."&tab=videos' onsubmit='return confirm(\"Yakin?\")' style='margin:0;'><button type='submit' class='delete-btn'>🗑 Hapus</button></form>";
    echo '</div></div>';
}
?>
</div>

<script>
function openTab(tabId) {
    document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    document.getElementById(tabId).classList.add('active');
    event.currentTarget.classList.add('active');
    document.getElementById('currentTabInput').value = tabId;
    const url = new URL(window.location);
    url.searchParams.set('tab', tabId);
    window.history.pushState({}, '', url);
}
</script>
</body>
</html>
