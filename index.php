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
                        <th ng-repeat="tableField in anT.currentTable.dataModel.fieldOrder"><span ng-bind="anT.currentTable.dataModel.fields[tableField].displayName"></span> <span ng-show="anT.currentTable.dataModel.fields[tableField].helpText" data-toggle="tooltip" data-placement="bottom" title="{{anT.currentTable.dataModel.fields[tableField].helpText}}" class="text-primary glyphicon glyphicon-question-sign" aria-hidden="true" ></span>
                        </th>
                        <th>&nbsp;</th>
                        </thead>
                        <tbody>
                            <tr ng-repeat="dataRow in anT.currentTable.data">
                                <td ng-repeat="tableField in anT.currentTable.dataModel.fieldOrder">

                                    <span class="ant-entry-fieldDisplay" ng-bind="dataRow[tableField]" >A Sample Title</span>
                                    <input class="form-control" type="text" value="{{dataRow[tableField]}}" ng-if="anT.isNotForeignKey(tableField,anT.currentTableSelected)" >
                                    <select class="form-control" name="select" ng-if="anT.isForeignKey(tableField,anT.currentTableSelected)"  ng-model="dataRow[tableField]">
                                        <option value="{{fkRow[anT.currentTable.dataModel.fields[tableField].foreignKeyColumns[0]]}}" ng-repeat="fkRow in anT.currentTable.fkdata[tableField]">{{fkRow[anT.currentTable.dataModel.fields[tableField].foreignKeyDisplayFields[0]]}}</option>
                                    </select>
                                </td>
                                <td>
                                    <!---->
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-success" aria-label="Save" disabled>
                                            <span class="glyphicon glyphicon-ok" aria-hidden="true"></span>
                                        </button>
                                        <button type="button" class="btn btn-info" aria-label="Undo" disabled>
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