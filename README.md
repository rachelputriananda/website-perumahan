# Perumahan RAB

Aplikasi PHP untuk **mendesain site plan perumahan** dan **menghitung RAB** (Rencana Anggaran Biaya) secara otomatis. Tanpa database, tanpa Composer: cukup PHP 8.1+.

## Menjalankan

```bash
cd perumahan-rab
php -S localhost:8000 router.php
```

Buka http://localhost:8000, lalu klik **Buat proyek contoh** untuk langsung melihat hasil lengkap.

Untuk hosting Apache/cPanel: unggah seluruh folder (PHP 8.1+). Folder `data/` harus bisa ditulis (`chmod 775`).
Untuk Nginx, blokir folder internal:

```nginx
location ~ ^/(data|lib|views|tests)/ { deny all; }
location ~ ^/(config|router)\.php$   { deny all; }
```

## Cara pakai

1. **Proyek baru** → isi nama dan ukuran lahan (panjang × lebar, meter).
2. **Tipe Rumah** → atur tipe (luas bangunan/tanah, ukuran kavling, kamar, spesifikasi: atap, dinding, lantai, plafon, closet, harga jual).
3. **Site Plan** → desain di kanvas, atau klik **⚡ Buat layout** untuk layout otomatis (jalan utama + jalan lingkungan + kavling berhadapan + fasum). Lalu **Simpan**.
4. **Harga Satuan** → ganti harga contoh dengan harga pasar/HSPK daerah Anda. **Analisa (AHSP)** menampilkan rincian koefisien tiap pekerjaan.
5. **RAB** → rekap per bagian, komposisi biaya, rincian per tipe rumah, kebutuhan material/upah/alat, kelayakan sederhana. **Unduh CSV** (buka di Excel) atau **Cetak/PDF**.

### Editor site plan

| Aksi | Cara |
|---|---|
| Alat | Pilih `V` · Jalan `J` · Kavling `K` · Fasum `F` · Geser `H` |
| Kavling | Klik = satu kavling ukuran tipe. **Seret area = diisi deretan kavling otomatis** |
| Pindah/ubah ukuran | Seret objek / seret titik handle (snap ke grid dan ke sisi objek lain) |
| Pilih banyak | `Shift`+klik, atau seret kotak di area kosong |
| Hapus / gandakan / putar | `Del` / `Ctrl+D` / `R` |
| Geser kanvas / zoom | Tahan `Spasi` atau klik kanan-seret / roda mouse |
| Urungkan / ulangi | `Ctrl+Z` / `Ctrl+Y` |
| Simpan | `Ctrl+S` |

Garis merah = objek tumpang tindih. Garis putus oranye pada kavling = belum ada tipe (tidak masuk RAB).

## Cara RAB dihitung

- **A. Persiapan & pematangan lahan**: pembersihan, pematokan, urugan, pagar, gerbang.
- **B. Infrastruktur**: sirtu, perkerasan jalan (paving/beton), drainase U-ditch, listrik TR, PJU, air bersih, sambungan per rumah. Luas jalan = gabungan semua objek jalan (persimpangan tidak dihitung dua kali).
- **C. Fasum**: harga borongan per m² menurut jenis (taman, ibadah, dll.).
- **D. Rumah**: volume dihitung parametrik dari tipe (denah 1 : 1,5, panjang dinding, pondasi, beton, atap kemiringan 30°, bukaan, finishing, sanitair, listrik) × harga satuan AHSP × jumlah kavling bertipe tersebut.
- Lalu overhead, kontingensi, PPN (semua bisa diatur di **Pengaturan**).

## Batasan penting

- **Harga dan koefisien AHSP adalah contoh/pendekatan.** Cocokkan dengan HSPK/AHSP resmi dan harga pasar lokal sebelum dipakai untuk tender, kontrak, atau pengajuan pembiayaan.
- Cocok untuk **estimasi awal / studi kelayakan**, bukan pengganti gambar kerja dan BQ detail.
- Tidak termasuk: biaya tanah (kecuali diisi di kelayakan), perizinan detail, jaringan PLN/PDAM di luar kawasan, topografi/cut-fill rinci.
- Lahan dianggap persegi panjang. Layout otomatis memakai pola sisir (kavling berhadapan di sepanjang jalan); lahan bentuk tidak beraturan perlu digambar manual.
- Satu pengguna, tanpa login. Jika dipasang di server publik, tambahkan autentikasi (mis. HTTP Basic Auth dari web server).

## Struktur

```
index.php            router + controller
config.php           konstanta (jenis fasum, warna)
router.php           router server bawaan PHP (blokir folder internal)
lib/ahsp.php         harga dasar + analisa harga satuan pekerjaan  ← sunting di sini untuk menambah item
lib/rab.php          mesin hitung RAB
lib/layout.php       generator layout, validasi, statistik site plan
lib/geometry.php     luas gabungan persegi panjang
lib/storage.php      penyimpanan proyek (JSON di data/projects/)
lib/svg.php          pratinjau site plan SVG
views/               halaman
assets/siteplan.js   editor kanvas
tests/run.php        uji mandiri mesin hitung:  php tests/run.php
```

Data proyek disimpan sebagai JSON di `data/projects/` — cukup salin folder itu untuk mencadangkan.
