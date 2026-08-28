<?php
session_start();

if (!isset($_SESSION['userid']) || !isset($_SESSION['role'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['userid'];
$role = $_SESSION['role'];

$host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "ecams"; 

$conn = new mysqli($host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("cannot connect: " . $conn->connect_error);
}

$student_data = null;
$teacher_data = null;
$assigned_activities = [];
$assigned_clubs = [];
$my_enrollments = [];
$my_participations = [];


if ($role === 'student' || $role === 'monitor') {
    $stmt = $conn->prepare("SELECT * FROM student WHERE sid = ?");
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $student_data = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $stmt = $conn->prepare("
        SELECT e.cid, c.cname 
        FROM enrollment e 
        JOIN club c ON e.cid = c.cid 
        WHERE e.sid = ? 
        ORDER BY e.cid
    ");
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $my_enrollments[] = $row;
    }
    $stmt->close();

    $stmt = $conn->prepare("
        SELECT p.aid, a.aname, a.adate, a.venue, p.status , a.cid
        FROM participation p 
        JOIN activity a ON p.aid = a.aid 
        WHERE p.sid = ? 
        ORDER BY a.adate DESC
    ");
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $my_participations[] = $row;
    }
    $stmt->close();

    
    if ($role === 'monitor') {
        $stmt = $conn->prepare("
            SELECT a.aid, a.aname, a.venue, p.sid, s.sname, p.status 
            FROM activity a
            LEFT JOIN participation p ON a.aid = p.aid
            LEFT JOIN student s ON p.sid = s.sid
            WHERE a.stuMon = ?
            ORDER BY a.aid, p.sid
        ");
        $stmt->bind_param("s", $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            if (!empty($row['aid'])) {
                $assigned_activities[$row['aid']]['aname'] = $row['aname'];
                $assigned_activities[$row['aid']]['venue'] = $row['venue'];
                if (!empty($row['sid'])) {
                    $assigned_activities[$row['aid']]['students'][] = [
                        'sid' => $row['sid'],
                        'sname' => $row['sname'],
                        'status' => $row['status']
                    ];
                }
            }
        }
        $stmt->close();
    }
} elseif ($role === 'teacher') {
    $stmt = $conn->prepare("SELECT * FROM teacher WHERE tid = ?");
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $teacher_data = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $stmt = $conn->prepare("
        SELECT c.cid, c.cname, a.aid, a.aname, p.sid, s.sname, p.status
        FROM club c
        LEFT JOIN activity a ON c.cid = a.cid
        LEFT JOIN participation p ON a.aid = p.aid
        LEFT JOIN student s ON p.sid = s.sid
        WHERE c.tid = ?
        ORDER BY c.cid, a.aid, p.sid
    ");
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $cid = $row['cid'];
        $aid = $row['aid'];
        $assigned_clubs[$cid]['cname'] = $row['cname'];
        if (!empty($aid)) {
            $assigned_clubs[$cid]['activities'][$aid]['aname'] = $row['aname'];
            if (!empty($row['sid'])) {
                $assigned_clubs[$cid]['activities'][$aid]['students'][] = [
                    'sid' => $row['sid'],
                    'sname' => $row['sname'],
                    'status' => $row['status']
                ];
            }
        }
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ecams</title>
    <style>
        body { background: #fdfdfd; color: #333; margin: 40px; }
        .header-bar { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #eaeaea; padding-bottom: 20px; }
        .identity-badge { font-weight: bold; background: #e0f0ff; color: #0066cc; padding: 4px 8px; border-radius: 4px; text-transform: uppercase; }
        .section-box { background: #fff; border: 1px solid #e1e4e8; border-radius: 6px; padding: 20px; margin-top: 25px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
        .btn { padding: 6px 12px; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; margin-right: 5px; }
        .btn-modify { font-family: Times; background: #007bff; color: white; }
        .btn-confirm { background: #28a745; color: white; display: none; }
        .btn-discard { background: #6c757d; color: white; display: none; }
        .btn-logout { background: #d73a49; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px; font-weight: bold; }
        .alert-box { background: #fff5f5; border-left: 4px solid #cc0000; padding: 12px; margin-bottom: 15px; font-size: 0.95em; color: #660000; }
        input[readonly], select[disabled] { background-color: #f8f9fa; border: 1px solid #ced4da; color: #495057; }
        input, select { padding: 6px; border: 1px solid #007BFF; border-radius: 4px; }
        table { border-collapse: collapse; width: 100%; margin-top: 10px; }
        th, td { border: 1px solid #dddddd; text-align: left; padding: 8px; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>

    <div class="header-bar">
        <div>
            <h2>ecams</h2>
            <p>user: <strong><?php echo htmlspecialchars($user_id); ?></strong> | role : <?php echo htmlspecialchars($role); ?></span></p>
        </div>

         <?php if (isset($_SESSION['undo_action'])): ?>
            <a href="update.php?action=perform_undo" class="btn-undo-header">⟲ Undo</a>
        <?php endif; ?>

        <a href="logout.php" class="btn-logout">log out</a>
    </div>

    <?php if ($role !== 'admin'): ?>
    <div class="section-box" id="sec-profile">
        <h3>user profile</h3>
        <form action="update.php" method="POST">
            <input type="hidden" name="action_type" value="update_profile">
            <table style="width: auto;">
                <?php if ($role === 'student' || $role === 'monitor'): ?>
                    <tr>
                        <td>sname:</td>
                        <td><input type="text" name="sname" value="<?php echo htmlspecialchars($student_data['sname'] ?? ''); ?>" readonly></td>
                    </tr>
                    <tr>
                        <td>cls:</td>
                        <td><input type="text" name="scls" value="<?php echo htmlspecialchars($student_data['scls'] ?? ''); ?>" readonly></td>
                    </tr>
                    <tr>
                        <td>cno:</td>
                        <td><input type="number" name="scno" value="<?php echo htmlspecialchars($student_data['scno'] ?? ''); ?>" readonly></td>
                    </tr>
                <?php elseif ($role === 'teacher'): ?>
                    <tr>
                        <td>tname</td>
                        <td><input type="text" name="tname" value="<?php echo htmlspecialchars($teacher_data['tname'] ?? ''); ?>" readonly></td>
                    </tr>
                <?php endif; ?>
            </table>
            <div style="margin-top: 15px;">
                <button type="button" class="btn btn-modify" onclick="enableSection('sec-profile')">modify</button>
                <button type="submit" class="btn btn-confirm">confirm</button>
                <button type="button" class="btn btn-discard" onclick="disableSection('sec-profile')">discard</button>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <?php if ($role === 'student' || $role === 'monitor'): ?>
    <div class="section-box" id='sec-my-records' style='border-top: 3px solid #007BFF'>
        <h3>enrollment</h3>
        <table>
            <thead>
                <tr><th>cid</th><th>cname</th></tr>
            </thead>
            <tbody>
                <?php if (!empty($my_enrollments)): ?>
                    <?php foreach ($my_enrollments as $enroll): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($enroll['cid']); ?></td>
                            <td><?php echo htmlspecialchars($enroll['cname']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="2" style="color: #666; ">you didnt join no club</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="section-box">
        <h3>participation</h3>
        <table>
            <thead>
                <tr><th>aid</th><th>aname</th><th>date</th><th>venue</th><th>status</th><th>cid</th></tr>
            </thead>
            <tbody>
                <?php if (!empty($my_participations)): ?>
                    <?php foreach ($my_participations as $part): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($part['aid']); ?></td>
                            <td><?php echo htmlspecialchars($part['aname']); ?></td>
                            <td><?php echo htmlspecialchars($part['adate'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($part['venue'] ?? 'N/A'); ?></td>
                            
                            <td>
                                <strong>
                                <?php 
                                    if ($part['status'] == 1) echo "<span style='color:green;'>Attended (1)</span>";
                                    elseif ($part['status'] == 2) echo "<span style='color:orange;'>Late (2)</span>";
                                    elseif ($part['status'] == 3) echo "<span style='color:red;'>Absent (3)</span>";
                                    else echo "Unknown (" . htmlspecialchars($part['status']) . ")";
                                ?>
                                </strong>
                            </td>
                            <td><?php echo htmlspecialchars($part['cid'] ?? 'NULL'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" style="color: #666; ">no record</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <?php if ($role === 'monitor' && !empty($assigned_activities)): ?>
        <?php foreach ($assigned_activities as $aid => $act): ?>
        <div class="section-box" id="sec-act-<?php echo $aid; ?>">
            <h3>assigned activity - <?php echo htmlspecialchars($act['aname']); ?> (<?php echo htmlspecialchars($aid); ?>)</h3>
            <form action="update.php" method="POST">
                <input type="hidden" name="action_type" value="update_activity_attendance">
                <input type="hidden" name="aid" value="<?php echo htmlspecialchars($aid); ?>">
                
                <p>venue <input type="text" name="venue" value="<?php echo htmlspecialchars($act['venue'] ?? ''); ?>" readonly></p>
                
                <h4>participation</h4>
                <table>
                    <thead>
                        <tr><th>sid</th><th>sname</th><th>status</th></tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($act['students'])): ?>
                            <?php foreach ($act['students'] as $stu): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($stu['sid']); ?></td>
                                <td><?php echo htmlspecialchars($stu['sname']); ?></td>
                                <td>
                                    <select name="status[<?php echo htmlspecialchars($stu['sid']); ?>]" disabled>
                                        <option value="1" <?php echo $stu['status'] == 1 ? 'selected' : ''; ?>>attended (1)</option>
                                        <option value="2" <?php echo $stu['status'] == 2 ? 'selected' : ''; ?>>late (2)</option>
                                        <option value="3" <?php echo $stu['status'] == 3 ? 'selected' : ''; ?>>absent (3)</option>
                                    </select>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="3">data unavailable for activity</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <div style="margin-top: 15px;">
                    <button type="button" class="btn btn-modify" onclick="enableSection('sec-act-<?php echo $aid; ?>')">modify</button>
                    <button type="submit" class="btn btn-confirm">confirm</button>
                    <button type="button" class="btn btn-discard" onclick="disableSection('sec-act-<?php echo $aid; ?>')">discard</button>
                </div>
            </form>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if ($role === 'teacher' && !empty($assigned_clubs)): ?>
        <?php foreach ($assigned_clubs as $cid => $club): ?>
            
            <div class="section-box" id="sec-club-<?php echo $cid; ?>">
                <h3>club data - <?php echo htmlspecialchars($club['cname']); ?> (<?php echo htmlspecialchars($cid); ?>)</h3>
                <form action="update.php" method="POST">
                    <input type="hidden" name="action_type" value="update_club">
                    <input type="hidden" name="cid" value="<?php echo htmlspecialchars($cid); ?>">
                    club <input type="text" name="cname" value="<?php echo htmlspecialchars($club['cname']); ?>" readonly>
                    <div style="margin-top: 15px;">
                        <button type="button" class="btn btn-modify" onclick="enableSection('sec-club-<?php echo $cid; ?>')">modify</button>
                        <button type="submit" class="btn btn-confirm">confirm</button>
                        <button type="button" class="btn btn-discard" onclick="disableSection('sec-club-<?php echo $cid; ?>')">discard</button>
                    </div>
                </form>
            </div>

            <?php if (!empty($club['activities'])): ?>
                <?php foreach ($club['activities'] as $aid => $act): ?>
                <div class="section-box" id="sec-teacher-act-<?php echo $aid; ?>">
                    <h3>section: <?php echo htmlspecialchars($club['cname']); ?> activity <?php echo htmlspecialchars($act['aname']); ?></h3>
                    <form action="update.php" method="POST">
                        <input type="hidden" name="action_type" value="update_activity_attendance">
                        <input type="hidden" name="aid" value="<?php echo htmlspecialchars($aid); ?>">
                        aname: <input type="text" name="aname" value="<?php echo htmlspecialchars($act['aname']); ?>" readonly>
                        
                        <table style="margin-top:15px;">
                            <thead>
                                <tr><th>sid</th><th>sname</th><th>status</th></tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($act['students'])): ?>
                                    <?php foreach ($act['students'] as $stu): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($stu['sid']); ?></td>
                                        <td><?php echo htmlspecialchars($stu['sname']); ?></td>
                                        <td>
                                            <select name="status[<?php echo htmlspecialchars($stu['sid']); ?>]" disabled>
                                                <option value="1" <?php echo $stu['status'] == 1 ? 'selected' : ''; ?>>attended (1)</option>
                                                <option value="2" <?php echo $stu['status'] == 2 ? 'selected' : ''; ?>>late (2)</option>
                                                <option value="3" <?php echo $stu['status'] == 3 ? 'selected' : ''; ?>>absent (3)</option>
                                            </select>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="3">n/a</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                        <div style="margin-top: 15px;">
                            <button type="button" class="btn btn-modify" onclick="enableSection('sec-teacher-act-<?php echo $aid; ?>')">modify</button>
                            <button type="submit" class="btn btn-confirm">confirm</button>
                            <button type="button" class="btn btn-discard" onclick="disableSection('sec-teacher-act-<?php echo $aid; ?>')">discard</button>
                        </div>
                    </form>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <?php if ($role === 'admin'): ?>
        <div class="section-box" style="border-left: 4px solid #28a745;">
            <div class="alert-box" style="background:#f0fff4; border-left-color:#28a745; color:#155724;">
                <strong>do anything you want yayayyaa</strong> rewrite or change stuff idk
            </div>

            <div style="margin-bottom: 30px;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <h4>student</h4>
                    <a href="admin.php?table=student" class="btn btn-modify" style="background:#28a745; text-decoration:none;">overwrite / insert student</a>
                </div>
                <table>
                    <thead>
                        <tr><th>sid</th><th>sname</th><th>scls</th><th>scno</th><th>role</th><th>pw</th></tr>
                    </thead>
                    <tbody>
                        <?php 
                        $adm_res = $conn->query("SELECT * FROM student ORDER BY sid");
                        while($s_row = $adm_res->fetch_assoc()): 
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($s_row['sid']); ?></td>
                            <td><?php echo htmlspecialchars($s_row['sname']); ?></td>
                            <td><?php echo htmlspecialchars($s_row['scls']); ?></td>
                            <td><?php echo htmlspecialchars($s_row['scno']); ?></td>
                            <td><?php echo htmlspecialchars($s_row['role']); ?></td>
                            <td><code><?php echo htmlspecialchars($s_row['pw']); ?></code></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <div style="margin-bottom: 30px;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <h4>teacher personal data</h4>
                    <a href="admin.php?table=teacher" class="btn btn-modify" style="background:#28a745; text-decoration:none;">overwrite / insert teacher</a>
                </div>
                <table>
                    <thead>
                        <tr><th>tid</th><th>tname</th><th>role</th><th>pw</th></tr>
                    </thead>
                    <tbody>
                        <?php 
                        $adm_res = $conn->query("SELECT * FROM teacher ORDER BY tid");
                        while($t_row = $adm_res->fetch_assoc()): 
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($t_row['tid']); ?></td>
                            <td><?php echo htmlspecialchars($t_row['tname']); ?></td>
                            <td><?php echo htmlspecialchars($t_row['role']); ?></td>
                            <td><code><?php echo htmlspecialchars($t_row['pw']); ?></code></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <div style="margin-bottom: 30px;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <h4>club</h4>
                    <a href="admin.php?table=club" class="btn btn-modify" style="background:#28a745; text-decoration:none;">overwrite / insert club</a>
                </div>
                <table>
                    <thead>
                        <tr><th>cid</th><th>cname</th><th>tid</th></tr>
                    </thead>
                    <tbody>
                        <?php 
                        $adm_res = $conn->query("SELECT * FROM club ORDER BY cid");
                        while($c_row = $adm_res->fetch_assoc()): 
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($c_row['cid']); ?></td>
                            <td><?php echo htmlspecialchars($c_row['cname']); ?></td>
                            <td><?php echo htmlspecialchars($c_row['tid'] ?? 'NULL'); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <div style="margin-bottom: 30px;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <h4>activity</h4>
                    <a href="admin.php?table=activity" class="btn btn-modify" style="background:#28a745; text-decoration:none;">overwrite / insert activity</a>
                </div>
                <table>
                    <thead>
                        <tr><th>aid</th><th>aname</th><th>adate</th><th>venue</th><th>attendance</th><th>cid</th><th>stuMon</th></tr>
                    </thead>
                    <tbody>
                        <?php 
                        $adm_res = $conn->query("SELECT * FROM activity ORDER BY aid");
                        while($a_row = $adm_res->fetch_assoc()): 
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($a_row['aid']); ?></td>
                            <td><?php echo htmlspecialchars($a_row['aname']); ?></td>
                            <td><?php echo htmlspecialchars($a_row['adate'] ?? 'NULL'); ?></td>
                            <td><?php echo htmlspecialchars($a_row['venue'] ?? 'NULL'); ?></td>
                            <td><?php echo $a_row['attendance'] !== null ? (floatval($a_row['attendance']) * 100) . '%' : 'NULL'; ?></td>
                            <td><?php echo htmlspecialchars($a_row['cid'] ?? 'NULL'); ?></td>
                            <td><?php echo htmlspecialchars($a_row['stuMon'] ?? 'NULL'); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <div style="margin-bottom: 30px;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <h4>enrollment</h4>
                    <a href="admin.php?table=enrollment" class="btn btn-modify" style="background:#28a745; text-decoration:none;">overwrite / insert enrollment</a>
                </div>
                <table>
                    <thead>
                        <tr><th>sid</th><th>cid</th></tr>
                    </thead>
                    <tbody>
                        <?php 
                        $adm_res = $conn->query("SELECT * FROM enrollment ORDER BY sid, cid");
                        while($e_row = $adm_res->fetch_assoc()): 
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($e_row['sid'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($e_row['cid'] ?? ''); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <div style="margin-bottom: 30px;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <h4>participation</h4>
                    <a href="admin.php?table=participation" class="btn btn-modify" style="background:#28a745; text-decoration:none;">overwrite / insert participation</a>
                </div>
                <table>
                    <thead>
                        <tr><th>sid</th><th>aid</th><th>status</th></tr>
                    </thead>
                    <tbody>
                        <?php 
                        $adm_res = $conn->query("SELECT * FROM participation ORDER BY aid, sid");
                        while($p_row = $adm_res->fetch_assoc()): 
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($p_row['sid'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($p_row['aid'] ?? ''); ?></td>
                            <td>
                                <?php 
                                $st = $p_row['status'];
                                if ($st == 1) echo "attended (1)";
                                elseif ($st == 2) echo "late (2)";
                                elseif ($st == 3) echo "absent (3)";
                                else echo "NULL (" . htmlspecialchars($st ?? 'NULL') . ")";
                                ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

        </div>
    <?php endif; ?>
    <script>
        function enableSection(sectionId) {
            const section = document.getElementById(sectionId);
            
            const inputs = section.querySelectorAll('input');
            inputs.forEach(input => {
                if (input.name !== 'action_type' && input.name !== 'aid' && input.name !== 'cid') {
                    input.removeAttribute('readonly');
                }
            });
            
            const selects = section.querySelectorAll('select');
            selects.forEach(select => select.removeAttribute('disabled'));
            
            section.querySelector('.btn-modify').style.display = 'none';
            section.querySelector('.btn-confirm').style.display = 'inline-block';
            section.querySelector('.btn-discard').style.display = 'inline-block';
        }

        function disableSection(sectionId) {
            window.location.reload();
        }
    </script>
</body>
</html>