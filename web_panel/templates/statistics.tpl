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

    <title>Statistics - {$site.title}</title>

    {include file='header.tpl'}
</head>

<body>

<div id="wrapper">

    {include file='navigation.tpl'}

    <div id="page-wrapper">
        <div class="row">
            <div class="col-lg-12">
                <h1 class="page-header">Statistics</h1>
                {if isset($error)}
                    <div class="alert alert-warning alert-dismissable">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                        {$error}
                    </div>
                {/if}
            </div>
            <!-- /.col-lg-12 -->
        </div>
        <!-- /.row -->

        <div class="row">
            <div class="col-lg-2">
                <div class="panel panel-primary">
                    <div class="panel-heading">
                        Total Objectives
                    </div>
                    <div class="panel-body">
                        <div id="numTotalObjectives" style="height: 250px;"></div>
                    </div>
                </div>
                <!-- /.panel-primary -->
            </div>
            <!-- /.col-lg-2 -->
            <div class="col-lg-2">
                <div class="panel panel-primary">
                    <div class="panel-heading">
                        Current queue sizes
                    </div>
                    <div class="panel-body">
                        <div id="queues" style="height: 250px;"></div>
                    </div>
                </div>
                <!-- /.panel-primary -->
            </div>
            <!-- /.col-lg-2 -->

            <div class="col-lg-4">
                <div class="panel panel-primary">
                    <div class="panel-heading">
                        Daily delivered images
                    </div>
                    <div class="panel-body">
                        <div id="daily-requests" style="height: 250px;"></div>
                    </div>
                </div>
                <!-- /.panel-primary -->
            </div>
            <!-- /.col-lg-4 -->

            <div class="col-lg-4">
                <div class="panel panel-primary">
                    <div class="panel-heading">
                        Daily processed items
                    </div>
                    <div class="panel-body">
                        <div id="daily-processed-items" style="height: 250px;"></div>
                    </div>
                </div>
                <!-- /.panel-primary -->
            </div>
            <!-- /.col-lg-4 -->
        </div>
        <!-- /.row -->

        <div class="row">
            <div class="col-lg-4">
                <div class="panel panel-primary">
                    <div class="panel-heading">
                        Daily new targets
                    </div>
                    <div class="panel-body">
                        <div id="daily-newtargets" style="height: 250px;"></div>
                    </div>
                </div>
                <!-- /.panel-primary -->
            </div>
            <!-- /.col-lg-4 -->

            <div class="col-lg-4">
                <div class="panel panel-primary">
                    <div class="panel-heading">
                        Daily new referrers
                    </div>
                    <div class="panel-body">
                        <div id="daily-newreferrers" style="height: 250px;"></div>
                    </div>
                </div>
                <!-- /.panel-primary -->
            </div>
            <!-- /.col-lg-4 -->

            <div class="col-lg-4">
                <div class="panel panel-primary">
                    <div class="panel-heading">
                        Daily active accounts
                    </div>
                    <div class="panel-body">
                        <div id="daily-activeaccounts" style="height: 250px;"></div>
                    </div>
                </div>
                <!-- /.panel-primary -->
            </div>
            <!-- /.col-lg-4 -->
        </div>
        <!-- /.row -->

        <div class="row">
            <div class="col-lg-3">
                <div class="panel panel-primary">
                    <div class="panel-heading">
                        Daily processed masters (normal)
                    </div>
                    <div class="panel-body">
                        <div id="daily-processed-masters-normal" style="height: 250px;"></div>
                    </div>
                </div>
                <!-- /.panel-primary -->
            </div>
            <!-- /.col-lg-3 -->

            <div class="col-lg-3">
                <div class="panel panel-primary">
                    <div class="panel-heading">
                        Daily processed masters (longrun)
                    </div>
                    <div class="panel-body">
                        <div id="daily-processed-masters-longrun" style="height: 250px;"></div>
                    </div>
                </div>
                <!-- /.panel-primary -->
            </div>
            <!-- /.col-lg-3 -->

            <div class="col-lg-3">
                <div class="panel panel-primary">
                    <div class="panel-heading">
                        Daily processed masters (phantom)
                    </div>
                    <div class="panel-body">
                        <div id="daily-processed-masters-phantom" style="height: 250px;"></div>
                    </div>
                </div>
                <!-- /.panel-primary -->
            </div>
            <!-- /.col-lg-3 -->

            <div class="col-lg-3">
                <div class="panel panel-primary">
                    <div class="panel-heading">
                        Daily processed images
                    </div>
                    <div class="panel-body">
                        <div id="daily-processed-thumbnails" style="height: 250px;"></div>
                    </div>
                </div>
                <!-- /.panel-primary -->
            </div>
            <!-- /.col-lg-3 -->
        </div>
        <!-- /.row -->

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
<script src="../bower_components/raphael/raphael.min.js"></script>
<script src="../bower_components/morrisjs/morris.min.js"></script>

<!-- Custom Theme JavaScript -->
<script src="../bower_components/startbootstrap-sb-admin-2/dist/js/sb-admin-2.js"></script>

<script type="text/javascript">
    $(document).ready(function () {

        var refreshIntervalShort = 5000;
        var refreshIntervalLong = 30000;

        //////////////////////////////
        // num TotalObjectives
        //////////////////////////////

        var chartNumTotalObjectives = Morris.Bar({
            element: 'numTotalObjectives',
            xkey: 'date',
            ykeys: ['targets', 'images', 'referrers'],
            xLabels: 'day',
            labels: ['Targets', 'Images', 'referrers'],
            hideHover: false
        });

        var ajaxNumTotalObjectives = function () {
            $.ajax({
                type: "GET",
                dataType: 'json',
                url: "/json/numtotalobjectives.php",
                success: function (response) {
                    if (response == 'True') {
                        clearTimeout(numTotalObjectivesTimeOutId);
                    } else {
                        numTotalObjectivesTimeOutId = setTimeout(ajaxNumTotalObjectives, refreshIntervalShort);
                    }
                }
            })
                    .done(function (data) {
                        chartNumTotalObjectives.setData(data);
                    })
                    .fail(function () {
                        console.log("error occurred while refreshing ajaxNumTotalObjectives");
                    });
        };

        var numTotalObjectivesTimeOutId = 0;
        ajaxNumTotalObjectives();



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
                url: "/json/dailyrequests.php",
                data: { days: 30 },
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



        //////////////////////////////
        // daily-processed-items
        //////////////////////////////

        var chartItemsUpdatedDaily = Morris.Area({
            element: 'daily-processed-items',
            //data: [0, 0],
            xkey: 'date',
            ykeys: ['targetsUpdated', 'imagesUpdated', 'targetsFailed', 'targetsForbidden', 'imagesFailed'],
            xLabels: 'day',
            xLabelAngle: '45',
            labels: ['updated targets', 'updated images', 'failed targets', 'forbidden targets', 'failed images'],
            hideHover: 'auto'
        });

        var ajaxDailyItemsUpdated = function () {
            $.ajax({
                type: "GET",
                dataType: 'json',
                url: "/json/dailyprocesseditems.php",
                data: { days: 30 },
                success: function (response) {
                    if (response == 'True') {
                        clearTimeout(dailyItemsUpdatedTimeOutId);
                    } else {
                        dailyItemsUpdatedTimeOutId = setTimeout(ajaxDailyItemsUpdated, refreshIntervalLong);
                    }
                }
            })
                    .done(function (data) {
                        chartItemsUpdatedDaily.setData(data);
                    })
                    .fail(function () {
                        console.log("error occurred while refreshing ajaxDailyItemsUpdated");
                    });
        };

        var dailyItemsUpdatedTimeOutId = 0;
        ajaxDailyItemsUpdated();



        //////////////////////////////
        // Queues
        //////////////////////////////

        var chartQueueSizes = Morris.Bar({
            element: 'queues',
            //data: [0, 0],
            xkey: 'date',
            ykeys: ['targetNormal', 'targetLongrun', 'targetPhantom', 'image'],
            labels: ['targets normal', 'targets longrun', 'targets phantom', 'images'],
            hideHover: false
        });

        var ajaxQueueSizes = function () {
            $.ajax({
                type: "GET",
                dataType: 'json',
                url: "/json/queues.php",
                data: { days: 30 },
                success: function (response) {
                    if (response == 'True') {
                        clearTimeout(queueSizesTimeOutId);
                    } else {
                        queueSizesTimeOutId = setTimeout(ajaxQueueSizes, refreshIntervalShort);
                    }
                }
            })
                    .done(function (data) {
                        chartQueueSizes.setData(data);
                    })
                    .fail(function () {
                        console.log("error occurred while refreshing ajaxQueueSizes");
                    });
        };

        var queueSizesTimeOutId = 0;
        ajaxQueueSizes();



        //////////////////////////////
        // daily-newtargets
        //////////////////////////////

        var chartNewTargetsDaily = Morris.Area({
            element: 'daily-newtargets',
            //data: [0, 0],
            xkey: 'date',
            ykeys: ['value'],
            xLabels: 'day',
            xLabelAngle: '45',
            labels: ['new targets'],
            hideHover: 'auto'
        });

        var ajaxDailyNewTargets = function () {
            $.ajax({
                type: "GET",
                dataType: 'json',
                url: "/json/dailynewtargets.php",
                data: { days: 30 },
                success: function (response) {
                    if (response == 'True') {
                        clearTimeout(dailyNewTargetsTimeOutId);
                    } else {
                        dailyNewTargetsTimeOutId = setTimeout(ajaxDailyNewTargets, refreshIntervalLong);
                    }
                }
            })
                    .done(function (data) {
                        chartNewTargetsDaily.setData(data);
                    })
                    .fail(function () {
                        console.log("error occurred while refreshing ajaxDailyNewTargets");
                    });
        };

        var dailyNewTargetsTimeOutId = 0;
        ajaxDailyNewTargets();



        //////////////////////////////
        // daily-newreferrers
        //////////////////////////////

        var chartNewReferrersDaily = Morris.Area({
            element: 'daily-newreferrers',
            //data: [0, 0],
            xkey: 'date',
            ykeys: ['value'],
            xLabels: 'day',
            xLabelAngle: '45',
            labels: ['new referrers'],
            hideHover: 'auto'
        });

        var ajaxDailyNewReferrers = function () {
            $.ajax({
                type: "GET",
                dataType: 'json',
                url: "/json/dailynewreferrers.php",
                data: { days: 30 },
                success: function (response) {
                    if (response == 'True') {
                        clearTimeout(dailyNewReferrersTimeOutId);
                    } else {
                        dailyNewReferrersTimeOutId = setTimeout(ajaxDailyNewReferrers, refreshIntervalLong);
                    }
                }
            })
                    .done(function (data) {
                        chartNewReferrersDaily.setData(data);
                    })
                    .fail(function () {
                        console.log("error occurred while refreshing ajaxDailyNewReferrers");
                    });
        };

        var dailyNewReferrersTimeOutId = 0;
        ajaxDailyNewReferrers();



        //////////////////////////////
        // daily-activeaccounts
        //////////////////////////////

        var chartActiveAccountsDaily = Morris.Area({
            element: 'daily-activeaccounts',
            //data: [0, 0],
            xkey: 'date',
            ykeys: ['value'],
            xLabels: 'day',
            xLabelAngle: '45',
            labels: ['active accounts'],
            hideHover: 'auto'
        });

        var ajaxDailyActiveAccounts = function () {
            $.ajax({
                type: "GET",
                dataType: 'json',
                url: "/json/dailyactiveaccounts.php",
                data: { days: 30 },
                success: function (response) {
                    if (response == 'True') {
                        clearTimeout(dailyActiveAccountsTimeOutId);
                    } else {
                        dailyActiveAccountsTimeOutId = setTimeout(ajaxDailyActiveAccounts, refreshIntervalLong);
                    }
                }
            })
                    .done(function (data) {
                        chartActiveAccountsDaily.setData(data);
                    })
                    .fail(function () {
                        console.log("error occurred while refreshing ajaxDailyActiveAccounts");
                    });
        };

        var dailyActiveAccountsTimeOutId = 0;
        ajaxDailyActiveAccounts();



        //////////////////////////////
        // daily-processed-masters-normal
        //////////////////////////////

        var chartProcessedMastersNormalDaily = Morris.Area({
            element: 'daily-processed-masters-normal',
            //data: [0, 0],
            xkey: 'date',
            ykeys: ['agentConnections', 'targetsUpdated', 'targetsFailed', 'targetsForbidden'],
            xLabels: 'day',
            xLabelAngle: '45',
            labels: ['agent connections','updated targets', 'failed targets', 'forbidden targets'],
            hideHover: 'auto'
        });

        var ajaxProcessedMastersNormalDaily = function () {
            $.ajax({
                type: "GET",
                dataType: 'json',
                url: "/json/dailyprocessedmasters.php",
                data: { mode: 'normal', days: 30 },
                success: function (response) {
                    if (response == 'True') {
                        clearTimeout(dailyProcessedMastersNormalTimeOutId);
                    } else {
                        dailyProcessedMastersNormalTimeOutId = setTimeout(ajaxProcessedMastersNormalDaily, refreshIntervalLong);
                    }
                }
            })
                    .done(function (data) {
                        chartProcessedMastersNormalDaily.setData(data);
                    })
                    .fail(function () {
                        console.log("error occurred while refreshing ajaxDailyItemsUpdated");
                    });
        };

        var dailyProcessedMastersNormalTimeOutId = 0;
        ajaxProcessedMastersNormalDaily();



        //////////////////////////////
        // daily-processed-masters-longrun
        //////////////////////////////

        var chartProcessedMastersLongrunDaily = Morris.Area({
            element: 'daily-processed-masters-longrun',
            //data: [0, 0],
            xkey: 'date',
            ykeys: ['agentConnections', 'targetsUpdated', 'targetsFailed', 'targetsForbidden'],
            xLabels: 'day',
            xLabelAngle: '45',
            labels: ['agent connections','updated targets', 'failed targets', 'forbidden targets'],
            hideHover: 'auto'
        });

        var ajaxProcessedMastersLongrunDaily = function () {
            $.ajax({
                type: "GET",
                dataType: 'json',
                url: "/json/dailyprocessedmasters.php",
                data: { mode: 'longrun', days: 7 },
                success: function (response) {
                    if (response == 'True') {
                        clearTimeout(dailyProcessedMastersLongrunTimeOutId);
                    } else {
                        dailyProcessedMastersLongrunTimeOutId = setTimeout(ajaxProcessedMastersLongrunDaily, refreshIntervalLong);
                    }
                }
            })
                    .done(function (data) {
                        chartProcessedMastersLongrunDaily.setData(data);
                    })
                    .fail(function () {
                        console.log("error occurred while refreshing ajaxDailyItemsUpdated");
                    });
        };

        var dailyProcessedMastersLongrunTimeOutId = 0;
        ajaxProcessedMastersLongrunDaily();


        //////////////////////////////
        // daily-processed-masters-phantom
        //////////////////////////////

        var chartProcessedMastersPhantomDaily = Morris.Area({
            element: 'daily-processed-masters-phantom',
            //data: [0, 0],
            xkey: 'date',
            ykeys: ['agentConnections', 'targetsUpdated', 'targetsFailed', 'targetsForbidden'],
            xLabels: 'day',
            xLabelAngle: '45',
            labels: ['agent connections','updated targets', 'failed targets', 'forbidden targets'],
            hideHover: 'auto'
        });

        var ajaxProcessedMastersPhantomDaily = function () {
            $.ajax({
                type: "GET",
                dataType: 'json',
                url: "/json/dailyprocessedmasters.php",
                data: { mode: 'phantom', days: 30 },
                success: function (response) {
                    if (response == 'True') {
                        clearTimeout(dailyProcessedMastersPhantomTimeOutId);
                    } else {
                        dailyProcessedMastersPhantomTimeOutId = setTimeout(ajaxProcessedMastersPhantomDaily, refreshIntervalLong);
                    }
                }
            })
                    .done(function (data) {
                        chartProcessedMastersPhantomDaily.setData(data);
                    })
                    .fail(function () {
                        console.log("error occurred while refreshing ajaxDailyItemsUpdated");
                    });
        };

        var dailyProcessedMastersPhantomTimeOutId = 0;
        ajaxProcessedMastersPhantomDaily();



        //////////////////////////////
        // daily-processed-thumbnails
        //////////////////////////////

        var chartProcessedThumbnailsDaily = Morris.Area({
            element: 'daily-processed-thumbnails',
            //data: [0, 0],
            xkey: 'date',
            ykeys: ['agentConnections', 'imagesUpdated', 'imagesFailed'],
            xLabels: 'day',
            xLabelAngle: '45',
            labels: ['agent connections','updated images', 'failed images'],
            hideHover: 'auto'
        });

        var ajaxProcessedThumbnailsDaily = function () {
            $.ajax({
                type: "GET",
                dataType: 'json',
                url: "/json/dailyprocessedthumbnails.php",
                data: { days: 7 },
                success: function (response) {
                    if (response == 'True') {
                        clearTimeout(dailyProcessedThumbnailsTimeOutId);
                    } else {
                        dailyProcessedThumbnailsTimeOutId = setTimeout(ajaxProcessedThumbnailsDaily, refreshIntervalLong);
                    }
                }
            })
                    .done(function (data) {
                        chartProcessedThumbnailsDaily.setData(data);
                    })
                    .fail(function () {
                        console.log("error occurred while refreshing ajaxDailyItemsUpdated");
                    });
        };

        var dailyProcessedThumbnailsTimeOutId = 0;
        ajaxProcessedThumbnailsDaily();
    });
</script>
</body>

</html>
