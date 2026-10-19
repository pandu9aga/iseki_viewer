# Plan Implementation: Asynchronous Dynamic Content Polling & Single-Page Transition in `iseki_viewer`

## 1. Analisis Kebutuhan
1. **Tidak Ada Full Page Reload**:
   - Menghapus `location.reload()` berkala (sebelumnya setiap 30 menit atau timer reload lainnya).
   - Menggantinya dengan **Ajax polling secara background / asynchronous** (misalnya setiap 30-60 detik).
2. **Dynamic Sync Media List**:
   - Client meminta daftar file terbaru dari server melalui AJAX (JSON endpoint).
   - Server mengembalikan daftar file beserta metadata (nama, waktu modifikasi/filemtime, tipe video/foto).
   - Jika terdapat file baru ditambahkan, dihapus, atau diubah di folder server:
     - Media baru diunduh dan dimasukkan ke dalam daftar slide secara mulus di background tanpa menginterupsi slide yang sedang diputar.
     - Media yang telah dihapus di server dibersihkan dari DOM dan cache lokal.
     - Media yang tidak berubah tetap utuh dan diputar tanpa flicker/interupsi.
3. **Single Page Slideshow Navigation**:
   - Seluruh pergantian slide (Next, Prev, Auto Next, Looping) beroperasi 100% di client-side tanpa reload halaman (sudah satu halaman DOM transisi CSS opacity).
   - Video dan gambar berpindah mulus menggunakan fade in/out tanpa jeda layar putih / reload.

---

## 2. Arsitektur Teknis

### A. Endpoint AJAX untuk Sinkronisasi Media
Di dalam `viewer.php` (atau endpoint parameter `viewer.php?ajax=1&parent=...&dir=...`):
- Jika parameter `ajax=1` dipanggil:
  - PHP memindai folder yang bersangkutan (`scandir`).
  - Menghasilkan output format JSON berisi array media:
    ```json
    {
      "success": true,
      "files": [
        {
          "name": "clip1.mp4",
          "url": "slides/videos/sample/clip1.mp4",
          "type": "video",
          "mtime": 1729321456
        }
      ]
    }
    ```
  - Mengembalikan HTTP 200 JSON dan langsung `exit()`, sehingga tidak perlu membuat file skrip baru yang terpisah.

### B. Mekanisme Background Polling di Client-side (JavaScript)
1. **Interval Polling**:
   - Jalankan `setInterval(checkUpdatesFromServer, 30000)` (setiap 30 detik).
   - Lakukan `fetch('viewer.php?ajax=1&parent=...&dir=...')`.
   - Jika browser sedang offline, error `fetch` di-catch secara diam-diam tanpa memutus slideshow yang sedang berjalan.
2. **Reconciliation / Diffing Media List**:
   - Bandingkan daftar file lama dan daftar file baru:
     - **File Baru**: Buat elemen `.slide-container` baru di DOM, lakukan pre-cache ke Cache Storage, dan tambahkan ke array slide.
     - **File Terhapus**: Hapus elemen slide dari DOM, bersihkan dari Blob URL dan cache jika memungkinkan.
     - **File Diperbarui (mtime berbeda)**: Update cache URL dan ganti source element.
   - Sinkronisasi dilakukan tanpa menghentikan pemutaran slide yang sedang aktif saat ini (`currentSlide` tetap jalan).

### C. Single Page Navigation & Transisi Mulus
1. **Peralihan Slide**:
   - Sudah berbasis manipulasi class `.slide-container.active` dengan CSS transition `opacity: 0.8s ease-in-out`.
   - Menggunakan `Blob URL` lokal sehingga pergantian slide instan tanpa delay jaringan.
2. **Penanganan Kasus Ujung (Edge Cases)**:
   - Jika semua file di folder dihapus oleh user melalui dashboard: Tampilkan pesan empty state yang elegan ("Tidak ada media dalam folder ini") tanpa merusak skrip.
   - Jika file yang sedang aktif diputar ternyata dihapus: selesaikan atau pindah ke slide berikutnya dengan aman.

---

## 3. Rencana Langkah Eksekusi

### Langkah 1: Tambahkan Handler AJAX di `viewer.php`
- Tambahkan pengecekan di awal file PHP:
  ```php
  if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
      header('Content-Type: application/json');
      // output json list files
      exit;
  }
  ```

### Langkah 2: Tambahkan Modul Background Polling di JavaScript `viewer.php`
- Fungsi `checkUpdatesFromServer()`:
  - Mengambil daftar file dari server via `fetch`.
  - Melakukan diffing dengan `currentMediaList`.
  - Jika ada perubahan: perbarui DOM slides, pre-cache file baru, dan update indikator cache di UI.
- Ganti script `setTimeout(location.reload)` dengan interval polling AJAX.

### Langkah 3: Pengujian & Validasi
1. Uji pemutaran terus menerus tanpa reload halaman.
2. Uji upload file baru atau hapus file dari tab browser lain: pastikan dalam 30 detik file baru otomatis masuk ke putaran slideshow tanpa me-reload browser.
3. Uji saat kabel LAN / koneksi dimatikan (offline): slideshow tetap berputar tanpa error dan tanpa reload yang gagal.
