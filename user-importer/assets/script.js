jQuery(document).ready(function ($) {
    // Show selected file info
    $('#csvFile').on('change', function () {
        const file = this.files[0];
        if (!file) return;

        const fileSizeKB = (file.size / 1024).toFixed(2);
        $('#fileInfo').html(`<strong>File:</strong> ${file.name} (${fileSizeKB} KB)`);
    });

    $('#startImport').on('click', function () {
        const file = $('#csvFile')[0].files[0];
        if (!file) {
            alert("Please select a CSV file.");
            return;
        }

        $(this).prop('disabled', true).text('Importing...');
        $('#progressContainer').show();
        $('#importProgress').val(0);
        $('#progressText').html("Reading file...");

        // Add spinner if not already added
        if (!$('#importSpinner').length) {
            $('#progressContainer').prepend(`
                <div id="importSpinner" style="float: right; margin-top: -40px;">
                    <img src="${userImporter.spinner}" width="24" height="24" alt="Loading..." />
                </div>
            `);
        }

        const reader = new FileReader();

        reader.onload = function (e) {
            const rows = e.target.result.split(/\r?\n/).filter(line => line.trim() !== '');
            const total = rows.length;
			console.log(total);
            let inserted = 0, batchSize = 200, current = 0;

            function sendBatch() {
                const batch = rows.slice(current, current + batchSize);
				console.log(batch);
                const isFinal = current + batchSize >= total;

                $.post(userImporter.ajaxUrl, {
                    action: 'import_users_batch',
                    batch_data: JSON.stringify(batch),
                    file_name: file.name,
                    total_rows: total,
                    is_final_batch: isFinal
                }, function (data) {
                    try {
                        const result = JSON.parse(data);
                        inserted += result.inserted;
                        current += batchSize;

                        const percent = Math.min(100, Math.round((current / total) * 100));
                        $('#importProgress').val(percent);

                        $('#progressText').html(`
                            <strong>Percentage Complete:</strong> ${percent}%<br>
                            <strong>Processed:</strong> ${inserted}/${total}<br>
                            <strong>File:</strong> ${file.name}<br>
                            <strong>Post Type:</strong> user
                        `);

                        if (current < total) {
                            sendBatch();
                        } else {
                            $('#progressText').prepend('✅ <strong>Import complete!</strong><br>');
                            $('#startImport').prop('disabled', false).text('Import');
                            $('#importSpinner').remove();
                        }
                    } catch (err) {
                        $('#progressText').html('❌ Error: Invalid server response.');
                        console.error('Server error:', err, data);
                        $('#startImport').prop('disabled', false).text('Import');
                        $('#importSpinner').remove();
                    }
                }).fail(function (xhr, status, error) {
                    $('#progressText').html('❌ AJAX request failed: ' + error);
                    $('#startImport').prop('disabled', false).text('Import');
                    $('#importSpinner').remove();
                });
            }

            sendBatch(); // Start first batch
        };

        reader.readAsText(file);
    });
});