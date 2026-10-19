<?php
$slidesBase = 'slides/';
$parent = isset($_GET['parent']) ? $_GET['parent'] : 'photos';
$subdir = isset($_GET['dir']) ? basename($_GET['dir']) : '';
$dir = rtrim($slidesBase.$parent.'/'.$subdir, '/').'/';

if (! is_dir($dir)) {
    if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Direktori tidak ditemukan.']);
        exit;
    }
    exit('Direktori tidak ditemukan.');
}

$regex = ($parent === 'videos') ? '/\.(mp4|webm|ogg)$/i' : '/\.(jpg|jpeg|png|gif|mp4|webm|ogg)$/i';

$files = array_values(array_filter(scandir($dir), function ($file) use ($dir, $regex) {
    return is_file($dir.$file) && preg_match($regex, $file);
}));

usort($files, 'strnatcmp');

// Handle AJAX Polling Request
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    header('Content-Type: application/json');
    $mediaList = [];
    foreach ($files as $file) {
        $filePath = $dir.$file;
        $isVideo = (bool) preg_match('/\.(mp4|webm|ogg)$/i', $file);
        $mediaList[] = [
            'name'  => $file,
            'url'   => $filePath,
            'type'  => $isVideo ? 'video' : 'photo',
            'mtime' => filemtime($filePath),
        ];
    }
    echo json_encode([
        'success' => true,
        'parent'  => $parent,
        'dir'     => $subdir,
        'files'   => $mediaList,
    ]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Iseki Viewer - Slideshow</title>
    <link rel="icon" href="/iseki_viewer/logo-iseki.png" type="image/x-icon">
    <link rel="shortcut icon" href="/iseki_viewer/logo-iseki.png" type="image/x-icon">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body {
            width: 100%;
            height: 100%;
            background: #000000;
            overflow: hidden;
            font-family: Arial, sans-serif;
            user-select: none;
        }
        #slidesWrapper {
            position: relative;
            width: 100vw;
            height: 100vh;
            overflow: hidden;
        }
        .slide-container {
            position: absolute;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            max-width: 100%;
            max-height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.8s ease-in-out;
            z-index: 0;
            overflow: hidden;
            background: #000000;
        }
        .slide-container.active {
            opacity: 1;
            z-index: 2;
        }
        .slide-container img,
        .slide-container video {
            max-width: 100vw;
            max-height: 100vh;
            width: auto;
            height: auto;
            object-fit: contain;
            object-position: center;
            display: block;
        }
        .controls, .settings-panel, .cache-badge {
            transition: opacity 0.5s ease;
        }
        .controls.hidden, .settings-panel.hidden, .back-button.hidden, .cache-badge.hidden {
            opacity: 0;
            pointer-events: none;
        }
        .controls {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0,0,0,0.65);
            backdrop-filter: blur(8px);
            padding: 10px 20px;
            border-radius: 12px;
            display: flex;
            gap: 10px;
            z-index: 10;
            border: 1px solid rgba(255,255,255,0.15);
        }
        .controls button {
            padding: 10px 15px;
            font-size: 15px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            background: rgba(255,255,255,0.2);
            color: white;
            transition: background 0.2s;
        }
        .controls button:hover {
            background: rgba(255,255,255,0.35);
        }
        .settings-panel {
            position: fixed;
            top: 20px;
            right: 20px;
            background: rgba(0,0,0,0.75);
            backdrop-filter: blur(8px);
            padding: 15px;
            border-radius: 10px;
            color: white;
            display: flex;
            flex-direction: column;
            gap: 10px;
            z-index: 10;
            font-size: 14px;
            border: 1px solid rgba(255,255,255,0.15);
        }
        .settings-panel input[type="number"] {
            width: 60px;
            padding: 4px 6px;
            border-radius: 4px;
            border: 1px solid #555;
            background: #222;
            color: #fff;
        }
        .settings-panel label {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .btn-action-small {
            padding: 6px 10px;
            font-size: 12px;
            background: #e91e63;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }
        .btn-action-small:hover {
            background: #c2185b;
        }
        .back-button {
            position: fixed;
            top: 20px;
            left: 20px;
            background: rgba(0,0,0,0.75);
            backdrop-filter: blur(8px);
            color: white;
            padding: 10px 15px;
            border-radius: 8px;
            cursor: pointer;
            z-index: 20;
            border: 1px solid rgba(255,255,255,0.15);
            font-size: 14px;
        }
        .cache-badge {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: rgba(0, 0, 0, 0.7);
            color: #aaa;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            z-index: 9;
            pointer-events: none;
            display: flex;
            align-items: center;
            gap: 6px;
            border: 1px solid rgba(255,255,255,0.1);
        }
        .cache-badge .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #ff9800;
        }
        .cache-badge.ready .dot {
            background: #4caf50;
        }
        .media-spinner {
            position: absolute;
            width: 48px;
            height: 48px;
            border: 4px solid rgba(255,255,255,0.2);
            border-top-color: #e91e63;
            border-radius: 50%;
            animation: spin 0.9s linear infinite;
            display: none;
            z-index: 1;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        #emptyState {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: #888;
            font-size: 18px;
            display: none;
            text-align: center;
        }
    </style>
    <script src="/iseki_pro_app/js/dynamic-favicon.js"></script>
    <script>document.addEventListener("DOMContentLoaded", function() { setDynamicFavicon("connected_tv", "Viewer"); });</script>
    <link rel="stylesheet" href="/iseki_pro_app/css/icon.css">
</head>
<body>
<button class="back-button" id="backButton" onclick="window.location.href='index.php?tab=<?= htmlspecialchars($parent, ENT_QUOTES) ?>'">⬅️ Kembali</button>

<div class="cache-badge" id="cacheBadge">
    <span class="dot"></span>
    <span id="cacheText">Memeriksa cache offline...</span>
</div>

<div id="slidesWrapper">
    <div id="emptyState">Tidak ada foto/video dalam folder ini.</div>
    <?php
    foreach ($files as $index => $file) {
        $active = $index === 0 ? 'active' : '';
        $filePath = $dir.$file;
        $is_video = preg_match('/\.(mp4|webm|ogg)$/i', $file);
        $mtime = filemtime($filePath);
        echo "<div class='slide-container $active' data-index='$index' data-name='".htmlspecialchars($file, ENT_QUOTES)."' data-type='".($is_video ? 'video' : 'photo')."' data-url='".htmlspecialchars($filePath, ENT_QUOTES)."' data-mtime='$mtime'>";
        echo "<div class='media-spinner'></div>";
        if ($is_video) {
            echo "<video class='media' playsinline muted preload='auto'></video>";
        } else {
            echo "<img class='media' alt=''>";
        }
        echo '</div>';
    }
    ?>
</div>

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
    <button class="btn-action-small" onclick="clearMediaCache()">🔄 Hapus & Perbarui Cache</button>
</div>

<script>
    const CACHE_NAME = 'iseki-viewer-cache-v1';
    const slidesWrapper = document.getElementById("slidesWrapper");
    const emptyState = document.getElementById("emptyState");
    let slides = Array.from(document.querySelectorAll(".slide-container"));
    let current = 0;
    let isPlaying = true;
    let duration = 3000;
    let timeout;
    let currentVolume = 1;
    let isMuted = false;
    let blobUrls = {}; // Map file URL to blob URL
    let cacheStorageSupported = ('caches' in window);
    let isSyncing = false;

    // Elements
    const progressBar = document.getElementById("progressBar");
    const volumeBar = document.getElementById("volumeBar");
    const timeDisplay = document.getElementById("timeDisplay");
    const muteBtn = document.getElementById("muteBtn");
    const videoControls = document.getElementById("videoControls");
    const cacheBadge = document.getElementById("cacheBadge");
    const cacheText = document.getElementById("cacheText");

    // Cache Manager using Cache Storage API + Blob Object URLs
    async function getMediaSourceUrl(url, forceRefresh = false) {
        if (!cacheStorageSupported) {
            return url;
        }

        if (!forceRefresh && blobUrls[url]) {
            return blobUrls[url];
        }

        try {
            const cache = await caches.open(CACHE_NAME);
            let response = forceRefresh ? null : await cache.match(url);
            
            if (!response) {
                response = await fetch(url);
                if (response.ok) {
                    await cache.put(url, response.clone());
                }
            }

            const blob = await response.blob();
            if (blobUrls[url]) {
                URL.revokeObjectURL(blobUrls[url]);
            }
            const blobUrl = URL.createObjectURL(blob);
            blobUrls[url] = blobUrl;
            return blobUrl;
        } catch (err) {
            console.warn("Cache fetch fallback to direct URL:", err);
            return url;
        }
    }

    // Preload & Cache all media for seamless offline looping
    async function preloadAllMedia() {
        if (!cacheStorageSupported || slides.length === 0) {
            cacheBadge.classList.add('ready');
            cacheText.textContent = slides.length === 0 ? "Tidak ada media" : "Offline cache tidak didukung browser";
            return;
        }

        let cachedCount = 0;
        const total = slides.length;
        cacheBadge.classList.remove('ready');
        cacheText.textContent = `Menyiapkan cache offline (0/${total})...`;

        for (let i = 0; i < slides.length; i++) {
            const slide = slides[i];
            const originalUrl = slide.dataset.url;
            try {
                const blobUrl = await getMediaSourceUrl(originalUrl);
                const media = slide.querySelector(".media");
                
                if (!media.src || media.src === window.location.href) {
                    media.src = blobUrl;
                    if (slide.dataset.type === 'video') {
                        media.load();
                    }
                }
                cachedCount++;
                cacheText.textContent = `Caching offline (${cachedCount}/${total})...`;
            } catch (e) {
                console.error("Gagal pre-cache:", originalUrl, e);
            }
        }

        cacheBadge.classList.add('ready');
        cacheText.textContent = `Offline Ready (${cachedCount}/${total})`;
    }

    async function clearMediaCache() {
        if (confirm("Hapus cache offline dan download ulang konten terbaru dari server?")) {
            if (cacheStorageSupported) {
                await caches.delete(CACHE_NAME);
            }
            Object.values(blobUrls).forEach(url => URL.revokeObjectURL(url));
            blobUrls = {};
            // Trigger ajax sync immediately without full page reload
            syncMediaFromServer(true);
        }
    }

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

    async function playMedia(index) {
        clearTimeout(timeout);
        slides = Array.from(document.querySelectorAll(".slide-container"));

        if (slides.length === 0) {
            emptyState.style.display = "block";
            videoControls.style.display = "none";
            return;
        } else {
            emptyState.style.display = "none";
        }

        if (index >= slides.length) index = 0;
        if (index < 0) index = slides.length - 1;
        current = index;

        slides.forEach(s => s.classList.remove('active'));
        const slide = slides[current];
        slide.classList.add('active');
        const media = slide.querySelector(".media");
        const spinner = slide.querySelector(".media-spinner");

        // Make sure media source is assigned
        if (!media.src || media.src === window.location.href) {
            if (spinner) spinner.style.display = "block";
            const srcUrl = await getMediaSourceUrl(slide.dataset.url);
            media.src = srcUrl;
            if (slide.dataset.type === 'video') {
                media.load();
            }
            if (spinner) spinner.style.display = "none";
        }

        if (slide.dataset.type === 'video') {
            videoControls.style.display = 'flex';
            media.volume = isMuted ? 0 : currentVolume;
            media.muted = isMuted;
            
            // Set initial state
            media.currentTime = 0;
            updateVideoUI(media);
            
            if (isPlaying) {
                media.play().catch(e => {
                    console.log("Auto-play prevented:", e);
                });
            }
            
            media.onended = () => { if (isPlaying) nextSlide(); };
            media.ontimeupdate = () => { updateVideoUI(media); };
            
            progressBar.oninput = (e) => {
                if (media.duration) {
                    const seekTime = (e.target.value / 100) * media.duration;
                    media.currentTime = seekTime;
                }
            };
        } else {
            videoControls.style.display = 'none';
            if (isPlaying) {
                timeout = setTimeout(nextSlide, duration);
            }
        }
    }

    function nextSlide() {
        slides = Array.from(document.querySelectorAll(".slide-container"));
        if (slides.length === 0) return;
        if (slides[current] && slides[current].dataset.type === 'video') {
            slides[current].querySelector(".media").pause();
        }
        current = (current + 1) % slides.length;
        playMedia(current);
    }

    function prevSlide() {
        slides = Array.from(document.querySelectorAll(".slide-container"));
        if (slides.length === 0) return;
        if (slides[current] && slides[current].dataset.type === 'video') {
            slides[current].querySelector(".media").pause();
        }
        current = (current - 1 + slides.length) % slides.length;
        playMedia(current);
    }

    function togglePlay() {
        slides = Array.from(document.querySelectorAll(".slide-container"));
        if (slides.length === 0) return;
        isPlaying = !isPlaying;
        document.getElementById("togglePlay").textContent = isPlaying ? "⏸ Pause" : "▶️ Play";
        
        const slide = slides[current];
        if (!slide) return;
        const media = slide.querySelector(".media");

        if (isPlaying) {
            if (slide.dataset.type === 'video') {
                media.play().catch(e => console.log(e));
            } else {
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
        
        slides = Array.from(document.querySelectorAll(".slide-container"));
        if (slides.length > 0 && slides[current] && slides[current].dataset.type === 'video') {
            const media = slides[current].querySelector(".media");
            media.muted = isMuted;
        }
        
        if (isMuted) {
            volumeBar.value = 0;
        } else {
            volumeBar.value = currentVolume;
        }
    }

    volumeBar.oninput = (e) => {
        currentVolume = parseFloat(e.target.value);
        if (currentVolume > 0) {
            isMuted = false;
            muteBtn.textContent = "🔊";
        } else {
            isMuted = true;
            muteBtn.textContent = "🔇";
        }
        slides = Array.from(document.querySelectorAll(".slide-container"));
        if (slides.length > 0 && slides[current] && slides[current].dataset.type === 'video') {
            const media = slides[current].querySelector(".media");
            media.muted = isMuted;
            media.volume = currentVolume;
        }
    };

    document.getElementById("durationInput").oninput = function() {
        duration = parseInt(this.value) * 1000;
        slides = Array.from(document.querySelectorAll(".slide-container"));
        if (isPlaying && slides.length > 0 && slides[current] && slides[current].dataset.type === 'photo') {
            clearTimeout(timeout);
            timeout = setTimeout(nextSlide, duration);
        }
    };

    // Asynchronous background polling to sync media without full page reload
    async function syncMediaFromServer(forceFullRefresh = false) {
        if (isSyncing) return;
        if (!navigator.onLine && !forceFullRefresh) return; // Silent skip if offline

        isSyncing = true;
        try {
            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set('ajax', '1');
            const res = await fetch(currentUrl.toString());
            if (!res.ok) throw new Error("Gagal mengambil data ajax");
            const data = await res.json();

            if (data.success && Array.isArray(data.files)) {
                reconcileSlides(data.files, forceFullRefresh);
            }
        } catch (err) {
            // Silent error on background poll
            console.debug("Background sync check offline/gagal:", err.message);
        } finally {
            isSyncing = false;
        }
    }

    // Reconcile / Diff server files with DOM slides seamlessly
    async function reconcileSlides(serverFiles, forceFullRefresh) {
        const existingSlideElements = Array.from(document.querySelectorAll(".slide-container"));
        const existingMap = new Map();
        existingSlideElements.forEach(el => {
            existingMap.set(el.dataset.name, el);
        });

        const serverNames = new Set(serverFiles.map(f => f.name));
        let domChanged = false;

        // 1. Remove deleted files from DOM & cache
        for (const [name, el] of existingMap.entries()) {
            if (!serverNames.has(name)) {
                if (el.classList.contains('active')) {
                    // Jika slide yang sedang aktif terhapus, pindah ke slide berikutnya
                    nextSlide();
                }
                const url = el.dataset.url;
                if (blobUrls[url]) {
                    URL.revokeObjectURL(blobUrls[url]);
                    delete blobUrls[url];
                }
                el.remove();
                domChanged = true;
            }
        }

        // 2. Add new files or update modified files
        for (let i = 0; i < serverFiles.length; i++) {
            const file = serverFiles[i];
            const existingEl = existingMap.get(file.name);

            if (!existingEl) {
                // New file detected -> create slide container in DOM
                const newSlide = document.createElement("div");
                newSlide.className = "slide-container";
                newSlide.dataset.index = i;
                newSlide.dataset.name = file.name;
                newSlide.dataset.type = file.type;
                newSlide.dataset.url = file.url;
                newSlide.dataset.mtime = file.mtime;

                const spinner = document.createElement("div");
                spinner.className = "media-spinner";
                newSlide.appendChild(spinner);

                if (file.type === 'video') {
                    const video = document.createElement("video");
                    video.className = "media";
                    video.playsInline = true;
                    video.muted = true;
                    video.preload = "auto";
                    newSlide.appendChild(video);
                } else {
                    const img = document.createElement("img");
                    img.className = "media";
                    img.alt = "";
                    newSlide.appendChild(img);
                }

                slidesWrapper.appendChild(newSlide);
                domChanged = true;

                // Cache the new file in background
                getMediaSourceUrl(file.url).then(blobUrl => {
                    const media = newSlide.querySelector(".media");
                    media.src = blobUrl;
                    if (file.type === 'video') media.load();
                });
            } else {
                // Check if file was updated on server
                if (existingEl.dataset.mtime !== String(file.mtime) || forceFullRefresh) {
                    existingEl.dataset.mtime = file.mtime;
                    getMediaSourceUrl(file.url, true).then(blobUrl => {
                        const media = existingEl.querySelector(".media");
                        media.src = blobUrl;
                        if (file.type === 'video') media.load();
                    });
                }
            }
        }

        slides = Array.from(document.querySelectorAll(".slide-container"));

        if (domChanged || forceFullRefresh) {
            // Update cache badge
            cacheBadge.classList.add('ready');
            cacheText.textContent = `Offline Ready (${slides.length}/${slides.length})`;
            
            // If was empty before and now has slides, start playing
            if (slides.length > 0 && !document.querySelector(".slide-container.active")) {
                playMedia(0);
            } else if (slides.length === 0) {
                emptyState.style.display = "block";
            }
        }
    }

    let hideTimeout;
    const elements = [
        document.getElementById("controls"),
        document.getElementById("settingsPanel"),
        document.getElementById("backButton"),
        document.getElementById("cacheBadge")
    ];

    function resetHideTimer() {
        elements.forEach(el => {
            if (el) el.classList.remove("hidden");
        });
        clearTimeout(hideTimeout);
        hideTimeout = setTimeout(() => {
            elements.forEach(el => {
                if (el) el.classList.add("hidden");
            });
        }, 3000);
    }
    
    document.onmousemove = resetHideTimer;
    document.ontouchstart = resetHideTimer;

    window.onload = async () => {
        if (slides.length > 0) {
            await playMedia(0);
            resetHideTimer();
            preloadAllMedia();
        } else {
            emptyState.style.display = "block";
        }

        // Start background polling every 20 seconds (AJAX without page reload)
        setInterval(() => {
            syncMediaFromServer(false);
        }, 20 * 1000);
    };
</script>
</body>
</html>
