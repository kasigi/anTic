angular.module('anTicketer').controller("MainController", function($scope, $http) {
    $http.defaults.headers.post["Content-Type"] = "application/x-www-form-urlencoded";
    $scope.dataModel = {};
    $scope.currentTableSelected = "";
    $scope.currentTable = {};
    $scope.currentTable.dataModel = {};
    $scope.currentTable.data = {};
    $scope.currentTable.fkdata = {};
    $scope.currentTable.pkdata = {};
    $scope.recordEditPending = [];
    // Initial Run

        // Get data model

    var responsePromise = $http.get("interface/dataStructure.php");
    var firstRun = true;
    responsePromise.success(function(data, status, headers, config) {

        console.log(data);
        $scope.dataModel = data;
        $scope.tableList = [];
        jQuery.each(data,function(index,value){
            if(firstRun == true){
                $scope.currentTableSelected = index;
                firstRun = false;
            }
            $scope.tableList.push({"tableName":index,"displayName":value.displayName});
        });

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

            //$scope.myData.fromServer = data.title;
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



    $scope.saveRecord = function(dataRowID){
        console.log($scope.currentTable.data[dataRowID]);

        var tableData = {};
        tableData.tableName = $scope.currentTableSelected;
        tableData.action = "set";
        tableData.pkey = {};
        tableData.data = {};
        var keyName = "";
        for(var keyID in $scope.currentTable.dataModel.primaryKey){
            keyName = $scope.currentTable.dataModel.primaryKey[keyID];
            tableData.pkey[keyName]=$scope.currentTable.pkdata[dataRowID][keyName];
        }

        for(var fieldName in $scope.currentTable.dataModel.fields){
            tableData.data[fieldName]=$scope.currentTable.data[dataRowID][fieldName];
        }
        console.log("Planning to Send");
        console.log(tableData);
        console.log("Sending");
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


});