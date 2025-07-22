<?php
$base = "slides/";
$subdir = isset($_GET['dir']) ? basename($_GET['dir']) : "";
$dir = rtrim($base . $subdir, '/') . '/';

if (!is_dir($dir)) die("Folder tidak ditemukan.");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['reset'])) {
        $images = array_merge(
            glob($dir . "*.jpg"),
            glob($dir . "*.jpeg"),
            glob($dir . "*.png"),
            glob($dir . "*.gif")
        );
        foreach ($images as $img) {
            unlink($img);
        }
        $message = "Semua gambar telah dihapus.";
    } elseif (!empty($_FILES['images'])) {
        foreach ($_FILES['images']['tmp_name'] as $i => $tmp) {
            $name = basename($_FILES['images']['name'][$i]);
            $dest = $dir . $name;
            move_uploaded_file($tmp, $dest);
        }
        $message = "Gambar berhasil diupload.";
    }
}

// Ambil gambar terbaru setelah upload/reset
$images = array_values(array_filter(scandir($dir), function($file) use ($dir) {
    return is_file($dir . $file) && preg_match('/\.(jpg|jpeg|png|gif)$/i', $file);
}));

// Urutkan dengan natural sorting
usort($images, 'strnatcmp');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Iseki Viewer - Upload Gambar <?= htmlspecialchars($subdir) ?></title>
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
        .reset-btn { background: #dc3545; color: white; }
        .back-btn { background: #6c757d; color: white; text-decoration: none; padding: 10px 15px; border-radius: 6px; display: inline-block; margin-bottom: 20px; }

        .preview-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 30px;
        }
        .preview-grid img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid #ccc;
        }
        .image-item {
            text-align: center;
            font-size: 14px;
            word-break: break-word;
        }
        .image-name {
            margin-top: 5px;
            color: #333;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php" class="back-btn">⬅️ Kembali ke Menu Utama</a>
        <h1>Upload Gambar ke Folder: <?= htmlspecialchars($subdir) ?></h1>

        <?php if (!empty($message)): ?>
            <div class="message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <input type="file" name="images[]" multiple required><br>
            <button type="submit" class="upload-btn">📤 Upload</button>
        </form>

        <!-- <form method="POST" onsubmit="return confirm('Yakin ingin menghapus semua gambar?');">
            <input type="hidden" name="reset" value="1">
            <button type="submit" class="reset-btn">🗑 Reset Semua Gambar</button>
        </form> -->

        <?php if (count($images) > 0): ?>
            <h2>Preview Gambar:</h2>
            <div class="preview-grid">
                <?php foreach ($images as $img): ?>
                    <div class="image-item">
                        <img src="<?= $dir . $img ?>?v=<?= time() ?>" alt="Preview">
                        <div class="image-name"><?= htmlspecialchars($img) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php else: ?>
            <p style="margin-top: 20px; color: #888;">Tidak ada gambar dalam folder ini.</p>
        <?php endif; ?>
    </div>
</body>
</html>
