<?php
$slidesDir = "slides/";
$parent = isset($_GET['parent']) ? $_GET['parent'] : "photos";
$subdir = isset($_GET['dir']) ? basename($_GET['dir']) : "";
$dir = rtrim($slidesDir . $parent . '/' . $subdir, '/') . '/';

if (!is_dir($dir)) die("Folder tidak ditemukan.");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_FILES['files'])) {
        foreach ($_FILES['files']['tmp_name'] as $i => $tmp) {
            $name = basename($_FILES['files']['name'][$i]);
            $dest = $dir . $name;
            move_uploaded_file($tmp, $dest);
        }
        $message = "File berhasil diupload.";
    }
}

// Ambil semua file (gambar dan video)
$files = array_values(array_filter(scandir($dir), function($file) use ($dir) {
    return is_file($dir . $file) && preg_match('/\.(jpg|jpeg|png|gif|mp4|webm|ogg)$/i', $file);
}));

// Urutkan dengan natural sorting
usort($files, 'strnatcmp');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Iseki Viewer - Upload File <?= htmlspecialchars($subdir) ?></title>
    <link rel="icon" href="/iseki_viewer/logo-iseki.png" type="image/x-icon">
    <link rel="shortcut icon" href="/iseki_viewer/logo-iseki.png" type="image/x-icon">
    <style>
        body { font-family: sans-serif; padding: 20px; background: #f8f9fa; }
        h1 { margin-bottom: 20px; }
        .container { max-width: 900px; margin: auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .message { margin-bottom: 20px; padding: 10px; background: #e9ecef; border-radius: 6px; }
        input[type="file"] { margin-bottom: 10px; }
        button { padding: 10px 20px; margin-right: 10px; border: none; border-radius: 6px; cursor: pointer; }
        .upload-btn { background: #007bff; color: white; }
        .back-btn { background: #6c757d; color: white; text-decoration: none; padding: 10px 15px; border-radius: 6px; display: inline-block; margin-bottom: 20px; }

        .preview-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 30px;
        }
        .preview-grid img, .preview-grid video {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid #ccc;
        }
        .file-item {
            text-align: center;
            font-size: 14px;
            word-break: break-word;
        }
        .file-name {
            margin-top: 5px;
            color: #333;
        }
    </style>
    <script src="/iseki_pro_app/js/dynamic-favicon.js"></script>
    <script>document.addEventListener("DOMContentLoaded", function() { setDynamicFavicon("connected_tv", "Viewer"); });</script>
    <link rel="stylesheet" href="/iseki_pro_app/css/icon.css">
</head>
<body>
    <div class="container">
        <a href="index.php?tab=<?= $parent ?>" class="back-btn">⬅️ Kembali ke Menu Utama</a>
        <h1>Upload File ke Folder: <?= htmlspecialchars($subdir) ?> (<?= ucfirst($parent) ?>)</h1>

        <?php if (!empty($message)): ?>
            <div class="message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <input type="file" name="files[]" multiple required><br>
            <button type="submit" class="upload-btn">📤 Upload</button>
        </form>

        <?php if (count($files) > 0): ?>
            <h2>Preview File:</h2>
            <div class="preview-grid">
                <?php foreach ($files as $file): ?>
                    <div class="file-item">
                        <?php if (preg_match('/\.(mp4|webm|ogg)$/i', $file)): ?>
                            <video src="<?= $dir . $file ?>" muted></video>
                        <?php else: ?>
                            <img src="<?= $dir . $file ?>?v=<?= time() ?>" alt="Preview">
                        <?php endif; ?>
                        <div class="file-name"><?= htmlspecialchars($file) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p style="margin-top: 20px; color: #888;">Tidak ada file dalam folder ini.</p>
        <?php endif; ?>
    </div>
</body>
</html>
