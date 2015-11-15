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

    <title>Account information - {$site.title}</title>

    {include file='header.tpl'}
</head>

<body>

    <div id="wrapper">

	{include file='navigation.tpl'}

        <div id="page-wrapper">
            <div class="row">
                <div class="col-lg-12">
                    <h1 class="page-header">Account information</h1>
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
                <div class="col-lg-8">
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
                                        <td style="vertical-align: middle;">{$myaccount->getId()}</td>
                                    </tr>
                                    <tr>
                                        <td style="vertical-align: middle;">first name</td>
                                        <td style="vertical-align: middle;">{$myaccount->getFirstName()}</td>
                                    </tr>
                                    <tr>
                                        <td style="vertical-align: middle;">last name</td>
                                        <td style="vertical-align: middle;">{$myaccount->getLastName()}</td>
                                    </tr>
                                    <tr>
                                        <td style="vertical-align: middle;">E-Mail</td>
                                        <td style="vertical-align: middle;">
                                            {$myaccount->getEmail()}
                                            {if $myaccount->isEmailVerified()}
                                                <i title="verified" class="fa fa-check"></i>
                                            {/if}
                                        </td>
                                    </tr>
                                    {if isset($account) && ($account->isAdmin() == true && $account->isActAsAdmin())}
                                    <tr>
                                        <td style="vertical-align: middle;">active</td>
                                        <td style="vertical-align: middle;">
                                            {if $myaccount->isActive()}
                                            <i title="verified" class="fa fa-check">
                                                {else}
                                                <i title="verified" class="fa fa-times">
                                                    {/if}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="vertical-align: middle;">admin</td>
                                        <td style="vertical-align: middle;">
                                            {if $myaccount->isAdmin()}
                                            <i title="verified" class="fa fa-check">
                                                {else}
                                                <i title="verified" class="fa fa-times">
                                                    {/if}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="vertical-align: middle;">Domain verification key</td>
                                        <td style="vertical-align: middle;">{$myaccount->getDomainVerificationKey()}</td>
                                    </tr>
                                    {/if}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
		    <!-- /.panel-primary -->

                    {if $googleAuthEnabled == true}
                        {if isset($oauthProfiles) && isset($oauthProfiles.google)}
                                <div class="panel panel-primary">
                                    <div class="panel-heading">
                                        <i class="fa fa-google"></i> Linked with Google account
                                    </div>
                                    <div class="panel-body">
                                        {if isset($account) && ($account->isAdmin() == true && $account->isActAsAdmin())}
                                        <div class="table-responsive">
                                            <table class="table">
                                                <tbody>
                                                <tr>
                                                    <td style="vertical-align: middle;">Oauth ID</td>
                                                    <td style="vertical-align: middle;">{$oauthProfiles.google->getId()}</td>
                                                </tr>
                                                <tr>
                                                    <td style="vertical-align: middle;">access token type</td>
                                                    <td style="vertical-align: middle;">{$oauthProfiles.google->getAccessTokenType()}</td>
                                                </tr>
                                                <tr>
                                                    <td style="vertical-align: middle;">access token</td>
                                                    <td style="vertical-align: middle;">{$oauthProfiles.google->getAccessToken()}</td>
                                                </tr>
                                                <tr>
                                                    <td style="vertical-align: middle;">access token expiry</td>
                                                    <td style="vertical-align: middle;">{$oauthProfiles.google->getAccessTokenExpiry()}</td>
                                                </tr>
                                                <tr>
                                                    <td style="vertical-align: middle;">refresh token</td>
                                                    <td style="vertical-align: middle;">{$oauthProfiles.google->getRefreshToken()}</td>
                                                </tr>
                                                <tr>
                                                    <td style="vertical-align: middle;">added</td>
                                                    <td style="vertical-align: middle;">{$oauthProfiles.google->getTsAdded()|date_format:"%d.%m.%Y %H:%M:%S"}</td>
                                                </tr>
                                                <tr>
                                                    <td style="vertical-align: middle;">updated</td>
                                                    <td style="vertical-align: middle;">{$oauthProfiles.google->getTsLastUpdated()|date_format:"%d.%m.%Y %H:%M:%S"}</td>
                                                </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                        {else}
                                            connection established
                                        {/if}
                                    </div>
                                </div>
                                <!-- /.panel-primary -->
                        {else}
                            <div class="panel panel-primary">
                                <div class="panel-heading">
                                    <i class="fa fa-google"></i> Google+
                                </div>
                                <div class="panel-body">
                                    {if $myaccount->getId() == $account->getId()}
                                    <a class="btn btn-social btn-google-plus" href="/pages/googleAuth.php">
                                        <i class="fa fa-google-plus"></i> Connect with Google+
                                    </a>
                                    {else}
                                        not connected
                                    {/if}
                                </div>
                            </div>
                            <!-- /.panel-primary -->
                        {/if}
                    {/if}

                    {if $twitterAuthEnabled == true}
                        {if isset($oauthProfiles) && isset($oauthProfiles.twitter)}
                            <div class="panel panel-primary">
                                <div class="panel-heading">
                                    <i class="fa fa-twitter"></i> Linked with Twitter account
                                </div>
                                <div class="panel-body">
                                    {if isset($account) && ($account->isAdmin() == true && $account->isActAsAdmin())}
                                    <div class="table-responsive">
                                        <table class="table">
                                            <tbody>
                                            <tr>
                                                <td style="vertical-align: middle;">Oauth ID</td>
                                                <td style="vertical-align: middle;">{$oauthProfiles.twitter->getId()}</td>
                                            </tr>
                                            <tr>
                                                <td style="vertical-align: middle;">screen name</td>
                                                <td style="vertical-align: middle;">{$oauthProfiles.twitter->getScreenName()}</td>
                                            </tr>
                                            <tr>
                                                <td style="vertical-align: middle;">access token</td>
                                                <td style="vertical-align: middle;">{$oauthProfiles.twitter->getAccessToken()}</td>
                                            </tr>
                                            <tr>
                                                <td style="vertical-align: middle;">access token secret</td>
                                                <td style="vertical-align: middle;">{$oauthProfiles.twitter->getAccessTokenSecret()}</td>
                                            </tr>
                                            <tr>
                                                <td style="vertical-align: middle;">added</td>
                                                <td style="vertical-align: middle;">{$oauthProfiles.twitter->getTsAdded()|date_format:"%d.%m.%Y %H:%M:%S"}</td>
                                            </tr>
                                            <tr>
                                                <td style="vertical-align: middle;">updated</td>
                                                <td style="vertical-align: middle;">{$oauthProfiles.twitter->getTsLastUpdated()|date_format:"%d.%m.%Y %H:%M:%S"}</td>
                                            </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    {else}
                                        connection established
                                    {/if}
                                </div>
                            </div>
                            <!-- /.panel-primary -->
                        {else}
                            <div class="panel panel-primary">
                                <div class="panel-heading">
                                    <i class="fa fa-twitter"></i> Twitter
                                </div>
                                <div class="panel-body">
                                    {if $myaccount->getId() == $account->getId()}
                                    <a class="btn btn-social btn-twitter" href="/pages/twitterAuthRedirect.php">
                                            <i class="fa fa-twitter"></i> Connect with Twitter
                                    </a>
                                    {else}
                                        not connected
                                    {/if}
                                </div>
                            </div>
                        <!-- /.panel-primary -->
                        {/if}
                    {/if}
                </div>
                <!-- /.col-lg-6 -->

                <div class="col-lg-4">
                    <div class="panel panel-primary">
                        <div class="panel-heading">
                            API-Key Information
                        </div>
                        <div class="panel-body">
                            {if $myaccount->getApiKey() != null}
                                <div class="table-responsive">
                                    <table class="table">
                                        <tbody>
                                        {if isset($account) && ($account->isAdmin() == true && $account->isActAsAdmin())}
                                        <tr>
                                            <td style="vertical-align: middle;">key (MD5)</td>
                                            <td style="vertical-align: middle;">{$myaccount->getApiKey()}</td>
                                        </tr>
                                        <tr>
                                            <td style="vertical-align: middle;">added</td>
                                            <td style="vertical-align: middle;">{$myaccount->getApiKeyTsAdded()|date_format:"%d.%m.%Y %H:%M:%S"}</td>
                                        </tr>
                                        <tr>
                                            <td style="vertical-align: middle;">active</td>
                                            <td style="vertical-align: middle;">
                                                {if $myaccount->isApiKeyActive()}
                                                <i title="verified" class="fa fa-check">
                                                    {else}
                                                    <i title="verified" class="fa fa-times">
                                                        {/if}
                                            </td>
                                        </tr>
                                        {/if}
                                        <tr>
                                            <td>Expires:</td>
                                            <td>{$myaccount->getApiKeyTsExpire()|date_format:"%m/%d/%Y - %H:%M:%S"}</td>
                                        </tr>
                                        {if isset($account) && ($account->isAdmin() == true && $account->isActAsAdmin())}
                                        <tr>
                                            <td>Type:</td>
                                            <td>{$myaccount->getApiKeyType()}</td>
                                        </tr>
                                        {/if}
                                        <tr>
                                            <td>Limit:</td>
                                            {if is_null($myaccount->getMaxDailyRequests())}
                                                <td>unlimited</td>
                                            {else}
                                                <td>{$myaccount->getMaxDailyRequests()|number_format:0:",":"."} requests/day</td>
                                            {/if}
                                        </tr>
                                        </tbody>
                                    </table>
                                </div>
                            {/if}

                            <div class="panel panel-red">
                                <div class="panel-body">
                                    {if $myaccount->getApiKey() == null}
                                        <div style="padding: 5px; text-align: center;"><a style="font-weight: bold;" href="/pages/accountinfo.php?action=create"><button type="button" class="btn btn-danger">create API key</button></a></div>
                                    {else}
                                        {if isset($privateApiKey)}
                                            <div style="padding: 5px; text-align: center; font-weight: bold;">Please keep your personal private API key safe. It won't be shown here again:</div>
                                            <div style="padding: 5px;"><input class="form-control" style="text-align: center;" value="{$privateApiKey}" readonly /></div>
                                        {else}
                                            <div style="padding: 5px; text-align: center;">If your API key was exposed or even exploited, you should reset your API key here.</div>
                                            <div style="padding: 5px; text-align: center;"><button type="button" class="btn btn-danger" data-toggle="modal" data-target="#resetApiKey">generate new API key</button></div>
                                            <div style="padding: 5px; text-align: center;">Your current key will become invalid immediately, so be careful!</div>
                                        {/if}
                                    {/if}
                                </div>
                            </div>
                            <!-- /.panel-danger -->
                        </div>
                    </div>
                    <!-- /.panel-primary -->

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
        </div>
        <!-- /#page-wrapper -->

    </div>
    <!-- /#wrapper -->

    <div style="display: none;" class="modal fade" id="resetApiKey" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                    <h4 class="modal-title" id="myModalLabel">Confirm API key reset</h4></div>
                <div class="modal-body">Do you really want to reset your API key?</div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Abort</button>
                    <a href="/pages/accountinfo.php?action=reset">
                        <button type="button" class="btn btn-primary">Reset</button>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery -->
    <script src="../bower_components/jquery/dist/jquery.min.js"></script>

    <!-- Bootstrap Core JavaScript -->
    <script src="../bower_components/bootstrap/dist/js/bootstrap.min.js"></script>

    <!-- Metis Menu Plugin JavaScript -->
    <script src="../bower_components/metisMenu/dist/metisMenu.min.js"></script>

    <!-- Morris Charts JavaScript -->
    <script src="../bower_components/raphael/raphael-min.js"></script>
    <script src="../bower_components/morrisjs/morris.min.js"></script>

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
                ykeys: ['numRequests'],
                xLabels: 'day',
                xLabelAngle: '45',
                labels: ['Requests'],
                hideHover: 'auto',
                goals: [{($myaccount->getMaxDailyRequests() / 100) * 80}, {$myaccount->getMaxDailyRequests()}],
                goalLineColors: ['#EC971F', '#D43F3A']
            });

            var ajaxDailyRequests = function () {
                $.ajax({
                    type: "GET",
                    dataType: 'json',
                    url: "/json/myaccount-dailyrequests.php",
                    data: { oauth_id: "{$myaccount->getId()}", days: 30 },
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
