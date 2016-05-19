<?php

function initDB(){
    global $db,$dbAuth;

    if($db instanceof PDO){
        $status = $db->getAttribute(PDO::ATTR_CONNECTION_STATUS);
    }else{

        $settingsSet = include('../systemSettings.php');

        // Check System Settings
        if(!$settingsSet){
            // The system settings and DB connection values not set. Return Failure.
            $returnData['error'] = "System Settings File Missing.";
            echo json_encode($returnData);
            die;
        }


        // Create Database Connection
        $db = new PDO("mysql:host=".$dbAuth['addr'].";port=".$dbAuth['port'].";dbname=".$dbAuth['schema'].";charset=utf8mb4", $dbAuth['user'], $dbAuth['pw']);

    }

}// initDB


// This function will build a description of the data structure
function buildDataModels(){
    global $dataModels;

    initDB();

    // Build the Data Models if they do not exist
    if($dataModels == null){
        // Load Data Model Files

        $files = glob('../dataModelMeta/*.{json}', GLOB_BRACE);
        foreach($files as $file) {
            $tableName = basename($file,".json");
            $dataModels[$tableName] = json_decode(file_get_contents($file),true);
            //[$interim]
        }// Populate Load Default/Standard Values From Database Structure


        // Populate Columns from Table Structure
        foreach($dataModels as $tableName => $dataModel){
            global $db;

            $stmt = $db->prepare("DESCRIBE $tableName;");

            $stmt->execute();
            //$output = $stmt->fetchAll();
            //var_dump($output);
            while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if($data['Field']!=""){
                    $dataModels[$tableName]['fieldOrder'][]=$data['Field'];
                    $dataModels[$tableName]['fields'][$data['Field']]['type']=$data['Type'];
                    if($data['Null']=="YES"){
                        $dataModels[$tableName]['fields'][$data['Field']]['null']=true;
                    }else{
                        $dataModels[$tableName]['fields'][$data['Field']]['null']=false;
                    }
                    $dataModels[$tableName]['fields'][$data['Field']]['default']=$data['Default'];
                    if($data['Key']=="PRI"){
                        $dataModels[$tableName]['primaryKey'][]=$data['Field'];
                    }
                }
            }
            // If no primary key is defined assume that all fields are required
            if(count($dataModels[$tableName]['primaryKey'])==0){
                foreach($dataModel['fields'] as $fieldName => $fieldData){
                    $dataModels[$tableName]['primaryKey'][] = $fieldName;
                }
            }

            // Populate Default Values in Models
            if(!isset($dataModel['displayName'])){
                $dataModels[$tableName]['displayName']=ucwords($tableName);
            }
            foreach($dataModels[$tableName]['fields'] as $fieldMachineName=>$field){
                if(!isset($field['displayName'])){
                    $dataModels[$tableName]['fields'][$fieldMachineName]['displayName']=ucwords($fieldMachineName);
                }
            }

        }




        // Get Foreign Key List
        $sql = "SELECT i.TABLE_NAME, i.CONSTRAINT_TYPE, i.CONSTRAINT_NAME, k.REFERENCED_TABLE_NAME, k.REFERENCED_COLUMN_NAME 
FROM information_schema.TABLE_CONSTRAINTS i 
LEFT JOIN information_schema.KEY_COLUMN_USAGE k ON i.CONSTRAINT_NAME = k.CONSTRAINT_NAME 
WHERE i.CONSTRAINT_TYPE = 'FOREIGN KEY' 
AND i.TABLE_SCHEMA = DATABASE();";

        $stmt = $db->prepare($sql);

        $stmt->execute();
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC))  {
            if(isset($dataModels[$data['TABLE_NAME']])){

                $dataModels[$data['TABLE_NAME']]['fields'][$data['CONSTRAINT_NAME']]['foreignKeyTable']=$data['REFERENCED_TABLE_NAME'];
                $dataModels[$data['TABLE_NAME']]['fields'][$data['CONSTRAINT_NAME']]['foreignKeyColumns'][]=$data['REFERENCED_COLUMN_NAME'];

                // Set default FK display field
                if(!isset($dataModels[$data['TABLE_NAME']]['fields'][$data['CONSTRAINT_NAME']]['foreignKeyDisplayFields'])){
                    $dataModels[$data['TABLE_NAME']]['fields'][$data['CONSTRAINT_NAME']]['foreignKeyDisplayFields'][]=$data['REFERENCED_COLUMN_NAME'];
                }
            }
        }
    }




    return json_encode($dataModels);

}// end buildDataModels


