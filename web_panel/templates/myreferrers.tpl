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

    <title>My Referrers - {$site.title}</title>

    {include file='header.tpl'}
</head>

<body>

    <div id="wrapper">

	{include file='navigation.tpl'}

        <div id="page-wrapper">
            <div class="row">
                <div class="col-lg-12">
                    <h1 class="page-header">My Referrers</h1>
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
                <div class="col-lg-6">
                    <div class="panel panel-primary">
                        <div class="panel-heading">
                            Add whitelist domain
                        </div>
                        <div class="panel-body">
                            <p>In order to add a whitelisted domain, you need to create a file in the document root of
                                your domain's webspace. When you entered a URL and hit the "Add" button, the system
                                checks for the existence of that file. Please make sure that the file is created prior
                                to adding the domain.</p>

                            <p style="font-weight: bold;">verification file: <span
                                        style="color: red;">/{$account->getDomainVerificationKey()}.html</span></p>
                        </div>
                        <div class="panel-footer">
                            <div class="form-group">
                                <form action="/pages/myreferrers.php" method="POST">
                                    <div class="input-group">
                                        <input class="form-control" name="url" placeholder="Enter full-qualified URL">
		                        <span class="input-group-btn">
                		                <button type="submit" class="btn btn-default">Add</button>
		                        </span>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /.col-lg-6 -->

                <div class="col-lg-6">
                    <div class="panel panel-primary">
                        <div class="panel-heading">
                            Whitelist settings
                        </div>
                        <div class="panel-body">
                            <p>
                                You may disable the whitelist detection (based on referrer addresses) here.
                                This would be typically desired if you only do API-key-based requests to get ThumbSniper's thumbnails
                                and you don't want to let someone "hijack" your domain to abuse your quota. API-Key usage is preferred!
                            </p>
                        </div>
                        <div class="panel-footer" style="text-align: center;">
                            {if $account->isWhitelistActive()}
                            <a style="font-weight: bold;" href="/pages/myreferrers.php?action=disable"><button type="button" class="btn btn-success">disable whitelist referrers</button></a>
                            {else}
                                {if $account->getApiKey()}
                                <a style="font-weight: bold;" href="/pages/myreferrers.php?action=enable">
                                    <button type="button" class="btn btn-danger" >enable whitelist referrers</button>
                                </a>
                                {else}
                                    <button type="button" class="btn btn-danger" disabled>enable whitelist</button> Please create an API key first
                                {/if}
                            {/if}
                        </div>
                    </div>
                </div>
                <!-- /.col-lg-6 -->
            </div>
            <!-- /.row -->

            <div class="row">
                <div class="col-lg-12">
                    <div class="panel panel-primary">
                        <div class="panel-heading">
                            Base URL's of registered sites
                        </div>
                        <!-- /.panel-heading -->
                        <div class="panel-body">
                            <div class="table-responsive" style="overflow-x: unset;">
                                <table id="myreferrers" class="table table-striped table-bordered table-hover">
                                    <thead>
                                    <tr>
                                        <th style="text-align: right; width: 30px;"></th>
                                        <th>URL</th>
                                        <th style="text-align: center; width: 150px;">added</th>
                                        <th style="text-align: center; width: 150px;">requested</th>
                                        <th style="text-align: center; width: 50px;">#r</th>
                                        <th style="text-align: center; width: 50px;">Link</th>
                                        <th style="text-align: center; width: 50px;">Delete</th>
                                    </tr>
                                    </thead>
                                </table>
                            </div>
                            <!-- /.table-responsive -->
                        </div>
                        <!-- /.panel-body -->
                        </div>
                        <!-- /.panel -->
                </div>
                <!-- /.col-lg-12 -->
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

    <!-- DataTables JavaScript -->
    <script src="../bower_components/datatables/media/js/jquery.dataTables.min.js"></script>

    <!-- Custom Theme JavaScript -->
    <script src="../bower_components/startbootstrap-sb-admin-2/dist/js/sb-admin-2.js"></script>

    <script type="text/javascript">
        $(document).ready(function () {
            $('#myreferrers').dataTable({
                stateSave: true,
                responsive: true,
                processing: true,
                serverSide: true,
                ajax: '/json/myreferrers.php',
                order: [[ 1, "asc" ]],
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
                        orderable: true
                    },
                    {
                        targets: 4,
                        className: "col-center",
                        orderable: true
                    },
                    {
                        targets: 5,
                        render: function (data, type, row) {
                            return '<a href="' + row[1] + '" target="_blank"><button type="button" class="btn btn-success btn-xs"><i class="fa fa-link"></i></button></a>';
                        },
                        className: "col-center",
                        orderable: false
                    },
                    {
                        targets: 6,
                        render: function (data, type, row) {
                            return '<button class="btn btn-warning btn-xs" data-toggle="modal" data-target="#modalDelete' + row[0] + '"><i class="fa fa-times-circle"></i></button>' +
                                    '<div style="display: none;" class="modal fade" id="modalDelete' + row[0] + '" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">' +
                                    '<div class="modal-dialog"><div class="modal-content"><div class="modal-header">' +
                                    '<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>' +
                                    '<h4 class="modal-title" id="myModalLabel">Confirm deletion</h4></div>' +
                                    '<div class="modal-body">Do you really want to delete referrer ' + row[0] + '?</div>' +
                                    '<div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">Abort</button>' +
                                    '<a href="/pages/myreferrers.php?action=delete&id=' + row[0] + '"><button type="button" class="btn btn-primary">Delete</button></a>' +
                                    '</div></div></div></div>';
                        },
                        className: "col-center",
                        orderable: false
                    }
                ]
            });
        });
    </script>
</body>

</html>
