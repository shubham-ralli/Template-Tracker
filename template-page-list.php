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
 * Text Domain:       template-tracker-main
 */

defined('ABSPATH') or die('No script kiddies please!');

// Enqueue scripts and styles
function tpl_enqueue_scripts() {

    // Enqueue DataTables script and CSS from local files
    wp_enqueue_script('tpl-script-table', plugin_dir_url(__FILE__) . 'js/dataTables.min.js', array('jquery'), '2.1.8', true);
    wp_enqueue_style('tpl-admin-style-table', plugin_dir_url(__FILE__) . 'css/dataTables.dataTables.min.css', array(), '2.1.8');

    wp_enqueue_script('tpl-script', plugin_dir_url(__FILE__) . 'js/tpl-script.js', array('jquery'), '3', true);
    wp_localize_script('tpl-script', 'tpl_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('tpl_nonce')));
    wp_enqueue_style('tpl-admin-style', plugin_dir_url(__FILE__) . 'css/tpl-style.css', array(), '2.1');
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
                    <option value="default">Default Template</option>
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
                <div class="header_right"><button id="download-csv" class="button" style="display:none;">Download CSV</button></div>
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
    // Check nonce for security
    check_ajax_referer('tpl_nonce', 'nonce');

    // Check if template and status are set, then sanitize and unslash
    $template = isset($_POST['template']) ? sanitize_text_field(wp_unslash($_POST['template'])) : '';
    $status = isset($_POST['status']) ? sanitize_text_field(wp_unslash($_POST['status'])) : '';

    $args = array(
        'post_type' => 'any',
        'posts_per_page' => -1,
    );

    if ($template) {
        $args['meta_query'] = array(
            array(
                'key' => '_wp_page_template',
                'value' => $template,
            ),
        );
    }

    if ($status) {
        $args['post_status'] = $status;
    } else {
        $args['post_status'] = array('publish', 'draft', 'private', 'trash');
    }

    $pages = get_posts($args);
    $output = array();

    foreach ($pages as $index => $page) {
        $output[] = array(
            'index' => $index + 1,
            'title' => $page->post_title,
            'template' => get_post_meta($page->ID, '_wp_page_template', true),
            'type' => $page->post_type,
            'status' => $page->post_status,
            'url' => get_permalink($page->ID)
        );
    }
    wp_send_json($output);
}
add_action('wp_ajax_tpl_fetch_pages', 'tpl_fetch_pages');
