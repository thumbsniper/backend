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

<div class="row">
    <div class="col-md-6">
        <div class="login-panel panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">Sign In</h3>
            </div>

            <div class="panel-body">
                <form id="loginForm" role="form" action="/pages/localAuth.php" method="post" data-toggle="validator">
                    <fieldset>
                        <div class="form-group">
                            <label for="loginFormUsername" class="control-label">Username</label>
                            <input id="loginFormUsername" class="form-control" name="username" type="text" autofocus required
                                   pattern="[a-z0-9]{literal}{5,20}{/literal}" data-minlength="5" maxlength="20">
                            <span class="help-block with-errors"></span>
                        </div>
                        <div class="form-group">
                            <label for="loginFormPassword" class="control-label">Password</label>
                            <input id="loginFormPassword" class="form-control" name="password" type="password" required
                                   data-minlength="8" maxlength="50">
                            <span class="help-block with-errors"></span>
                        </div>
                        <div class="form-group text-center">
                            <button type="submit" class="btn btn-success">Login</button>
                        </div>
                    </fieldset>
                </form>

                <div style="margin-top: 50px;">
                    <a class="btn btn-block btn-social btn-google-plus" href="/pages/googleAuth.php" onclick="$('#loginForm').validator('destroy')">
                        <i class="fa fa-google-plus"></i> Sign in with Google
                    </a>
                    <a class="btn btn-block btn-social btn-twitter" href="/pages/twitterAuthRedirect.php" onclick="$('#loginForm').validator('destroy')">
                        <i class="fa fa-twitter"></i> Sign in with Twitter
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="login-panel panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">Explanatory Notes</h3>
            </div>

            <div class="panel-body">
                <p>Please login with your personal credentials locally or use your Google/Twitter account if you registered yourself here before.</p>
                <p>If you don't have an account, simply create one on the <strong>New Account</strong> tab.</p>
            </div>
        </div>
    </div>
</div>
<!-- /.row -->

<script type="text/javascript">
    $('#loginForm').validator()
</script>