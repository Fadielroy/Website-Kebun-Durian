<?php
session_start();
$host = "localhost";
$user = "root";
$pass = ""; // Ubah jadi "" jika password root KSWEB Anda kosong
$db   = "db_durian";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Koneksi Database Gagal: " . $conn->connect_error);
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $conn->real_escape_string(trim($_POST['username']));
    $password = trim($_POST['password']);

    // Cek user berdasarkan username
    $stmt = $conn->prepare("SELECT id_user, username, password, nama_lengkap, role FROM users WHERE username = ? AND status_aktif = 'Y'");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        
        // Pengecekan langsung (Plain Text) untuk memastikan tidak ada error hash
        if ($password === $row['password']) {
            $_SESSION['id_user'] = $row['id_user'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['nama_lengkap'] = $row['nama_lengkap'];
            $_SESSION['role'] = $row['role'];
            
            header("Location: index.php");
            exit;
        } else {
            $error = "Password salah! Anda memasukkan: '$password', di database: '" . $row['password'] . "'";
        }
    } else {
        $error = "Username '$username' tidak ditemukan di database!";
    }
    $stmt->close();
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login WebGIS Durian</title>
    <style>
        body { background: #1a202c; color: #fff; font-family: sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-box { background: #2d3748; padding: 30px; border-radius: 8px; width: 320px; box-shadow: 0 4px 6px rgba(0,0,0,0.5); }
        h2 { text-align: center; margin-bottom: 20px; color: #48bb78; }
        input { width: 100%; padding: 10px; margin-bottom: 15px; background: #1a202c; border: 1px solid #4a5568; color: #fff; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background: #48bb78; border: none; color: #fff; font-weight: bold; border-radius: 4px; cursor: pointer; }
        .error { background: #e53e3e; color: #fff; padding: 10px; font-size: 11px; border-radius: 4px; margin-bottom: 15px; word-break: break-all; }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>Login Durian GIS</h2>
        <?php if(!empty($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="POST">
            <label style="font-size:12px; color:#a0aec0;">Username:</label>
            <input type="text" name="username" required autocomplete="off" placeholder="owner atau pekerja_a">
            <label style="font-size:12px; color:#a0aec0;">Password:</label>
            <input type="password" name="password" required placeholder="password123">
            <button type="submit">Masuk</button>
        </form>
    </div>
</body>
</html>
