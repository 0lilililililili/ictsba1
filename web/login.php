<!DOCTYPE html>
<html>
<head>
    <title>login</title>
</head>
<body>
    <form action="processing.php" method="POST">
    <table border="1" cellpadding="8" style="border-collapse: collapse; margin-bottom: 15px;">
        <thead>
        <tr>
            <th>select</th>
            <th>user role</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td><input type="radio" id="student" name="role" value="student" checked></td>
            <td><label for="student">student</label></td>
        </tr>
        <tr>
            <td><input type="radio" id="monitor" name="role" value="monitor"></td>
            <td><label for="monitor">student monitor</label></td>
        </tr>
        <tr>
            <td><input type="radio" id="teacher" name="role" value="teacher"></td>
            <td><label for="teacher">teacher</label></td>
        </tr>
        <tr>
            <td><input type="radio" id="admin" name="role" value="admin"></td>
            <td><label for="admin">admin</label></td>
        </tr>
        </tbody>
    </table>

    <div style="margin-bottom: 10px;">
        <label for="userid" style="display: inline-block; width: 80px;">user id :</label>
        <input type="text" id="user_id" name="userid" required placeholder="enter id :">
    </div>

    <div style="margin-bottom: 15px;">
        <label for="password" style="display: inline-block; width: 80px;">password:</label>
        <input type="password" id="password" name="password" required placeholder="enter password">
    </div>

    <button type="submit">log in</button>

    </form>
</body>
</html>