<?php

// Check for valid table name
global $dataModels,$db;

$settingsSet = include('../includes/loadDataConnections.php');

buildDataModels('data');

if($_REQUEST['tableName']!=""){

    $targetTable = preg_replace("/[^a-zA-Z0-9\-_\.]/", "", $_REQUEST['tableName']);;

    if(!in_array($targetTable,$dataModels['data'])){
        echo "Invalid Selection";
        exit;
    }

    $fileName = $targetTable.".csv";

    $sql = "SELECT * FROM $targetTable";


    $statement = $db->prepare($sql);
    $statement->execute();

    // Process Results
    while ($data = $statement->fetchAll(PDO::FETCH_ASSOC))  {
        $output=$data;
    }


    if($output)

    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header('Content-Description: File Transfer');
    header("Content-type: text/csv");
    header("Content-Disposition: attachment; filename={$fileName}");
    header("Expires: 0");
    header("Pragma: public");

    $fh = @fopen( 'php://output', 'w' );

    $headerDisplayed = false;

    foreach ( $output as $data ) {
        // Add a header row if it hasn't been added yet
        if ( !$headerDisplayed ) {
            // Use the keys from $data as the titles
            fputcsv($fh, array_keys($data));
            $headerDisplayed = true;
        }

        // Put the data into the stream
        fputcsv($fh, $data);
    }
// Close the file
    fclose($fh);
// Make sure nothing else is sent, our file is done
    exit;
}




