angular.module('anTicketer').controller("MainController", function($scope, $http) {
    var anT = this;
    $http.defaults.headers.post["Content-Type"] = "application/x-www-form-urlencoded";
    anT.dataModel = {};
    anT.currentTableSelected = "";
    anT.currentTable = {};
    anT.currentTable.dataModel = {};
    anT.currentTable.data = {};
    anT.currentTable.fkdata = {};
    anT.currentTable.pkdata = {};
    anT.recordEditPending = [];
    // Initial Run

        // Get data model

    var responsePromise = $http.get("interface/dataStructure.php");
    var firstRun = true;
    responsePromise.success(function(data, status, headers, config) {

        console.log(data);
        anT.dataModel = data;
        anT.tableList = [];
        jQuery.each(data,function(index,value){
            if(firstRun == true){
                anT.currentTableSelected = index;
                firstRun = false;
            }
            anT.tableList.push({"tableName":index,"displayName":value.displayName});
        });

        //$scope.myData.fromServer = data.title;
        anT.selectTable();
    });
    responsePromise.error(function(data, status, headers, config) {
        alert("AJAX failed!");
    });

// End Initialization




    anT.getForeignKeyDisplayFieldsForRecordField = function(fieldName,fieldValue){

        if(typeof(anT.currentTable.fkdata) != "undefined" && typeof(anT.currentTable.fkdata[fieldName])!= "undefined"){


        var outputArray=[];
        var thisField = "";
        var fkMatchColumn = anT.currentTable.dataModel.fields[fieldName].foreignKeyColumns[0];
        var fkTableName = anT.currentTable.dataModel.fields[fieldName].foreignKeyTable;

        for (var b = 0; b < anT.currentTable.fkdata[fkTableName].length; b++) {
            var fkData = anT.currentTable.fkdata[fkTableName][b];

            if(fkData[fkMatchColumn] == fieldValue){
                for (i = 0; i < anT.currentTable.dataModel.fields[fieldName].foreignKeyDisplayFields.length; i++) {
                    thisField = anT.currentTable.dataModel.fields[fieldName].foreignKeyDisplayFields[i];
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
    anT.selectTable = function(){
        anT.currentTable.dataModel= anT.dataModel[anT.currentTableSelected];
        anT.getAllForTableData(anT.currentTableSelected);
    }// end selectTable
    
    
    
    anT.isForeignKey = function(fieldName,tableName){
        if('foreignKeyTable' in anT.dataModel[tableName]['fields'][fieldName]){
            return true;
        }else{
            return false;
        }
    }//isForeignKey

    anT.isNotForeignKey = function(fieldName,tableName){
        if(anT.isForeignKey(fieldName,tableName)){
            return false;
        }else{
            return true;
        }
    }//isForeignKey

    // Function Load Table Data
    anT.getAllForTableData = function(tableName){
        var tableData = {};
        tableData.tableName = tableName;
        tableData.action = "get";
        var responsePromise = $http.post("interface/data.php",tableData);

        responsePromise.success(function(data, status, headers, config) {
            anT.currentTable.data = data['data'];
            anT.currentTable.pkdata = [];
            for (var row in data['data']){
                for(var keyID in anT.currentTable.dataModel.primaryKey){
                    var keyName = anT.currentTable.dataModel.primaryKey[keyID];
                    anT.currentTable.pkdata[row] = {};
                    anT.currentTable.pkdata[row][keyName]=data['data'][row][keyName];
                }
            }
            if("fkdata" in data){
                anT.currentTable.fkdata = data['fkdata'];
            }else{
                anT.currentTable.fkdata = {};
            }

            console.log(anT.currentTable);

            //$scope.myData.fromServer = data.title;
        });
        responsePromise.error(function(data, status, headers, config) {
            alert("AJAX failed!");
        });
    }// end getAllForTableData


    anT.setRecordEditPending = function(dataRowID){
        //ant-entry-editPending
        if(anT.recordEditPending.indexOf(dataRowID)==-1){
            anT.recordEditPending.push(dataRowID);
        }
        //return "ant-entry-editPending";
    }// end recordEditPending



    anT.saveRecord = function(dataRowID){
        console.log(anT.currentTable.data[dataRowID]);

        var tableData = {};
        tableData.tableName = anT.currentTableSelected;
        tableData.action = "set";
        tableData.pkey = {};
        tableData.data = {};
        var keyName = "";
        for(var keyID in anT.currentTable.dataModel.primaryKey){
            keyName = anT.currentTable.dataModel.primaryKey[keyID];
            tableData.pkey[keyName]=anT.currentTable.pkdata[dataRowID][keyName];
        }

        for(var fieldName in anT.currentTable.dataModel.fields){
            tableData.data[fieldName]=anT.currentTable.data[dataRowID][fieldName];
        }
        console.log("Planning to Send");
        console.log(tableData);
        console.log("Sending");
        var responsePromise = $http.post("interface/data.php",tableData);

        responsePromise.success(function(data, status, headers, config) {
            console.log(status);
            console.log(data);
            var updatedIndex = anT.recordEditPending.indexOf(dataRowID);
            if (updatedIndex > -1) {
                anT.recordEditPending.splice(updatedIndex, 1);
            }

        });
        responsePromise.error(function(data, status, headers, config) {
            alert("AJAX failed!");
        });

    }


});