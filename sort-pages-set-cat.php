<?php
/*
	Plugin Name: Sort Pages, Set Categories
	Description: Organize pages with drag-and-drop and set categories.
	Version: 1.0
	Stable Tag: 6.6
	Requires at least: 5.3
	Tested up to: 6.6
	Requires PHP: 7.3
	Donate link: https://sloansweb.com/say-thanks/
	Author: Sloan Thrasher
	Author URI: https://sloansweb.com/page-4/
	License: GPLv3 or later
	License URI: http://www.gnu.org/licenses/gpl-3.0.html

	A very simple plugin to order pages with drag-and-drop and assign categories to pages. Use if your theme does not support setting the order of pages.
	This plugin adds a new submenu page under Appearance called "Sort And Categorize Pages". This page displays a table of all pages with their current order and category. The user can drag and drop the pages to change their order. The user can also set one or more categories for each page.
*/

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

/**
 * Enqueue necessary scripts and styles for the Sort Pages, Set Categories admin page.
 *
 * @param string $hook_suffix The current admin page.
 */
function cstspp_enqueue_scripts($hook_suffix)
{
	// Only enqueue scripts and styles on the Sort and Categorize Pages admin page.
	if ($hook_suffix !== 'appearance_page_cstspp-sort-pages') {
		return;
	}

	$ver = '1.0';

	// Enqueue the plugin's stylesheet.
	wp_enqueue_style(
		'cstspp-styles',
		plugin_dir_url(__FILE__) . 'css/cstspp_styles.css',
		array(),
		$ver
	);

	// Enqueue the plugin's JavaScript file.
	wp_enqueue_script(
		'cstspp-scripts',
		plugin_dir_url(__FILE__) . 'js/cstspp_scripts.js',
		array('jquery'),
		$ver,
		true
	);

	// Localize the JavaScript file with REST API root and nonce.
	wp_localize_script(
		'cstspp-scripts',
		'cstsppRestApi',
		array(
			'root' => esc_url_raw(rest_url()),
			'nonce' => wp_create_nonce('wp_rest'),
		)
	);
}
add_action('admin_enqueue_scripts', 'cstspp_enqueue_scripts');

/**
 * Adds a menu item under Appearance for the Sort and Categorize Pages admin page.
 *
 * This function is hooked to the 'admin_menu' action.
 */
function cstspp_add_menu_item()
{
	// Add a submenu page under Appearance.
	// The submenu page is titled "Sort And Categorize Pages".
	// The capability required to access this page is 'manage_options'.
	// The menu slug is 'cstspp-sort-pages'.
	// The function to display the admin page is 'cstspp_display_admin_page'.
	add_submenu_page(
		'themes.php', // The parent menu slug.
		'Sort And Categorize Pages', // The page title.
		'Sort And Categorize Pages', // The menu title.
		'manage_options', // The required capability.
		'cstspp-sort-pages', // The menu slug.
		'cstspp_display_admin_page' // The function to display the admin page.
	);
}
add_action('admin_menu', 'cstspp_add_menu_item');

/**
 * Display admin page for sorting and categorizing pages.
 *
 * This function generates the admin page for sorting and categorizing pages.
 * It displays a table of all pages with their current order and category.
 * The user can drag and drop the pages to change their order.
 * The user can also select a category for each page.
 *
 * @return void
 * 
 * E:\Proj_Src\MyMMAPS.com\New Site\Plugins\sort-pages-plugin\Active From Site\cstspp-sort-pages\sort-pages-set-cat.php
 */
function cstspp_display_admin_page()
{
	// Get hierarchical categories options for the select dropdown
	$opts = cstspp_get_hierarchical_categories_options(array(0));

	// Start the admin page HTML
	?>
	<div class="wrap">
		<div class="cstspp-header">
			<div class="cstspp-shove-left">
				<div class="cstspp-intro">
					<img id="cstspp_logo"
						src="<?php echo esc_url(plugin_dir_url(__FILE__) . "/assets/cstspp-sort-pages-sm-icon.png"); ?>"
						alt=" <?php echo esc_html_e('Sort Pages, Set Categories', 'cstspp-sort-pages'); ?>"
						title="<?php echo esc_html_e('Sort Pages, Set Categories', 'cstspp-sort-pages'); ?>" />
					<h1 style="display: inline-block;vertical-align: top;">
						<?php echo esc_html_e('Set Page Order &amp; Assign Categories to Pages', 'cstspp-sort-pages'); ?>
					</h1>
					<h3><?php echo esc_html_e('Drag and drop page sorting, assign one or more categories to pages.', 'cstspp-sort-pages'); ?>
					</h3>
					<h4><?php echo esc_html_e('Be sure to click the Save Changes button when done.', 'cstspp-sort-pages'); ?>
					</h4>
					<div class="save-div">

						<!-- Add the Save Changes button -->
						<button id="cstspp-save-changes-1"
							class="button button-primary"><?php esc_html_e('Save Changes', 'cstspp-sort-pages'); ?></button>

					</div>
				</div>
			</div>
			<div class="cstspp-shove-right">
				<p>
					<?php echo esc_html_e('This is Donate-Ware. No payment required for full features, there is no ', 'cstspp-sort-pages'); ?>
					<i>
						<?php echo esc_html_e('"Pro"', 'cstspp-sort-pages'); ?>
					</i>
					<?php echo esc_html_e(' version with extra features, all are included!', 'cstspp-sort-pages'); ?>
					<br />
					<?php echo esc_html_e('Use on as many sites as you want, and use forever!', 'cstspp-sort-pages'); ?>
				</p>
				<p><?php echo esc_html_e('But, if you find it useful, and want to encourage me to make updates to this plugin, and write other plug-ins, it would be appreciated if you could consider making a donation.', 'cstspp-sort-pages'); ?><br />
					<br /><a class="cstspp-button" href="https://sloansweb.com/say-thanks/"
						target="_blank"><?php echo esc_html_e('Donate', 'cstspp-sort-pages'); ?></a>
				</p>
			</div>
		</div>
		<br />

		<!-- Display the table of pages -->
		<table id="cstspp-the-list" class="widefat fixed">
			<thead>
				<tr>
					<th><?php esc_html_e('Order', 'cstspp-sort-pages'); ?></th>
					<th><?php esc_html_e('Page Title', 'cstspp-sort-pages'); ?></th>
					<th><?php esc_html_e('Category', 'cstspp-sort-pages'); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php


				// Get all pages sorted by menu order
				$pages = get_pages(array('sort_column' => 'menu_order'));
				$order = 0;
				foreach ($pages as $page) {

					// Check if the page has an excerpt; if not, create one from the content
					if (!empty($page->post_excerpt)) {
						$excerpt = $page->post_excerpt;
					} else {
						// Create an excerpt from the content with a length of 55 words
						$excerpt = wp_trim_words($page->post_content, 55, '...');
					}
					$page_categories = wp_get_post_terms($page->ID, 'category', array('fields' => 'ids'));
					echo '<tr data-id="' . esc_attr($page->ID) . '">';
					echo '<td class="cstspp-order-number">' . esc_html($order) . '</td>';
					echo '<td class="cstspp-page-title">(ID: ' . esc_attr($page->ID) . ') <span style="font-size: 1.2em;font-weight:bold;">' . esc_html($page->post_title) . '</span><br />' . esc_html($excerpt) . '</td>';
					echo '<td>';
					echo '<select class="cstspp-category-selector" autocomplete="off" multiple="multiple" size="5">';
					echo "<option value='0'>" . esc_html_e('None Selected', 'cstspp-sort-pages') . "</option>";


					foreach ($opts as $opt) {
						$selected = in_array(esc_html($opt['id']), $page_categories) ? ' selected' : '';
						$indent = "├" . str_repeat('─', esc_html($opt['level']) * 4);
						echo '<option value="' . esc_attr($opt['id']) . '"' . esc_attr($selected) . '>' . esc_html($indent . $opt['name']) . '</option>';
					}
					echo '</select>';
					echo '</td>';
					echo '</tr>';
					$order++;
				}
				?>
			</tbody>
		</table>

		<!-- Add the Save Changes button -->
		<button id="cstspp-save-changes-2"
			class="button button-primary"><?php esc_html_e('Save Changes', 'cstspp-sort-pages'); ?></button>
	</div>
	<?php
}

/**
 * Get hierarchical categories options.
 *
 * This function retrieves all categories and organizes them in a hierarchical structure.
 * The resulting array contains the categories with their parent and child categories.
 *
 * @param array $selected_categories The array of selected category IDs.
 * @return array The hierarchical categories options.
 */
function cstspp_get_hierarchical_categories_options($selected_categories = array())
{
	// Get all categories
	$categories = get_categories(array(
		'hide_empty' => false, // Include empty categories
		'orderby' => 'name', // Order by name
		'order' => 'ASC' // Ascending order
	));

	// Build a nested array of categories by parent
	$categories_by_parent = array();
	foreach ($categories as $category) {
		$categories_by_parent[$category->parent][] = $category;
	}

	/**
	 * Recursive function to generate the hierarchical list.
	 *
	 * @param int $parent_id The parent category ID.
	 * @param array $categories_by_parent The nested array of categories by parent.
	 * @param int $level The level of the category.
	 * @param array $selected_categories The array of selected category IDs.
	 * @return array The options for the hierarchical list.
	 */
	function cstspp_build_category_options($parent_id, $categories_by_parent, $level = 0, $selected_categories = array())
	{
		$options = array();
		if (!isset($categories_by_parent[$parent_id])) {
			return $options;
		}
		foreach ($categories_by_parent[$parent_id] as $category) {
			$options[] = array(
				'id' => $category->term_id, // Category ID
				'name' => $category->name, // Category name
				'level' => $level, // Category level
				'selected' => in_array($category->term_id, $selected_categories) ? 1 : 0 // Selected status
			);
			$options = array_merge(
				$options,
				cstspp_build_category_options($category->term_id, $categories_by_parent, $level + 1, $selected_categories)
			);
		}
		return $options;
	}

	// Generate the hierarchical list starting from the root (parent_id = 0)
	$hierarchical_categories = cstspp_build_category_options(0, $categories_by_parent, 0, $selected_categories);
	return $hierarchical_categories;
}

// Assign category to page
function cstspp_assign_category_to_page($page_id, $category_ids)
{
	if (!is_array($category_ids) || empty($category_ids)) {
		return;
	}
	$cat_ids = $category_ids[$page_id];
	$result = wp_set_post_terms($page_id, $cat_ids, 'category');
	if (is_wp_error($result)) {
		error_log("cstspp_assign_category_to_page: Error setting page categories for page ID $page_id: " . $result->get_error_message());
	} else {
		//		error_log("cstspp_assign_category_to_page: Page categories set successfully for page ID $page_id: " . print_r($cat_ids, true));
	}

	return $result;
}

/**
 * Registers a custom taxonomy for pages.
 *
 * Registers the 'category' taxonomy for the 'page' post type.
 * This allows for the creation of hierarchical categories for pages.
 *
 * @return void
 */
function cstspp_register_page_categories()
{
	// Register the 'category' taxonomy
	register_taxonomy(
		'category',	// Taxonomy name
		'page',		// Object type (post type)
		[
			//			'label' => esc_html_e('Page - Categories', 'cstspp-sort-pages'),  // Display name for the taxonomy
			'rewrite' => array('slug' => 'page-category'),  // Custom slug for the taxonomy URL
			'hierarchical' => true,  // Enable hierarchical terms
		]
	);
}

add_action('init', 'cstspp_register_page_categories');

// Register REST API routes
function cstspp_register_rest_routes()
{
	register_rest_route('cstspp/v1', '/save-order-and-category', array(
		'methods' => 'POST',
		'callback' => 'cstspp_saveOrderAndCategory',
		'permission_callback' => function () {
			return current_user_can('edit_pages');
		},
	));
}
add_action('rest_api_init', 'cstspp_register_rest_routes');

// Save order and category via REST API
function cstspp_saveOrderAndCategory(WP_REST_Request $request)
{
	$params = $request->get_json_params();
	$order = $params['order'];
	$categories = $params['categories'];

	foreach ($order as $index => $pageId) {
		wp_update_post(array(
			'ID' => $pageId,
			'menu_order' => $index + 1,
		));

		if (isset($categories[$pageId])) {
			cstspp_assign_category_to_page($pageId, $categories);
		}
	}

	return new WP_REST_Response(array('status' => 'success'), 200);
}

?>