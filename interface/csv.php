<?php


require_once(dirname(__FILE__).'/../includes/engine/engine.php');
$anTicData = new anTicData;

$anTicData->initDB();
$models = $anTicData->buildDataModels('data');

if($_REQUEST['tableName']!=""){
// Check for valid table name

    $targetTable = preg_replace("/[^a-zA-Z0-9\-_\.]/", "", $_REQUEST['tableName']);

    if(!array_key_exists($targetTable,$models)){
        echo "Invalid Selection";
        exit;
    }
    $fileName = $targetTable.".csv";

    $sql = "SELECT * FROM $targetTable";


    $statement = $anTicData->db->prepare($sql);
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




