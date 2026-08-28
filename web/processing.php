<?php
session_start();

$host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "ecams";

$conn = new mysqli($host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("cannot connect db : " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $role = $_POST['role'];
    $userid = $_POST['userid'];
    $password = $_POST['password'];

    if ($role === 'student' || $role === 'monitor') {
        $table = "student";
        $id_column = "sid";} 
    elseif ($role === 'teacher' || $role === 'admin') {
        $table = "teacher";
        $id_column = "tid";} 
    else {
        die("invalid role");
    }

    $sql = "SELECT $id_column, role, pw FROM $table WHERE $id_column = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $userid);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if ($user['role'] !== $role) {
             echo "wrong role";
             exit;
        }

        if ($password === $user['pw']) {
            $_SESSION['userid'] = $user[$id_column];
            $_SESSION['role'] = $role;
            
            echo "welcome, " . htmlspecialchars($role);
            header("Location: dashboard.php");
        } else {
            echo "invalid password";
        }
    } else {
        echo "is not found in " . htmlspecialchars($table);
    }

    $stmt->close();
}
$conn->close();
?>
