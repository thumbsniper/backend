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

    <title>Login - {$site.title}</title>

    {include file='header.tpl'}
</head>

<body>
<div id="wrapper">

    {include file='navigation.tpl'}
    {include file='usermessages.tpl'}

    <div class="row">
        <div class="col-md-6 col-md-offset-3" id="tabs" style="margin-top: 50px;">
            <ul class="nav nav-pills" id="ulTabs">
                <li><a data-toggle="tab" href="#login" data-url="/pages/loginTabLogin.php">Login</a></li>
                <li><a data-toggle="tab" href="#newAccount" data-url="/pages/loginTabNewAccount.php">New Account</a></li>
            </ul>
            <div class="tab-content">
                <div id="login" class="tab-pane active"></div>
                <div id="newAccount" class="tab-pane active"></div>
            </div>
        </div>
    </div>
    <!-- /.row -->
</div>

<!-- jQuery -->
<script src="../bower_components/jquery/dist/jquery.min.js"></script>

<!-- Bootstrap Core JavaScript -->
<script src="../bower_components/bootstrap/dist/js/bootstrap.min.js"></script>

<!-- Metis Menu Plugin JavaScript -->
<script src="../bower_components/metisMenu/dist/metisMenu.min.js"></script>

<!-- Custom Theme JavaScript -->
<script src="../bower_components/startbootstrap-sb-admin-2/dist/js/sb-admin-2.js"></script>

<!-- Bootstrap validator -->
<script src="../bower_components/bootstrap-validator/dist/validator.min.js"></script>

<script type="text/javascript">
    function loadTabContent(pane, href, url)
    {
        // ajax load from data-url
        $(href).load(url,function(result){
            pane.tab('show');
        });
    }

    $('#tabs').on('click','.tablink,#ulTabs a',function (e) {
        e.preventDefault();
        var url = $(this).attr("data-url");

        if (typeof url !== "undefined") {
            var pane = $(this);
            var href = this.hash;
            loadTabContent(pane, href, url)
        } else {
            $(this).tab('show');
        }
    });

    $(document).ready(function(){
        var pane = $('#ulTabs a:first');
        var url = pane.attr("data-url");
        var href = '#login';
        loadTabContent(pane, href, url);

    });
</script>

</body>

</html>
