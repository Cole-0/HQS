<?php


 $pattern = '/^(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,12}$/'; // Regex pattern for password validation

 if (isset($_POST["btnSave"])) {
     require("lib/conn.php");
     $username = $_POST["username"];
     $email = $_POST["email"];
     $password = $_POST["password"]; // Get the password before validation
     $role = $_POST["role"];
     $dept_id = $_POST["dept_id"];


     // Validate the password
     if (!preg_match($pattern, $password)) {
        $showAlert = true;
        $errorMsg = "Password must contain at least one uppercase letter, one number, one special character, and be between 8 to 12 characters long.";
     } else {
         $password = password_hash($password, PASSWORD_BCRYPT); // Hash the password if it passes validation
     }

     if (empty($email)) {
         $showAlert = true;
         $errorMsg = "Please enter a valid email!";
     } else if (!empty($errorMsg)) {
         $showAlert = true;
         $errorMsg = "Password must contain at least one uppercase letter, one number, one special character, and be between 8 to 12 characters long";
         
     } else {
         $sql = "INSERT INTO users (username, email, password, role, dept_id, status) VALUES (:username, :email, :password, :role, :dept_id, :status)";
         $values = array(
            ":username" => $username,
             ":email" => $email,
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
    <link rel="stylesheet" href="lib\images\styles.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
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
                        <label>Email:</label><br>
                        <input type="text" name="email">
                    </div>
                    <div class="field input">
                        <label>Password:</label><br>
                        <input type="password" name="password" id="password" required>
                    </div>
                    <div class="field input">
                        <label>Role:</label><br>
                        <select name="role">
                            <option value="">Select Role</option>
                            <option value="admin">Admin</option>
                            <option value="doctor">Doctor</option>
                            <option value="nurse">Nurse</option>
                            <option value="receptionist">Receptionist</option>
                        </select>
                    </div>
                    <div class="field input">
                        <label>Department:</label><br>
                        <select name="dept_id">
                            <option value="">Select Department</option>
                            <option value="1">Billing</option>
                            <option value="2">Pharmacy</option>
                            <option value="3">Medical Records</option>
                            <option value="4">Ultrasound</option>
                            <option value="5">X-ray</option>
                            <option value="6">Rehabilitation</option>
                            <option value="7">Dialysis</option>
                            <option value="8">Laboratory</option>
                            <option value="9">Admitting</option>
                        </select>
                    </div>
                    <input type="checkbox" onclick="showPassword()"> Show Password
                    <div class="field">
                        <button class="btn btn-primary"  type="submit" name="btnSave">Sign Up</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('showPassword').addEventListener('change', function() {
                var passwordField = document.getElementById('password');
                if (passwordField) { // Check if the password field exists
                    if (this.checked) {
                        passwordField.type = 'text';
                    } else {
                        passwordField.type = 'password';
                    }
                } else {
                    console.error('Password field not found');
                }
            });
        });
</script>
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



