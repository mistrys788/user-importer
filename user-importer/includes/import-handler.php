<?php

add_action('wp_ajax_import_users_batch', function () {
    require_once USER_IMPORTER_PATH . 'includes/logger.php';
    require_once USER_IMPORTER_PATH . 'includes/history-handler.php';

    $batch_data = json_decode(stripslashes($_POST['batch_data']), true);
    $file_name = sanitize_file_name($_POST['file_name'] ?? 'unknown.csv');
    $total_rows = intval($_POST['total_rows'] ?? 0);
    $is_final = $_POST['is_final_batch'] === 'true';

    $inserted = 0;
	$skipped = 0;
    $processed = 0;
    $file_hash = md5($file_name);

    foreach ($batch_data as $index => $row) {
		try {
			$columns = str_getcsv($row);
			if (count($columns) < 6) {
				user_importer_log_error("Row $index skipped: Not enough columns");
				continue;
			}

			list($user_login, $user_pass, $user_email, $first_name, $last_name, $role) = $columns;

			if (username_exists($user_login) || email_exists($user_email)) {
				user_importer_log_error("Row $index skipped: user exists ($user_login)");
				continue;
			}

			$user_id = wp_insert_user([
				'user_login' => sanitize_user($user_login),
				'user_pass' => $user_pass,
				'user_email' => sanitize_email($user_email),
				'first_name' => sanitize_text_field($first_name),
				'last_name' => sanitize_text_field($last_name),
				'role' => sanitize_key($role),
			]);

			if (is_wp_error($user_id)) {
				user_importer_log_error("Row $index error: " . $user_id->get_error_message());
			} else {
				$inserted++;
			}

		} catch (Exception $e) {
			user_importer_log_error("Row $index failed: " . $e->getMessage());
		}
	}
	// Insert history only at the end
    if ($is_final) {
		user_importer_upsert_history([
			'file_id'        => 0, // If not using file IDs, just keep 0 or null
            'file_name'      => $file_name,
            'post_type'      => 'user',
            'total_rows'     => $total_rows,
            'inserted_rows'  => $inserted,
            'skipped_rows'   => $skipped,
            'status'         => 'completed',
            'imported_at'    => current_time('mysql'),
		]);
    }
    echo json_encode([
        'inserted' => $inserted,
        'processed' => count($batch_data),
        'skipped' => $skipped
    ]);
    wp_die();
});
