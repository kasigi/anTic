angular.module('anTicketer').controller("TableController",function($scope, $route,$location, $routeParams, $http) {
    $http.defaults.headers.post["Content-Type"] = "application/x-www-form-urlencoded";
    $scope.dataModel = {};
    $scope.currentTableSelected = "";
    $scope.currentTable = {};
    $scope.currentTable.dataModel = {};
    $scope.currentTable.data = {};
    $scope.currentTable.fkdata = {};
    $scope.currentTable.pkdata = {};
    $scope.recordEditPending = [];
    $scope.recordDeletePending = [];
    // Initial Run
    console.log($routeParams.currentTableSelected);

        // Get data model

    var responsePromise = $http.get("interface/dataStructure.php");
    var firstRun = true;
    responsePromise.success(function(data, status, headers, config) {

        console.log(data);
        $scope.dataModel = data;
        $scope.tableList = [];
        $scope.tableIndex = [];
        jQuery.each(data,function(index,value){
            $scope.tableList.push({"tableName":index,"displayName":value.displayName});
            $scope.tableIndex.push(index);
        });

        if(typeof $routeParams.currentTableSelected  != "undefined" && $scope.tableIndex.indexOf($routeParams.currentTableSelected)>-1){
            $scope.currentTableSelected = $routeParams.currentTableSelected;
        }else{
            $scope.currentTableSelected = $scope.tableList[0]['tableName'];
        };

        //$scope.myData.fromServer = data.title;
        $scope.selectTable();
    });
    responsePromise.error(function(data, status, headers, config) {
        alert("AJAX failed!");
    });

// End Initialization




    $scope.getForeignKeyDisplayFieldsForRecordField = function(fieldName,fieldValue){
        if(typeof($scope.currentTable.fkdata) != "undefined" && typeof($scope.currentTable.fkdata[$scope.currentTable.dataModel.fields[fieldName].foreignKeyTable])!= "undefined"){


        var outputArray=[];
        var thisField = "";
        var fkMatchColumn = $scope.currentTable.dataModel.fields[fieldName].foreignKeyColumns[0];
        var fkTableName = $scope.currentTable.dataModel.fields[fieldName].foreignKeyTable;

        for (var b = 0; b < $scope.currentTable.fkdata[fkTableName].length; b++) {
            var fkData = $scope.currentTable.fkdata[fkTableName][b];

            if(fkData[fkMatchColumn] == fieldValue){

                for (i = 0; i < $scope.currentTable.dataModel.fields[fieldName].foreignKeyDisplayFields.length; i++) {
                    thisField = $scope.currentTable.dataModel.fields[fieldName].foreignKeyDisplayFields[i];
                    outputArray.push(fkData[thisField]);
                }
            }


        }
        return outputArray.join(" ");
        }else{
            return fieldValue;
        }
    }//getForeignKeyDisplayFieldsForRecordField





    // Function Select Table Data
    $scope.selectTable = function(){
        $scope.currentTable.dataModel= $scope.dataModel[$scope.currentTableSelected];
        //route.updateParams("table",$scope.currentTableSelected);
        $location.search('currentTableSelected', $scope.currentTableSelected);
        $scope.getAllForTableData($scope.currentTableSelected);
    }// end selectTable



    $scope.isForeignKey = function(fieldName,tableName){
        if('foreignKeyTable' in $scope.dataModel[tableName]['fields'][fieldName]){
            return true;
        }else{
            return false;
        }
    }//isForeignKey

    $scope.isNotForeignKey = function(fieldName,tableName){
        if($scope.isForeignKey(fieldName,tableName)){
            return false;
        }else{
            return true;
        }
    }//isForeignKey

    // Function Load Table Data
    $scope.getAllForTableData = function(tableName){
        var tableData = {};
        tableData.tableName = tableName;
        tableData.action = "get";
        var responsePromise = $http.post("interface/data.php",tableData);

        responsePromise.success(function(data, status, headers, config) {
            $scope.currentTable.data = data['data'];
            $scope.currentTable.pkdata = [];
            for (var row in data['data']){
                for(var keyID in $scope.currentTable.dataModel.primaryKey){
                    var keyName = $scope.currentTable.dataModel.primaryKey[keyID];
                    $scope.currentTable.pkdata[row] = {};
                    $scope.currentTable.pkdata[row][keyName]=data['data'][row][keyName];
                }
            }
            if("fkdata" in data){
                $scope.currentTable.fkdata = data['fkdata'];
            }else{
                $scope.currentTable.fkdata = {};
            }
            console.log($scope.currentTable);

        });
        responsePromise.error(function(data, status, headers, config) {
            alert("AJAX failed!");
        });
    }// end getAllForTableData


    $scope.setRecordEditPending = function(dataRowID){
        //ant-entry-editPending
        if($scope.recordEditPending.indexOf(dataRowID)==-1){
            $scope.recordEditPending.push(dataRowID);
        }
        //return "ant-entry-editPending";
    }// end recordEditPending



    $scope.getRecord = function(dataRowID){
        var tableData = {};
        tableData.tableName = $scope.currentTableSelected;
        tableData.action = "get";
        tableData.pkey = {};

        var keyName = "";
        for(var keyID in $scope.currentTable.dataModel.primaryKey){
            keyName = $scope.currentTable.dataModel.primaryKey[keyID];
            tableData.pkey[keyName]=$scope.currentTable.pkdata[dataRowID][keyName];
        }
        var responsePromise = $http.post("interface/data.php",tableData);
        responsePromise.success(function(data, status, headers, config) {
            $scope.currentTable.data[dataRowID]=data['data'][0];
        });
        responsePromise.error(function(data, status, headers, config) {
            alert("AJAX failed!");
        });

    }// end getRecord



    $scope.saveRecord = function(dataRowID){
        console.log($scope.currentTable.data[dataRowID]);

        var tableData = {};
        tableData.tableName = $scope.currentTableSelected;
        tableData.data = {};
        var keyName = "";


        if(typeof $scope.currentTable.pkdata[dataRowID] == "undefined"){
            // New record
            tableData.action = "add";


        }else{
            // Edit extant record
            tableData.action = "set";
            tableData.pkey = {};

            for(var keyID in $scope.currentTable.dataModel.primaryKey){
                keyName = $scope.currentTable.dataModel.primaryKey[keyID];
                tableData.pkey[keyName]=$scope.currentTable.pkdata[dataRowID][keyName];
            }
        }



        for(var fieldName in $scope.currentTable.dataModel.fields){
            tableData.data[fieldName]=$scope.currentTable.data[dataRowID][fieldName];
        }


        var responsePromise = $http.post("interface/data.php",tableData);

        responsePromise.success(function(data, status, headers, config) {
            console.log(status);
            console.log(data);
            var updatedIndex = $scope.recordEditPending.indexOf(dataRowID);
            if (updatedIndex > -1) {
                $scope.recordEditPending.splice(updatedIndex, 1);
            }

        });
        responsePromise.error(function(data, status, headers, config) {
            alert("AJAX failed!");
        });
    }


    $scope.addRecord = function(){
        var newRecord = {};
        for (var fieldID in $scope.currentTable.dataModel.fieldOrder){
            var fieldName = $scope.currentTable.dataModel.fieldOrder[fieldID];
            if($scope.currentTable.dataModel.fields[fieldName].default != null){

                // If a default value is defined by the table, use that
                newRecord[fieldName]=$scope.currentTable.dataModel.fields[fieldName].default;

            }else if(typeof $scope.currentTable.dataModel.fields[fieldName].foreignKeyColumns != "undefined"){

                // If FK, select first option in FK
                var firstForeignKeyColumn = $scope.currentTable.dataModel.fields[fieldName].foreignKeyColumns[0];
                var foreignKeyTable = $scope.currentTable.dataModel.fields[fieldName].foreignKeyTable;
                newRecord[fieldName]=$scope.currentTable.fkdata[foreignKeyTable][0][firstForeignKeyColumn];


            }else{
                // Default to ""
                newRecord[fieldName]="";
            }
        }

        var newIndex = $scope.currentTable.data.push(newRecord) - 1;
        $scope.setRecordEditPending(newIndex);
    }// end addRecord



    $scope.recordPrepareDelete = function(dataRowID){

        if($scope.recordDeletePending.indexOf(dataRowID)==-1){
            $scope.recordDeletePending.push(dataRowID);
        }

    } //recordPrepareDelete


    $scope.deleteRecord = function(dataRowID){


        var tableData = {};
        tableData.tableName = $scope.currentTableSelected;
        tableData.action = "delete";
        tableData.pkey = {};

        var keyName = "";
        for(var keyID in $scope.currentTable.dataModel.primaryKey){
            keyName = $scope.currentTable.dataModel.primaryKey[keyID];
            tableData.pkey[keyName]=$scope.currentTable.pkdata[dataRowID][keyName];
        }


        var responsePromise = $http.post("interface/data.php",tableData);
        responsePromise.success(function(data, status, headers, config) {

            // Remove from data object
            //delete $scope.currentTable.data[dataRowID];
            $scope.currentTable.data.splice(dataRowID,1);
            // Remove from Primary Key Index
            $scope.currentTable.pkdata.splice(dataRowID,1);
            // Remove from pending delete
            var deleteIndex = $scope.recordDeletePending.indexOf(dataRowID);
            if(deleteIndex > -1){
                $scope.recordDeletePending.splice(deleteIndex,1);
            }

        });
        responsePromise.error(function(data, status, headers, config) {
            alert("AJAX failed!");
        });

    }// end deleteRecord



});