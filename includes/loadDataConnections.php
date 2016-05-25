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
function buildDataModels($dataType){
    global $dataModels;

    initDB();

    if(!in_array($dataType,["data","system"])){
        $dataType = "data";
    }

    // Build the Data Models if they do not exist
    if($dataModels[$dataType] == null){
        // Load Data Model Files

        $files = glob('../dataModelMeta/'.$dataType.'/*.{json}', GLOB_BRACE);
        foreach($files as $file) {
            $tableName = basename($file,".json");
            $dataModels[$dataType][$tableName] = json_decode(file_get_contents($file),true);
            if(!isset($dataModels[$dataType][$tableName]['displayName'])){
                $dataModels[$dataType][$tableName]['displayName'] = $tableName;
            }
            //[$interim]
        }// Populate Load Default/Standard Values From Database Structure


        // Populate Columns from Table Structure
        foreach($dataModels[$dataType] as $tableName => $dataModel){
            global $db;

            $stmt = $db->prepare("DESCRIBE $tableName;");

            $stmt->execute();
            //$output = $stmt->fetchAll();
            //var_dump($output);
            while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if($data['Field']!=""){
                    $dataModels[$dataType][$tableName]['fieldOrder'][]=$data['Field'];
                    $dataModels[$dataType][$tableName]['fields'][$data['Field']]['type']=$data['Type'];
                    if($data['Null']=="YES"){
                        $dataModels[$dataType][$tableName]['fields'][$data['Field']]['null']=true;
                    }else{
                        $dataModels[$dataType][$tableName]['fields'][$data['Field']]['null']=false;
                    }
                    $dataModels[$dataType][$tableName]['fields'][$data['Field']]['default']=$data['Default'];
                    if($data['Key']=="PRI"){
                        $dataModels[$dataType][$tableName]['primaryKey'][]=$data['Field'];
                    }
                    if(strpos($data['Extra'],"auto_increment")!==false){
                        $dataModels[$dataType][$tableName]['fields'][$data['Field']]['auto_increment']=true;
                    }else{
                        $dataModels[$dataType][$tableName]['fields'][$data['Field']]['auto_increment']=false;
                    }

                }
            }

            // If no primary key is defined assume that all fields are required
            if(!isset($dataModels[$dataType][$tableName]['primaryKey'])){
                foreach($dataModels[$dataType][$tableName]['fields'] as $fieldName => $fieldData){
                    $dataModels[$dataType][$tableName]['primaryKey'][] = $fieldName;
                }
            }

            // Populate Default Values in Models
            if(!isset($dataModel['displayName'])){
                $dataModels[$dataType][$tableName]['displayName']=ucwords($tableName);
            }
            foreach($dataModels[$dataType][$tableName]['fields'] as $fieldMachineName=>$field){
                if(!isset($field['displayName'])){
                    $dataModels[$dataType][$tableName]['fields'][$fieldMachineName]['displayName']=ucwords($fieldMachineName);
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
            if(isset($dataModels[$dataType][$data['TABLE_NAME']])){

                $dataModels[$dataType][$data['TABLE_NAME']]['fields'][$data['CONSTRAINT_NAME']]['foreignKeyTable']=$data['REFERENCED_TABLE_NAME'];
                $dataModels[$dataType][$data['TABLE_NAME']]['fields'][$data['CONSTRAINT_NAME']]['foreignKeyColumns'][]=$data['REFERENCED_COLUMN_NAME'];

                // Set default FK display field
                if(!isset($dataModels[$dataType][$data['TABLE_NAME']]['fields'][$data['CONSTRAINT_NAME']]['foreignKeyDisplayFields'])){
                    $dataModels[$dataType][$data['TABLE_NAME']]['fields'][$data['CONSTRAINT_NAME']]['foreignKeyDisplayFields'][]=$data['REFERENCED_COLUMN_NAME'];
                }
            }
        }
    }




    return json_encode($dataModels[$dataType]);

}// end buildDataModels


