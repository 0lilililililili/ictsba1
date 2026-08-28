<?php
session_start();

if (!isset($_SESSION['userid']) || $_SESSION['role'] !== 'admin') {
    die("youre not admin");
}

$host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "ecams";

$conn = new mysqli($host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("cannot connect: " . $conn->connect_error);
}

$target_table = $_GET['table'] ?? '';
$allowed_tables = ['student', 'teacher', 'club', 'activity', 'enrollment', 'participation'];

if (!in_array($target_table, $allowed_tables)) {
    die("idk wrong table data unavailable");
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    
    if ($target_table === 'student') {
        $stmt = $conn->prepare("REPLACE INTO student (sid, sname, scls, scno, role, pw) VALUES (?, ?, ?, ?, ?, ?)");
        $scno_val = intval($_POST['scno']);
        $stmt->bind_param("sssiss", $_POST['sid'], $_POST['sname'], $_POST['scls'], $scno_val, $_POST['user_role'], $_POST['pw']);
        
    } elseif ($target_table === 'teacher') {
        $stmt = $conn->prepare("REPLACE INTO teacher (tid, tname, role, pw) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $_POST['tid'], $_POST['tname'], $_POST['user_role'], $_POST['pw']);
        
    } elseif ($target_table === 'club') {
        $stmt = $conn->prepare("REPLACE INTO club (cid, cname, tid) VALUES (?, ?, ?)");
        $tid_val = !empty($_POST['tid']) ? $_POST['tid'] : null;
        $stmt->bind_param("sss", $_POST['cid'], $_POST['cname'], $tid_val);
        
    } elseif ($target_table === 'activity') {
        $stmt = $conn->prepare("REPLACE INTO activity (aid, aname, adate, venue, attendance, cid, stuMon) VALUES (?, ?, ?, ?, NULL, ?, ?)");
        $adate_val = !empty($_POST['adate']) ? $_POST['adate'] : null;
        $venue_val = !empty($_POST['venue']) ? $_POST['venue'] : null;
        $cid_val = !empty($_POST['cid']) ? $_POST['cid'] : null;
        $stuMon_val = !empty($_POST['stuMon']) ? $_POST['stuMon'] : null;
        $stmt->bind_param("ssssss", $_POST['aid'], $_POST['aname'], $adate_val, $venue_val, $cid_val, $stuMon_val);
        
    } elseif ($target_table === 'enrollment') {
        $stmt = $conn->prepare("REPLACE INTO enrollment (sid, cid) VALUES (?, ?)");
        $stmt->bind_param("ss", $_POST['sid'], $_POST['cid']);
        
    } elseif ($target_table === 'participation') {
        $stmt = $conn->prepare("REPLACE INTO participation (sid, aid, status) VALUES (?, ?, ?)");
        $status_val = intval($_POST['status']);
        $stmt->bind_param("ssi", $_POST['sid'], $_POST['aid'], $status_val);
    }

    if (isset($stmt)) {
        if ($stmt->execute()) {
            $message = "<div style='color: #155724; background-color: #d4edda; padding: 10px; border-radius: 4px; margin-bottom: 15px;'>Data record successfully committed/overridden inside table: " . htmlspecialchars($target_table) . "</div>";
        } else {
            $message = "<div style='color: #721c24; background-color: #f8d7da; padding: 10px; border-radius: 4px; margin-bottom: 15px;'>Execution error: " . htmlspecialchars($stmt->error) . "</div>";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>admin</title>
    <style>
        body { background: #fdfdfd; margin: 40px; }
        .container { max-width: 600px; background: white; border: 1px solid #e1e4e8; border-radius: 6px; padding: 25px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-weight: bold; margin-bottom: 5px; text-transform: uppercase; font-size: 0.85em; color: #555; }
        .form-group input, .form-group select { width: 100%; padding: 8px; box-sizing: border-box; border: 1px solid #ced4da; border-radius: 4px; }
        .btn-submit { background: #28a745; color: white; border: none; padding: 10px 15px; border-radius: 4px; font-weight: bold; cursor: pointer; width: 100%; font-size: 1em; }
        .btn-submit:hover { background: #218838; }
        .btn-back { background: #007BFF; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-weight: bold; display: inline-block; }
        .btn-back:hover { background: #0069d9; }
    </style>
</head>
<body>

    <div style="margin-bottom: 20px;">
        <a href="dashboard.php" class="btn-back">← go back to data</a>
    </div>

    <div class="container">
        <h2>do anything you want yayaya <u><?php echo htmlspecialchars(strtoupper($target_table)); ?></u></h2>
        <p style="color: #666; font-size: 0.9em; margin-bottom: 20px;">
            submit to insert or rewrite row
        </p>
        
        <?php echo $message; ?>

        <form action="admin_write.php?table=<?php echo urlencode($target_table); ?>" method="POST" autocomplete="off">
            
            <?php if ($target_table === 'student'): ?>
                <div class="form-group">
                    <label>sid</label>
                    <input type="text" name="sid" required maxlength="5" placeholder="e.g. s0001">
                </div>
                <div class="form-group">
                    <label>sname</label>
                    <input type="text" name="sname" required maxlength="40" placeholder="e.g. Chan Tai Man">
                </div>
                <div class="form-group">
                    <label>scls</label>
                    <input type="text" name="scls" required maxlength="2" placeholder="e.g. 4B">
                </div>
                <div class="form-group">
                    <label>scno</label>
                    <input type="number" name="scno" required placeholder="e.g. 15">
                </div>
                <div class="form-group">
                    <label>role</label>
                    <select name="user_role" required>
                        <option value="student">student</option>
                        <option value="monitor">monitor</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>pw</label>
                    <input type="text" name="pw" required maxlength="8" placeholder="Max 8 characters">
                </div>
            <?php endif; ?>

            <?php if ($target_table === 'teacher'): ?>
                <div class="form-group">
                    <label>tid</label>
                    <input type="text" name="tid" required maxlength="5" placeholder="e.g. t0001">
                </div>
                <div class="form-group">
                    <label>tname</label>
                    <input type="text" name="tname" required maxlength="40" placeholder="e.g. Linda">
                </div>
                <div class="form-group">
                    <label>role</label>
                    <select name="user_role" required>
                        <option value="teacher">teacher</option>
                        <option value="admin">admin</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>pw</label>
                    <input type="text" name="pw" required maxlength="8" placeholder="Max 8 characters">
                </div>
            <?php endif; ?>

            <?php if ($target_table === 'club'): ?>
                <div class="form-group">
                    <label>cid</label>
                    <input type="text" name="cid" required maxlength="5" placeholder="e.g. c0001">
                </div>
                <div class="form-group">
                    <label>cname</label>
                    <input type="text" name="cname" required maxlength="40" placeholder="e.g. ict club">
                </div>
                <div class="form-group">
                    <label>tid</label>
                    <input type="text" name="tid" maxlength="5" placeholder="e.g. t0001 (Leave empty for NULL)">
                </div>
            <?php endif; ?>

            <?php if ($target_table === 'activity'): ?>
                <div class="form-group">
                    <label>aid</label>
                    <input type="text" name="aid" required maxlength="5" placeholder="e.g. a0001">
                </div>
                <div class="form-group">
                    <label>aname</label>
                    <input type="text" name="aname" required maxlength="40" placeholder="e.g. radioactivity">
                </div>
                <div class="form-group">
                    <label>adate</label>
                    <input type="datetime-local" name="adate" step ="1">
                </div>
                <div class="form-group">
                    <label>venue</label>
                    <input type="text" name="venue" maxlength="40" placeholder="e.g. 6/F physics lab">
                </div>
                <div class="form-group">
                    <label>cid</label>
                    <input type="text" name="cid" maxlength="5" placeholder="e.g. c0002">
                </div>
                <div class="form-group">
                    <label>stuMon</label>
                    <input type="text" name="stuMon" maxlength="5" placeholder="e.g. s0001">
                </div>
            <?php endif; ?>

            <?php if ($target_table === 'enrollment'): ?>
                <div class="form-group">
                    <label>sid</label>
                    <input type="text" name="sid" required maxlength="5" placeholder="e.g. s0001">
                </div>
                <div class="form-group">
                    <label>cid</label>
                    <input type="text" name="cid" required maxlength="5" placeholder="e.g. c0001">
                </div>
            <?php endif; ?>

            <?php if ($target_table === 'participation'): ?>
                <div class="form-group">
                    <label>sid</label>
                    <input type="text" name="sid" required maxlength="5" placeholder="e.g. s0001">
                </div>
                <div class="form-group">
                    <label>aid</label>
                    <input type="text" name="aid" required maxlength="5" placeholder="e.g. a0002">
                </div>
                <div class="form-group">
                    <label>status</label>
                    <select name="status" required>
                        <option value="1">attended (1)</option>
                        <option value="2">late (2)</option>
                        <option value="3">absent (3)</option>
                    </select>
                </div>
            <?php endif; ?>

            <button type="submit" class="btn-submit">overwrite</button>
        </form>
    </div>

</body>
</html>
<?php $conn->close(); ?>