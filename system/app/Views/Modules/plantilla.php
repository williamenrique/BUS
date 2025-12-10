<?= head($data)?>

    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Main content -->
        <section class="content">

            <div class="container-fluid">
                <div class="row">
                    <div class="col-12">
                    <!-- Default box -->
                    <?=dep( $_SESSION['userData']) ?>
                    <!-- /box end -->
                    </div>
                </div>
            </div>
        </section>
        <!-- /.content -->
    </div>
    <!-- /.content-wrapper -->

<?= footer($data)?>