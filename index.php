<?php
if (isset($_POST["btnLogin"])) {
    require("lib/conn.php");
    $username = isset($_POST["username"]) ? $_POST["username"] : '';
    $password = isset($_POST["password"]) ? $_POST["password"] : '';

    if (empty($username)) {
        $showAlert = true;
        $errorMsg = "Please enter a valid username!";
    } else {
        $sql = "SELECT * FROM users WHERE username = :username";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(":username", $username);
        $stmt->execute();
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_start();
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $username;

            if ($user['role'] == 'Admin') {
                header("Location: mainpage.php");
                exit();
            } elseif ($user['role'] == 'Admitting') {
                header("Location: queue_display.php");
                exit();
            } elseif ($user['role'] == 'Nurse') {
                header("Location: queue_lab.php");
                exit();
            }
        } else {
            echo "<script>alert('Incorrect username or password!'); history.back();</script>";
        }
    }
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="lib/images/styles.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <title>Login</title>
    <style>
       body {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    margin: 0;
    background-color: rgba(255, 255, 255, 0.8); /* Slightly transparent */
    padding: 0;
    font-family: Arial, sans-serif;
    overflow-x: hidden;
}


.container {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    width: 100%;
    max-width: 500px;
    padding: 20px;
    position: relative;
}

.logo {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    z-index: 0;
}

.logo img {
    height: 750px;
    width: 750px;
    opacity: .55;
}

.H1 {
    font-size: 2.5rem;
    font-weight: bold;
    margin-bottom: 20px;
    z-index: 1;
    color: #1d3557;
}

.form-box {
    background-color: rgba(255, 255, 255, 0.65); /* More transparent white */
    backdrop-filter: blur(5px);
    border-radius: 15px;
    padding: 30px 25px;
    width: 100%;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    z-index: 1;
}

.form-box header {
    color: #1d3557;
    font-size: 28px;
    font-weight: bold;
    text-align: center;
    margin-bottom: 25px;
}

.field {
    margin-bottom: 20px;
}

.field label {
    display: block;
    margin-bottom: 8px;
    color: #1d3557;
    font-weight: 500;
}

.field input[type="text"],
.field input[type="password"] {
    width: 100%;
    padding: 12px 15px;
    border: 1px solid rgba(29, 53, 87, 0.3);
    border-radius: 8px;
    background-color: rgba(255, 255, 255, 0.7);
    transition: all 0.3s ease;
}

.field input:focus {
    outline: none;
    border-color: #457b9d;
    box-shadow: 0 0 0 3px rgba(69, 123, 157, 0.2);
}

.checkbox-field {
    display: flex;
    align-items: center;
    margin-bottom: 20px;
    color: #1d3557;
    font-size: 0.9rem;
}

.checkbox-field input[type="checkbox"] {
    margin-right: 8px;
    transform: scale(1.2);
    cursor: pointer;
}

.btn {
    width: 100%;
    padding: 12px;
    background-color: #1d3557;
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: bold;
    transition: background-color 0.3s ease;
}

.btn:hover {
    background-color: #457b9d;
}

.text-center {
    margin-top: 15px;
    font-size: 0.9rem;
    text-align: center;
}

    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <img src="lib/images/callanglogo.png" alt="Logo">
        </div>

        <div class="H1">HQS</div>

        <div class="box form-box">
            <header>LOGIN</header>
            <form action="index.php" method="POST">
                <div class="field input">
                    <label for="username">Username</label>
                    <input type="text" name="username" id="username" autocomplete="off" required>
                </div>

                <div class="field input">
                    <label for="password">Password</label>
                    <input type="password" name="password" id="password" autocomplete="off" required>
                </div>

                <div class="checkbox-field">
                    <input type="checkbox" onclick="showPassword()" id="showPass">
                    <label for="showPass">Show Password</label>
                </div>

                <div class="field">
                    <button type="submit" class="btn" name="btnLogin">Login</button>
                </div>
            </form>
            <div class="row">
                <medium class="text-center">Don't have an account? <a href="register.php">Sign up</a></medium>
            </div>
        </div>
    </div>

    <script>
        function showPassword() {
            var passwordField = document.getElementById("password");
            if (passwordField.type === "password") {
                passwordField.type = "text";
            } else {
                passwordField.type = "password";
            }
        }

        <?php if (!empty($showAlert)) { ?>
        Swal.fire({
            icon: 'error',
            title: 'Oops...',
            text: '<?php echo $errorMsg; ?>',
        });
        <?php } ?>
    </script>
</body>
</html>