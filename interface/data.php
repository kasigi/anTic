<?php

$validRequests = array("get","getall","set","delete","add");

/*
Action Notes

get: Gets a record(s) and will retrieve all fields
getall: Gets all records from a table but will limit fields to the listViewDisplayFields and primary key lists
set: Updates a single existing record
        (note - it is POSSIBLE for set to affect multiple identical records on tables without a primary key)
add: Inserts a single record
delete: Deletes a single record



*/



// Gather data from angular's post method
$postdata = file_get_contents("php://input");
$aRequest = json_decode($postdata,true);




/*
Request Requirements
action
tableName
pkey
count
offset

Optional:
data

*/

// Some basic cleaning and preparation


if(isset($aRequest['pkey'])){
    if(!is_array($aRequest['pkey'])){
        $primaryRecordKeys = json_decode($aRequest['pkey'],true);
    }else{
        $primaryRecordKeys = $aRequest['pkey'];
    }
}else{
    $primaryRecordKeys=[];
}


// Check for valid request action
if(!isset($aRequest['action'])){
    $returnData['error'] = "No action defined";
    echo json_encode($returnData);
    die;
}

// Check for valid request action
if(!isset($aRequest['tableName'])){
    $returnData['error'] = "No tableName defined";
    echo json_encode($returnData);
    die;
}

$aRequest['action'] = strtolower($aRequest['action']);
if(!in_array($aRequest['action'],$validRequests)){
    $returnData['error'] = "Invalid Request Type";
    echo $returnData;
    die;
}


// Check for valid table name
global $dataModels,$db;

$settingsSet = include('../includes/engine/loadDataConnections.php');

buildDataModels('data');

if(!isset($dataModels['data'][$aRequest['tableName']])){
    $returnData['error'] = "Invalid Table Selected";
    echo $returnData;
    die;
}else{
    $targetTable = $aRequest['tableName'];
}




// If $primaryRecordKeys are specified, validate against table definition
foreach($primaryRecordKeys as $key=>$value){
    if(!in_array($key,$dataModels['data'][$aRequest['tableName']]['primaryKey'])){
        $returnData['error'] = "Invalid Primary Key Request";
        echo $returnData;
        die;
    }
}



// If Data is being submitted, validate field list
if(isset($aRequest['data'])){
    if(!is_array($aRequest['data'])){
        $inputData = json_decode($aRequest['data'],true);
    }else{
        $inputData = $aRequest['data'];
    }
    foreach($inputData as $key=>$value){
        if(!isset($dataModels['data'][$aRequest['tableName']]['fields'][$key])){
            $returnData['error'] = "Invalid Fields in Data Request";
            echo $returnData;
            die;
        }
    }
}else{
    $inputData=[];
}




// Actions (must have passed all previous tests


$bindArray = [];

if($aRequest['action']=="get"){
    $sql = "SELECT * FROM $targetTable";
}// end get


if($aRequest['action']=="getall"){
    $fieldArray = [];
    $fieldListString = "*";
    // Look for listViewDisplayFields
    if(isset($dataModels['data'][$targetTable]['listViewDisplayFields'])){

        // Add the manually specified display fields
        foreach($dataModels['data'][$targetTable]['listViewDisplayFields'] as $fieldName){
            if(!in_array($fieldName,$fieldArray)){
                $fieldArray[]=$fieldName;
            }
        }

        // Primary keys are mandatory fields and must be added if not already specified
        foreach($dataModels['data'][$aRequest['tableName']]['primaryKey'] as $fieldName){
            if(!in_array($fieldName,$fieldArray)){
                $fieldArray[]=$fieldName;
            }
        }

        $fieldListString = implode(", ",$fieldArray);
    }

    $sql = "SELECT $fieldListString FROM $targetTable";  // This is the main query
    $countSql = "SELECT count(*) as recordTotalCount FROM $targetTable"; // This is the query used to determine total number of records
}// end get


if($aRequest['action']=="set"){
    if(count($primaryRecordKeys) == 0){
        $returnData['error'] = "Primary Keys Required for Action";
        echo $returnData;
        die;
    }else{
        $sql = "UPDATE $targetTable SET ";
        unset($setArray);
        if(count($inputData)==0){
            $returnData['error'] = "Data must be supplied for the Update Action";
            echo $returnData;
            die;
        }
        foreach($inputData as $key=>$value) {
            $setArray[]="`".$key."`=:".$key."DValue";
            //$setArray[]="`".$key."`=\"".$value."\"";

            $bindArray[':'.$key.'DValue']=$value;
        }
        $sql .= implode(" , ",$setArray);

        }
}// end get

if($aRequest['action']=="delete"){
    if(count($primaryRecordKeys) == 0){
        $returnData['error'] = "Primary Keys Required for Action";
        echo $returnData;
        die;
    }else{
        $sql = "DELETE FROM $targetTable";
    }

}// end get


if($aRequest['action']=="add") {
    // INSERT INTO tbl_name (col1,col2) VALUES(15,col1*2);

    $sql = "INSERT INTO $targetTable ";
    unset($setArray);
    unset($setArrayFields);
    if(count($inputData)==0){
        $returnData['error'] = "Data must be supplied for the Update Action";
        echo $returnData;
        die;
    }
    foreach($inputData as $key=>$value) {
        $setArrayFields[]="`".$key."`";
        $setArray[]=":".$key."DValue";
        //$setArray[]="`".$key."`=\"".$value."\"";

        $bindArray[':'.$key.'DValue']=$value;
    }
    $sql .= "(".implode(" , ",$setArrayFields).") VALUES (". implode(" , ",$setArray).")";
}// end add



// Common Code
if($aRequest['action']!=="add"){

    unset($sqlWhere);
    if(count($primaryRecordKeys)>0){
        // target one record with primary keys
$output['pk']=$primaryRecordKeys;
        foreach($primaryRecordKeys as $key=>$value) {
            $sqlWhere[] = " $key = :".$key."Value";
            $bindArray[':'.$key.'Value']=$value;
        }
        $sql.=" WHERE ".implode(" AND ",$sqlWhere);

    }

    $sql = addLimits($sql);
}




    // Run Query
    $statement = $db->prepare($sql);
    foreach($bindArray as $bKey => $bValue){
         $statement->bindValue($bKey, $bindArray[$bKey]);
    }

    $success = $statement->execute();
    if(!$success){
        $output['status']="error";
        $output['error']=$statement->errorCode();
        $output['sqlError']=$statement->errorInfo();
        //$output['sqlError']['sql']=$sql;
        echo json_encode($output);
        die;
    }


    // Process Results
    $output = null;
    while ($data = $statement->fetch(PDO::FETCH_ASSOC))  {
        $output['status']="success";
        $output['data'][]=$data;
        //$output['sql']=$sql;
    }

    if($countSql!=""){
        $statement = $db->prepare($countSql);
        $success = $statement->execute();
        while ($data = $statement->fetch(PDO::FETCH_ASSOC))  {
            $output['recordTotalCount']=$data['recordTotalCount'];
            //$output['sql']=$sql;
        }
    }




    if($aRequest['action']=="add"){
        $output['insertedID'] = $db->lastInsertId();
    }




// If get, retrieve the foreign key tables as well

if($aRequest['action']=="get" || $aRequest['action']=="getall") {
    // TODO: Foreign Key Data
    $foreignKeyTables = [];
    // look for Foreign Key fields
    foreach($dataModels['data'][$aRequest['tableName']]['fields'] as $field){
        // Create list of foreignKeyTables and all required fields
        if(isset($field['foreignKeyTable'])){
            if(!isset($foreignKeyTables[$field['foreignKeyTable']])){
                $foreignKeyTables[$field['foreignKeyTable']] = [];
            }

            if(isset($field['foreignKeyColumns'])){
                foreach($field['foreignKeyColumns'] as $fkField){
                    if(!in_array($fkField,$foreignKeyTables[$field['foreignKeyTable']])){
                        $foreignKeyTables[$field['foreignKeyTable']][]=$fkField;
                    }
                }
            }


            if(isset($field['foreignKeyDisplayFields'])){
                foreach($field['foreignKeyDisplayFields'] as $fkField){
                    if(!in_array($fkField,$foreignKeyTables[$field['foreignKeyTable']])){
                        $foreignKeyTables[$field['foreignKeyTable']][]=$fkField;
                    }
                }
            }

        }
    }

    // Get the foreign key data tables

    foreach($foreignKeyTables as $fkTable => $fkFields){
        $sql = "SELECT ".implode(", ",$fkFields)." FROM $fkTable";

        $statement = $db->prepare($sql);
        $statement->execute();

        // Process Results
        while ($data = $statement->fetch(PDO::FETCH_ASSOC))  {
            $output['fkdata'][$fkTable][]=$data;
        }
    }
}





    // Return Results
    echo json_encode($output);







function addLimits($sql){
    global $aRequest;
    if(isset($aRequest['offset']) || isset($aRequest['count'])){
        if(!isset($aRequest['count'])){
            $count=1000;
        }else{
            $count = intval($aRequest['count']);
            if($count==0){
                $count=1000;
            }
        }
        if(isset($aRequest['offset'])){
            $offset = intval($aRequest['offset']);
        }else{
            $offset = 0;
        }
        $sql.=" LIMIT $offset, $count";

    }
    return $sql;
}// end addLimits