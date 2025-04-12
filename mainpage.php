<?php      
        
        session_start();

            if (!isset($_SESSION["uid"])) {
                header("location:index.php");
                exit();
            }

            require("lib/conn.php");

            if(isset($_REQUEST["logout"])){
                session_destroy();
                header("location:index.php");
                exit();
            }

            
?>

<!DOCTYPE html>
<html lang="en">
<header>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title></title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="lib\images\styles.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
</header>
<body>
    <div class="row">
    <?php include('sidebar.php');?>
    <div class="col-md-8 col-lg-10 d-flex justify-content-start align-items-start" style="min-height: 100vh;">
  <div>
  <h2>Departments</h2>
  </div>
  echo "<h2>STUDENT INFO:</h2>";

if ($students) {
    echo "<table border=1>";
    echo "<thead><tr><th>Student ID</th><th>First Name</th><th>Last Name</th><th>Birth Date</th><th>Home Address</th><th>Boarding Address</th><th>Contact No.</th><th>Email Address</th><th>Civil Status</th><th>Religion</th><th>Sex</th><th>Course</th><th>Year Level</th><th>Parents' Name</th><th>Edit</th><th>Delete</th></tr></thead>";
    foreach ($students as $student){
        echo "<tr>";
        echo "<th>". $student['StudentID']. "</th>";
        echo "<th>". str_replace($searchTerm, "<span class='highlight'>$searchTerm</span>", $student['Fname']). "</th>";
        echo "<th>". str_replace($searchTerm, "<span class='highlight'>$searchTerm</span>", $student['Lname']). "</th>";
        echo "<th>". $student['bdate']. "</th>";
        echo "<th>". $student['homeaddr']. "</th>";
        echo "<th>". $student['boardingaddr']. "</th>";
        echo "<th>". $student['contact']. "</th>";
        echo "<th>". $student['email']. "</th>";
        echo "<th>". $student['civil_status']. "</th>";
        echo "<th>". $student['religion']. "</th>";
        echo "<th>". ($student['sex'] == 'M'? 'Male' : 'Female'). "</th>";
        echo "<th>". $student['course']. "</th>";
        echo "<th>". $student['year_level']. "</th>";
        echo "<th>Mother: ". $student['mother_name']. "<br> Father: ". $student['father_name']. "</th>";
        echo "<th><a href='edit_info.php?student_id=". $student['StudentID']. "'><button><i class='fas fa-user-pen'></i></button></a></th>";
        echo "<th><button type='button' onclick='confirmDelete(". $student['StudentID']. ")'><i class='fa fa-trash'></i></button></th>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No students found!";
}
    </div>
    </div>



    

</body>

<script>

    function redirectTo(url) {
        window.location.href = url;
    }

    document.addEventListener("DOMContentLoaded", function() {
        document.querySelector(".btn1").addEventListener("click", function() {
            redirectTo('add_stud.php');
        });

    });

    document.addEventListener("DOMContentLoaded", function() {
        document.querySelector(".btn2").addEventListener("click", function() {
            redirectTo('register.php');
        });

    });
</script>

</html>


