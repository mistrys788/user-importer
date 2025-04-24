<?php

function user_importer_log_error($message) {
    $log_dir = USER_IMPORTER_PATH . 'logs';
    $log_file = $log_dir . 'error-log.txt';

    if (!file_exists($log_dir)) mkdir($log_dir, 0755, true);

    $timestamp = date('Y-m-d H:i:s');
    $log_entry = $timestamp.$message;
    error_log($log_entry, 3, $log_file);
}
