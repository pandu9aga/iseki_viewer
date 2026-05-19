<?php
$slidesDir = "slides/";
$parent = isset($_GET['parent']) ? $_GET['parent'] : "photos";
$subdir = isset($_GET['dir']) ? basename($_GET['dir']) : "";
$dir = rtrim($slidesDir . $parent . '/' . $subdir, '/') . '/';

if (!is_dir($dir)) die("Folder tidak ditemukan.");

// Validasi tipe file
if ($parent === 'videos') {
    $allowedRegex = '/\.(mp4|webm|ogg)$/i';
    $acceptAttr = 'video/*';
    $allowedText = 'Video (MP4, WebM, OGG)';
} else {
    $allowedRegex = '/\.(jpg|jpeg|png|gif)$/i';
    $acceptAttr = 'image/*';
    $allowedText = 'Foto (JPG, JPEG, PNG, GIF)';
}

$message = "";
$messageType = "info";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_FILES['files'])) {
        $uploadedCount = 0;
        $rejectedCount = 0;
        foreach ($_FILES['files']['tmp_name'] as $i => $tmp) {
            $name = basename($_FILES['files']['name'][$i]);
            
            // Validasi nama file di backend
            if (preg_match($allowedRegex, $name)) {
                $dest = $dir . $name;
                if (move_uploaded_file($tmp, $dest)) {
                    $uploadedCount++;
                }
            } else {
                $rejectedCount++;
            }
        }
        
        if ($uploadedCount > 0 && $rejectedCount === 0) {
            $message = "$uploadedCount file berhasil diupload.";
            $messageType = "success";
        } elseif ($uploadedCount > 0 && $rejectedCount > 0) {
            $message = "$uploadedCount file berhasil diupload, namun $rejectedCount file ditolak karena format tidak sesuai.";
            $messageType = "warning";
        } else {
            $message = "Semua file ditolak karena format tidak sesuai. Hanya diperbolehkan: $allowedText.";
            $messageType = "danger";
        }
    }
}

// Ambil semua file (gambar dan video)
$files = array_values(array_filter(scandir($dir), function($file) use ($dir, $allowedRegex) {
    return is_file($dir . $file) && preg_match($allowedRegex, $file);
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
        .message { margin-bottom: 20px; padding: 12px; border-radius: 6px; font-weight: bold; }
        .message.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message.warning { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
        .message.danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .message.info { background: #e2e3e5; color: #383d41; border: 1px solid #d6d8db; }
        input[type="file"] { margin-bottom: 10px; padding: 8px; border: 1px solid #ccc; border-radius: 4px; width: 100%; box-sizing: border-box; }
        button { padding: 10px 20px; margin-right: 10px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; }
        .upload-btn { background: #007bff; color: white; }
        .upload-btn:hover { background: #0056b3; }
        .back-btn { background: #6c757d; color: white; text-decoration: none; padding: 10px 15px; border-radius: 6px; display: inline-block; margin-bottom: 20px; }
        .back-btn:hover { background: #5a6268; }

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
            <div class="message <?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" style="background: #f1f3f5; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
            <label style="display: block; font-weight: bold; margin-bottom: 8px;">Pilih file untuk diupload:</label>
            <input type="file" name="files[]" accept="<?= $acceptAttr ?>" multiple required>
            <p style="font-size: 13px; color: #666; margin: 0 0 10px 0;">Format yang diperbolehkan: <strong><?= $allowedText ?></strong></p>
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
