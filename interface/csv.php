<?php


require_once(dirname(__FILE__).'/../includes/engine/engine.php');
$anTicData = new anTicData;
$anTicUser = new anTicUser;

if(!$anTicUser->checkLogin()){
    $anTicData->returnError("Not logged in",2);
    die;
}

$anTicData->initDB();
$models = $anTicData->buildDataModels('data');

if($_REQUEST['tableName']!=""){
// Check for valid table name
    $permissions = $anTicUser->permissionCheck($_REQUEST['tableName']);

    if($permissions['data']['anticRead']!=1){
        $anTicData->returnError("Not allowed to read table",2);
        die;
    }

    $targetTable = preg_replace("/[^a-zA-Z0-9\-_\.]/", "", $_REQUEST['tableName']);

    if(!array_key_exists($targetTable,$models)){
        $anTicData->returnError("CSV Download invalid selection",4);
        exit;
    }
    $fileName = $targetTable.".csv";



    if($_REQUEST['includeFKDisplay']==1){
        // Include the display fields from foreign key parent tables

        $foreignKeyTables = [];
        $fkSelectFields = "";
        $fkSelectJoin = "";
        $selectFields = [];
        $joinIndex = 1;
        // look for Foreign Key fields
        foreach ($anTicData->dataModels['data'][$targetTable]['fields'] as $fieldName => $field) {
            // Add the base field
            $selectFields[] = "T0.$fieldName";

            // Create list of foreignKeyTables and all required fields
            if (isset($field['foreignKeyTable'])) {
                if (!isset($foreignKeyTables[$field['foreignKeyTable']])) {
                    $foreignKeyTables[$field['foreignKeyTable']] = [];
                    $fkSelectJoin .= "\nLEFT JOIN ".$field['foreignKeyTable']." T$joinIndex ON T0.$fieldName = T$joinIndex.".$field['foreignKeyColumns'][0]." \n";
                }


                if (isset($field['foreignKeyDisplayFields'])) {
                    foreach ($field['foreignKeyDisplayFields'] as $fkField) {
                        if (!in_array($fkField, $foreignKeyTables[$field['foreignKeyTable']])) {
                            $selectFields[] = "T$joinIndex.$fkField";
                        }
                    }
                }
                $joinIndex++;
            }
        }

        // Get the foreign key data tables




        $sql = "SELECT ".implode(", ",$selectFields)." FROM $targetTable T0 $fkSelectJoin";
    }else{
        $sql = "SELECT * FROM $targetTable";
    }

    $statement = $anTicData->db->prepare($sql);
    $statement->execute();

    // Process Results
    while ($data = $statement->fetchAll(PDO::FETCH_ASSOC))  {
        $output=$data;
    }








    if($output ){

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
}




