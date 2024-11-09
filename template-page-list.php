<?php
/**
 * Plugin Name:       Template Tracker
 * Version:           1.0.0
 * Plugin URI:        https://github.com/shubham-ralli/template-tracker
 * Description:       Displays a list of posts based on the selected template and status.
 * Author:            Shubham Ralli
 * Author URI:        https://github.com/shubham-ralli
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       template-tracker
 */

defined('ABSPATH') or die('No script kiddies please!');

// Enqueue scripts and styles
function tpl_enqueue_scripts() {


    wp_enqueue_script('tpl-script-table', 'https://cdn.datatables.net/2.1.8/js/dataTables.min.js', array('jquery'), '1', true);

    wp_enqueue_script('tpl-script', plugin_dir_url(__FILE__) . 'tpl-script.js', array('jquery'), '1', true);
    wp_localize_script('tpl-script', 'tpl_ajax', array('ajax_url' => admin_url('admin-ajax.php')));
    
    // Enqueue admin CSS for styling
    wp_enqueue_style('tpl-admin-style', plugin_dir_url(__FILE__) . 'css/tpl-style.css', array(), '1');

    wp_enqueue_style('tpl-admin-style-table', 'https://cdn.datatables.net/2.1.8/css/dataTables.dataTables.min.css', array(), '1');
}
add_action('admin_enqueue_scripts', 'tpl_enqueue_scripts');

// Create the admin page under Tools menu
function tpl_create_admin_page() {
    add_management_page('Template Tracker', 'Template Tracker', 'manage_options', 'tpl-page-list', 'tpl_render_admin_page');
}
add_action('admin_menu', 'tpl_create_admin_page');



// Add "Settings" and "Deactivate" links on the Plugins page
function sf_plugin_action_links( $links ) {
    $settings_link = '<a href="' . admin_url( 'tools.php?page=tpl-page-list' ) . '">Settings</a>';
    array_unshift( $links, $settings_link );
    return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'sf_plugin_action_links' );


// Render the admin page
function tpl_render_admin_page() {
    $templates = wp_get_theme()->get_page_templates();
    ?>
<div class="template-tracker-wrap">
    <h1>Template Tracker</h1>


    <div class="template-tracker-from">

    <div id="error" style="display:none;"></div>

    <div class="ttm-frm-group">
            <label for="template-select">Select a Template:</label>
            <select id="template-select">
                <option value="">Select a Template</option>
                <option value="default">Default Template</option> <!-- Default Template Option -->
                <?php foreach ($templates as $template_name => $template_file) : ?>
                    <option value="<?php echo esc_html($template_name); ?>"><?php echo esc_attr($template_file); ?></option>
                <?php endforeach; ?>
            </select>
        </div>


        <div class="ttm-frm-group">
            <label for="status-select">Select Post Status:</label>
            <select id="status-select">
                <option value="">All Statuses</option>
                <option value="publish">Published</option>
                <option value="draft">Draft</option>
                <option value="private">Private</option>
                <option value="trash">Trash</option>
            </select>
        </div>

        <button id="search-button" class="button">Search Posts <div id="loader" style="display:none;"></div></button>

    </div>

    <div class="result" id="template-pages" style="display:none;">

        <div class="result_header">
            <div class="header_left">
                <h3>Result</h3>
            </div>
            <div class="header_right"><button id="download-csv" class="button" style="display:none;">Download
                    CSV</button></div>
        </div>
        <table class="widefat display" id="myTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Title</th>
                    <th>Template Name</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
        <p id="total-results" style="display:none;"></p>


    </div>
</div>
<?php
}

// AJAX function to fetch pages
function tpl_fetch_pages() {
    $template = sanitize_text_field($_POST['template']);
    $status = sanitize_text_field($_POST['status']);

    // Prepare arguments to get all post types
    $args = array(
        'post_type' => 'any', // Get all post types
        'posts_per_page' => -1, // Get all posts
    );

    // If a specific template is selected, add it to the query
    if ($template) {
        $args['meta_query'] = array(
            array(
                'key' => '_wp_page_template',
                'value' => $template,
            ),
        );
    }

    // If a specific post status is selected, add it to the query
    if ($status) {
        $args['post_status'] = $status;
    } else {
        $args['post_status'] = array('publish', 'draft', 'private', 'trash'); // Default to all statuses
    }

    $pages = get_posts($args);
    $output = array();
    
    foreach ($pages as $index => $page) {
        $output[] = array(
            'index' => $index + 1, // Add index for display
            'title' => $page->post_title,
            'template' => get_post_meta($page->ID, '_wp_page_template', true),
            'type' => $page->post_type, // Retrieve the actual post type
            'status' => $page->post_status, // Retrieve the post status
            'url' => get_permalink($page->ID) // Include the permalink
        );
    }
    wp_send_json($output);
}
add_action('wp_ajax_tpl_fetch_pages', 'tpl_fetch_pages');
