<?php
session_start();
if (!isset($_SESSION['role'])) {
    header("Location: login.php");
    exit;
}

$role_user   = $_SESSION['role']; // 'owner', 'admin', atau 'pekerja'
$nama_user   = $_SESSION['nama_lengkap'];

$host = "localhost";
$user = "root";
$pass = ""; // Sesuaikan jika password root KSWEB kosong ("")
$db   = "db_durian";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// 1. Status Checklist Layer (DI-SET FALSE / NONAKTIF SECARA DEFAULT DI AWAL)
$show_petak  = isset($_GET['layer_petak']) ? true : false;
$show_pohon  = isset($_GET['layer_pohon']) ? true : false;
$show_kerja  = isset($_GET['layer_kerja']) ? true : false;
$show_zona   = isset($_GET['layer_zona']) ? true : false;
$show_buah   = isset($_GET['layer_buah']) ? true : false;

// Jika baru pertama kali dibuka (belum ada parameter GET), semua layer mati/nonaktif
if (empty($_GET)) {
    $show_petak = $show_pohon = $show_kerja = $show_zona = $show_buah = false;
}

// 2. Parameter Filter
$filter_petak        = isset($_GET['petak']) ? $_GET['petak'] : 'ALL';
$filter_tahun_tanam  = isset($_GET['tahun_tanam']) ? $_GET['tahun_tanam'] : 'ALL';
$filter_pekerja      = isset($_GET['pekerja']) ? $_GET['pekerja'] : 'ALL';
$filter_kerja_target = isset($_GET['kerja_target']) ? $_GET['kerja_target'] : 'ALL';
$filter_start        = isset($_GET['start']) ? $_GET['start'] : '';
$filter_end          = isset($_GET['end']) ? $_GET['end'] : '';

if ($role_user == 'pekerja') {
    $filter_varietas = ['ALL'];
} else {
    $filter_varietas = isset($_GET['varietas']) ? $_GET['varietas'] : ['ALL'];
}

// --- REFERENSI DATA ---
$list_varietas = [];
$res_var = $conn->query("SELECT DISTINCT varietas FROM pohon");
while($row = $res_var->fetch_assoc()) { $list_varietas[] = $row['varietas']; }

$list_tahun = [];
$res_thn = $conn->query("SELECT DISTINCT tahun_tanam FROM pohon ORDER BY tahun_tanam DESC");
while($row = $res_thn->fetch_assoc()) { $list_tahun[] = $row['tahun_tanam']; }

$list_pekerja = [];
$res_pkj = $conn->query("SELECT id_pekerja, nama FROM pekerja");
while($row = $res_pkj->fetch_assoc()) { $list_pekerja[] = $row; }

$list_petak = [];
$res_ptk = $conn->query("SELECT nama_petak FROM petak_polygon");
while($row = $res_ptk->fetch_assoc()) { $list_petak[] = $row['nama_petak']; }

// --- QUERY DATA UTAMA (Hanya diambil jika layer terkait diaktifkan user) ---

// A. Data Petak Polygon
$data_polygon = [];
if ($show_petak) {
    $sql_poly = "SELECT * FROM petak_polygon";
    if ($filter_petak != 'ALL') {
        $sql_poly .= " WHERE nama_petak = '" . $conn->real_escape_string($filter_petak) . "'";
    }
    $res_polygon = $conn->query($sql_poly);
    while($row = $res_polygon->fetch_assoc()) { $data_polygon[] = $row; }
}

// B. Data Pohon Durian
$data_pohon = [];
if ($show_pohon || $show_buah) {
    $sql_pohon = "SELECT * FROM pohon WHERE 1=1";
    if ($filter_petak != 'ALL') {
        $sql_pohon .= " AND petak = '" . $conn->real_escape_string($filter_petak) . "'";
    }
    if ($role_user != 'pekerja' && !empty($filter_varietas) && !in_array('ALL', $filter_varietas)) {
        $escaped_vars = array_map([$conn, 'real_escape_string'], $filter_varietas);
        $sql_pohon .= " AND varietas IN ('" . implode("','", $escaped_vars) . "')";
    }
    if ($filter_tahun_tanam != 'ALL') {
        $sql_pohon .= " AND tahun_tanam = " . intval($filter_tahun_tanam);
    }
    $res_pohon = $conn->query($sql_pohon);
    while($row = $res_pohon->fetch_assoc()) { $data_pohon[] = $row; }
}

// C. Data Zona Irigasi
$data_zona = [];
if ($show_zona) {
    $res_zona = $conn->query("SELECT * FROM zona_irigasi");
    while($row = $res_zona->fetch_assoc()) { $data_zona[] = $row; }
}

// D. Data Pekerjaan Lapangan
$data_pekerjaan = [];
if ($show_kerja) {
    if ($role_user == 'owner' || $role_user == 'admin') {
        $sql_pekerjaan = "SELECT pl.*, p.nama AS nama_pekerja_lengkap FROM pekerjaan_lapangan pl JOIN pekerja p ON pl.id_pekerja = p.id_pekerja WHERE 1=1";
    } else {
        $sql_pekerjaan = "SELECT pl.id, pl.target_tipe, pl.target_id, pl.jenis_pekerjaan, pl.tanggal_mulai, pl.tanggal_selesai, pl.keterangan, 'Sistem Kebun' AS nama_pekerja_lengkap FROM pekerjaan_lapangan pl WHERE 1=1";
    }

    if ($filter_petak != 'ALL') {
        $sql_pekerjaan .= " AND (
            (pl.target_tipe = 'PETAK' AND pl.target_id = '" . $conn->real_escape_string($filter_petak) . "') OR 
            (pl.target_tipe = 'POHON' AND pl.target_id IN (SELECT nomor_pohon FROM pohon WHERE petak = '" . $conn->real_escape_string($filter_petak) . "')) OR
            (pl.target_tipe = 'MASSAL_BLOK' AND pl.target_id LIKE '%" . $conn->real_escape_string($filter_petak) . "%')
        )";
    }
    if ($role_user != 'pekerja' && $filter_pekerja != 'ALL') {
        $sql_pekerjaan .= " AND pl.id_pekerja = '" . $conn->real_escape_string($filter_pekerja) . "'";
    }
    if ($filter_kerja_target != 'ALL') {
        $sql_pekerjaan .= " AND pl.target_tipe = '" . $conn->real_escape_string($filter_kerja_target) . "'";
    }
    if (!empty($filter_start) && !empty($filter_end)) {
        $s = $conn->real_escape_string($filter_start);
        $e = $conn->real_escape_string($filter_end);
        $sql_pekerjaan .= " AND (pl.tanggal_mulai <= '$e' AND pl.tanggal_selesai >= '$s')";
    }

    $res_pekerjaan = $conn->query($sql_pekerjaan);
    while($row = $res_pekerjaan->fetch_assoc()) {
        $data_pekerjaan[$row['target_id']][] = $row;
    }
}

// E. Data Pohon Berbuah (Yield)
$data_buah = [];
if ($show_buah && $role_user != 'pekerja') {
    $sql_buah = "SELECT pb.*, p.varietas, p.petak FROM pohon_berbuah pb JOIN pohon p ON pb.nomor_pohon = p.nomor_pohon WHERE 1=1";
    $res_buah = $conn->query($sql_buah);
    while($row = $res_buah->fetch_assoc()) {
        $data_buah[$row['nomor_pohon']][] = $row;
    }
}

$conn->close();
include 'peta_interaktif.php';
?>
