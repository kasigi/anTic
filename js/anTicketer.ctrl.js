angular.module('anTicketer').controller("MainController", function($scope, $http) {
    var anT = this;
    $http.defaults.headers.post["Content-Type"] = "application/x-www-form-urlencoded";
    anT.dataModel = {};
    anT.currentTableSelected = "";
    anT.currentTable = {};
    anT.currentTable.dataModel = {};
    anT.currentTable.data = {};
    anT.currentTable.fkdata = {};
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
            console.log(data);
            anT.currentTable.data = data['data'];
            if("fk" in data){
                anT.currentTable.fkdata = data['fk'];
            }else{
                anT.currentTable.fkdata = {};
            }
            //$scope.myData.fromServer = data.title;
        });
        responsePromise.error(function(data, status, headers, config) {
            alert("AJAX failed!");
        });
    }// end getAllForTableData



});