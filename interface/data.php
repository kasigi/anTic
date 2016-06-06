<?php
error_reporting(E_ERROR | E_WARNING | E_PARSE);


require('../includes/engine/dataController.php');

$anTicData = new anTicData;


$anTicData->initDB();
$models = $anTicData->buildDataModels('data');


// Data Models Action
$postdata = file_get_contents("php://input");
$aRequest = json_decode($postdata, true);

if($aRequest['action']=="buildModels") {
    echo json_encode($models);
    die;
}// end buildModels


$requestedActionInputs = $anTicData->gatherInputs();



if($requestedActionInputs['action']=="get"){
    // Get the data
    $response = $anTicData->dataGet($requestedActionInputs['targetTable'],$requestedActionInputs['primaryRecordKeys']);

    // Return JSON
    echo json_encode($response);
    
}// end get

if($requestedActionInputs['action']=="getversionlog"){
    // Get the data
    $response = $anTicData->dataVersionList($requestedActionInputs['targetTable'],$requestedActionInputs['primaryRecordKeys']);
    // Return JSON
    echo json_encode($response);

}// end get


if($requestedActionInputs['action']=="getall"){
    // Get the data
    $response = $anTicData->dataGetAll($requestedActionInputs['targetTable']);

    $response['requestedActionInputs']=$requestedActionInputs;
    // Return JSON
    echo json_encode($response);
    
}// end add


if($requestedActionInputs['action']=="set"){
    // Get the Previous Value
    $previousValue = $anTicData->dataGet($requestedActionInputs['targetTable'],$requestedActionInputs['primaryRecordKeys']);

    // Add previous values to log table
    $models = $anTicData->buildDataModels('system');
    $versionRecord = [];
    $versionRecord['tableName']=$requestedActionInputs['targetTable'];
    $versionRecord['data']=json_encode($previousValue['data']);
    $versionRecord['pkArrayBaseJson']=json_encode($requestedActionInputs['primaryRecordKeys']);
    $versionRecord['userID']=0;//TODO: Make this the current user when user system is present
    $newVersion = $anTicData->dataAdd("anticVersionLog",$versionRecord);


    // Set the data
    $response = $anTicData->dataSet($requestedActionInputs['targetTable'],$requestedActionInputs['primaryRecordKeys'],      $requestedActionInputs['inputData']);
    $response['newVersion']=$newVersion;
    $response['newVersionReq']=$versionRecord;
    // Return JSON
    echo json_encode($response);

}// end set


if($requestedActionInputs['action']=="delete"){
    // Delete the data
    $response = $anTicData->dataSet($requestedActionInputs['targetTable'],$requestedActionInputs['primaryRecordKeys']);

    // Return JSON
    echo json_encode($response);
}// end delete


if($requestedActionInputs['action']=="add"){
    // Add the data
    $response = $anTicData->dataAdd($requestedActionInputs['targetTable'],$requestedActionInputs['inputData']);

    // Return JSON
    echo json_encode($response);
}





