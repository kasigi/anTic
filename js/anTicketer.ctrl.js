angular.module('anTicketer')
    .controller("TableController", function ($scope, $route, $location, $routeParams, $http, $filter) {
        $http.defaults.headers.post["Content-Type"] = "application/x-www-form-urlencoded";
        $scope.dataModel = {};
        $scope.currentTableSelected = "";
        $scope.currentTable = {};
        $scope.currentTable.dataModel = {};
        $scope.currentTable.data = [];
        $scope.currentTable.versionLogData = {};
        $scope.currentTable.recordTotalCount = 0;
        $scope.currentTable.fkdata = {};
        $scope.currentTable.pkdata = {};
        $scope.recordEditPending = [];
        $scope.recordDeletePending = [];
        $scope.fieldTypeMapping = {};
        $scope.displayMode = "table";
        $scope.singleRecordTarget = {};
        $scope.displayCountOptions = [5,10,50,100,250,1000,5000];
        $scope.paginationOptions = [];
        // Initial Run
        // Get initial number of records to display
        if (typeof $routeParams.displayCount != "undefined") {
            $routeParams.displayCount = parseInt($routeParams.displayCount);
            if($scope.displayCountOptions.indexOf($routeParams.displayCount) > -1){
                $scope.displayCount = $routeParams.displayCount;
            }else{
                $scope.displayCount = 1000;
            }
        } else {
            $scope.displayCount = 1000;
        }

        // Get initial display records offset
        if (typeof $routeParams.displayOffset != "undefined" && parseInt($routeParams.displayOffset) > -1) {
            $scope.displayOffset = parseInt($routeParams.displayOffset);
        } else {
            $scope.displayOffset = 0;
        }


        // Get data model

        var modelRequest = {};
        modelRequest.action = "buildModels";

        var responsePromise = $http.post("interface/data.php", modelRequest);

        $scope.firstRun = true;
        responsePromise.success(function (data, status, headers, config) {

            console.log(data);
            $scope.dataModel = data;
            $scope.tableList = [];
            $scope.tableIndex = [];
            jQuery.each(data, function (index, value) {
                $scope.tableList.push({"tableName": index, "displayName": value.displayName});
                $scope.tableIndex.push(index);
            });

            // Get initial current table selection
            if (typeof $routeParams.currentTableSelected != "undefined" && $scope.tableIndex.indexOf($routeParams.currentTableSelected) > -1) {
                $scope.currentTableSelected = $routeParams.currentTableSelected;
            } else {
                $scope.currentTableSelected = $scope.tableList[0]['tableName'];
            }

            $scope.initialSelectTable();

        });
        responsePromise.error(function (data, status, headers, config) {
            alert("AJAX failed!");
        });

        // Get meta data on possible field types
        var responsePromise2 = $http.get("dataModelMeta/validDataTypesMap.json");
        responsePromise2.success(function (data, status, headers, config) {
            console.log(data);
            $scope.fieldTypeMapping = data;
            $scope.initialSelectTable();

        });
        responsePromise2.error(function (data, status, headers, config) {
            alert("AJAX failed!");
        });


        var orderBy = $filter('orderBy');
        $scope.predicate = '';
        $scope.reverse = true;

// End Initialization

        $scope.order = function (predicate) {
            $scope.reverse = ($scope.predicate === predicate) ? !$scope.reverse : false;
            $scope.currentTable.data = orderBy($scope.currentTable.data, predicate, $scope.reverse);
            $scope.predicate = predicate;
            console.log(predicate);
            $scope.rekeyPKData();
            console.log($scope.currentTable);
        };


        $scope.initialSelectTable = function () {
            if(Object.keys($scope.fieldTypeMapping).length > 0 && Object.keys($scope.dataModel).length > 0 && $scope.firstRun == true){
                $scope.firstRun = false;
                $scope.selectTable(function(){
                    // If a specific record has been requested in the url, switch to the singleRecord editing mode
                    if (typeof $routeParams.specificRecord != "undefined") {
                        var candidateRecord = parseInt($routeParams.specificRecord);
                        if(typeof $scope.currentTable.data[candidateRecord] != "undefined"){
                            $scope.editSingleRecord(candidateRecord);
                        }
                    }
                    });
            }
        }// end initialSelectTable


        $scope.editAllRecord = function(){
            $scope.displayMode = "table";
            $location.search('specificRecord',null);
            $scope.selectTable();
        }// end editAllRecord


        $scope.editSingleRecord = function(dataRowID){
            $scope.displayMode = "singleRecord";
            $location.search('specificRecord',dataRowID);
            $scope.getRecord(dataRowID);
            $scope.getVersionRecord(dataRowID);
            // Build Primary Key Array to Get ONE record
        }// end editSingleRecord


        $scope.setDisplayCount = function(displayCount){
            var dataLength = $scope.currentTable.data.length;
            $scope.displayCount = displayCount;

            if(dataLength > displayCount){
                // Data is LONGER than the desired display. The correct display can be achieved by trimming down the data array.
                var amountToRemove = dataLength - displayCount;
                $scope.checkDisplayOffsetAgainstRecordCount();
                $scope.currentTable.data.splice(displayCount,amountToRemove);
                $scope.currentTable.pkdata.splice(displayCount,amountToRemove);
            }else{
                // Data is equal to or shorter than the desired display. Data must be reloaded from the server
                $scope.getAllForTableData($scope.currentTableSelected);
            }
            $scope.calculatePagination();
            $location.search('displayCount', displayCount);

        }//end setDisplayCount


        $scope.checkDisplayOffsetAgainstRecordCount = function(){
            // If there are fewer records to display than allowed by displayCount, show all and set offset back to 0
            if($scope.displayCount > $scope.currentTable.recordTotalCount){
                $scope.displayOffset = 0;
                $location.search('displayOffset', 0);
            }

        }//checkDisplayOffsetAgainstRecordCount


        $scope.setDisplayOffset = function(displayOffset){
            displayOffset = parseInt(displayOffset);
            if(displayOffset < 0){
                displayOffset = 0;
            }
            $scope.displayOffset = displayOffset;

            $location.search('displayOffset', displayOffset);
            $scope.getAllForTableData($scope.currentTableSelected);

        }//setDisplayOffset


        $scope.calculatePagination = function(){
            // Create an array that lists the various record offset numbers for a given table
            $scope.paginationOptions = [];
            if($scope.displayCount>0){
                var pages = Math.ceil(this.currentTable.recordTotalCount / this.displayCount);
                for(var q=0; q<pages; q++){
                    $scope.paginationOptions.push(q*$scope.displayCount);
                }
            }
        }// end calculatePagination


        $scope.getForeignKeyDisplayFieldsForRecordField = function (fieldName, fieldValue) {
            if (typeof($scope.currentTable.fkdata) != "undefined" && typeof($scope.currentTable.fkdata[$scope.currentTable.dataModel.fields[fieldName].foreignKeyTable]) != "undefined") {


                var outputArray = [];
                var thisField = "";
                var fkMatchColumn = $scope.currentTable.dataModel.fields[fieldName].foreignKeyColumns[0];
                var fkTableName = $scope.currentTable.dataModel.fields[fieldName].foreignKeyTable;

                for (var b = 0; b < $scope.currentTable.fkdata[fkTableName].length; b++) {
                    var fkData = $scope.currentTable.fkdata[fkTableName][b];

                    if (fkData[fkMatchColumn] == fieldValue) {

                        for (i = 0; i < $scope.currentTable.dataModel.fields[fieldName].foreignKeyDisplayFields.length; i++) {
                            thisField = $scope.currentTable.dataModel.fields[fieldName].foreignKeyDisplayFields[i];
                            outputArray.push(fkData[thisField]);
                        }
                    }


                }
                return outputArray.join(" ");
            } else {
                return fieldValue;
            }
        }//getForeignKeyDisplayFieldsForRecordField


        // Function Select Table Data
        $scope.selectTable = function (callback) {
            //route.updateParams("table",$scope.currentTableSelected);
            $scope.currentTable.dataModel = $scope.dataModel[this.currentTableSelected];
            $location.search('currentTableSelected', this.currentTableSelected);
            $scope.currentTableSelected = this.currentTableSelected;
            $scope.recordEditPending = [];
            $scope.recordDeletePending = [];
            $scope.getAllForTableData(this.currentTableSelected,callback);
        }// end selectTable


        $scope.fieldType = function (fieldName, tableName) {

            if ('foreignKeyTable' in $scope.dataModel[tableName]['fields'][fieldName]) {
                return "foreignKey";
            } else {

                // Look for model defined custom field display types
                if(typeof $scope.dataModel[tableName]['fields'][fieldName]['fieldEditDisplayType'] !== 'undefined'){
                    for(var keyType in $scope.fieldTypeMapping.customValidDataTypes){
                        if(keyType == fieldType){
                            console.log("Identified key "+keyType);
                            return keyType;
                        }
                    }
                }

                var fieldType = $scope.dataModel[tableName]['fields'][fieldName]['type'];

                // Use the MYSQL-defined field type
                for(var keyType in $scope.fieldTypeMapping.mysqlDataTypeMap){

                    if(typeof $scope.fieldTypeMapping.mysqlDataTypeMap[keyType] != 'undefined' && $scope.fieldTypeMapping.mysqlDataTypeMap[keyType].indexOf(fieldType)>-1){
                        return keyType;
                    }
                }

                return "text";
            }
        }

        // Function Load Table Data
        $scope.getAllForTableData = function (tableName,callback) {
            var tableData = {};
            tableData.tableName = tableName;
            tableData.action = "getall";

            tableData.count = $scope.displayCount;
            tableData.offset = $scope.displayOffset;
            console.log(tableData);

            var responsePromise = $http.post("interface/data.php", tableData);

            responsePromise.success(function (data, status, headers, config) {

                console.log(data);
                if (typeof data['data'] != "undefined") {
                    // Table has data
                    $scope.currentTable.data = data['data'];
                    if(typeof data['recordTotalCount'] != "undefined"){
                        $scope.currentTable.recordTotalCount = parseInt(data['recordTotalCount']);
                    }else{
                        $scope.currentTable.recordTotalCount = data['data'].length;
                    }

                } else {
                    // Table is presently empty
                    $scope.currentTable.data = [];
                    $scope.currentTable.recordTotalCount = 0;

                }
                $scope.currentTable.pkdata = [];

                for (var row in data['data']) {
                    $scope.currentTable.pkdata[row] = {};
                    for (var keyID in $scope.currentTable.dataModel.primaryKey) {
                        var keyName = $scope.currentTable.dataModel.primaryKey[keyID];
                        $scope.currentTable.pkdata[row][keyName] = data['data'][row][keyName];
                    }
                }
                if (data.hasOwnProperty("fkdata")) {
                    $scope.currentTable.fkdata = data['fkdata'];
                } else {
                    $scope.currentTable.fkdata = {};
                }

                $scope.calculatePagination();
                $scope.checkDisplayOffsetAgainstRecordCount();
                if(callback){
                    callback();
                };
                console.log($scope.currentTable);

            });
            responsePromise.error(function (data, status, headers, config) {
                alert("AJAX failed!");
            });
        }// end getAllForTableData


        $scope.setRecordEditPending = function (dataRowID) {
            //ant-entry-editPending
            if ($scope.recordEditPending.indexOf(dataRowID) == -1) {
                $scope.recordEditPending.push(dataRowID);
            }
            //return "ant-entry-editPending";
        }// end recordEditPending


        $scope.getRecord = function (dataRowID) {
            var tableData = {};
            tableData.tableName = $scope.currentTableSelected;
            tableData.action = "get";
            tableData.pkey = {};
            tableData.count = 1;
            tableData.offset = 0;

            var keyName = "";
            for (var keyID in $scope.currentTable.dataModel.primaryKey) {
                keyName = $scope.currentTable.dataModel.primaryKey[keyID];
                tableData.pkey[keyName] = $scope.currentTable.pkdata[dataRowID][keyName];
            }
            var responsePromise = $http.post("interface/data.php", tableData);
            responsePromise.success(function (data, status, headers, config) {

                if($scope.displayMode == "singleRecord"){
                    // Empty the data table if only editing a single record
                    $scope.currentTable.data = [];
                    $scope.currentTable.data.push(data['data'][0]);

                    // Rebuild the pkData if switching to single record / in single record dislay mode
                    $scope.rekeyPKData();
                }else{
                    $scope.currentTable.data[dataRowID] = data['data'][0];
                }


            });
            responsePromise.error(function (data, status, headers, config) {
                alert("AJAX failed!");
            });

        }// end getRecord



        $scope.getVersionRecord = function (dataRowID) {
            var tableData = {};
            tableData.tableName = $scope.currentTableSelected;
            tableData.action = "getVersionLog";
            tableData.pkey = {};

            var keyName = "";
            for (var keyID in $scope.currentTable.dataModel.primaryKey) {
                keyName = $scope.currentTable.dataModel.primaryKey[keyID];
                tableData.pkey[keyName] = $scope.currentTable.pkdata[dataRowID][keyName];
            }
            var responsePromise = $http.post("interface/data.php", tableData);
            responsePromise.success(function (data, status, headers, config) {
                  //versionLogData
                $scope.currentTable.versionLogData[dataRowID] = data['data'];
                console.log($scope.currentTable.versionLogData);

            });
            responsePromise.error(function (data, status, headers, config) {
                alert("AJAX failed!");
            });

        }// end getRecord



        $scope.saveRecord = function (dataRowID) {
            console.log($scope.currentTable.data[dataRowID]);

            // Set up submission variables
            var tableData = {};
            tableData.tableName = $scope.currentTableSelected;
            tableData.data = {};
            var keyName = "";


            // Determine if this is a new or existing entry to update
            if (typeof $scope.currentTable.pkdata[dataRowID] == "undefined") {
                // New record
                tableData.action = "add";


            } else {
                // Edit extant record
                tableData.action = "set";
                tableData.pkey = {};

                for (var keyID in $scope.currentTable.dataModel.primaryKey) {
                    keyName = $scope.currentTable.dataModel.primaryKey[keyID];
                    tableData.pkey[keyName] = $scope.currentTable.pkdata[dataRowID][keyName];
                }
            }


            for (var fieldName in $scope.currentTable.dataModel.fields) {
                tableData.data[fieldName] = $scope.currentTable.data[dataRowID][fieldName];
            }

console.log("add/update");
            console.log(tableData);
            var responsePromise = $http.post("interface/data.php", tableData);

            responsePromise.success(function (data, status, headers, config) {
                console.log(status);
                console.log(data);

                // Remove the edit pending status of the row
                var updatedIndex = $scope.recordEditPending.indexOf(dataRowID);
                if (updatedIndex > -1) {
                    $scope.recordEditPending.splice(updatedIndex, 1);
                }

                // Add Primary key if it does not exist
                if (typeof $scope.currentTable.pkdata[dataRowID] == "undefined") {

                    // Create new PKData Record
                    var newPKData = {};

                    // For each primary key field, add to PK Record
                    for (var keyID in $scope.currentTable.dataModel.primaryKey) {
                        keyName = $scope.currentTable.dataModel.primaryKey[keyID];

                        if ($scope.currentTable.dataModel.fields[keyName].auto_increment == true) {
                            // If autoincrementing, get inserted ID from the database return and use it
                            if (typeof data['insertedID'] != "undefined") {
                                newPKData[keyName] = data['insertedID'];
                                $scope.currentTable.data[dataRowID][keyName] = data['insertedID'];
                                console.log([keyName, data['insertedID']]);
                            }
                        } else {
                            // Set the data summarily if not autoincrementing
                            newPKData[keyName] = tableData.data[fieldName];
                        }
                    }
                    $scope.currentTable.pkdata.push(newPKData);
                }


            });
            responsePromise.error(function (data, status, headers, config) {
                alert("AJAX failed!");
            });
        }


        $scope.saveAllPendingEdits = function(){
            for(var s = 0;s < $scope.recordEditPending.length;s++){
                $scope.saveRecord($scope.recordEditPending[s]);
            }
        }


        $scope.addRecord = function () {

            // Create the new record object
            var newRecord = {};

            // Add the fields
            for (var fieldID in $scope.currentTable.dataModel.fieldOrder) {
                var fieldName = $scope.currentTable.dataModel.fieldOrder[fieldID];
                if ($scope.currentTable.dataModel.fields[fieldName].default != null) {

                    // If a default value is defined by the table, use that
                    newRecord[fieldName] = $scope.currentTable.dataModel.fields[fieldName].default;

                } else if (typeof $scope.currentTable.dataModel.fields[fieldName].foreignKeyColumns != "undefined") {

                    // If FK, select first option in FK
                    var firstForeignKeyColumn = $scope.currentTable.dataModel.fields[fieldName].foreignKeyColumns[0];
                    var foreignKeyTable = $scope.currentTable.dataModel.fields[fieldName].foreignKeyTable;
                    newRecord[fieldName] = $scope.currentTable.fkdata[foreignKeyTable][0][firstForeignKeyColumn];


                } else {
                    // Default to ""
                    newRecord[fieldName] = "";
                }
            }


            // Add the new record to the data
            var newIndex = $scope.currentTable.data.push(newRecord) - 1;
            $scope.setRecordEditPending(newIndex);

            // Create the PK Index Record
            /*
             var newPKRecord = {};
             for (var fieldID in $scope.currentTable.dataModel.primaryKey) {
             var fieldName = $scope.currentTable.dataModel.primaryKey[fieldID];
             newPKRecord[fieldName]=newRecord[fieldName];
             }


             $scope.currentTable.pkdata.push(newPKRecord) - 1;
             */

        }// end addRecord


        $scope.recordPrepareDelete = function (dataRowID) {

            if ($scope.recordDeletePending.indexOf(dataRowID) == -1) {
                $scope.recordDeletePending.push(dataRowID);
            }

        } //recordPrepareDelete


        $scope.deleteRecord = function (dataRowID) {


            var tableData = {};
            tableData.tableName = $scope.currentTableSelected;
            tableData.action = "delete";
            tableData.pkey = {};


            if (typeof $scope.currentTable.pkdata[dataRowID] == "undefined") {
                // Record is new and has not yet been saved
                $scope.currentTable.data.splice(dataRowID, 1);
                var deleteIndex = $scope.recordDeletePending.indexOf(dataRowID);
                if (deleteIndex > -1) {
                    $scope.recordDeletePending.splice(deleteIndex, 1);
                }
            } else {
                var keyName = "";
                for (var keyID in $scope.currentTable.dataModel.primaryKey) {
                    keyName = $scope.currentTable.dataModel.primaryKey[keyID];
                    tableData.pkey[keyName] = $scope.currentTable.pkdata[dataRowID][keyName];
                }

                // Record is in database and needs to be deleted
                var responsePromise = $http.post("interface/data.php", tableData);
                responsePromise.success(function (data, status, headers, config) {

                    // Remove from data object
                    //delete $scope.currentTable.data[dataRowID];
                    $scope.currentTable.data.splice(dataRowID, 1);
                    // Remove from Primary Key Index
                    $scope.currentTable.pkdata.splice(dataRowID, 1);
                    // Remove from pending delete
                    var deleteIndex = $scope.recordDeletePending.indexOf(dataRowID);
                    if (deleteIndex > -1) {
                        $scope.recordDeletePending.splice(deleteIndex, 1);
                    }

                });
                responsePromise.error(function (data, status, headers, config) {
                    alert("AJAX failed!");
                });
            }


        }// end deleteRecord


        $scope.rekeyPKData = function () {
            //This function updates the pkdata array after a sort / filter operation
            var newPKData = [];
            for (var entryID in $scope.currentTable.data) {
                var newPKDataRow = {};
                for (var pkFieldID in $scope.currentTable.dataModel.primaryKey) {
                    var thisField = $scope.currentTable.dataModel.primaryKey[pkFieldID];
                    newPKDataRow[thisField] = $scope.currentTable.data[entryID][thisField];
                }
                newPKData.push(newPKDataRow);
            }
            $scope.currentTable.pkdata = newPKData;

        }//rekeyPKData
        

    })
    .directive("tablefield", function () {
        return {
            restrict: 'E',
            replace: 'true',
            scope: false,
            templateUrl: 'partials/directive-templates/tablefield.html'
        }
    })
    .directive("datatable",function(){
        return {
            restrict: 'E',
            replace: 'true',
            scope: false,
            templateUrl: 'partials/directive-templates/dataTable.html'
        }
    })
    .directive("singlerecordcontrols",function(){
        return {
            restrict: 'E',
            replace: 'true',
            scope: false,
            templateUrl: 'partials/directive-templates/singleRecordControls.html'
        }
    })
    .directive("tablerecordcontrols",function(){
        return {
            restrict: 'E',
            replace: 'true',
            scope: false,
            templateUrl: 'partials/directive-templates/tableRecordControls.html'
        }
    })
    .directive("singlerecordedit",function(){
        return {
            restrict: 'E',
            replace: 'true',
            scope: false,
            templateUrl: 'partials/directive-templates/singleRecordEdit.html'
        }
    })
    .directive("tablelistpagination",function(){
        return {
            restrict: 'E',
            replace: 'true',
            scope: false,
            templateUrl: 'partials/directive-templates/tableListPagination.html'
        }
    })

    .directive("tableselect",function(){
        return {
            restrict: 'E',
            replace: 'true',
            scope: false,
            templateUrl: 'partials/directive-templates/tableSelect.html'
        }
    });