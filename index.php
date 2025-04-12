<?php
if (isset($_POST["btnLogin"])) {
    require("lib/conn.php");
    $email = isset($_POST["email"]) ? $_POST["email"] : '';
    $password = isset($_POST["password"]) ? $_POST["password"] : '';

    // Adjusted regex pattern for strong password
    $pattern = '/^(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,12}$/';

    // Check if password meets the criteria
    if (!preg_match($pattern, $password)) {
        $showAlert = true;
        $errorMsg = "Password must be between 8 and 12 characters long, include at least one uppercase letter, one special character, and one number.";
    } else {
        $sql = "SELECT * FROM users WHERE email = :email";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(":email", $email);
        $stmt->execute();
        $user = $stmt->fetch();

        if ($user) {
            
        if ($user && password_verify($password, $user['password'])) {
                if ($user['role'] == 'admin') {
                    session_start();
                    $_SESSION['uid'] = $user['uid'];
                    $_SESSION['email'] = $email;
                    header("Location: mainpage.php");
                    exit();
                } elseif ($user['role'] == 'doctor'){
                    $_SESSION['uid'] = $user['uid'];
                    $_SESSION['email'] = $email;
                    header("Location: docmain.php");
                }
            
             } else {
                    // Incorrect username or password
                    echo "<script>alert('Incorrect username or password!'); history.back();</script>";
                }
        } else {
            $showAlert = true;
            $errorMsg = "No account found with this email.";
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
    <link rel="stylesheet" href="lib\images\styles.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
    <title>Login</title>

</head>


<style type="text/css">
    body{
    background-color: #E4E6C9;
    background-repeat: no-repeat;
    background-attachment: fixed;
    background-size: cover;
}
</style>



<body>

      <div class="container">

    

        <div>
        <img src="lib\images\callanglogo.png" height="325px", width="325px">
        </div>
        
        <div class="box form-box">
            
        
            
            <header>LOGIN</header>
            <!-- <?php if (!empty($errorMsg)) { ?>
            <p class="error-message"><?php echo $errorMsg; ?></p>
        <?php } ?> -->
            <form action="index.php" method="POST">
                <div class="field input">
                    <label for="email">Email</label>
                    <input type="text" name="email" id="email" autocomplete="off" required>
                </div>

                <div class="field input">
                    <label for="password">Password</label>
                    <input type="password" name="password" id="password" autocomplete="off" required>
                </div>
                <input type="checkbox" onclick="showPassword()"> Show Password
                <div class="field">
                    
                    <button type="submit" class="btn" name="btnLogin"  >Login</button>
                </div>
                
            </form>
            <div class="row">
                   <medium class="text-center">Don't have account? <a href="register.php">Sign up</a></medium>
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
    </script>
   
      </div>

</body>
<script>
    <?php if ($showAlert) { ?>
        Swal.fire({
            icon: 'error',
            title: 'Oops...',
            text: '<?php echo $errorMsg; ?>',
        });
        <?php } ?>
        
</script>
</html>