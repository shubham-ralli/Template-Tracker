jQuery(document).ready(function ($) {
    var resultData = []; // Store result data for CSV download

    $('#search-button').click(function () {
        var template = $('#template-select').val();
        var status = $('#status-select').val();
        
        $('#template-pages tbody').empty();
        $('#loader').show(); // Show loader

        // Fetch pages based on selected template and status
        $.ajax({
            url: tpl_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'tpl_fetch_pages',
                template: template,
                status: status,
            },
            success: function (response) {
                $('#loader').hide(); // Hide loader
                $('#template-pages').show(); // Show table after results are fetched
                $('#download-csv').show(); // Show download CSV button after results are shown

                resultData = response; // Store response data for CSV download

                if (response.length > 0) {
                    var totalResults = response.length; // Count total results
                    $.each(response, function (index, page) {
                        $('#template-pages tbody').append(
                            '<tr><td>' + page.index + '</td><td>' + page.title + '</td><td>' + page.template + '</td><td>' + page.type + '</td><td>' + page.status + '</td><td><button class="view-page button" data-url="' + page.url + '">View Page</button></td></tr>'
                        );
                    });
                    $('#total-results').text('Total Results: ' + totalResults); // Show total results
                } else {
                    $('#template-pages tbody').append('<tr><td colspan="6">No pages found for this template and status.</td></tr>');
                    $('#total-results').text('Total Results: 0'); // No results found
                }
            },
            error: function () {
                $('#loader').hide(); // Hide loader
                $('#template-pages').show(); // Show table even if there is an error
                $('#template-pages tbody').append('<tr><td colspan="6">Error fetching pages.</td></tr>');
                $('#total-results').text('Total Results: 0'); // Error occurred
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
