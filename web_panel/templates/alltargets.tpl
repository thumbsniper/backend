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

    <title>All Targets - {$site.title}</title>

    {include file='header.tpl'}
</head>

<body>

<div id="wrapper">

    {include file='navigation.tpl'}

    <div id="page-wrapper">
        <div class="row">
            <div class="col-lg-12">
                <h1 class="page-header">All Targets</h1>
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
            <div class="col-lg-12">
                <div class="panel panel-primary">
                    <div class="panel-heading">
                        Targets in database
                    </div>
                    <!-- /.panel-heading -->
                    <div class="panel-body">
                        <div class="table-responsive" style="overflow-x: unset;">
                            <table id="targets" class="table table-striped table-bordered table-hover">
                                <thead>
                                <tr>
                                    <th style="text-align: center; width: 30px;"></th>
                                    <th>URL</th>
                                    <th style="text-align: center; width: 130px;">added</th>
                                    <th style="text-align: center; width: 130px;">requested</th>
                                    <th style="text-align: center; width: 50px;">#u</th>
                                    <th style="text-align: center; width: 50px;">#f</th>
                                    <th style="text-align: center; width: 50px;">#r</th>
                                    <th style="text-align: center; width: 50px;">Link</th>
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
        $('#targets').dataTable({
            stateSave: true,
            responsive: true,
            processing: true,
            serverSide: true,
            ajax: '/json/alltargets.php',
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
                    orderable: true
                },
                {
                    targets: 4,
                    className: "col-center",
                    orderable: true
                },
                {
                    targets: 5,
                    className: "col-center",
                    orderable: true
                },
                {
                    targets: 6,
                    className: "col-center",
                    orderable: true
                },
                {
                    targets: 7,
                    render: function (data, type, row) {
                        return '<a href="' + row[1] + '" target="_blank"><button type="button" class="btn btn-success btn-xs"><i class="fa fa-link"></i></button></a>';
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
