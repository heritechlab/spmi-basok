<footer class="app-footer">
                &copy; <?= date('Y') ?> <?= APP_NAME ?> — Sistem Informasi Audit Mutu Internal.
                Dikembangkan oleh <strong>Basok Buhari</strong>. Hak Cipta Dilindungi.
            </footer>

        </main>

    </div>

</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>

<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script src="<?= BASE_URL ?>assets/js/notifications.js"></script>
<script src="<?= BASE_URL ?>assets/js/global-search.js"></script>

<script>

    const SIQUA = {

        BASE_URL : "<?= BASE_URL ?>",

        APP_NAME : "<?= APP_NAME ?>",

        VERSION : "<?= APP_VERSION ?>"

    };

</script>

<?php

if(!empty($pageScript))
{

echo '<script src="'.BASE_URL.$pageScript.'"></script>';

}

?>

</body>

</html>
