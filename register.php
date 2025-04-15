<?php
require("lib/conn.php");

// Fetch department list
$departments = [];
try {
    $stmt = $conn->prepare("SELECT dept_id, name FROM departments ORDER BY name ASC");
    $stmt->execute();
    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error fetching departments: " . $e->getMessage();
}

// Fetch ENUM values for the 'role' column from 'users' table
$roles = [];
try {
    $stmt = $conn->prepare("SHOW COLUMNS FROM users LIKE 'role'");
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row && preg_match("/^enum\((.*)\)$/", $row['Type'], $matches)) {
        $enumValues = explode(",", $matches[1]);
        foreach ($enumValues as $value) {
            $roles[] = trim($value, "'");
        }
    }
} catch (PDOException $e) {
    echo "Error fetching roles: " . $e->getMessage();
}

$showAlert = false;
$errorMsg = "";

if (isset($_POST["btnSave"])) {
    $username = $_POST["username"];
    $password = $_POST["password"];
    $role = $_POST["role"];
    $dept_id = $_POST["dept_id"];

    $password = password_hash($password, PASSWORD_BCRYPT);

    if (empty($username)) {
        $showAlert = true;
        $errorMsg = "Please enter a valid username!";
    } else {
        $sql = "INSERT INTO users (username, password, role, dept_id, status) 
                VALUES (:username, :password, :role, :dept_id, :status)";
        $values = array(
            ":username" => $username,
            ":password" => $password,
            ":role" => $role,
            ":dept_id" => $dept_id,
            ":status" => 2
        );

        $result = $conn->prepare($sql);
        $result->execute($values);

        if ($result->rowCount() > 0) {
            echo "User has been created!";
            header("Location: index.php");
            exit();
        } else {
            echo "No record has been saved!";
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HQS</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="lib/images/styles.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <style>
        body {
            background-color: #E4E6C9;
            background-repeat: no-repeat;
            background-attachment: fixed;
            background-size: cover;
        }
    </style>
</head>

<body>
<div class="container-fluid">
    <div class="row">
        <?php include('sidebar.php'); ?>

        <div class="col-md-7 col-lg-10 d-flex justify-content-center align-items-center" style="min-height: 100vh;">
            <div class="box form-box">
                <center><header>SIGN UP</header></center>
                <form action="register.php" method="POST">
                    <div class="line"></div>
                    <div class="field input">
                        <label>Username:</label><br>
                        <input type="text" name="username">
                    </div>
                    <div class="field input">
    <label>Role:</label><br>
    <select name="role">
        <option value="">Select Role</option>
        <?php foreach ($roles as $roleOption): ?>
            <option value="<?= htmlspecialchars($roleOption) ?>">
                <?= htmlspecialchars(ucfirst($roleOption)) ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>

                    <div class="field input">
                        <label>Department:</label><br>
                        <select name="dept_id">
                            <option value="">Select Department</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?= htmlspecialchars($dept['dept_id']) ?>">
                                    <?= htmlspecialchars($dept['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field input">
                        <label>Password:</label><br>
                        <input type="password" name="password" id="password" required>
                    </div>
                    <input type="checkbox" id="showPassword"> Show Password
                    <div class="field">
                        <button class="btn btn-primary" type="submit" name="btnSave">Sign Up</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('showPassword').addEventListener('change', function () {
        var passwordField = document.getElementById('password');
        passwordField.type = this.checked ? 'text' : 'password';
    });
</script>

<script>
    <?php if ($showAlert) { ?>
        Swal.fire({
            icon: 'error',
            title: 'Oops...',
            text: '<?php echo $errorMsg; ?>',
        });
    <?php } ?>
</script>
</body>
</html>
