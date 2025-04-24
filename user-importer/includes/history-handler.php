<?php
function user_importer_upsert_history($args) {
    global $wpdb;
    $table = $wpdb->prefix . 'user_import_history';

    // Use file_name + post_type combo to check uniqueness (if no file_id or file_hash exists)
    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $table WHERE file_name = %s AND post_type = %s",
        $args['file_name'],
        $args['post_type']
    ));

    if ($existing) {
        $wpdb->update($table, [
            'total_rows'    => $args['total_rows'],
            'inserted_rows' => $args['inserted_rows'],
            'skipped_rows'  => $args['skipped_rows'],
            'status'        => $args['status'],
            'imported_at'   => current_time('mysql')
        ], ['id' => $existing]);
    } else {
        $wpdb->insert($table, [
            'file_id'       => $args['file_id'] ?? 0,
            'file_name'     => $args['file_name'],
            'post_type'     => $args['post_type'],
            'total_rows'    => $args['total_rows'],
            'inserted_rows' => $args['inserted_rows'],
            'skipped_rows'  => $args['skipped_rows'],
            'status'        => $args['status'],
            'imported_at'   => current_time('mysql')
        ]);
    }
}


