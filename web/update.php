<?php
session_start();

if (!isset($_SESSION['userid']) || !isset($_SESSION['role'])) {
    header("Location: login.php");
    exit;
}

$session_id = $_SESSION['userid'];
$session_role = $_SESSION['role'];

$host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "ecams";

$conn = new mysqli($host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("cannot connect db: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action_type = $_POST['action_type'] ?? '';

    if ($action_type === 'update_profile') {
        if ($session_role === 'student' || $session_role === 'monitor') {
            $sname = $_POST['sname'];
            $scls = $_POST['scls'];
            $scno = intval($_POST['scno']);

            $stmt = $conn->prepare("UPDATE student SET sname = ?, scls = ?, scno = ? WHERE sid = ?");
            $stmt->bind_param("ssis", $sname, $scls, $scno, $session_id);
            $stmt->execute();
            $stmt->close();
        } elseif ($session_role === 'teacher') {
            $tname = $_POST['tname'];

            $stmt = $conn->prepare("UPDATE teacher SET tname = ? WHERE tid = ?");
            $stmt->bind_param("ss", $tname, $session_id);
            $stmt->execute();
            $stmt->close();
        }
    }

    elseif ($action_type === 'update_activity_attendance') {
        $aid = $_POST['aid'] ?? '';
        $statuses = $_POST['status'] ?? [];

        $authorize = false;

        if ($session_role === 'monitor') {
            $check_stmt = $conn->prepare("SELECT aid FROM activity WHERE aid = ? AND stuMon = ?");
            $check_stmt->bind_param("ss", $aid, $session_id);
            $check_stmt->execute();
            if ($check_stmt->get_result()->num_rows === 1) {
                $authorize = true;
            }
            $check_stmt->close();
        } elseif ($session_role === 'teacher') {
            $check_stmt = $conn->prepare("
                SELECT a.aid FROM activity a 
                JOIN club c ON a.cid = c.cid 
                WHERE a.aid = ? AND c.tid = ?
            ");
            $check_stmt->bind_param("ss", $aid, $session_id);
            $check_stmt->execute();
            if ($check_stmt->get_result()->num_rows === 1) {
                $authorize = true;
            }
            $check_stmt->close();
        }

        if ($authorize && !empty($statuses)) {
            $stmt = $conn->prepare("UPDATE participation SET status = ? WHERE sid = ? AND aid = ?");
            
            foreach ($statuses as $sid => $status_val) {
                $status_int = intval($status_val);
                $stmt->bind_param("iss", $status_int, $sid, $aid);
                $stmt->execute();
            }
            $stmt->close();
        } else {
            die("you aint authorized");
        }
    }

    elseif ($action_type === 'update_club') {
        $cid = $_POST['cid'] ?? '';
        $cname = $_POST['cname'] ?? '';

        if ($session_role === 'teacher') {
            $check_stmt = $conn->prepare("SELECT cid FROM club WHERE cid = ? AND tid = ?");
            $check_stmt->bind_param("ss", $cid, $session_id);
            $check_stmt->execute();
            $res = $check_stmt->get_result();
            
            if ($res->num_rows === 1) {
                $stmt = $conn->prepare("UPDATE club SET cname = ? WHERE cid = ?");
                $stmt->bind_param("ss", $cname, $cid);
                $stmt->execute();
                $stmt->close();
            } else {
                die("you aint authorized");
            }
            $check_stmt->close();
        }
    }

    header("Location: dashboard.php");
    exit;
}

$conn->close();
?>