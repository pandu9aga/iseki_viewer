<?php
$slidesBase = 'slides/';
$parent = isset($_GET['parent']) ? $_GET['parent'] : 'photos';
$subdir = isset($_GET['dir']) ? basename($_GET['dir']) : '';
$dir = rtrim($slidesBase.$parent.'/'.$subdir, '/').'/';

if (! is_dir($dir)) {
    exit('Direktori tidak ditemukan.');
}

$regex = ($parent === 'videos') ? '/\.(mp4|webm|ogg)$/i' : '/\.(jpg|jpeg|png|gif|mp4|webm|ogg)$/i';

$files = array_values(array_filter(scandir($dir), function ($file) use ($dir, $regex) {
    return is_file($dir.$file) && preg_match($regex, $file);
}));

usort($files, 'strnatcmp');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Iseki Viewer - Slideshow</title>
    <link rel="icon" href="/iseki_viewer/logo-iseki.png" type="image/x-icon">
    <link rel="shortcut icon" href="/iseki_viewer/logo-iseki.png" type="image/x-icon">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { width: 100%; height: 100%; background: black; overflow: hidden; font-family: Arial, sans-serif; }
        .slide-container { position: absolute; top: 0; left: 0; width: 100vw; height: 100vh; display: flex; align-items: center; justify-content: center; opacity: 0; transition: opacity 1s ease; z-index: 0; }
        .slide-container.active { opacity: 1; z-index: 2; }
        .slide-container img, .slide-container video { max-width: 100%; max-height: 100%; object-fit: contain; }
        .controls, .settings-panel { transition: opacity 0.5s ease; }
        .controls.hidden, .settings-panel.hidden, .back-button.hidden { opacity: 0; pointer-events: none; }
        .controls { position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%); background: rgba(0,0,0,0.5); padding: 10px 20px; border-radius: 12px; display: flex; gap: 10px; z-index: 10; }
        .controls button { padding: 10px 15px; font-size: 16px; border: none; border-radius: 6px; cursor: pointer; background: #ffffff33; color: white; }
        .controls button:hover { background: #ffffff55; }
        .settings-panel { position: fixed; top: 20px; right: 20px; background: rgba(0,0,0,0.8); padding: 15px; border-radius: 10px; color: white; display: flex; flex-direction: column; gap: 10px; z-index: 10; }
        .settings-panel input { width: 60px; }
        .settings-panel label { display: flex; align-items: center; gap: 8px; }
        .back-button { position: fixed; top: 20px; left: 20px; background: rgba(0,0,0,0.7); color: white; padding: 10px 15px; border-radius: 8px; cursor: pointer; z-index: 20; border: none; }
    </style>
    <script src="/iseki_pro_app/js/dynamic-favicon.js"></script>
    <script>document.addEventListener("DOMContentLoaded", function() { setDynamicFavicon("connected_tv", "Viewer"); });</script>
    <link rel="stylesheet" href="/iseki_pro_app/css/icon.css">
</head>
<body>
<button class="back-button" id="backButton" onclick="window.location.href='index.php?tab=<?= $parent ?>'">⬅️ Kembali</button>
<?php
foreach ($files as $index => $file) {
    $active = $index === 0 ? 'active' : '';
    $timestamp = time();
    $filePath = $dir.$file;
    $is_video = preg_match('/\.(mp4|webm|ogg)$/i', $file);
    echo "<div class='slide-container $active' data-index='$index' data-type='".($is_video ? 'video' : 'photo')."'>";
    if ($is_video) {
        echo "<video src='$filePath?v=$timestamp' class='media' playsinline muted></video>";
    } else {
        echo "<img src='$filePath?v=$timestamp' class='media'>";
    }
    echo '</div>';
}
?>
<div class="controls" id="controls">
    <button onclick="prevSlide()">⏮ Prev</button>
    <button id="togglePlay" onclick="togglePlay()">⏸ Pause</button>
    <button onclick="nextSlide()">⏭ Next</button>
    <div id="videoControls" style="display: none; align-items: center; gap: 10px; margin-left: 15px; border-left: 1px solid rgba(255,255,255,0.3); padding-left: 15px;">
        <span id="timeDisplay" style="color: white; font-size: 14px; min-width: 80px; text-align: center;">0:00 / 0:00</span>
        <input type="range" id="progressBar" min="0" max="100" value="0" style="width: 150px; cursor: pointer;">
        <button id="muteBtn" onclick="toggleMute()" style="padding: 5px 10px; min-width: 40px;">🔊</button>
        <input type="range" id="volumeBar" min="0" max="1" step="0.05" value="1" style="width: 80px; cursor: pointer;">
    </div>
</div>
<div class="settings-panel" id="settingsPanel">
    <label>Durasi Foto (detik): <input type="number" id="durationInput" min="1" value="3"></label>
</div>
<script>
    let slides = document.querySelectorAll(".slide-container");
    let current = 0;
    let isPlaying = true;
    let duration = 3000;
    let timeout;
    let currentVolume = 1;
    let isMuted = false;

    // Elements
    const progressBar = document.getElementById("progressBar");
    const volumeBar = document.getElementById("volumeBar");
    const timeDisplay = document.getElementById("timeDisplay");
    const muteBtn = document.getElementById("muteBtn");
    const videoControls = document.getElementById("videoControls");

    function init() { if (slides.length > 0) playMedia(0); }

    function formatTime(seconds) {
        if (isNaN(seconds)) return "0:00";
        const m = Math.floor(seconds / 60);
        const s = Math.floor(seconds % 60);
        return m + ":" + (s < 10 ? "0" : "") + s;
    }

    function updateVideoUI(media) {
        if (media.duration) {
            progressBar.value = (media.currentTime / media.duration) * 100;
            timeDisplay.textContent = formatTime(media.currentTime) + " / " + formatTime(media.duration);
        }
    }

    function playMedia(index) {
        clearTimeout(timeout);
        slides.forEach(s => s.classList.remove('active'));
        const slide = slides[index];
        slide.classList.add('active');
        const media = slide.querySelector(".media");

        if (slide.dataset.type === 'video') {
            videoControls.style.display = 'flex';
            media.volume = isMuted ? 0 : currentVolume;
            media.muted = isMuted; // Use actual muted property or set volume to 0
            
            // Set initial state
            media.currentTime = 0;
            updateVideoUI(media);
            
            if (isPlaying) {
                media.play().catch(e => console.log("Auto-play prevented:", e));
            }
            
            media.onended = () => { if (isPlaying) nextSlide(); };
            media.ontimeupdate = () => { updateVideoUI(media); };
            
            // Allow clicking progress bar to seek
            progressBar.oninput = (e) => {
                const seekTime = (e.target.value / 100) * media.duration;
                media.currentTime = seekTime;
            };
        } else {
            videoControls.style.display = 'none';
            if (isPlaying) {
                timeout = setTimeout(nextSlide, duration);
            }
        }
    }

    function nextSlide() {
        if (slides[current].dataset.type === 'video') slides[current].querySelector(".media").pause();
        current = (current + 1) % slides.length;
        playMedia(current);
    }

    function prevSlide() {
        if (slides[current].dataset.type === 'video') slides[current].querySelector(".media").pause();
        current = (current - 1 + slides.length) % slides.length;
        playMedia(current);
    }

    function togglePlay() {
        isPlaying = !isPlaying;
        document.getElementById("togglePlay").textContent = isPlaying ? "⏸ Pause" : "▶️ Play";
        
        const slide = slides[current];
        const media = slide.querySelector(".media");

        if (isPlaying) {
            if (slide.dataset.type === 'video') {
                media.play().catch(e => console.log(e));
            } else {
                // Resume photo countdown
                timeout = setTimeout(nextSlide, duration);
            }
        } else {
            clearTimeout(timeout);
            if (slide.dataset.type === 'video') {
                media.pause();
            }
        }
    }

    function toggleMute() {
        isMuted = !isMuted;
        muteBtn.textContent = isMuted ? "🔇" : "🔊";
        
        if (slides[current].dataset.type === 'video') {
            const media = slides[current].querySelector(".media");
            media.muted = isMuted;
            // Also update volume property based on mute state if needed, but muted attribute is usually enough
        }
        
        if (isMuted) {
            volumeBar.value = 0;
        } else {
            volumeBar.value = currentVolume;
        }
    }

    volumeBar.oninput = (e) => {
        currentVolume = e.target.value;
        if (currentVolume > 0) {
            isMuted = false;
            muteBtn.textContent = "🔊";
        } else {
            isMuted = true;
            muteBtn.textContent = "🔇";
        }
        if (slides[current].dataset.type === 'video') {
            const media = slides[current].querySelector(".media");
            media.muted = isMuted;
            media.volume = currentVolume;
        }
    };

    document.getElementById("durationInput").oninput = function() {
        duration = parseInt(this.value) * 1000;
        if (isPlaying && slides[current].dataset.type === 'photo') {
            clearTimeout(timeout);
            timeout = setTimeout(nextSlide, duration);
        }
    };

    let hideTimeout;
    const elements = [document.getElementById("controls"), document.getElementById("settingsPanel"), document.getElementById("backButton")];
    function resetHideTimer() {
        elements.forEach(el => el.style.opacity = 1);
        clearTimeout(hideTimeout);
        hideTimeout = setTimeout(() => elements.forEach(el => el.style.opacity = 0), 3000);
    }
    
    document.onmousemove = resetHideTimer;
    // Unmute video explicitly if requested previously (in case muted is removed in HTML)
    window.onload = init;
    setTimeout(() => location.reload(), 30 * 60 * 1000);
</script>
</body>
</html>
