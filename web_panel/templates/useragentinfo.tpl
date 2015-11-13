{*
 Copyright (C) 2015  Thomas Schulte <thomas@cupracer.de>

 This program is free software: you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation, either version 3 of the License, or
 (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program.  If not, see <http://www.gnu.org/licenses/>.
*}

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>User Agent information - {$site.title}</title>

    {include file='header.tpl'}
</head>

<body>

    <div id="wrapper">

	{include file='navigation.tpl'}

        <div id="page-wrapper">
            <div class="row">
                <div class="col-lg-12">
                    <h1 class="page-header">User Agent information</h1>
                        {if isset($error)}
                        <div class="alert alert-warning alert-dismissable">
                                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                                {$error}
                        </div>
                        {/if}
                </div>
                <!-- /.col-lg-12 -->
            </div>
            {if isset($userAgent)}
            <!-- /.row -->
            <div class="row">
                <div class="col-lg-6">
			<div class="panel panel-primary">
                        	<div class="panel-heading">
                            		Basic information
                        	</div>
                        	<div class="panel-body">
					<div class="table-responsive">
					<table class="table">
                                            <tbody>
                                            {if isset($account) && ($account->isAdmin() == true && $account->isActAsAdmin())}
                                                <tr>
                                                        <td style="vertical-align: middle;">ID</td>
                                                        <td style="vertical-align: middle;">{$userAgent->getId()}</td>
                                                </tr>
                                            {/if}
                                                <tr>
                                                        <td style="vertical-align: middle;">Description</td>
                                                        <td style="vertical-align: middle;">{$userAgent->getDescription()}</td>
                                                </tr>
                                            </tbody>
                                        </table>
	                            </div>
                        	</div>
                    </div>
		    <!-- /.panel-primary -->
                </div>
                <!-- /.col-lg-6 -->

                <div class="col-lg-6">
                <div class="panel panel-primary">
                    <div class="panel-heading">
                        Daily requests
                    </div>
                    <div class="panel-body">
                        <div id="daily-requests" style="height: 250px;"></div>
                    </div>
                </div>
                <!-- /.panel-primary -->
            </div>
            <!-- /.col-lg-6 -->
            </div>
            <!-- /.row -->
            {/if}

            {if isset($userAgent)}
                <div class="col-lg-12">
                        <div class="panel panel-primary">
                                <div class="panel-heading">
                                        Targets
                                </div>
                                <div class="panel-body">
                                    <div class="table-responsive" style="overflow-x: unset;">
                                        <table id="targets" class="table table-striped table-bordered table-hover">
                                            <thead>
                                            <tr>
                                                <th style="text-align: center; width: 50px;"></th>
                                                <th>URL</th>
                                                <th style="text-align: center;">added</th>
                                                <th style="text-align: center; width: 50px;">Link</th>
                                            </tr>
                                            </thead>
                                        </table>
                                    </div>
                                    <!-- /.table-responsive -->
                                </div>
                    </div>
                    <!-- /.panel-primary -->
                </div>
		<!-- /.col-lg-6 -->
            {/if}
        </div>
        <!-- /#page-wrapper -->

    </div>
    <!-- /#wrapper -->

    <!-- jQuery -->
    <script src="../bower_components/jquery/dist/jquery.min.js"></script>

    <!-- Bootstrap Core JavaScript -->
    <script src="../bower_components/bootstrap/dist/js/bootstrap.min.js"></script>

    <!-- Metis Menu Plugin JavaScript -->
    <script src="../bower_components/metisMenu/dist/metisMenu.min.js"></script>

    <!-- Morris Charts JavaScript -->
    <script src="../bower_components/raphael/raphael-min.js"></script>
    <script src="../bower_components/morrisjs/morris.min.js"></script>

    <!-- DataTables JavaScript -->
    <script src="../bower_components/datatables/media/js/jquery.dataTables.min.js"></script>

    <!-- Custom Theme JavaScript -->
    <script src="../bower_components/startbootstrap-sb-admin-2/dist/js/sb-admin-2.js"></script>

    <script type="text/javascript">
        $(document).ready(function () {

            var refreshIntervalShort = 5000;
            var refreshIntervalLong = 30000;

            //////////////////////////////
            // daily-requests
            //////////////////////////////

            var chartDeliveredDaily = Morris.Area({
                element: 'daily-requests',
                //data: [0, 0],
                xkey: 'date',
                ykeys: ['value'],
                xLabels: 'day',
                xLabelAngle: '45',
                labels: ['Requests'],
                hideHover: 'auto'
            });

            var ajaxDailyRequests = function () {
                $.ajax({
                    type: "GET",
                    dataType: 'json',
                    url: "/json/useragentinfo-dailyrequests.php",
                    data: { id: "{$userAgent->getId()}", days: 30 },
                    success: function (response) {
                        if (response == 'True') {
                            clearTimeout(dailyRequestsTimeOutId);
                        } else {
                            dailyRequestsTimeOutId = setTimeout(ajaxDailyRequests, refreshIntervalLong);
                        }
                    }
                })
                        .done(function (data) {
                            chartDeliveredDaily.setData(data);
                        })
                        .fail(function () {
                            console.log("error occurred while refreshing ajaxDailyRequests");
                        });
            };

            var dailyRequestsTimeOutId = 0;
            ajaxDailyRequests();

            $('#targets').dataTable({
                stateSave: true,
                responsive: true,
                processing: true,
                serverSide: true,
                ajax: '/json/useragentinfo-targets.php?id={$userAgent->getId()}',
                order: [[ 2, "asc" ]],
                columnDefs: [
                    {
                        targets: "_all"
                    },
                    {
                        targets: 0,
                        className: "col-center",
                        render: function (data, type, row) {
                                return '<a href="/pages/targetinfo.php?id=' + row[0] + '"class="btn btn-warning btn-xs">' +
                                    'View <i class="fa fa-folder-open-o pl-10"></i></a>';
                        },
                        orderable: false
                    },
                    {
                        targets: 1,
                        orderable: true
                    },
                    {
                        targets: 2,
                        className: "col-center",
                        orderable: true
                    },
                    {
                        targets: 3,
                        className: "col-center",
                        render: function (data, type, row) {
                            return '<a href="' + row[1] + '" target="_blank"><button type="button" class="btn btn-success btn-xs"><i class="fa fa-link"></i></button></a>';
                        },
                        orderable: false
                    }
                ]
            });
        });
    </script>
</body>

</html>
