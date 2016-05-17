<?php

$validRequests = array("get","set","delete","add");


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
    $primaryRecordKeys = json_decode($aRequest['pkey'],true);
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

$settingsSet = include('../includes/loadDataConnections.php');

buildDataModels();

if(!isset($dataModels[$aRequest['tableName']])){
    $returnData['error'] = "Invalid Table Selected";
    echo $returnData;
    die;
}else{
    $targetTable = $aRequest['tableName'];
}




// If $primaryRecordKeys are specified, validate against table definition
foreach($primaryRecordKeys as $key=>$value){
    if(!in_array($key,$dataModels[$aRequest['tableName']]['primaryKey'])){
        $returnData['error'] = "Invalid Primary Key Request";
        echo $returnData;
        die;
    }
}



// If Data is being submitted, validate field list
if(isset($aRequest['data'])){
    $inputData = json_decode($aRequest['data'],true);
    foreach($inputData as $key=>$value){
        if(!isset($dataModels[$aRequest['tableName']]['fields'][$key])){
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
            $setArray[]=$key."=:".$key."DValue";
            $bindArray[':'.$key.'DValue']=$value;
        }
        $sql .= implode(" AND ",$setArray);

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




// Common Code
    if(count($primaryRecordKeys)>0){
        // get one record
        unset($sqlWhere);
        foreach($primaryRecordKeys as $key=>$value) {
            $sqlWhere[] = " $key = :".$key."Value";
            $bindArray[':'.$key.'Value']=$value;
        }
        $sql.=" WHERE ".implode(" AND ",$sqlWhere);

    }

    $sql = addLimits($sql);


    // Run Query
    $statement = $db->prepare($sql);
    foreach($bindArray as $bKey => $bValue){
         $statement->bindValue($bKey, $bindArray[$bKey]);
    }

    $statement->execute();

// TODO: Add handling for new

    // Process Results
    $output = null;
    while ($data = $statement->fetch(PDO::FETCH_ASSOC))  {
        $output['status']="success";
        $output['data'][]=$data;
    }














// If get, retrieve the foreign key tables as well

if($aRequest['action']=="get") {
    // TODO: Foreign Key Data
    $foreignKeyTables = [];
    // look for Foreign Key fields
    foreach($dataModels[$aRequest['tableName']]['fields'] as $field){
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
            $output['fk'][$fkTable][]=$data;
        }
    }
}





















    // Return Results
    echo json_encode($output);







function addLimits($sql){
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