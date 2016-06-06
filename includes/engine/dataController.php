<?php

class anTicData
{

    public $db;
    public $dataModels;
    public $validRequests = array("get", "getversionlog","getall", "set", "delete", "add","buildModels");
    public $validDataModelTypes = array("data", "system");


    function anTicData()
    {

    }


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
    }


    function buildDataModels($dataType)
    {
        if (!in_array($dataType,$this->validDataModelTypes)) {
            return false;
        }


        if (count($this->dataModels[$dataType]) <= 0) {
            $this->initDB();

            if (!in_array($dataType, $this->validDataModelTypes)) {
                $dataType = "data";
            }

            // Build the Data Models if they do not exist
            if ($this->dataModels[$dataType] == null) {
                // Load Data Model Files

                $files = glob(dirname(__FILE__).'/../../dataModelMeta/' . $dataType . '/*.{json}', GLOB_BRACE);
                foreach ($files as $file) {
                    $tableName = basename($file, ".json");
                    $this->dataModels[$dataType][$tableName] = json_decode(file_get_contents($file), true);
                    if (!isset($this->dataModels[$dataType][$tableName]['displayName'])) {
                        $this->dataModels[$dataType][$tableName]['displayName'] = $tableName;
                    }

                    if (isset($this->dataModels[$dataType][$tableName]['listViewDisplayFields'])) {
                        // Clean the inputs as these will later be used in SQL
                        foreach ($this->dataModels[$dataType][$tableName]['listViewDisplayFields'] as $key => $fieldName) {
                            $this->dataModels[$dataType][$tableName]['listViewDisplayFields'][$key] = preg_replace("/[^a-zA-Z0-9\-_]/", "", $fieldName);
                        }

                    }


                    //[$interim]
                }// Populate Load Default/Standard Values From Database Structure


                // Populate Columns from Table Structure
                foreach ($this->dataModels[$dataType] as $tableName => $dataModel) {

                    $stmt = $this->db->prepare("DESCRIBE $tableName;");

                    $stmt->execute();
                    //$output = $stmt->fetchAll();
                    //var_dump($output);
                    while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        if ($data['Field'] != "") {
                            $data['Field'] = preg_replace("/[^a-zA-Z0-9\-_]/", "", $data['Field']); // Cleaning/safety

                            // Add field to model
                            $this->dataModels[$dataType][$tableName]['fieldOrder'][] = $data['Field'];


                            // Specify whether null is allowed
                            if ($data['Null'] == "YES") {
                                $this->dataModels[$dataType][$tableName]['fields'][$data['Field']]['null'] = true;
                            } else {
                                $this->dataModels[$dataType][$tableName]['fields'][$data['Field']]['null'] = false;
                            }

                            // Add primary keys to primaryKey list
                            $this->dataModels[$dataType][$tableName]['fields'][$data['Field']]['default'] = $data['Default'];
                            if ($data['Key'] == "PRI") {
                                $this->dataModels[$dataType][$tableName]['primaryKey'][] = $data['Field'];
                            }
                            if (strpos($data['Extra'], "auto_increment") !== false) {
                                $this->dataModels[$dataType][$tableName]['fields'][$data['Field']]['auto_increment'] = true;
                            } else {
                                $this->dataModels[$dataType][$tableName]['fields'][$data['Field']]['auto_increment'] = false;
                            }


                            // Split field types and length
                            $pattern = '/\(([0-9]*)\)/';
                            $lengthMatches = null;
                            $data['Type'] = strtolower($data['Type']);

                            // Remove any length specifications from the data type itself
                            $this->dataModels[$dataType][$tableName]['fields'][$data['Field']]['type'] = preg_replace('/(\(.*\))/', "", $data['Type']);


                        }
                    }

                    // If no primary key is defined assume that all fields are required
                    if (!isset($this->dataModels[$dataType][$tableName]['primaryKey'])) {
                        foreach ($this->dataModels[$dataType][$tableName]['fields'] as $fieldName => $fieldData) {
                            $this->dataModels[$dataType][$tableName]['primaryKey'][] = preg_replace("/[^a-zA-Z0-9\-_]/", "", $fieldName);
                        }
                    }

                    // Populate Default Values in Models
                    if (!isset($dataModel['displayName'])) {
                        $this->dataModels[$dataType][$tableName]['displayName'] = ucwords($tableName);
                    }
                    foreach ($this->dataModels[$dataType][$tableName]['fields'] as $fieldMachineName => $field) {
                        if (!isset($field['displayName'])) {
                            $this->dataModels[$dataType][$tableName]['fields'][$fieldMachineName]['displayName'] = ucwords($fieldMachineName);
                        }
                    }

                }


                // Add maximum field lengths to definitions
                $sql = "SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE, CHARACTER_MAXIMUM_LENGTH
  FROM INFORMATION_SCHEMA.COLUMNS
WHERE table_schema = DATABASE()";


                $stmt = $this->db->prepare($sql);

                $stmt->execute();
                while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    if (isset($this->dataModels[$dataType][$data['TABLE_NAME']])) {

                        $this->dataModels[$dataType][$data['TABLE_NAME']]['fields'][$data['COLUMN_NAME']]['length'] = intval($data['CHARACTER_MAXIMUM_LENGTH']);

                        // Extract length from field type for INTs
                        if ($this->dataModels[$dataType][$data['TABLE_NAME']]['fields'][$data['COLUMN_NAME']]['length'] == null) {
                            preg_match("/\(([0-9]*)\)/", $data['COLUMN_TYPE'], $matches);
                            if (isset($matches[1]) && intval($matches[1]) > 0) {
                                $this->dataModels[$dataType][$data['TABLE_NAME']]['fields'][$data['COLUMN_NAME']]['length'] = intval($matches[1]);
                            }
                        }


                        // Force TINYINT(1) to report as boolean
                        if ($this->dataModels[$dataType][$data['TABLE_NAME']]['fields'][$data['COLUMN_NAME']]['type'] == "tinyint" && $this->dataModels[$dataType][$data['TABLE_NAME']]['fields'][$data['COLUMN_NAME']]['length'] == 1) {
                            $this->dataModels[$dataType][$data['TABLE_NAME']]['fields'][$data['COLUMN_NAME']]['type'] = "boolean";
                        }
                    }
                }


                // Get Foreign Key List
                $sql = "SELECT i.TABLE_NAME, i.CONSTRAINT_TYPE, i.CONSTRAINT_NAME, k.REFERENCED_TABLE_NAME, k.REFERENCED_COLUMN_NAME 
FROM information_schema.TABLE_CONSTRAINTS i 
LEFT JOIN information_schema.KEY_COLUMN_USAGE k ON i.CONSTRAINT_NAME = k.CONSTRAINT_NAME 
WHERE i.CONSTRAINT_TYPE = 'FOREIGN KEY' 
AND i.TABLE_SCHEMA = DATABASE();";

                $stmt = $this->db->prepare($sql);

                $stmt->execute();
                while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    if (isset($this->dataModels[$dataType][$data['TABLE_NAME']])) {

                        $this->dataModels[$dataType][$data['TABLE_NAME']]['fields'][$data['CONSTRAINT_NAME']]['foreignKeyTable'] = $data['REFERENCED_TABLE_NAME'];
                        $this->dataModels[$dataType][$data['TABLE_NAME']]['fields'][$data['CONSTRAINT_NAME']]['foreignKeyColumns'][] = $data['REFERENCED_COLUMN_NAME'];

                        // Set default FK display field
                        if (!isset($this->dataModels[$dataType][$data['TABLE_NAME']]['fields'][$data['CONSTRAINT_NAME']]['foreignKeyDisplayFields'])) {
                            $this->dataModels[$dataType][$data['TABLE_NAME']]['fields'][$data['CONSTRAINT_NAME']]['foreignKeyDisplayFields'][] = $data['REFERENCED_COLUMN_NAME'];
                        }
                    }
                }
            }

        }


        return $this->dataModels[$dataType];

    }// end buildDataModels


    function inferTableDataType($tableName)
    {
        foreach ($this->validDataModelTypes as $dataType) {

            // If the requisite data model list isn't loaded, attempt to load it
            if (count($this->dataModels[$dataType]) <= 0) {
                $this->buildDataModels($dataType);
            }

            // Look for the table name in the models list
            foreach ($this->dataModels as $tableNameModel => $tableModel) {
                if ($tableName == $tableNameModel) {
                    return $dataType;
                }
            }
        }

        // default to the first datatype
        return $this->validDataModelTypes[0];

    }// end inferTableDataType


    function gatherInputs()
    {
        // Gathers the inputs submitted via $_REQUEST, etc. and prepares theme for an action

        // Gather data from angular's post method
        $postdata = file_get_contents("php://input");
        $aRequest = json_decode($postdata, true);


        if (isset($aRequest['pkey'])) {
            if (!is_array($aRequest['pkey'])) {
                $primaryRecordKeys = json_decode($aRequest['pkey'], true);
            } else {
                $primaryRecordKeys = $aRequest['pkey'];
            }
        } else {
            $primaryRecordKeys = [];
        }


// Check for valid request action
        if (!isset($aRequest['action'])) {
            $returnData['error'] = "No action defined";
            return $returnData;
        }

// Check for valid request action
        if (!isset($aRequest['tableName'])) {
            $returnData['error'] = "No tableName defined";
            return $returnData;
        }

        $aRequest['action'] = strtolower($aRequest['action']);
        if (!in_array($aRequest['action'], $this->validRequests)) {
            $returnData['error'] = "Invalid Request Type";
            return $returnData;

        }

        // Build the data type models
        $this->buildDataModels('data');

        if (!isset($this->dataModels['data'][$aRequest['tableName']])) {

            // Check to see if request is system table
            $this->buildDataModels('system');
            if (!isset($this->dataModels['system'][$aRequest['tableName']])) {
                $returnData['error'] = "Invalid Table Selected";
                return $returnData;
                }
        }
            $targetTable = $aRequest['tableName'];


        $dataType = $this->inferTableDataType($targetTable);


        // If $primaryRecordKeys are specified, validate against table definition
        foreach ($primaryRecordKeys as $key => $value) {
            if (!in_array($key, $this->dataModels[$dataType][$aRequest['tableName']]['primaryKey'])) {
                $returnData['error'] = "Invalid Primary Key Request";
                return $returnData;
            }
        }


// If Data is being submitted, validate field list
        if (isset($aRequest['data'])) {
            if (!is_array($aRequest['data'])) {
                $inputData = json_decode($aRequest['data'], true);
            } else {
                $inputData = $aRequest['data'];
            }
            foreach ($inputData as $key => $value) {
                if (!isset($this->dataModels['data'][$aRequest['tableName']]['fields'][$key])) {
                    $returnData['error'] = "Invalid Fields in Data Request";
                    return $returnData;
                }
            }
        } else {
            $inputData = [];
        }


        $returnArr = [];
        $returnArr['inputData'] = $inputData;
        $returnArr['targetTable'] = $targetTable;
        $returnArr['action'] = $aRequest['action'];
        $returnArr['primaryRecordKeys'] = $primaryRecordKeys;
        return $returnArr;

    }// end function gatherInputs


    function dataGetAll($targetTable)
    {
        $bindArray = [];
        $fieldArray = [];
        $fieldListString = "*";
        // Look for listViewDisplayFields
        if (isset($this->dataModels['data'][$targetTable]['listViewDisplayFields'])) {

            // Add the manually specified display fields
            foreach ($this->dataModels['data'][$targetTable]['listViewDisplayFields'] as $fieldName) {
                if (!in_array($fieldName, $fieldArray)) {
                    $fieldArray[] = $fieldName;
                }
            }

            // Primary keys are mandatory fields and must be added if not already specified
            foreach ($this->dataModels['data'][$targetTable]['primaryKey'] as $fieldName) {
                if (!in_array($fieldName, $fieldArray)) {
                    $fieldArray[] = $fieldName;
                }
            }

            $fieldListString = implode(", ", $fieldArray);
        }

        $sql = "SELECT $fieldListString FROM $targetTable";  // This is the main query
        $countSql = "SELECT count(*) as recordTotalCount FROM $targetTable"; // This is the query used to determine total number of records

        //$this->addWhereToQuery($sql,$primaryRecordKeys);
        $sql = $this->addLimitsToQuery($sql);

        // Run Query
        $statement = $this->db->prepare($sql);
        foreach ($bindArray as $bKey => $bValue) {
            $statement->bindValue($bKey, $bindArray[$bKey]);
        }

        $success = $statement->execute();
        if (!$success) {
            $output['status'] = "error";
            $output['error'] = $statement->errorCode();
            $output['sqlError'] = $statement->errorInfo();
            //$output['sqlError']['sql']=$sql;
            return $output;
        }


        // Process Results
        $output = null;
        while ($data = $statement->fetch(PDO::FETCH_ASSOC)) {
            $output['status'] = "success";
            $output['data'][] = $data;
            //$output['sql']=$sql;
        }

        if ($countSql != "") {
            $statement = $this->db->prepare($countSql);
            $success = $statement->execute();
            while ($data = $statement->fetch(PDO::FETCH_ASSOC)) {
                $output['recordTotalCount'] = $data['recordTotalCount'];
                //$output['sql']=$sql;
            }
        }
        $foreignKeyTables = [];
        // look for Foreign Key fields
        foreach ($this->dataModels['data'][$targetTable]['fields'] as $field) {
            // Create list of foreignKeyTables and all required fields
            if (isset($field['foreignKeyTable'])) {
                if (!isset($foreignKeyTables[$field['foreignKeyTable']])) {
                    $foreignKeyTables[$field['foreignKeyTable']] = [];
                }

                if (isset($field['foreignKeyColumns'])) {
                    foreach ($field['foreignKeyColumns'] as $fkField) {
                        if (!in_array($fkField, $foreignKeyTables[$field['foreignKeyTable']])) {
                            $foreignKeyTables[$field['foreignKeyTable']][] = $fkField;
                        }
                    }
                }


                if (isset($field['foreignKeyDisplayFields'])) {
                    foreach ($field['foreignKeyDisplayFields'] as $fkField) {
                        if (!in_array($fkField, $foreignKeyTables[$field['foreignKeyTable']])) {
                            $foreignKeyTables[$field['foreignKeyTable']][] = $fkField;
                        }
                    }
                }

            }
        }

        // Get the foreign key data tables

        foreach ($foreignKeyTables as $fkTable => $fkFields) {
            $sql = "SELECT " . implode(", ", $fkFields) . " FROM $fkTable";

            $statement = $this->db->prepare($sql);
            $statement->execute();

            // Process Results
            while ($data = $statement->fetch(PDO::FETCH_ASSOC)) {
                $output['fkdata'][$fkTable][] = $data;
            }
        }
        // Return Results
        return $output;

    }//dataGetAll


    function dataGet($targetTable, $primaryRecordKeys)
    {
        $bindArray = [];
        $sql = "SELECT * FROM $targetTable";
        // Run Query
        $statement = $this->db->prepare($sql);
        foreach ($bindArray as $bKey => $bValue) {
            $statement->bindValue($bKey, $bindArray[$bKey]);
        }

        $success = $statement->execute();
        if (!$success) {
            $output['status'] = "error";
            $output['error'] = $statement->errorCode();
            $output['sqlError'] = $statement->errorInfo();
            //$output['sqlError']['sql']=$sql;
            return $output;
        }


        $where = $this->addWhereToQuery($sql,$primaryRecordKeys);
        $sql = $where['sql'];
        $bindArray = array_merge($bindArray,$where['bindArray']);

        // Run Query
        $statement = $this->db->prepare($sql);
        foreach($bindArray as $bKey => $bValue){
            $statement->bindValue($bKey, $bindArray[$bKey]);
        }

        $success = $statement->execute();
        if(!$success){
            $output['status']="error";
            $output['error']=$statement->errorCode();
            $output['sqlError']=$statement->errorInfo();
            //$output['sqlError']['sql']=$sql;
            return $output;
        }

        // Process Results
        $output = null;
        while ($data = $statement->fetch(PDO::FETCH_ASSOC)) {
            $output['status'] = "success";
            $output['data'][] = $data;
            //$output['sql']=$sql;
        }


        $foreignKeyTables = [];
        // look for Foreign Key fields
        foreach ($this->dataModels['data'][$targetTable]['fields'] as $field) {
            // Create list of foreignKeyTables and all required fields
            if (isset($field['foreignKeyTable'])) {
                if (!isset($foreignKeyTables[$field['foreignKeyTable']])) {
                    $foreignKeyTables[$field['foreignKeyTable']] = [];
                }

                if (isset($field['foreignKeyColumns'])) {
                    foreach ($field['foreignKeyColumns'] as $fkField) {
                        if (!in_array($fkField, $foreignKeyTables[$field['foreignKeyTable']])) {
                            $foreignKeyTables[$field['foreignKeyTable']][] = $fkField;
                        }
                    }
                }


                if (isset($field['foreignKeyDisplayFields'])) {
                    foreach ($field['foreignKeyDisplayFields'] as $fkField) {
                        if (!in_array($fkField, $foreignKeyTables[$field['foreignKeyTable']])) {
                            $foreignKeyTables[$field['foreignKeyTable']][] = $fkField;
                        }
                    }
                }

            }
        }

        // Get the foreign key data tables

        foreach ($foreignKeyTables as $fkTable => $fkFields) {
            $sql = "SELECT " . implode(", ", $fkFields) . " FROM $fkTable";

            $statement = $this->db->prepare($sql);
            $statement->execute();

            // Process Results
            while ($data = $statement->fetch(PDO::FETCH_ASSOC)) {
                $output['fkdata'][$fkTable][] = $data;
            }
        }


        // Return Results
        return $output;
    }//dataGet


    function dataVersionList($targetTable, $primaryRecordKeys){

        if(!isset($targetTable) || !isset($primaryRecordKeys)){
            $output['status']="error";
            $output['error']="A target table and primary key set MUST be specified";
        }

        $sql = "SELECT id, timestamp, userID FROM anTicketer.anticVersionLog WHERE tableName = :tableName AND pkArrayBaseJson = :pkArrayBaseJson ORDER BY timestamp DESC;";

        $statement = $this->db->prepare($sql);
        $statement->bindValue(":tableName", $targetTable);
        $statement->bindValue(":pkArrayBaseJson", json_encode($primaryRecordKeys));

        $success = $statement->execute();
        if(!$success){
            $output['status']="error";
            $output['error']=$statement->errorCode();
            $output['sqlError']=$statement->errorInfo();
            //$output['sqlError']['sql']=$sql;
            return $output;
        }

        // Process Results
        $output = null;
        while ($data = $statement->fetch(PDO::FETCH_ASSOC)) {
            $output['status'] = "success";
            $output['data'][] = $data;
            //$output['sql']=$sql;
        }

        return $output;

    }//dataVersionList


    function dataVersionGet($targetTable, $primaryRecordKeys,$versionID){


    }//dataVersionGet


    function dataDelete($targetTable, $primaryRecordKeys)
    {
        $bindArray = [];

        if (count($primaryRecordKeys) == 0) {
            $returnData['error'] = "Primary Keys Required for Action";
            return $returnData;
        } else {
            $sql = "DELETE FROM $targetTable";
        }

        $where = $this->addWhereToQuery($sql,$primaryRecordKeys);
        $sql = $where['sql'];
        $bindArray = array_merge($bindArray,$where['bindArray']);
        // Run Query
        $statement = $this->db->prepare($sql);
        foreach ($bindArray as $bKey => $bValue) {
            $statement->bindValue($bKey, $bindArray[$bKey]);
        }

        $success = $statement->execute();
        if (!$success) {
            $output['status'] = "error";
            $output['error'] = $statement->errorCode();
            $output['sqlError'] = $statement->errorInfo();
            //$output['sqlError']['sql']=$sql;
            return $output;
        }


        // Process Results
        $output = null;
        while ($data = $statement->fetch(PDO::FETCH_ASSOC)) {
            $output['status'] = "success";
            $output['data'][] = $data;
            //$output['sql']=$sql;
        }

        // Return Results
        return $output;


    }//dataDelete


    function dataSet($targetTable, $primaryRecordKeys, $inputData)
    {
        $bindArray = [];

        if (count($primaryRecordKeys) == 0) {
            $returnData['error'] = "Primary Keys Required for Action";
            return $returnData;
        } else {
            $sql = "UPDATE $targetTable SET ";
            unset($setArray);
            if (count($inputData) == 0) {
                $returnData['error'] = "Data must be supplied for the Update Action";
                return $returnData;
            }
            foreach ($inputData as $key => $value) {
                $setArray[] = "`" . $key . "`=:" . $key . "DValue";
                //$setArray[]="`".$key."`=\"".$value."\"";

                $bindArray[':' . $key . 'DValue'] = $value;
            }
            $sql .= implode(" , ", $setArray);

        }

        $where = $this->addWhereToQuery($sql,$primaryRecordKeys);
        $sql = $where['sql'];
        $bindArray = array_merge($bindArray,$where['bindArray']);
        // Run Query
        $statement = $this->db->prepare($sql);
        foreach ($bindArray as $bKey => $bValue) {
            $statement->bindValue($bKey, $bindArray[$bKey]);
        }

        $success = $statement->execute();
        if (!$success) {
            $output['status'] = "error";
            $output['error'] = $statement->errorCode();
            $output['sqlError'] = $statement->errorInfo();
            //$output['sqlError']['sql']=$sql;
            return $output;
        }


        // Process Results
        $output = null;
        while ($data = $statement->fetch(PDO::FETCH_ASSOC)) {
            $output['status'] = "success";
            $output['data'][] = $data;
            //$output['sql']=$sql;
        }


        // Return Results
        return $output;
    }//dataUpdate


    function dataAdd($targetTable, $inputData)
    {
        $bindArray = [];

        // INSERT INTO tbl_name (col1,col2) VALUES(15,col1*2);

        $sql = "INSERT INTO $targetTable ";
        unset($setArray);
        unset($setArrayFields);
        if (count($inputData) == 0) {
            $returnData['error'] = "Data must be supplied for the Update Action";
            return $returnData;
        }
        foreach ($inputData as $key => $value) {
            $setArrayFields[] = "`" . $key . "`";
            $setArray[] = ":" . $key . "DValue";
            //$setArray[]="`".$key."`=\"".$value."\"";

            $bindArray[':' . $key . 'DValue'] = $value;
        }
        $sql .= "(" . implode(" , ", $setArrayFields) . ") VALUES (" . implode(" , ", $setArray) . ")";


        // Run Query
        $statement = $this->db->prepare($sql);
        foreach ($bindArray as $bKey => $bValue) {
            $statement->bindValue($bKey, $bindArray[$bKey]);
        }

        $success = $statement->execute();
        if (!$success) {
            $output['status'] = "error";
            $output['error'] = $statement->errorCode();
            $output['sqlError'] = $statement->errorInfo();
            $output['sqlError']['sql']=$sql;
            return $output;

        }


        // Process Results
        $output = null;

        while ($data = $statement->fetch(PDO::FETCH_ASSOC)) {
            $output['status'] = "success";
            $output['data'][] = $data;
            //$output['sql']=$sql;
        }


        $output['insertedID'] = $this->db->lastInsertId();

        // Return Results
        return $output;

    }//dataAdd


    function addWhereToQuery($sql,$primaryRecordKeys){
        unset($sqlWhere);
        if (count($primaryRecordKeys) > 0) {
            // target one record with primary keys
            $output['pk'] = $primaryRecordKeys;
            foreach ($primaryRecordKeys as $key => $value) {
                $sqlWhere[] = " $key = :" . $key . "Value";
                $bindArray[':' . $key . 'Value'] = $value;
            }
            $sql .= " WHERE " . implode(" AND ", $sqlWhere);

        }

        $return['sql'] = $this->addLimitsToQuery($sql);
        $return['bindArray']=$bindArray;
        return $return;
    }


    function addLimitsToQuery($sql){
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
    }// end addLimitsToQuery

    
    function returnError($message)
    {

    }// end returnError


} // end class anTic Data