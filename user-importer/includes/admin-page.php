<?php

add_action('admin_menu', function () {
    add_menu_page(
        'User Importer',
        'User Importer',
        'manage_options',
        'user-importer',
        'user_importer_admin_page',
        'dashicons-upload',
        26
    );
});

function user_importer_admin_page() {
    ?>
    <div class="wrap">
        <h1>User Importer</h1>
        <input type="file" id="importFile" accept=".csv,.xml"><br><br>
        <button id="startImport" class="button button-primary">Import</button>

        <div id="fileDetails" style="margin-top: 10px;"></div>

        <div id="progressWrap" style="margin-top: 20px; display:none;">
            <div id="progressBar" style="width: 100%; background: #eee;">
                <div id="progress" style="width: 0; height: 20px; background: green;"></div>
            </div>
            <p id="importStatus"></p>
        </div>
    </div>

    <script>
        const ajaxUrl = "<?= admin_url('admin-ajax.php'); ?>";
    </script>
    <script src="<?= USER_IMPORTER_URL . 'assets/script.js'; ?>"></script>
    <link rel="stylesheet" href="<?= USER_IMPORTER_URL . 'assets/style.css'; ?>">
    <?php
}
