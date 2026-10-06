<?php

include("connect.php");
$rfidid = $_GET['rfid_id'];
// Check connection
if ($connect->connect_error) {
    die("Connection failed: " . $connect->connect_error);
}

// SQL query to retrieve RFID data (adjust the query according to your database schema)
$sql = "SELECT rfidid_parents FROM parents_card WHERE rfidid_parents = $rfidid";
$result = $connect->query($sql);

// Check if there are results
if ($result->num_rows > 0) {
    
    // Create an array to store RFID data
    $rfidData = array();

    // Fetch data and add it to the array
    while ($row = $result->fetch_assoc()) {
        $rfidData[] = array(
            'rfid_id' => $row['rfidid_parents'],
        );
    }

    // Convert the array to JSON
    $jsonResponse = json_encode($rfidData);

    // Send the JSON response
    header('Content-Type: application/json');
    echo $jsonResponse;
} else {
    // No results found
    header('HTTP/1.1 404 Not Found');
    echo 'No RFID data found.';
}

// Close the database connection
$connect->close();
?>
