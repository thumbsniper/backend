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

    <title>Image information - {$site.title}</title>

    {include file='header.tpl'}
</head>

<body>

    <div id="wrapper">

	{include file='navigation.tpl'}

        <div id="page-wrapper">
            <div class="row">
                <div class="col-lg-12">
                    <h1 class="page-header">Image information</h1>
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
                                                        <td style="vertical-align: middle;">{$image->getId()}</td>
                                                </tr>
                                                <tr>
                                                        <td style="vertical-align: middle;">target</td>
                                                        <td style="vertical-align: middle;">
                                                            <a href="/pages/targetinfo.php?id={$image->getTargetId()}">{$image->getTargetId()}</a>
							                            </td>
                                                </tr>
						        <tr>
                					<td style="vertical-align: middle;">width:</td>
                					<td style="vertical-align: middle;">{$image->getWidth()}</td>
            					</tr>
                                                {if $image->getHeight()}
                                                    <tr>
                                                        <td style="vertical-align: middle;">height:</td>
                                                        <td style="vertical-align: middle;">{$image->getHeight()}</td>
                                                    </tr>
                                                {/if}
                                                <tr>
                                                    <td style="vertical-align: middle;">effect:</td>
                                                    <td style="vertical-align: middle;">{$image->getEffect()}</td>
                                                </tr>
            					<tr>
                					<td style="vertical-align: middle;">filename suffix:</td>
                					<td style="vertical-align: middle;">{$image->getFileNameSuffix()}</td>
            					</tr>
                                                {if $image->getLocalPath()}
                                                    <tr>
                                                        <td style="vertical-align: middle;">local path:</td>
                                                        <td style="vertical-align: middle;">{$image->getLocalPath()}</td>
                                                    </tr>
                                                {/if}
                                                {if $image->getAmazonS3url()}
                                                <tr>
                                                    <td style="vertical-align: middle;">Amazon S3:</td>
                                                    <td style="vertical-align: middle;"><a href="{$image->getAmazonS3url()}" target="_blank">{$image->getAmazonS3url()}</a></td>
                                                </tr>
                                                {/if}
                                                <tr>
                                                        <td style="vertical-align: middle;">added</td>
                                                        <td style="vertical-align: middle;">{$image->getTsAdded()|date_format:"%d.%m.%Y %H:%M:%S"}</td>
                                                </tr>
						<tr>
                                                        <td style="vertical-align: middle;">updated</td>
                                                        <td style="vertical-align: middle;">{if !is_null($image->getTsLastUpdated())}{$image->getTsLastUpdated()|date_format:"%d.%m.%Y %H:%M:%S"}{else}never{/if}</td>
                                                </tr>
						<tr>
                                                        <td style="vertical-align: middle;">requested</td>
                                                        <td style="vertical-align: middle;">{$image->getTsLastRequested()|date_format:"%d.%m.%Y %H:%M:%S"}</td>
                                                </tr>
                                                <tr>
                                                    <td style="vertical-align: middle;">checkouts</td>
                                                    <td style="vertical-align: middle;">{$image->getCounterCheckedOut()}</td>
                                                </tr>
                                                <tr>
                                                    <td style="vertical-align: middle;">updates</td>
                                                    <td style="vertical-align: middle;">{$image->getCounterUpdated()}</td>
                                                </tr>
                                                <tr>
                                                    <td style="vertical-align: middle;">requests</td>
                                                    <td style="vertical-align: middle;">{$image->getNumRequests()}</td>
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

                {if is_array($thumbnail) && (isset($thumbnail['status']) && $thumbnail['status'] == "ok")}
                    <div class="col-lg-4">
                        <div class="panel panel-primary">
                            <div class="panel-heading">
                                Thumbnail
                            </div>
                            <div class="panel-body" style="text-align: center;">
                                <img style="max-width: 100%;" src="{$thumbnail['redirectUrl']}{if isset($thumbnail['newTargetUrl'])}{$thumbnail['newTargetUrl']}{/if}" />
                            </div>
                        </div>
                        <!-- /.panel-primary -->
                    </div>
                    <!-- /.col-lg-4 -->
                {/if}
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

    <!-- Page-Level Demo Scripts - Tables - Use for reference -->
    <script>
    $(document).ready(function() {

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
                url: "/json/imageinfo-dailyrequests.php",
                data: { id: "{$image->getId()}", days: 7 },
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
