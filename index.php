<?php
$baseURL = 'http://localhost/iseki_viewer/';
$baseDir = 'slides/';

// Tangani permintaan pembuatan folder
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_folder'])) {
    $folderName = trim($_POST['new_folder']);
    
    // Validasi: hanya huruf, angka, spasi, garis bawah, strip
    if (preg_match('/^[\w\- ]{1,50}$/', $folderName)) {
        $newPath = $baseDir . $folderName;
        if (!file_exists($newPath)) {
            mkdir($newPath, 0775, true);
            header("Location: ".$baseURL);
        } else {
            $message = "Folder '$folderName' sudah ada.";
        }
    } else {
        $message = "Nama folder tidak valid.";
    }
}

function deleteFolder($folderPath) {
    if (!is_dir($folderPath)) return;

    $items = scandir($folderPath);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = $folderPath . '/' . $item;
        if (is_dir($path)) {
            deleteFolder($path); // rekursif
        } else {
            unlink($path); // hapus file
        }
    }
    rmdir($folderPath); // hapus folder
}

// Tangani request hapus folder
if (isset($_GET['delete']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $folderToDelete = basename($_GET['delete']); // cegah path traversal
    $pathToDelete = $baseDir . $folderToDelete;
    if (is_dir($pathToDelete)) {
        deleteFolder($pathToDelete);
        header("Location: ".$baseURL);
    } else {
        $message = "Folder tidak ditemukan.";
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
        body { font-family: Arial; padding: 20px; }
        h2 { margin-bottom: 20px; }
        .folder {
            padding: 10px;
            border: 1px solid #ccc;
            margin-bottom: 10px;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .folder:hover {
            background-color: #f0f0f0;
        }
        .play-btn {
            background-color: green;
            color: white;
            border: none;
            padding: 5px 10px;
            cursor: pointer;
        }
    </style>

    <!-- Dynamic Favicon -->
    <script src="/iseki_pro_app/js/dynamic-favicon.js"></script>
    <script>document.addEventListener("DOMContentLoaded", function() { setDynamicFavicon("connected_tv", "Viewer"); });</script>

    <!-- Dynamic Favicon Assets -->
    <link rel="stylesheet" href="/iseki_pro_app/css/icon.css">
    <script src="/iseki_pro_app/js/dynamic-favicon.js"></script>
    <script>document.addEventListener("DOMContentLoaded", function() { setDynamicFavicon("connected_tv", "Viewer"); });</script>
</head>
<body>
<!-- Form Buat Folder Baru -->
<form method="POST" style="margin: 20px; display: flex; gap: 10px; align-items: center;">
    <input type="text" name="new_folder" placeholder="Nama folder baru" required
           pattern="[\w\- ]{1,50}" title="Hanya huruf, angka, -, _, dan spasi" style="padding: 8px;">
    <button type="submit" style="padding: 8px 12px;">➕ Buat Folder</button>
    Hanya huruf, angka, -, _, dan spasi
</form>

<?php if (isset($message)): ?>
    <p style="margin-left: 20px; color: white; background: #333; display: inline-block; padding: 10px; border-radius: 8px;">
        <?= htmlspecialchars($message) ?>
    </p>
<?php endif; ?>

<h2>📁 Daftar Folder</h2>

<?php
$folders = array_filter(scandir($baseDir), function($item) use ($baseDir) {
    return is_dir($baseDir . $item) && !in_array($item, ['.', '..']);
});

foreach ($folders as $folder) {
    echo "<div style='margin: 10px; background: #f4f4f4; padding: 10px; border-radius: 6px; display: flex; align-items: center; gap: 10px;'>";

    // Nama folder
    echo "<strong style='margin-right: auto;'>$folder</strong>";

    // Tombol Play
    echo "<a href='viewer.php?dir=" . urlencode($folder) . "' style='text-decoration: none; padding: 6px 10px; background: #28a745; color: white; border-radius: 4px;'>▶ Play</a>";

    // Tombol Upload
    echo "<a href='upload.php?dir=" . urlencode($folder) . "' style='text-decoration: none; padding: 6px 10px; background: #007bff; color: white; border-radius: 4px;'>📤 Upload</a>";

    // Tombol Hapus (pakai form POST agar aman)
    echo "
        <form method='POST' action='?delete=" . urlencode($folder) . "' onsubmit='return confirm(\"Yakin ingin menghapus folder ini?\")'>
            <button type='submit' style='padding: 6px 10px; background: #dc3545; color: white; border: none; border-radius: 4px;'>🗑 Hapus</button>
        </form>
    ";
    echo "</div>";
}
?>

<script>
function goToViewer(folder) {
    window.location.href = 'viewer.php?dir=' + encodeURIComponent(folder);
}
</script>

</body>
</html>
