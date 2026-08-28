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

if (isset($_GET['action']) && $_GET['action'] === 'perform_undo' && isset($_SESSION['undo_action'])) {
    $undo = $_SESSION['undo_action'];
    $tbl = $undo['table'];
    $data = $undo['data'];
    
    if ($tbl === 'profile_student') {
        $stmt = $conn->prepare("UPDATE student SET sname = ?, scls = ?, scno = ? WHERE sid = ?");
        $stmt->bind_param("ssis", $data['sname'], $data['scls'], $data['scno'], $data['sid']);
        $stmt->execute(); $stmt->close();
    } elseif ($tbl === 'profile_teacher') {
        $stmt = $conn->prepare("UPDATE teacher SET tname = ? WHERE tid = ?");
        $stmt->bind_param("ss", $data['tname'], $data['tid']);
        $stmt->execute(); $stmt->close();
    } elseif ($tbl === 'attendance_batch') {
        $stmt = $conn->prepare("UPDATE participation SET status = ? WHERE sid = ? AND aid = ?");
        foreach ($data as $row) {
            $stmt->bind_param("iss", $row['status'], $row['sid'], $row['aid']);
            $stmt->execute();
        }
        $stmt->close();
    } elseif ($tbl === 'club_update') {
        $stmt = $conn->prepare("UPDATE club SET cname = ? WHERE cid = ?");
        $stmt->bind_param("ss", $data['cname'], $data['cid']);
        $stmt->execute(); $stmt->close();
    }
    unset($_SESSION['undo_action']);
    header("Location: dashboard.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action_type = $_POST['action_type'] ?? '';

    if ($action_type === 'update_profile') {
        if ($session_role === 'student' || $session_role === 'monitor') {

            $chk = $conn->prepare("SELECT * FROM student WHERE sid = ?");
            $chk->bind_param("s", $session_id); $chk->execute();
            $old = $chk->get_result()->fetch_assoc(); $chk->close();
            if ($old) $_SESSION['undo_action'] = ['table' => 'profile_student', 'data' => $old];
           
            $sname = $_POST['sname'];
            $scls = $_POST['scls'];
            $scno = intval($_POST['scno']);

            $stmt = $conn->prepare("UPDATE student SET sname = ?, scls = ?, scno = ? WHERE sid = ?");
            $stmt->bind_param("ssis", $sname, $scls, $scno, $session_id);
            $stmt->execute();
            $stmt->close();
        } elseif ($session_role === 'teacher') {

            $chk = $conn->prepare("SELECT * FROM teacher WHERE tid = ?");
            $chk->bind_param("s", $session_id); $chk->execute();
            $old = $chk->get_result()->fetch_assoc(); $chk->close();
            if ($old) $_SESSION['undo_action'] = ['table' => 'profile_teacher', 'data' => $old];

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

            $old_rows = [];
            $chk = $conn->prepare("SELECT * FROM participation WHERE aid = ?");
            $chk->bind_param("s", $aid); $chk->execute();
            $res = $chk->get_result();
            while($r = $res->fetch_assoc()) { $old_rows[] = $r; }
            $chk->close();
            $_SESSION['undo_action'] = ['table' => 'attendance_batch', 'data' => $old_rows];
            
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
                
                $chk_old = $conn->prepare("SELECT * FROM club WHERE cid = ?");
                $chk_old->bind_param("s", $cid); $chk_old->execute();
                $old_club = $chk_old->get_result()->fetch_assoc(); $chk_old->close();
                $_SESSION['undo_action'] = ['table' => 'club_update', 'data' => $old_club];
                
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