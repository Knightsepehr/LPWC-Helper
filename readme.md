# LP WC Helper

LP WC Helper is a WordPress plugin that aids in the integration of LearnPress and Woocommerce. This plugin adds a custom field to the product add/edit page that allows searching for LearnPress courses and their details.

## Installation

1. Download the plugin zip folder and extract it.
2. Upload `lp-wc-helper` to the `/wp-content/plugins/` directory.
3. Activate the LP WC Helper plugin from the WordPress admin dashboard.

## Usage

After activating the plugin, a new input field will appear in the product add/edit page of the Woocommerce section (under the general product data tab). The field is used to search the LearnPress courses according to the selected criteria.

Once a query is submitted, a table will appear that displays the details of the LearnPress course that matches the search criteria. Additionally, the course's excerpt and description are also displayed.

### Search Options

The plugin provides the following search options:

- **Course Detail:** Enter the course name, slug, or ID.
- **Search By:** Choose the search criteria from the four available options:
	- Post Slug
	- Post Title
	- Post ID
	- Search

### Search Results Table

The search results table is displayed in a scrollable table format that includes the following columns:

- Course PID
- Name/Slug
- Title
- Status
- Comment Status
- Last Modified
- Author ID

## Plugin Information

The LP WC Helper plugin was written by MeowMeowKhan. The following are the details of the plugin:

|  Detail  | Description |
| -------  | ----------- |
| Plugin Name | LP WC Helper |
| Description | A plugin to aid in the integration of LearnPress and WooCommerce. |
| Version | 1.1 |
| Author | Sepehr Zekavat |
| Text Domain | lpwchelperr |
| Domain Path | /languages |

### Plugin Text Domain

The plugin uses the `load_plugin_textdomain()` function to load the `lpwchelperr` text domain. All plugin strings should be wrapped with the `__()` or `_e()` functions to make them translatable.

### Plugin Hooks

The plugin uses the following hooks:

- `plugins_loaded`: Loads the plugin text domain.
- `woocommerce_product_options_general_product_data`: Adds a custom field to the Woocommerce product add/edit page.
- `admin_footer`: Adds JavaScript for the custom button jQuery event.
- `wp_ajax_my_custom_action`: Handles the AJAX request.
- `admin_head`: Adds CSS styles to the admin login page.

### Plugin Functions

The LP WC Helper plugin includes the following functions:

#### `load_my_plugin_textdomain()`

This function loads the plugin text domain for localization.

#### `add_custom_field()`

This function adds a custom field to the general product data tab in the Woocommerce product add/edit page.

#### `add_custom_button()`

This function adds a custom button to the general product data tab in the Woocommerce product add/edit page.

#### `custom_button_script()`

This function adds JavaScript for the custom button jQuery event.

#### `handle_custom_action()`

This function handles the AJAX request sent by the custom button.

#### `add_custom_css()`

This function adds CSS styles to the admin login page, which are used to display the search results table in a scrollable table format.