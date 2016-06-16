<?php

error_reporting(E_ERROR | E_PARSE | E_WARNING);


require_once(dirname(__FILE__).'/../includes/engine/engine.php');
global $anTicUser;
$anTicUser = new anTicUser;

$requestedActionInputs = $anTicUser->gatherInputs();

if($requestedActionInputs['action'] == "login"){
    $response = $anTicUser->login($requestedActionInputs['email'],$requestedActionInputs['password']);

    echo json_encode($response);
}// end login



if($requestedActionInputs['action'] == "logout"){
    $anTicUser->logout();

    $output['status'] = "success";
    echo json_encode($output);
}// end logout


if($requestedActionInputs['action'] == "whoami"){
    $response = $anTicUser->whoami();

    echo json_encode($response);
}//whoami

if($requestedActionInputs['action'] == "listgroups"){
    $response = $anTicUser->listGroups($requestedActionInputs['groupIDs']);

    echo json_encode($response);
}//listgroups


if($requestedActionInputs['action'] == "setpassword"){
    $response = $anTicUser->setPassword(null,$requestedActionInputs['password']);

    echo json_encode($response);
}// end setpassword