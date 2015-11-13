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

    <title>Register - {$site.title}</title>

    {include file='header.tpl'}
</head>

<body>
<div id="wrapper">

    {include file='navigation.tpl'}

        <div class="row">
            <div class="col-md-4 col-md-offset-4">
                <div class="login-panel panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title">Finish your registration</h3>
                    </div>
                    <div class="panel-body">
                        <p>
                            <strong>No account is associated with your login.</strong>
                        </p>
                        <p style="margin-bottom: 30px;">
                            If you think that this is incorrect, please cancel this dialog and try a different login method.
                        </p>
                        <form role="form" action="/pages/register.php" method="post">
                            <fieldset>
                                <div class="form-group">
                                    <input class="form-control" placeholder="first name" name="firstName" type="text" autofocus>
                                </div>
                                <div class="form-group">
                                    <input class="form-control" placeholder="last name" name="lastName" type="text">
                                </div>
                                <div class="form-group">
                                    <input class="form-control" placeholder="user name" name="userName" type="text">
                                </div>
                                <div class="form-group">
                                    <input class="form-control" placeholder="E-mail" name="email" type="email">
                                </div>
                                <div class="form-group text-right">
                                    <a href="/pages/register.php?action=cancel">
                                        <button type="button" class="btn btn-danger">Cancel</button>
                                    </a>
                                    <button type="submit" class="btn btn-success">Finish</button>
                                </div>
                            </fieldset>
                        </form>
                    </div>
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
</body>

</html>
