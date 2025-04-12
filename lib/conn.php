<?php

$servername = "localhost";
$dbusername = "root";
$database = "dbhqs";

try{	
	$conn = new PDO("mysql:host=$servername; dbname=$database", $dbusername);
	$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e){
	echo "Connection failed: " . $e->getMessage();
}

?>