<?php
// This PHP file is used to fetch the picture from the picture table in the database 
// and return it to 6.3.php to be displayed in the right post
header("Content-type: image");
require '6.3_db_configs.php';

// To fetch the right picture based on the id
$id = $_GET["id"];

    
if ($dbc) {
$pic_query = "SELECT picture FROM picture_table WHERE id=$id";
$pic_query_result = @mysqli_query($dbc, $pic_query);

    while ($pic_row = mysqli_fetch_row($pic_query_result)) {

        echo $pic_row[0];
  
    }

} else {
    echo 'Not connected to the database.';
    print mysqli_connect_error();
}

?>