<?php
// cari_data.php - Modul Pencarian Universal WebGIS
$host = "localhost";
$user = "root";
$pass = ""; // Sesuaikan jika password kosong
$db   = "db_durian";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    echo "<p style='color:#e53e3e; font-size:11px;'>Koneksi database gagal.</p>";
    exit;
}

$keyword = isset($_GET['q']) ? $conn->real_escape_string(trim($_GET['q'])) : '';

if (strlen($keyword) < 2) {
    echo "<p style='color:#a0aec0; font-size:11px; text-align:center; padding:10px;'>Ketik minimal 2 karakter untuk mulai mencari...</p>";
    exit;
}

// Array untuk menampung hasil pencarian
$hasil_pencarian = [];

// 1. Cari di Tabel Pohon (Nomor Pohon / Varietas / Petak)
$sql_pohon = "SELECT nomor_pohon AS judul, CONCAT('Pohon Durian - Varietas: ', varietas, ' (Blok: ', petak, ')') AS sub, latitude AS lat, longitude AS lon, 'pohon' AS tipe, nomor_pohon AS target_id FROM pohon WHERE nomor_pohon LIKE '%$keyword%' OR varietas LIKE '%$keyword%' OR petak LIKE '%$keyword%' LIMIT 5";
$res = $conn->query($sql_pohon);
while($row = $res->fetch_assoc()) { $hasil_pencarian[] = $row; }

// 2. Cari di Tabel Petak Polygon (Blok Kebun)
$sql_petak = "SELECT nama_petak AS judul, CONCAT('Blok / Petak Kebun - Warna: ', warna) AS sub, NULL AS lat, NULL AS lon, 'petak' AS tipe, nama_petak AS target_id FROM petak_polygon WHERE nama_petak LIKE '%$keyword%' LIMIT 5";
$res = $conn->query($sql_petak);
while($row = $res->fetch_assoc()) { $hasil_pencarian[] = $row; }

// 3. Cari di Tabel Pekerja (Nama Pekerja)
$sql_pekerja = "SELECT nama AS judul, CONCAT('Pekerja - ID: ', id_pekerja) AS sub, NULL AS lat, NULL AS lon, 'pekerja' AS tipe, id_pekerja AS target_id FROM pekerja WHERE nama LIKE '%$keyword%' OR id_pekerja LIKE '%$keyword%' LIMIT 5";
$res = $conn->query($sql_pekerja);
while($row = $res->fetch_assoc()) { $hasil_pencarian[] = $row; }

// 4. Cari di Tabel Pekerjaan Lapangan (Jenis Pekerjaan / Keterangan)
$sql_kerja = "SELECT jenis_pekerjaan AS judul, CONCAT('Aktivitas (', target_tipe, ': ', target_id, ') - ', tanggal_mulai) AS sub, NULL AS lat, NULL AS lon, 'pekerjaan' AS tipe, target_id AS target_id FROM pekerjaan_lapangan WHERE jenis_pekerjaan LIKE '%$keyword%' OR keterangan LIKE '%$keyword%' LIMIT 5";
$res = $conn->query($sql_kerja);
while($row = $res->fetch_assoc()) { $hasil_pencarian[] = $row; }

// 5. Cari di Zona Irigasi
$sql_zona = "SELECT nama_zona AS judul, CONCAT('Zona Irigasi - Sumber Air: ', sumber_air) AS sub, NULL AS lat, NULL AS lon, 'zona' AS tipe, id_zona AS target_id FROM zona_irigasi WHERE nama_zona LIKE '%$keyword%' OR sumber_air LIKE '%$keyword%' LIMIT 5";
$res = $conn->query($sql_zona);
while($row = $res->fetch_assoc()) { $hasil_pencarian[] = $row; }

$conn->close();

// Tampilkan Hasil ke HTML Sidebar
if (empty($hasil_pencarian)) {
    echo "<p style='color:#f6ad55; font-size:11px; text-align:center; padding:10px;'>Tidak ditemukan data untuk kata kunci: '<b>" . htmlspecialchars($keyword) . "</b>'</p>";
} else {
    echo "<p style='font-size:10px; color:#a0aec0; margin-bottom:6px;'>Ditemukan " . count($hasil_pencarian) + 0 . " hasil:</p>";
    foreach($hasil_pencarian as $item) {
        $lat_val = !is_null($item['lat']) ? $item['lat'] : 'null';
        $lon_val = !is_null($item['lon']) ? $item['lon'] : 'null';
        echo '<div onclick="fokusKeObjek(\'' . $item['tipe'] . '\', \'' . $item['target_id'] . '\', ' . $lat_val . ', ' . $lon_val . ')" style="background:#1a202c; padding:8px; margin-bottom:6px; border-radius:4px; border:1px solid #4a5568; cursor:pointer; transition:background 0.2s;">';
        echo '<div style="font-size:11px; font-weight:bold; color:#48bb78;">' . htmlspecialchars($item['judul']) . '</div>';
        echo '<div style="font-size:10px; color:#cbd5e0;">' . htmlspecialchars($item['sub']) . '</div>';
        echo '</div>';
    }
}
?>
