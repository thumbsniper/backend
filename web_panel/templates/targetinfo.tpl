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

    <title>Target information - {$site.title}</title>

    {include file='header.tpl'}
</head>

<body>

<div id="wrapper">

    {include file='navigation.tpl'}

    <div id="page-wrapper">
        <div class="row">
            <div class="col-lg-12">
                <h1 class="page-header">Target information</h1>
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
            <div class="col-lg-4">
                <div class="panel panel-primary">
                    <div class="panel-heading">
                        Basic information
                    </div>
                    <div class="panel-body">
                        <div class="table-responsive">
                            <table class="table">
                                <tbody>
                                <tr>
                                    <td style="vertical-align: middle;">ID</td>
                                    <td style="vertical-align: middle;">{$target->getId()}</td>
                                </tr>
                                <tr>
                                    <td style="vertical-align: middle;">URL</td>
                                    <td style="vertical-align: middle;">
                                        {$target->getUrl()} <a href="{$target->getUrl()}" target="_blank"><i
                                                    class="fa fa-external-link"></i></a>
                                    </td>
                                </tr>
                                {if isset($account) && ($account->isAdmin() == true && $account->isActAsAdmin())}
                                <tr>
                                    <td style="vertical-align: middle;">base filename:</td>
                                    <td style="vertical-align: middle;">{$target->getFileNameBase()}</td>
                                </tr>
                                {/if}
                                <tr>
                                    <td style="vertical-align: middle;">MIME type:</td>
                                    <td style="vertical-align: middle;">{if $target->getMimeType()}{$target->getMimeType()}{else}unknown{/if}</td>
                                </tr>
                                <tr>
                                    <td style="vertical-align: middle;">added</td>
                                    <td style="vertical-align: middle;">{$target->getTsAdded()|date_format:"%d.%m.%Y %H:%M:%S"}</td>
                                </tr>
                                <tr>
                                    <td style="vertical-align: middle;">updated</td>
                                    <td style="vertical-align: middle;">{if !is_null($target->getTsLastUpdated())}{$target->getTsLastUpdated()|date_format:"%d.%m.%Y %H:%M:%S"}{else}never{/if}</td>
                                </tr>
                                <tr>
                                    <td style="vertical-align: middle;">censored:</td>
                                    <td style="vertical-align: middle;">{if $target->isCensored() == true}yes{else}no{/if}</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <!-- /.panel-primary -->
            </div>
            <!-- /.col-lg-4 -->
            <div class="col-lg-4">
                <div class="panel panel-primary">
                    <div class="panel-heading">
                        Generator statistics
                    </div>
                    <div class="panel-body">
                        <div class="table-responsive">
                            <table class="table">
                                <tbody>
                                <tr>
                                    <td>checkouts</td>
                                    <td>{$target->getCounterCheckedOut()}</td>
                                </tr>
                                <tr>
                                    <td>updates</td>
                                    <td>{if !is_null($target->getCounterUpdated())}{$target->getCounterUpdated()}{else}never{/if}</td>
                                </tr>
                                <tr>
                                    <td>failures</td>
                                    <td>{$target->getCounterFailed()}</td>
                                </tr>
                                {if !is_null($target->getLastErrorMessage())}
                                <tr>
                                    <td>last error</td>
                                    <td>{$target->getLastErrorMessage()}</td>
                                </tr>
                                {/if}
                                {if !is_null($target->getTsLastFailed())}
                                    <tr>
                                        <td>last failed</td>
                                        <td>{$target->getTsLastFailed()|date_format:"%d.%m.%Y %H:%M:%S"}</td>
                                    </tr>
                                {/if}
                                <tr>
                                    <td>blacklisted</td>
                                    <td>{if $isBlacklisted == true}yes{else}no{/if}</td>
                                </tr>
                                {if isset($account) && ($account->isAdmin() == true && $account->isActAsAdmin())}
                                <tr>
                                    <td>allowed by robots.txt</td>
                                    <td>{if !is_null($target->isRobotsAllowed())}{if $target->isRobotsAllowed() == true}yes{else}no{/if}{else}unknown{/if}</td>
                                </tr>
                                <tr>
                                    <td>JavaScript</td>
                                    <td>{if !is_null($target->isJavaScriptEnabled())}{if $target->isJavaScriptEnabled() == true}yes{else}no{/if}{else}unknown{/if}</td>
                                </tr>
                                <tr>
                                    <td>duration</td>
                                    <td>{if !is_null($target->getSnipeDuration())}{$target->getSnipeDuration()} seconds{else}none{/if}</td>
                                </tr>
                                <tr>
                                    <td>weapon</td>
                                    <td>{if !is_null($target->getWeapon())}{$target->getWeapon()}{else}none{/if}</td>
                                </tr>
                                {/if}
                                <tr>
                                    <td colspan="2" style="text-align: center;"><a href="/pages/targetinfo.php?id={$target->getId()}&action=forceUpdate" class="btn btn-warning btn-xs">
                                            force update <i class="fa fa-refresh pl-10"></i></a></td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <!-- /.panel-primary -->
            </div>
            <!-- /.col-lg-4 -->
            <div class="col-lg-4">
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
            <!-- /.col-lg-4 -->
        </div>
        <!-- /.row -->

        <div class="row">
            <div class="col-lg-12">
                <div class="panel panel-primary">
                    <div class="panel-heading">
                        Image information
                    </div>
                    <div class="panel-body">
                        <div class="table-responsive" style="overflow-x: unset;">
                            <table id="images" class="table table-striped table-bordered table-hover">
                                <thead>
                                <tr>
                                    <th style="text-align: center; width: 30px;"></th>
                                    <th style="text-align: center;">width</th>
                                    <th style="text-align: center;">effect</th>
                                    <th style="text-align: center;">suffix</th>
                                    <th style="text-align: center; width: 170px;">added</th>
                                </tr>
                                </thead>
                            </table>
                        </div>
                        <!-- /.table-responsive -->
                    </div>
                </div>
                <!-- /.panel-primary -->
            </div>
            <!-- /.col-lg-12 -->

        </div>
        <!-- /.row -->

        <div class="row">
            <div class="col-lg-12">
                <div class="panel panel-primary">
                    <div class="panel-heading">
                        Referrer information
                    </div>
                    <div class="panel-body">

                        <div class="table-responsive" style="overflow-x: unset;">
                            <table id="referrers" class="table table-striped table-bordered table-hover">
                                <thead>
                                <tr>
                                    <th style="text-align: center; width: 30px;"></th>
                                    <th style="text-align: center;">URL base</th>
                                    <th style="text-align: center; width: 170px;">added</th>
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
            <!-- /.col-lg-12 -->
        </div>
        <!-- /.row -->

        {if isset($account) && ($account->isAdmin() == true && $account->isActAsAdmin())}
        <div class="row">
            <div class="col-lg-12">
                <div class="panel panel-primary">
                    <div class="panel-heading">
                        User Agent information
                    </div>
                    <div class="panel-body">

                        <div class="table-responsive" style="overflow-x: unset;">
                            <table id="useragents" class="table table-striped table-bordered table-hover">
                                <thead>
                                <tr>
                                    <th style="text-align: center; width: 30px;"></th>
                                    <th style="text-align: center;">Description</th>
                                    <th style="text-align: center; width: 170px;">added</th>
                                </tr>
                                </thead>
                            </table>
                        </div>
                        <!-- /.table-responsive -->
                    </div>
                </div>
                <!-- /.panel-primary -->
            </div>
            <!-- /.col-lg-12 -->
        </div>
        <!-- /.row -->
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
        $('#images').dataTable({
            stateSave: true,
            responsive: true,
            processing: true,
            serverSide: true,
            ajax: '/json/targetinfo-images.php?id={$target->getId()}',
            order: [[ 4, "asc" ]],
            columnDefs: [
                {
                    targets: "_all"
                },
                {
                    targets: 0,
                    className: "col-center",
                    render: function (data, type, row) {
                        return '<a href="/pages/imageinfo.php?id=' + row[0] + '"class="btn btn-warning btn-xs">' +
                                'View <i class="fa fa-folder-open-o pl-10"></i></a>';
                    },
                    orderable: false
                },
                {
                    targets: 1,
                    className: "col-center",
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
                    orderable: true
                },
                {
                    targets: 4,
                    className: "col-center",
                    orderable: true
                }
            ]
        });

        $('#referrers').dataTable({
            stateSave: true,
            responsive: true,
            processing: true,
            serverSide: true,
            ajax: '/json/targetinfo-referrers.php?id={$target->getId()}',
            order: [[ 2, "asc" ]],
            columnDefs: [
                {
                    targets: "_all"
                },
                {
                    targets: 0,
                    className: "col-center",
                    render: function (data, type, row) {
                        return '<a href="/pages/referrerinfo.php?id=' + row[0] + '"class="btn btn-warning btn-xs">' +
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

        {if isset($account) && ($account->isAdmin() == true && $account->isActAsAdmin())}
        $('#useragents').dataTable({
            stateSave: true,
            responsive: true,
            processing: true,
            serverSide: true,
            ajax: '/json/targetinfo-useragents.php?id={$target->getId()}',
            order: [[ 2, "asc" ]],
            columnDefs: [
                {
                    targets: "_all"
                },
                {
                    targets: 0,
                    className: "col-center",
                    render: function (data, type, row) {
                        return '<a href="/pages/useragentinfo.php?id=' + row[0] + '"class="btn btn-warning btn-xs">' +
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
                }
            ]
        });
        {/if}


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
                url: "/json/targetinfo-dailyrequests.php",
                data: { id: "{$target->getId()}", days: 30 },
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
    });
</script>

</body>

</html>
