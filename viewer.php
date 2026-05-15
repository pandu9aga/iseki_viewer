<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Iseki Viewer - Slideshow</title>
    <link rel="icon" href="/iseki_viewer/logo-iseki.png" type="image/x-icon">
    <link rel="shortcut icon" href="/iseki_viewer/logo-iseki.png" type="image/x-icon">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body {
            width: 100%;
            height: 100%;
            background: black;
            overflow: hidden;
            font-family: Arial, sans-serif;
            cursor: default;
        }
        .slide {
            position: absolute;
            top: 0; left: 0;
            width: 100vw;
            height: 100vh;
            object-fit: contain;
            opacity: 0;
            transition: opacity 1s ease, transform 1s ease;
            z-index: 0;
        }
        .slide.active {
            opacity: 1;
            z-index: 2;
        }

        .controls, .settings-panel {
            transition: opacity 0.5s ease;
        }
        .controls.hidden, .settings-panel.hidden, .back-button.hidden {
            opacity: 0;
            pointer-events: none;
        }

        .controls {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0,0,0,0.5);
            padding: 10px 20px;
            border-radius: 12px;
            display: flex;
            gap: 10px;
            z-index: 10;
        }
        .controls button {
            padding: 10px 15px;
            font-size: 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            background: #ffffff33;
            color: white;
        }
        .controls button:hover {
            background: #ffffff55;
        }

        .settings-panel {
            position: fixed;
            top: 20px;
            right: 20px;
            background: rgba(0,0,0,0.8);
            padding: 15px;
            border-radius: 10px;
            color: white;
            display: flex;
            flex-direction: column;
            gap: 10px;
            z-index: 10;
        }
        .settings-panel input[type="number"], .settings-panel input[type="checkbox"] {
            width: 60px;
        }
        .settings-panel label {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Kembali button di pojok kiri atas */
        .back-button {
            position: fixed;
            top: 20px;
            left: 20px;
            background: rgba(0,0,0,0.7);
            color: white;
            padding: 10px 15px;
            font-size: 16px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            z-index: 20;
        }

        .back-button:hover {
            background: rgba(61, 61, 61, 0.34);
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

<!-- Tombol Kembali -->
<button class="back-button" id="backButton" onclick="window.location.href='/iseki_viewer/'">⬅️ Kembali</button>

<?php
$base = "slides/"; // Path to images directory
$subdir = isset($_GET['dir']) ? basename($_GET['dir']) : "";
$dir = rtrim($base . $subdir, '/') . '/';

// Ensure the directory is valid
if (!is_dir($dir)) {
    die("Direktori tidak ditemukan.");
}

$images = array_values(array_filter(scandir($dir), function($file) use ($dir) {
    return is_file($dir . $file) && preg_match('/\.(jpg|jpeg|png|gif)$/i', $file);
}));
// Urutkan dengan natural sorting
usort($images, 'strnatcmp');

foreach ($images as $index => $img) {
    $active = $index === 0 ? 'active' : '';
    $timestamp = time(); // Or uniqid() for more uniqueness
    echo "<img src='{$dir}{$img}?v=$timestamp' class='slide $active'>";
}
?>

<!-- Control Buttons -->
<div class="controls" id="controls">
    <button onclick="prevSlide()">⏮ Prev</button>
    <button id="togglePlay" onclick="togglePlay()">⏸ Pause</button>
    <button onclick="nextSlide()">⏭ Next</button>
</div>

<!-- Settings Panel -->
<div class="settings-panel" id="settingsPanel">
    <label>
        Durasi (detik):
        <input type="number" id="durationInput" min="1" value="3">
    </label>
    <input type="hidden" id="loopToggle" checked>
    <input type="hidden" id="fullscreenToggle">
</div>

<script>
    let slides = document.querySelectorAll(".slide");
    let current = 0;
    let isPlaying = true;
    let loop = true;
    let duration = 3000;
    let interval = setInterval(showNextSlide, duration);

    function showSlide(index, direction = 'next') {
        if (index === current) return;

        const currentSlide = slides[current];
        const nextSlide = slides[index];

        // Reset all slides
        slides.forEach(slide => {
            slide.style.transition = 'none';
            slide.style.opacity = 0;
            slide.style.transform = 'translateX(0)';
            slide.classList.remove('active');
        });

        // Apply transition
        currentSlide.style.transition = 'opacity 1s ease, transform 1s ease';
        nextSlide.style.transition = 'opacity 1s ease, transform 1s ease';

        // Set transition direction
        nextSlide.style.transform = direction === 'next' ? 'translateX(100%)' : 'translateX(-100%)';
        nextSlide.style.opacity = 1;
        nextSlide.classList.add("active");

        requestAnimationFrame(() => {
            nextSlide.style.transform = 'translateX(0)';
            currentSlide.style.opacity = 0;
        });

        current = index;
    }

    function showNextSlide() {
        let next = current + 1 < slides.length ? current + 1 : (loop ? 0 : current);
        showSlide(next, 'next');
    }

    function prevSlide() {
        let prev = current - 1 >= 0 ? current - 1 : (loop ? slides.length - 1 : current);
        showSlide(prev, 'prev');
    }

    function nextSlide() {
        showNextSlide();
        if (isPlaying) resetInterval();
    }

    function togglePlay(force) {
        if (typeof force === "boolean") {
            isPlaying = force;
        } else {
            isPlaying = !isPlaying;
        }
        document.getElementById("togglePlay").textContent = isPlaying ? "⏸ Pause" : "▶️ Play";
        isPlaying ? resetInterval() : clearInterval(interval);
    }

    document.getElementById("loopToggle").addEventListener("change", function() {
        loop = this.checked;
    });

    document.getElementById("durationInput").addEventListener("input", function() {
        let seconds = parseInt(this.value);
        if (!isNaN(seconds) && seconds > 0) {
            duration = seconds * 1000;
            if (isPlaying) resetInterval();
        }
    });

    document.getElementById("fullscreenToggle").addEventListener("change", function() {
        if (this.checked) {
            if (document.documentElement.requestFullscreen) {
                document.documentElement.requestFullscreen();
            } else if (document.documentElement.mozRequestFullScreen) { // Firefox
                document.documentElement.mozRequestFullScreen();
            } else if (document.documentElement.webkitRequestFullscreen) { // Chrome, Safari and Opera
                document.documentElement.webkitRequestFullscreen();
            } else if (document.documentElement.msRequestFullscreen) { // IE/Edge
                document.documentElement.msRequestFullscreen();
            }
        } else {
            if (document.exitFullscreen) {
                document.exitFullscreen();
            } else if (document.mozCancelFullScreen) { // Firefox
                document.mozCancelFullScreen();
            } else if (document.webkitExitFullscreen) { // Chrome, Safari and Opera
                document.webkitExitFullscreen();
            } else if (document.msExitFullscreen) { // IE/Edge
                document.msExitFullscreen();
            }
        }
    });

    function resetInterval() {
        clearInterval(interval);
        interval = setInterval(showNextSlide, duration);
    }
</script>
<script>
    window.onload = function() {
        let hideTimeout;
        const elementsToHide = [
            document.getElementById("controls"),
            document.getElementById("settingsPanel"),
            document.getElementById("backButton")
        ];

        function hideUI() {
            elementsToHide.forEach(el => el.classList.add("hidden"));
        }

        function showUI() {
            elementsToHide.forEach(el => el.classList.remove("hidden"));
        }

        function resetHideTimer() {
            showUI();
            clearTimeout(hideTimeout);
            hideTimeout = setTimeout(hideUI, 10000);
        }

        document.addEventListener("mousemove", resetHideTimer);
        resetHideTimer();
    };
</script>
<script>
    setTimeout(() => {
        const url = new URL(location.href);
        url.searchParams.set('refresh', Date.now());
        location.href = url.toString();
    },  30 * 60 * 1000); // 30 menit dalam milidetik
</script>
</body>
</html>
