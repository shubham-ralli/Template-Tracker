jQuery(document).ready(function ($) {
    var resultData = []; // Store result data for CSV download
    var table; // Declare table variable to store DataTable instance

    // Initialize the DataTable
    function initializeTable() {
        table = $('#myTable').DataTable({
            destroy: true, // Allow table to be re-initialized
            responsive: true // Make table responsive
        });
    }

    // Initialize table on document ready
    initializeTable();

    $('#search-button').click(function () {
        var template = $('#template-select').val();
        var status = $('#status-select').val();
        
        $('#template-pages tbody').empty(); // Clear existing data
        $('#loader').show(); // Show loader
        $('#error').hide().text(''); // Clear any previous error messages
        $('#download-csv').hide(); // Hide download button initially

        // Check if a template is selected
        if (!template) {
            $('#loader').hide();
            $('#template-pages').hide();
            $('#error').show().text('Please select a template before searching.');
            return;
        }

        // Fetch pages based on selected template and status
        $.ajax({
            url: tpl_ajax.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'tpl_fetch_pages',
                template: template,
                status: status,
                nonce: tpl_ajax.nonce // Include nonce for security
            },
            success: function (response) {
                $('#loader').hide(); // Hide loader
                $('#template-pages').show(); // Show table after results are fetched
                $('#error').hide();

                resultData = response; // Store response data for CSV download

                if (response.length > 0) {
                    $('#download-csv').show(); // Show download CSV button if results are found
                    var totalResults = response.length; // Count total results

                    // Destroy existing DataTable instance before appending new data
                    if ($.fn.DataTable.isDataTable('#myTable')) {
                        table.destroy();
                    }

                    // Empty the table body to remove old data
                    $('#template-pages tbody').empty();

                    // Append new rows to the table
                    $.each(response, function (index, page) {
                        $('#template-pages tbody').append(
                            '<tr><td>' + page.index + '</td><td>' + page.title + '</td><td>' + page.template + '</td><td>' + page.type + '</td><td>' + page.status + '</td><td><button class="view-page button" data-url="' + page.url + '">View Page</button></td></tr>'
                        );
                    });

                    $('#total-results').text('Total Results: ' + totalResults); // Show total results

                    // Reinitialize the DataTable after the new rows are appended
                    initializeTable();
                } else {
                    $('#template-pages tbody').append('<tr><td colspan="6">No pages found for this template and status.</td></tr>');
                    $('#total-results').text('Total Results: 0').show(); // No results found
                }
            },
            error: function () {
                $('#loader').hide(); // Hide loader
                $('#template-pages').show(); // Show table even if there is an error
                $('#template-pages tbody').append('<tr><td colspan="6">Error fetching pages.</td></tr>');
                $('#total-results').text('Total Results: 0').show(); // Error occurred
            }
        });
    });

    // Event delegation for View Page buttons
    $(document).on('click', '.view-page', function () {
        var pageUrl = $(this).data('url');
        if (pageUrl) {
            window.open(pageUrl, '_blank'); // Open page in a new tab
        } else {
            alert("Page URL is not defined.");
        }
    });

    // Download CSV button click event
    $('#download-csv').click(function () {
        if (resultData.length === 0) {
            alert("No data available for download.");
            return;
        }

        // Create CSV content
        var csvContent = "data:text/csv;charset=utf-8,";
        csvContent += "Index,Title,Template,Type,Status,URL\n"; // CSV headers

        resultData.forEach(function (page) {
            var row = [
                page.index,
                '"' + page.title + '"', // Wrap text in quotes to handle commas
                page.template,
                page.type,
                page.status,
                page.url
            ].join(",");
            csvContent += row + "\n";
        });

        // Trigger CSV download
        var encodedUri = encodeURI(csvContent);
        var link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", "template_page_list.csv");
        document.body.appendChild(link); // Required for FF

        link.click();
        document.body.removeChild(link); // Clean up
    });
});
