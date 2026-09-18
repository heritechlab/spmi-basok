<?php

require_once "../config/config.php";

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header("Location: login.php");
    exit;
}

$username = trim($_POST['username']);
$password = trim($_POST['password']);

if ($username == "" || $password == "") {
    header("Location: login.php");
    exit;
}

$sql = "SELECT * FROM users WHERE username=? LIMIT 1";

$stmt = $conn->prepare($sql);

$stmt->bind_param("s", $username);

$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows == 0) {

    $_SESSION['error']="Username tidak ditemukan";

header("Location: login.php");

    exit;

    $_SESSION['error']="Password salah";

header("Location: login.php");

exit;
}

$user = $result->fetch_assoc();

/*
-------------------------------------------------
Jika password masih plain text
-------------------------------------------------
*/

if ($password == $user['password']) {

    $hash = password_hash($password, PASSWORD_DEFAULT);

    $update = $conn->prepare(
        "UPDATE users
         SET password=?
         WHERE id=?"
    );

    $update->bind_param(
        "si",
        $hash,
        $user['id']
    );

    $update->execute();

    $user['password'] = $hash;
}

/*
-------------------------------------------------
Verifikasi Password
-------------------------------------------------
*/

if (!password_verify($password, $user['password'])) {

    echo "<script>

    alert('Password salah');

    window.location='login.php';

    </script>";

    exit;

}

/*
-------------------------------------------------
Session
-------------------------------------------------
*/

$_SESSION['login'] = true;

$_SESSION['user_id'] = $user['id'];

$_SESSION['role_id'] = $user['role_id'];

$_SESSION['full_name'] = $user['full_name'];

$_SESSION['username'] = $user['username'];

$_SESSION['unit_id'] = $user['unit_id'] ?? null;
$_SESSION['dosen_id'] = $user['dosen_id'] ?? null;
$_SESSION['mahasiswa_id'] = $user['mahasiswa_id'] ?? null;

/*
-------------------------------------------------
Update Last Login
-------------------------------------------------
*/

$update = $conn->prepare(

"UPDATE users
SET last_login=NOW()
WHERE id=?"

);

$update->bind_param("i", $user['id']);

$update->execute();

/*
-------------------------------------------------
Redirect
-------------------------------------------------
*/
$_SESSION['success']="Selamat datang ".$user['full_name'];

if ((int) $user['must_change_password'] === 1) {
    header("Location: ganti_password_wajib.php");
    exit;
}

if ((int)$user['role_id'] === 4) {
    header("Location: ../auditee/");
    exit;
}

if ((int) $user['role_id'] === 6 || (int) $user['role_id'] === 7) {
    header("Location: ../dashboard/");
    exit;
}

header("Location: ../dashboard/");

exit;

?>