<?php

// Database configurations
$hostname = 'localhost';
$username = 'root';
$password = 'my_password';
$gb_database = 'guest_book';

$dbc = mysqli_connect($hostname, $username, $password, $gb_database);
// echo 'Successfully connected to the database!<br>'; // Used for debugging
// print mysqli_error($dbc); // Used for debugging

// Set the character set
mysqli_set_charset($dbc, 'utf8');

?>