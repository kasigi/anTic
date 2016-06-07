<?php


class anTicUser {

    public $db;
    public $validRequests = array("login","logout","setpassword","whoami");

    function anTicUser(){

        // Create the Session
        session_start();
        session_regenerate_id();


    }
/*
 * Initializes the database connection for the user class if not already connected
 */
    function initDB()
    {
        global $dbAuth;

        if ($this->db instanceof PDO) {
            $status = $this->db->getAttribute(PDO::ATTR_CONNECTION_STATUS);
        } else {

            $settingsSet = require(dirname(__FILE__).'/../../systemSettings.php');

            // Check System Settings
            if (!$settingsSet) {
                // The system settings and DB connection values not set. Return Failure.
                $returnData['error'] = "System Settings File Missing.";
                return $returnData;
            }


            // Create Database Connection
            $this->db = new PDO("mysql:host=" . $dbAuth['addr'] . ";port=" . $dbAuth['port'] . ";dbname=" . $dbAuth['schema'] . ";charset=utf8mb4", $dbAuth['user'], $dbAuth['pw']);

        }
    } // end initDB

/*
 * Gathers the inputs submitted via $_REQUEST, etc. and prepares theme for an action. The primary purpose is to handle angularJS's approach to POST.
 *
 */
    function gatherInputs()
    {

        // Gather data from angular's post method
        $postdata = file_get_contents("php://input");
        $aRequest = json_decode($postdata, true);


// Check for valid request action
        if (!isset($aRequest['action'])) {
            $returnData['error'] = "No action defined";
            return $returnData;
        }

// Check for valid request action
        $aRequest['action'] = strtolower($aRequest['action']);
        if (!in_array($aRequest['action'], $this->validRequests)) {
            $returnData['error'] = "Invalid Request Type";
            return $returnData;
        }



        $returnArr = [];
        $returnArr['action'] = $aRequest['action'];
        $returnArr['userEmail'] = $aRequest['userEmail'];
        $returnArr['password'] = $aRequest['password'];
        return $returnArr;

    }// end function gatherInputs



    function checkLogin(){
        // Checks for the presence of a logged in user

        if($_SESSION['fullyAuthenticated']!==true && (!isset($_SESSION['userID']) || $_SESSION['userID']<=0)){
            return false;
        }else{
            return true;
        }

    } // checkLogin


    function whoami(){

        if($this->checkLogin()){
            $this->initDB();
            $sql = "SELECT userID, email, firstName, lastName FROM anticUser WHERE userID=:userID LIMIT 0,1";
            $statement = $this->db->prepare($sql);
            $statement->bindValue(":userID",intval($_SESSION['userID']));
            $statement->execute();

            // Compare the hashes
            $data = null;
            while ($dbdata = $statement->fetch(PDO::FETCH_ASSOC)) {
                $data = $dbdata;
            }

            $output['data']=$data;
        }else{
            $output['status'] = "Not logged in.";
        }
            return $output;
    }// whoami


    function logout(){
        $_SESSION['fullyAuthenticated'] = false;
        unset($_SESSION['userID']);
        unset($_SESSION['userMeta']);
    }//logout


/*
 * Authenticates a user with email address and password.
 * @param $userEmail string The email address of the user
 * @param $password string  The password to verify
 */
    function login($userEmail,$password){
        if($userEmail == "" || $password == ""){
            return false;
        }

        $this->initDB();

        // Get the prospective user record
        $sql = "SELECT * FROM anticUser WHERE email = :email AND allowLogin = 1 and active = 1";
        $statement = $this->db->prepare($sql);
        $statement->bindValue(":email",$userEmail);
        $statement->execute();

        // Compare the hashes
        $data = null;
        while ($dbdata = $statement->fetch(PDO::FETCH_ASSOC)) {
            $data = $dbdata;
        }

        if (isset($data['password']) && password_verify($password, $data['password'])) {
            // If successful, store userID and First/Last name in session and set fullyAuthenticated to true

            $output['status'] = "success";
            $_SESSION['userID'] = intval($data['userID']);
            $_SESSION['fullyAuthenticated'] = true;
            $_SESSION['userMeta'] = [];
            $_SESSION['userMeta']['firstName'] = $data['firstName'];
            $_SESSION['userMeta']['lastName'] = $data['lastName'];
        } else {
            // If they fail, unset fullyAuthenticated and session user id return false
            $_SESSION['fullyAuthenticated'] = false;
            unset($_SESSION['userID']);
            unset($_SESSION['userMeta']);
            $output['status'] = "error";
        }


        return $output;

    }// end login


/*
 * Sets the password for a user
 * This function requires that the user being modified is either the user logged on OR that the user has admin powers on the users table.
 *
 * @param $userID int   The userID being modified.  If set to null or 0, it will default to the session userID
 * @param $password string  This is the password value to be hashed and saved.
 */
    function setPassword($userID,$password){

        // Set default value for userID if not defined
        if(!isset($userID) || $userID == null || $userID == ""){
            $userID = $_SESSION['userID'];
        }else{
            $userID = intval($userID);

            if($userID <=0){
                return false;
            }
        }
        if(!isset($password) || $password == ""){
            return false;
        }

        if($userID != $_SESSION['userID']){
            // Changing password for another user. Verify permission.
            $permissions = $this->permissionCheck("anticUser");
            if(!isset($permissions['data']['write']) || $permissions['data']['write']!=1) {
                // User does not have admin power over user table
                return false;
            }
        }
        // Connect DB
        $this->initDB();

        // Hash PW
        $options = ['cost' => 12];
        $hashedPW = password_hash($password, PASSWORD_BCRYPT, $options);

        // Prepare Update
        $sql = "UPDATE anticUser SET password=:password WHERE userID=:userID";

        $statement = $this->db->prepare($sql);

        $statement->bindValue(':password', $hashedPW);
        $statement->bindValue(':userID', intval($userID));

        // Run Update and Return Result
        $success = $statement->execute();
        if (!$success) {
            $output['status'] = "error";
            $output['error'] = $statement->errorCode();
            $output['sqlError'] = $statement->errorInfo();
            //$output['sqlError']['sql']=$sql;
        }else{
            $output['status'] = "success";
        }

        return $output;

    }// setPassword


    /*
     * Checks permissions for a single element or table.
     *
     * @param string $tableName This is the name of the table requested.
     * @param array/string $primaryKeys This can be an array of all required primary key/value pairs OR a JSON object of the same
     * @param int   $userID This optionally is the userID who's permissions are being checked. If not set, it will default to the session userID.
     *
     */
    function permissionCheck($tableName,$primaryKeys,$userID){
        if(!isset($userID)){
            $userID = $_SESSION['userID'];
        }else{
            $userID = intval($userID);
            if($userID <=0){
                return false;
            }
        }
        $this->initDB();

        $sql = "SELECT IF(sum(PMU.read)>=1,1,0) as `read`, IF(sum(PMU.write)>=1,1,0) as `write`,IF(sum(PMU.execute)>=1,1,0) as `execute`,IF(sum(PMU.administer)>=1,1,0) as `administer` FROM
(SELECT P.* FROM anticPermission P
INNER JOIN anticUserGroup UP ON UP.groupID = P.groupID AND UP.userID = :userID
WHERE P.tableName = :tableName
AND (P.pkArrayBaseJSON IS NULL OR P.pkArrayBaseJSON = :pkJSON)
UNION 
SELECT P.* FROM anticPermission P
WHERE P.tableName = :tableName
AND (P.pkArrayBaseJSON IS NULL OR P.pkArrayBaseJSON = :pkJSON)
AND P.groupID IS NULL
AND P.userID = :userID) as PMU;";


        $statement = $this->db->prepare($sql);

        $statement->bindValue(':userID', $userID);
        $statement->bindValue(':tableName', $tableName);
        if(is_array($primaryKeys)){
            $primaryKeys = json_encode($primaryKeys);
        }
        $statement->bindValue(':pkJSON', $primaryKeys);

        $success = $statement->execute();
        if (!$success) {
            $output['status'] = "error";
            $output['error'] = $statement->errorCode();
            $output['sqlError'] = $statement->errorInfo();
            //$output['sqlError']['sql']=$sql;
        }else{
            while ($data = $statement->fetch(PDO::FETCH_ASSOC)) {
                $output['status'] = "success";
                $output['data'] = $data;
                //$output['sql']=$sql;
            }
        }

        return $output;
    } // permissionCheck


} // end class anTicUser