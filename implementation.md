# Plan Implementation: Video/Image Offline Caching & Aspect Ratio Fix in `iseki_viewer`

## 1. Ringkasan Permasalahan & Kebutuhan
1. **Buffering & Re-download Loop**:
   - Saat ini, media dimuat menggunakan tag `<video src='$filePath?v=$timestamp'>` dan `<img src='$filePath?v=$timestamp'>`. Penggunaan query parameter timestamp acak / baru serta pemutaran video secara streaming normal membuat browser terus me-request data ke server setiap loop atau pergantian slide, menyebabkan buffering berulang dan membebani jaringan / server, serta gagal saat jaringan offline.
   - **Kebutuhan**: Media di-cache di client-side (sekali unduh, seterusnya diputar dari cache lokal tanpa download ulang), sehingga loop lancar tanpa buffering dan tetap dapat berjalan meskipun koneksi jaringan terputus (offline-ready).
2. **Media Ter-zoom & Gambar/Video Terpotong**:
   - Di beberapa resolusi / rasio layar atau browser mode tertentu, gambar atau video tampak ter-zoom atau terpotong.
   - **Kebutuhan**: Memastikan semua media (video dan gambar) selalu ditampilkan utuh sesuai rasio aslinya tanpa terpotong (`object-fit: contain` yang konsisten di berbagai ukuran layar dan orientasi, tanpa overflow container).

---

## 2. Arsitektur & Solusi Teknis

### A. Strategi Caching (Offline Playback & Zero Buffering)
Untuk file video berukuran menengah hingga besar di browser web, terdapat 2 pendekatan utama:
1. **Pendekatan Cache Storage API + Blob Object URL (Direkomendasikan)**:
   - Client JS menggunakan `caches.open('iseki-viewer-media-v1')` atau `fetch()` -> simpan respons ke Cache Storage API.
   - Mengambil `Response.blob()`, lalu membuat Object URL lokal: `URL.createObjectURL(blob)`.
   - Mengganti atribut `src` pada `<video>` dan `<img>` dengan `blob:...` lokal.
   - **Keuntungan**:
     - Membaca video langsung dari local storage RAM/Disk browser secara instan tanpa stream HTTP request berulang ke server saat looping.
     - Seek progress bar dan repeat loop instan 0ms latency.
     - Berfungsi penuh 100% offline setelah file ter-cache.
     - Memiliki indikator progress download/caching awal yang jelas di UI.
   - **Penyimpanan Cache**: Menggunakan Cache Storage API bawaan HTML5 (didukung Chrome, Edge, Firefox di semua platform modern).
2. **Service Worker Cache (Opsi Pelengkap)**:
   - Mendaftarkan Service Worker untuk meng-intercept request file media (`slides/...`) dengan strategi *Cache-First* atau *Stale-While-Revalidate*.

> **Pilihan Solusi Terbaik**: 
> Kombinasi **Preload + Cache Storage API + Blob Object URL**:
> - Saat halaman viewer dibuka, script memeriksa apakah media sudah ada di cache browser.
> - Jika belum, file di-download di background (dengan visual status bar "Downloading media 1/N...").
> - Video & foto langsung diset ke `URL.createObjectURL(blob)` dari cache lokal.
> - Pada putaran looping berikutnya, video diputar dari URL Blob lokal sehingga tidak ada network activity maupun buffering sama sekali.

### B. Memperbaiki Zoom & Gambar Terpotong (Aspect Ratio Containment)
- Memastikan wrapper layout dan CSS:
  ```css
  html, body {
      width: 100%;
      height: 100%;
      margin: 0;
      padding: 0;
      overflow: hidden;
      background: #000;
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
      overflow: hidden;
  }
  .slide-container img, 
  .slide-container video {
      max-width: 100vw;
      max-height: 100vh;
      width: auto;
      height: auto;
      object-fit: contain;
      object-position: center;
  }
  ```
- Menghindari properti yang memaksa `width: 100%; height: 100%` tanpa batasan aspect ratio jika aspect-ratio parent terdistorsi oleh browser toolbar atau fullscreen mode.
- Menghapus query string acak `?v=$timestamp` yang memaksa browser mem-bypass cache HTTP.

---

## 3. Langkah Implementasi Detil

### Tahap 1: Evaluasi & Modifikasi Markup PHP (`viewer.php`)
1. Bersihkan penyematan URL di tag media:
   - Simpan URL asli pada atribut `data-src="$filePath"` bukannya langsung menyematkan URL di `src` dengan timestamp acak.
   - Tambahkan placeholder loading spinner / progress overlay yang elegan jika file sedang di-cache.

### Tahap 2: Implementasi Media Cache Manager di JavaScript
1. Buat modul caching media di dalam `viewer.php`:
   - Gunakan nama Cache: `const CACHE_NAME = 'iseki-viewer-cache-v1';`
   - Fungsi `async function getCachedMediaUrl(url)`:
     - Cek `caches.match(url)`.
     - Jika ada di cache, ambil `response.blob()` -> `return URL.createObjectURL(blob)`.
     - Jika belum ada, lakukan `fetch(url)`, simpan clone respon ke `cache.put(url, response.clone())`, lalu buat Blob URL.
2. Mekanisme Pre-caching:
   - Segera load slide pertama agar langsung tayang.
   - Secara asinkron download dan cache slide-slide berikutnya (background preloading).
   - Tampilkan status kecil di pojok bawah: *"Tersimpan offline (X/Y)"* agar user tahu status kesiapan offline.
3. Tombol / Fitur Bersihkan Cache:
   - Tambahkan tombol di panel settings untuk *Refresh / Clear Cache* jika ada update file terbaru dari server.

### Tahap 3: Perbaikan Tampilan CSS & Media Scaling
1. Terapkan rule CSS `object-fit: contain`, `max-width: 100vw`, `max-height: 100vh`, dan center alignment.
2. Pastikan tidak ada scale transform yang tidak diinginkan dan tidak ada zoom otomatis dari browser viewport mobile/TV.
3. Tambahkan `<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">`.

### Tahap 4: Pengujian & Validasi
1. **Uji Caching & Zero Buffering**:
   - Jalankan viewer di Chrome/Edge, biarkan video looping 2-3 kali.
   - Buka DevTools Network Tab: pastikan setelah putaran pertama, tidak ada lagi request transfer data video via network.
   - Simulasikan *Offline mode* di DevTools Network tab: pastikan video dan foto tetap berputar lancar tanpa putus.
2. **Uji Layout & Scaling**:
   - Uji dengan berbagai resolusi: 16:9 (1920x1080), 4:3, vertikal/portrait (9:16), dan ultra-wide.
   - Pastikan tidak ada sisi foto/video yang terpotong dan rasio aspek tetap proporsional.
