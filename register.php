<?php
require("lib/conn.php");

$departments = [];
try {
    $stmt = $conn->prepare("SELECT dept_id, name FROM departments ORDER BY name ASC");
    $stmt->execute();
    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error fetching departments: " . $e->getMessage();
}

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
$showSuccess = false;

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
        $check = $conn->prepare("SELECT * FROM users WHERE username = :username");
        $check->execute([':username' => $username]);
        if ($check->rowCount() > 0) {
            $showAlert = true;
            $errorMsg = "User already exists!";
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
                $showSuccess = true;
            } else {
                $showAlert = true;
                $errorMsg = "No record has been saved!";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - HQS</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background: linear-gradient(to right, #dff1f9, #c4e0e5);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            overflow-x: hidden;
        }

        .container {
            max-width: 480px;
            padding: 20px;
            position: relative;
            z-index: 1;
        }

        .form-box {
            background-color: rgba(255, 255, 255, 0.85);
            padding: 30px 25px;
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }

        .form-box header {
            font-size: 24px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 25px;
            color: #1d3557;
        }

        .field {
            margin-bottom: 20px;
        }

        .field label {
            display: block;
            margin-bottom: 6px;
            font-weight: bold;
            color: #1d3557;
        }

        input[type="text"],
        input[type="password"],
        select {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 16px;
        }

        input[type="text"]:focus,
        input[type="password"]:focus,
        select:focus {
            outline: none;
            border-color: #457b9d;
            box-shadow: 0 0 5px rgba(69, 123, 157, 0.4);
        }

        .checkbox-field {
            margin-bottom: 20px;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            color: #1d3557;
        }

        .checkbox-field input {
            margin-right: 10px;
            transform: scale(1.2);
        }

        .btn {
            width: 100%;
            background-color: #1d3557;
            color: white;
            border: none;
            padding: 12px;
            font-weight: bold;
            border-radius: 8px;
            font-size: 16px;
            transition: background-color 0.3s ease;
        }

        .btn:hover {
            background-color: #457b9d;
        }

        .text-center {
            text-align: center;
            margin-top: 15px;
        }

        .text-center a {
            color: #1d3557;
            font-weight: bold;
            text-decoration: none;
        }

        .text-center a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-box">
            <header>SIGN UP</header>
            <form action="register.php" method="POST">
                <div class="field">
                    <label for="username">Username:</label>
                    <input type="text" name="username" id="username" required>
                </div>

                <div class="field">
                    <label for="role">Role:</label>
                    <select name="role" id="role" required>
                        <option value="">Select Role</option>
                        <?php foreach ($roles as $roleOption): ?>
                            <option value="<?= htmlspecialchars($roleOption) ?>">
                                <?= htmlspecialchars(ucfirst($roleOption)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field">
                    <label for="dept_id">Department:</label>
                    <select name="dept_id" id="dept_id" required>
                        <option value="">Select Department</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?= htmlspecialchars($dept['dept_id']) ?>">
                                <?= htmlspecialchars($dept['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field">
                    <label for="password">Password:</label>
                    <input type="password" name="password" id="password" required>
                </div>

                <div class="checkbox-field">
                    <input type="checkbox" onclick="togglePassword()" id="showPass">
                    <label for="showPass">Show Password</label>
                </div>

                <div class="field">
                    <button type="submit" class="btn" name="btnSave">Sign Up</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function togglePassword() {
            const pass = document.getElementById("password");
            pass.type = pass.type === "password" ? "text" : "password";
        }

        <?php if ($showSuccess): ?>
            Swal.fire({
                icon: 'success',
                title: 'Account Created!',
                text: 'User has been created successfully.',
                confirmButtonText: 'Go to Home'
            }).then(() => {
                window.location.href = 'index.php';
            });
        <?php elseif ($showAlert): ?>
            Swal.fire({
                icon: 'error',
                title: 'Oops...',
                text: '<?= $errorMsg; ?>'
            });
        <?php endif; ?>
    </script>
</body>
</html>