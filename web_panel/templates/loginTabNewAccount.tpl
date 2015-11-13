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
                <h3 class="panel-title">Enter account details</h3>
            </div>

            <div class="panel-body">
                <form id="newAccountForm" role="form" action="/pages/newAccount.php" method="post" data-toggle="validator">
                    <fieldset>
                        <div class="form-group">
                            <label for="newAccountFormUsername" class="control-label">Username</label>
                            <input id="newAccountFormUsername" class="form-control" name="username" type="text" autofocus required
                                   placeholder="username" pattern="[a-z0-9]{literal}{5,20}{/literal}"
                                   data-minlength="5" maxlength="20">
                            <span class="help-block with-errors"></span>
                        </div>
                        <div class="form-group">
                            <label for="newAccountFormPassword" class="control-label">Password</label>
                            <input id="newAccountFormPassword" class="form-control" name="password" type="password" required
                                   placeholder="password" data-minlength="8" maxlength="50"
                                   pattern="(?=.*\d)(?=.*[A-Za-z])[0-9A-Za-z!@#$%;:-_]{literal}{8,50}{/literal}">
                            <span class="help-block with-errors"></span>
                        </div>
                        <div class="form-group">
                            <label for="newAccountFormPasswordConfirm" class="control-label">Repeat password</label>
                            <input id="newAccountFormPasswordConfirm" class="form-control" placeholder="password (repeat)"
                                   required data-match="#newAccountFormPassword" data-match-error="Passwords don't match!"
                                   name="passwordRepeated" type="password">
                            <span class="help-block with-errors"></span>
                        </div>
                        <div class="form-group text-center">
                            <button type="submit" class="btn btn-success">Create</button>
                        </div>
                    </fieldset>
                </form>

                <div style="margin-top: 30px;">
                    <a class="btn btn-block btn-social btn-google-plus" href="/pages/googleAuth.php" onclick="$('#newAccountForm').validator('destroy')">
                        <i class="fa fa-google-plus"></i> Use your Google account
                    </a>
                    <a class="btn btn-block btn-social btn-twitter" href="/pages/twitterAuthRedirect.php" onclick="$('#newAccountForm').validator('destroy')">
                        <i class="fa fa-twitter"></i> Use your Twitter account
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
                <ul class="list-group">
                    <li class="list-group-item">
                        <p><strong>Username:</strong></p>
                        <ul>
                            <li>minimum length: 5 characters</li>
                            <li>maximum length: 20 characters</li>
                            <li>valid characters: a-z 0-9</li>
                        </ul>
                    </li>
                    <li class="list-group-item">
                        <p><strong>Password:</strong></p>
                        <ul>
                            <li>minimum length: 8 characters</li>
                            <li>maximum length: 50 characters</li>
                            <li>valid characters: a-z 0-9 ! @ # $ % ; : - _</li>
                        </ul>
                    </li>
                </ul>
                <p>You may alternatively use your existing <strong>Google</strong> or <strong>Twitter</strong> account.</p>
            </div>
        </div>
    </div>
</div>
<!-- /.row -->

<script type="text/javascript">
    $('#newAccountForm').validator()
</script>