<?php
// get_riwayat.php - Mengambil riwayat siklus pohon secara dinamis
$host = "localhost"; 
$user = "root"; 
$pass = ""; 
$db   = "db_durian";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    echo "Koneksi database gagal.";
    exit;
}

$no_pohon = isset($_GET['nomor']) ? $conn->real_escape_string($_GET['nomor']) : '';

if(!empty($no_pohon)) {
    $res = $conn->query("SELECT * FROM riwayat_pohon WHERE nomor_pohon = '$no_pohon' ORDER BY tahun_tanam DESC");
    
    if ($res && $res->num_rows > 0) {
        echo "<div style='font-size:11px; max-height:120px; overflow-y:auto; margin-top:5px; border-top:1px solid #ddd; padding-top:5px;'>";
        echo "<b>📜 Riwayat Siklus Pohon:</b><ul style='padding-left: 15px; margin: 3px 0;'>";
        while($row = $res->fetch_assoc()) {
            $status_teks = $row['tahun_selesai'] ? "Selesai ({$row['tahun_selesai']})" : "Aktif";
            echo "<li><b>{$row['varietas']}</b> ({$tahun = $row['tahun_tanam']} - " . ($row['tahun_selesai'] ?? 'Sekarang') . ")<br><span style='color:#666;'>Status: $status_teks | Ket: {$row['keterangan']}</span></li>";
        }
        echo "</ul></div>";
    } else {
        echo "<div style='font-size:11px; color:#666; margin-top:5px;'><i>Belum ada data riwayat siklus tercatat.</i></div>";
    }
}
$conn->close();
?>
