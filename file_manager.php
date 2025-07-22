<?php
$baseDir = 'slides/';
$dir = isset($_POST['dir']) ? $_POST['dir'] : '';
$fullPath = realpath($baseDir . '/' . $dir);
if (strpos($fullPath, realpath($baseDir)) !== 0) die("Akses ditolak");

if (!empty($_FILES['file']['name'])) {
    $targetFile = $fullPath . '/' . basename($_FILES['file']['name']);
    move_uploaded_file($_FILES['file']['tmp_name'], $targetFile);
}

header("Location: index.php?dir=" . urlencode($dir));
exit;
