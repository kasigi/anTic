<?php
include('includes/header.php');
?>
    <div class="container" ng-app="anTicketer" ng-controller="MainController as anT">

        <div class="row">
            <div class="col-md-6">
                <label for="tableSelection">Select a Table</label>
                <select class="form-control" name="tableSelection" id="tableSelection" ng-model="anT.currentTableSelected" ng-change="anT.selectTable()">
                    <option ng-repeat="dataTable in anT.tableList" value="{{dataTable.tableName}}" ng-bind="dataTable.displayName"></option>

                </select>
            </div>
            <div class="col-md-6">
                <button class="btn btn-primary btn-lg pull-right" type="button"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span> Add Entry</button>
            </div>

        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-default ant-entryTable-outer">
                    <table class="table table-striped ant-entryTable table-hover">
                        <thead>
                        <th ng-attr-id="thID-{{ tableField }}" ng-repeat="tableField in anT.currentTable.dataModel.fieldOrder"><span ng-bind="anT.currentTable.dataModel.fields[tableField].displayName"></span> <span ng-show="anT.currentTable.dataModel.fields[tableField].helpText" data-toggle="tooltip" data-placement="bottom" title="{{anT.currentTable.dataModel.fields[tableField].helpText}}" class="text-primary glyphicon glyphicon-question-sign" aria-hidden="true" ></span>
                        </th>
                        <th id="thID-controls">&nbsp;</th>
                        </thead>
                        <tbody>
                            <tr ng-repeat="dataRow in anT.currentTable.data" ng-init="dataRowIndex = $index" ng-click="anT.setRecordEditPending($index)" ng-class="anT.recordEditPending.indexOf($index)>-1 ? 'ant-entry-editPending' : ''
">
                                <td ng-attr-headers="thID-{{ tableField }}" ng-repeat="tableField in anT.currentTable.dataModel.fieldOrder">
                                    <span class="ant-entry-fieldDisplay" ng-bind="dataRow[tableField]" ng-if="anT.isNotForeignKey(tableField,anT.currentTableSelected)"></span>
                                    <span class="ant-entry-fieldDisplay" ng-bind="anT.getForeignKeyDisplayFieldsForRecordField(tableField,dataRow[tableField])" ng-if="anT.isForeignKey(tableField,anT.currentTableSelected)"></span>
                                    <input class="form-control" ng-model="anT.currentTable.data[dataRowIndex][tableField]" type="text" ng-if="anT.isNotForeignKey(tableField,anT.currentTableSelected)" >
                                    <select class="form-control" name="select" ng-if="anT.isForeignKey(tableField,anT.currentTableSelected)"  ng-model="anT.currentTable.data[dataRowIndex][tableField]">
                                        <option ng-repeat="fkRow in anT.currentTable.fkdata[anT.currentTable.dataModel.fields[tableField].foreignKeyTable]" value="{{fkRow[anT.currentTable.dataModel.fields[tableField].foreignKeyColumns[0]]}}">{{anT.getForeignKeyDisplayFieldsForRecordField(tableField,fkRow[anT.currentTable.dataModel.fields[tableField].foreignKeyColumns[0]])}}</option>
                                    </select>
                                </td>
                                <td headers="thID-controls">
                                    <!---->
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-success" aria-label="Save" ng-disabled="anT.recordEditPending.indexOf($index)==-1" ng-click="anT.saveRecord($index)">
                                            <span class="glyphicon glyphicon-ok" aria-hidden="true"></span>
                                        </button>
                                        <button type="button" class="btn btn-info" aria-label="Undo" ng-disabled="anT.recordEditPending.indexOf($index)==-1">
                                            <span class="glyphicon glyphicon-repeat" aria-hidden="true"></span>
                                        </button>
                                        <button type="button" class="btn btn-danger" aria-label="Delete">
                                            <span class="glyphicon glyphicon-remove" aria-hidden="true"></span>
                                        </button>
                                    </div>


                                </td>
                            </tr>


                        </tbody>
                        </table>
                    
                    
                    
                <?php //include('reference/entryTable.html'); ?>
                    </div>
            </div>

        </div>


    </div><!-- end container -->

<?php
include('includes/footer.php');