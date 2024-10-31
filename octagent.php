<?php
/**
 * Plugin Name: Octagent
 * Description: A plugin for integrating Octagent with WordPress.
 * Version: 1.0.0
 * Author: Octagent
 * Author URI: https://octagent.com
 * Text Domain: octagent
 * Domain Path: /languages
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

/*
 * Plugin constants
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if (!defined('Octagent_PLUGIN_VERSION'))
    define('Octagent_PLUGIN_VERSION', '1.0.0');
if (!defined('Octagent_URL'))
    define('Octagent_URL', esc_url(plugin_dir_url(__FILE__)));
if (!defined('Octagent_PATH'))
    define('Octagent_PATH', plugin_dir_path(__FILE__));
if (!defined('Octagent_ENDPOINT'))
    define('Octagent_ENDPOINT', 'octagent.com');
if (!defined('Octagent_PROTOCOL'))
    define('Octagent_PROTOCOL', 'https');

// Plugin activation hook
register_activation_hook(__FILE__, 'octagent_plugin_activate');

// Plugin deactivation hook
register_deactivation_hook(__FILE__, 'octagent_plugin_deactivate');

// Add settings link to the plugin page
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'octagent_plugin_settings_link');

// Initialize the plugin
add_action('admin_menu', 'octagent_plugin_menu');

function octagent_plugin_activate()
{
    // Activation code, if any
    // E.g., initialize default options or perform other activation tasks
    // You can leave it empty if no activation code is needed
}

function octagent_plugin_deactivate()
{
    // Deactivation code, if any
}

function octagent_plugin_settings_link($links)
{
    $settings_link = '<a href="options-general.php?page=octagent">Settings</a>';
    array_unshift($links, $settings_link);
    return $links;
}

function octagent_plugin_menu()
{
    $page_hook = add_menu_page(
        'Octagent',
        'Octagent',
        'manage_options',
        'octagent',
        'octagent_plugin_page'
    );
}

function octagent_plugin_page()
{
    if (!current_user_can('manage_options')) {
        wp_die(esc_html_e('You do not have sufficient permissions to access this page.', 'Octagent'));
    }

    // Determine the active tab
    //$active_tab = isset($_GET['tab']) ? esc_html(sanitize_key($_GET['tab'])) : __('integration', 'Octagent');
	$tab = filter_input(
		INPUT_GET, 
		'tab', 
		FILTER_CALLBACK, 
		['options' => 'esc_html']
	);

	$active_tab = $tab ?: 'integration';
    // Define tabs
    $tabs = array(
        'integration' => __('Octagent Integration', 'Octagent'),
        'apikeys' => __('Apikeys in Octagent', 'Octagent'),
        'popups' => __('Popups in Octagent', 'Octagent'),
    );
    ?>

    <div class="wrap">
        <h2>Octagent</h2>
        <h2 class="nav-tab-wrapper">
            <?php
            foreach ($tabs as $tab_key => $tab_label) {
                $tab_url = ($tab_key === 'apikeys') ? 'https://app.octagent.com/api/' : (($tab_key === 'popups') ? 'https://app.octagent.com/popup/' : add_query_arg(array('page' => 'octagent', 'tab' => $tab_key)));
                $class = ($tab_key === $active_tab) ? 'nav-tab nav-tab-active' : 'nav-tab';
                echo "<a href='" . esc_url($tab_url) . "' class='" . esc_attr($class) . "' target='_blank'>" . esc_html($tab_label) . "</a>";
            }
            ?>
        </h2>

        <?php
        // Display content based on the active tab
        switch ($active_tab) {
            case 'integration':
                octagent_plugin_integration_page();
                break;

            case 'apikeys':
                // External link tabs don't need dedicated content here
                break;

            case 'popups':
                // External link tabs don't need dedicated content here
                break;

            default:
                octagent_plugin_integration_page();
                break;
        }
        ?>
    </div>
<?php
}

function octagent_plugin_integration_page()
{
    if (isset($_POST['save_api_key']) && isset($_POST['set_api_key'])) {
        if (isset($_POST['set_api_key']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['set_api_key'])), 'save_api_key_action')) {
            $api_key_to_save = sanitize_key($_POST['octagent_api_key']);
            update_option('octagent_api_key', $api_key_to_save);
        }
    }

    // Retrieve the saved API key
    $api_key = get_option('octagent_api_key');

    $style_url = esc_url(plugin_dir_url(__FILE__) . 'assets/css/octagent.css');
    wp_enqueue_style('octagent', $style_url, array(), '1.0.0');
    ?>

    <div class="plg-octagent wrap">
        <div class="wrap1">
            <div class="wrap2">
                <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . '/assets/images/octagent-logo.png'); ?>">
                <h3><?php esc_html_e('Octagent connection', 'octagent'); ?></h3>
                <p><strong><?php esc_html_e('Get ready in 3 steps', 'octagent'); ?></strong></p>
                <ol>
                    <li><?php esc_html_e('Create an Octagent account', 'octagent'); ?></li>
                    <li><?php esc_html_e('Get your API keys', 'octagent'); ?></li>
                    <li><?php esc_html_e('Copy and paste', 'octagent'); ?></li>
                </ol>
                <form method="post">
                    <?php wp_nonce_field('save_api_key_action', 'set_api_key'); ?>
                    <div for="octagent_api_key"><?php esc_html_e('API Key', 'octagent'); ?>:</div>
                    <input type="text" id="octagent_api_key" name="octagent_api_key" value="<?php echo esc_attr($api_key); ?>">
                    <button type="submit" name="save_api_key"><?php esc_html_e('Save', 'octagent'); ?></button>
                </form>
            </div>
            <div class="imgp">
                <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . '/assets/images/connection.jpg'); ?>" alt="Octagent">
            </div>
        </div>
    </div>
<?php
}

$api_key = get_option('octagent_api_key');

if (!empty($api_key)) {

    function octagent_enqueue_preload_script()
    {
        $api_key = get_option('octagent_api_key');

        wp_enqueue_script('octagent-preload', 'https://api.octagent.com/plugins/popups/preload.js?key=' . esc_attr($api_key), array(), '1.0.0', true);
    }

    add_action('wp_enqueue_scripts', 'octagent_enqueue_preload_script');
}