<?php
// Program that uses a SQL database from a HTML form
// Guest book posts (including a picture) are inserted into the database and then retrieved to be printed out in a orded list below the form


require '6.3_db_configs.php';


// Function for uploading the picture and check for allowed file types
function upload_pic() {
    $name = $_FILES['file']['name'];
    $target_dir = "upload/";
    $target_file = $target_dir . basename($_FILES["file"]["name"]);

    // Select file type
    $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));

    // Valid file extensions
    $extensions_arr = array("jpg","jpeg","png","gif");

    // Check extension
    if( in_array($imageFileType, $extensions_arr) ){
    
        return file_get_contents($_FILES["file"]["tmp_name"]);

  }  
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['push_button']) { // Handle the form.

    // Validate and secure form data, check that all fields are filled
    $problem = FALSE;
    if (!empty($_POST['name']) && 
       !empty($_POST['email']) && 
       !empty($_POST['homepage']) && 
       (!empty($_POST['comment']) && trim($_POST['comment'])!=='') &&
       !empty($_FILES['file'])) {

        // Assign the submitted form data to a variable
        // mysqli_real_escape_string() is used to better secure the web application and the database from SQL injections
        // by escaping potentially harmful characters, making the data safe to use in a query
        $form_name = mysqli_real_escape_string($dbc, trim(strip_tags($_POST['name'])));
        $form_email = mysqli_real_escape_string($dbc, trim(strip_tags($_POST['email'])));
        $form_homepage = mysqli_real_escape_string($dbc, trim(strip_tags($_POST['homepage'])));
        $form_comment = mysqli_real_escape_string($dbc, trim(strip_tags($_POST['comment'])));
        

        $form_picture = upload_pic();
        $mime = mysqli_real_escape_string($dbc, trim(strip_tags($_FILES['file']['type'])));


        // Creates a string with current date and time
        date_default_timezone_set("Europe/Stockholm");
        $datetime = date('Y-m-d h:i:s A');

    } else {
        // If the user did not add text to all fields, which is required
        echo 'Please add your information to all fields!';
        $problem = TRUE;
    }

    if (!$problem) {

        if ($dbc) { // If connected to db
     
            $check_id_query = "SELECT MAX(id) FROM guest_data"; // To fetch and check the last (highest) id number inserted into the db
            
            $id_query_result = @mysqli_query($dbc, $check_id_query);
            $id_row = mysqli_fetch_row($id_query_result);
            $highest_id = $id_row[0];
            $new_id = intval($highest_id) + 1; // This is the id for the post (highest id currently in the db + 1)
            

            // START TRANSACTION for the INSERT queries
            mysqli_begin_transaction($dbc);

            try {

            // Create a query for inserting text data submitted from the form into the database, with placeholders as values
            $insert_query = "INSERT INTO guest_data (id, name, email, homepage, comment, datetime) VALUES (?, ?, ?, ?, ?, ?)";
            
            // Create a query for inserting the uploaded picture and its mime type into the database
            $picture_query = "INSERT INTO picture_table (id, picture, mimetype) VALUES (?, ?, ?)"; 

            // Create prepared statements (to avoid SQL injections)
            $stmt = mysqli_prepare($dbc, $insert_query);
            $stmt_pic = mysqli_prepare($dbc, $picture_query);

            // Bind parameters for markers
            mysqli_stmt_bind_param($stmt, "isssss", $new_id, $form_name, $form_email, $form_homepage, $form_comment, $datetime); // All text input
            mysqli_stmt_bind_param($stmt_pic, "ibs", $new_id, $form_picture, $mime); // The uploaded picture and its mime type

            mysqli_stmt_send_long_data($stmt_pic, 1, $form_picture);


            // Execute query and give feedback to user
            if (mysqli_stmt_execute($stmt) && mysqli_stmt_execute($stmt_pic)) { // User input is inserted into db
                print 'Your post has been added!';
            } else {
                print "Could not add your post" .
                // The line below is used for debugging but should not be displayed to a user
                " because: <br>" . mysqli_error($dbc) . "<br>The query being run was: " . $insert_query;
            }
            
            // If code reaches this point without errors then commit the data in the database 
            mysqli_commit($dbc);

            // If there is some error with the queries then do a rollback operation and inform the web client 
            } catch (mysqli_sql_exception $exception) {
                mysqli_rollback($mysqli);
                throw $exception;
            }
         
        } else {
            echo 'Not connected to the database.';
            print mysqli_connect_error();
        }

    } // No problem with form data 

} // End of form submission



$html = file_get_contents("6.3.html"); // Get the content from the html file
$html_pieces = explode("<!--===entries===-->", $html); // Split the html file by the given string
echo $html_pieces[0]; // Print the first substring from 6.3.html

$fetch_query = "SELECT * FROM guest_data"; // Query for fetching all columns from the table

if ($fetch_result = @mysqli_query($dbc, $fetch_query)) { // Run query

    while ($fetch_array = mysqli_fetch_row($fetch_result)) { // Fetch data row by row

        foreach ($fetch_array as $key => $value){
  
            if ($key == 0) {
                $html = file_get_contents("6.3.html");
                $html_pieces = explode("<!--===entries===-->", $html);
                $html_pieces = str_replace('---no---', $value, $html_pieces);

                $link = "6.3_fetch_pic.php?id=".$value."";
                $html_pieces = str_replace('---image_src---', $link, $html_pieces); 

            } elseif ($key == 1) {
                $html_pieces = str_replace('---name---', $value, $html_pieces);

            } elseif ($key == 2) {
                $html_pieces = str_replace('---email---', $value, $html_pieces);

            } elseif ($key == 3) {
                $html_pieces = str_replace('---homepage---', $value, $html_pieces);

            } elseif ($key == 4) {
                $html_pieces = str_replace('---comment---', $value, $html_pieces);

            } elseif ($key == 5) {
                $html_pieces = str_replace('---time---', $value, $html_pieces);

                // Print this second html substring multiple times, for each row in the database 
                echo $html_pieces[1];
                    
            } 

        }        
    }  
}

// Print the third substring from 6.3.html
echo $html_pieces[2];

mysqli_close($dbc); // Close the MySQL connection.

?>