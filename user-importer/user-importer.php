<?php
/**
 * Plugin Name: User Importer
 * Description: Import users via CSV with role assignment, batch processing & import history.
 * Version: 1.0
 * Author: Sanket
 */
// increase execution time
@ini_set('max_execution_time', 600);

define('USER_IMPORTER_PATH', plugin_dir_path(__FILE__));
define('USER_IMPORTER_URL', plugin_dir_url(__FILE__));

require_once USER_IMPORTER_PATH . 'includes/import-handler.php';
require_once USER_IMPORTER_PATH . 'includes/history-handler.php';
require_once USER_IMPORTER_PATH . 'includes/logger.php';

add_action('admin_menu', function () {
    add_menu_page('User Importer', 'User Importer', 'manage_options', 'user-importer', function () {
        ?>
        <div class="wrap">
            <h1>User Importer</h1>
			<h2 class="nav-tab-wrapper cust-tab">
				<a href="?page=user-importer" class="nav-tab active">Import</a>
				<a href="?page=user-importer-history" class="nav-tab">History</a>
			</h2>
            <div id="user-importer-box">
                <div style="margin-bottom: 10px;">
                    <input type="file" id="csvFile" accept=".csv" />
                    <div id="fileInfo"></div>
                </div>

                <button id="startImport" class="button button-primary">Import</button>

                <div id="progressContainer" style="display:none; margin-top: 20px;">
                    <div class="inner-boxpc">
                        <div>
                            <p id="progressText">Percentage Complete: 0%<br>Processed: 0/0<br>File: -</p>
                            <p id="postType">Post Type: user</p>
                        </div>
                        <div>
                            <div id="importSpinner">
                                <img src="<?php echo admin_url('images/spinner.gif'); ?>" alt="loading" />
                            </div>
                        </div>
                    </div>
                    <progress id="importProgress" value="0" max="100"></progress>
                </div>
            </div>
        </div>

        <?php
    });
	add_submenu_page('user-importer', 'Import History', 'History', 'manage_options', 'user-importer-history', 'render_import_history_page');
});

function render_import_history_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'user_import_history';
    $results = $wpdb->get_results("SELECT * FROM $table_name ORDER BY imported_at DESC");

    echo '<div class="wrap"><h1>Import History</h1>';
	echo '<h2 class="nav-tab-wrapper cust-tab"><a href="?page=user-importer" class="nav-tab">Import</a><a href="?page=user-importer-history" class="nav-tab active">History</a></h2>';
    echo '<table class="widefat fixed striped">';
    echo '<thead><tr>
            <th>ID</th><th>File</th><th>Post type</th><th>Processed</th>
            <th>Skipped</th><th>Status</th><th>Import Date</th>
        </tr></thead><tbody>';

    foreach ($results as $row) {
        echo "<tr>
            <td>{$row->id}</td>
            <td>{$row->file_id}</td>
            <td>{$row->post_type}</td>
            <td>{$row->inserted_rows} of {$row->total_rows}</td>
            <td>{$row->skipped_rows}</td>
            <td>{$row->status}</td>
            <td>{$row->imported_at}</td>
        </tr>";
    }

    echo '</tbody></table></div>';
}

add_action('admin_enqueue_scripts', function ($hook) {
	if (!in_array($hook, ['toplevel_page_user-importer', 'user-importer_page_user-importer-history'])) return;
		wp_enqueue_style('user-importer-admin-css', USER_IMPORTER_URL . 'assets/style.css', [], '1.0');
		wp_enqueue_script('jquery');
		wp_enqueue_script('user-importer-js', USER_IMPORTER_URL . 'assets/script.js', ['jquery'], null, true);
		wp_localize_script('user-importer-js', 'userImporter', [
			'ajaxUrl' => admin_url('admin-ajax.php'),
			'spinner' => admin_url('images/spinner.gif')
		]);
});

register_activation_hook(__FILE__, function () {
    global $wpdb;
    $table_name = $wpdb->prefix . 'user_import_history';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        file_id int NOT NULL,
        file_name varchar(255) NOT NULL,
        post_type varchar(50) NOT NULL,
        total_rows int NOT NULL,
        inserted_rows int NOT NULL,
        skipped_rows int DEFAULT 0,
        status varchar(20) DEFAULT 'new',
        imported_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
});