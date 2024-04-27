<?php
/*
Plugin Name: WordPress Site Map Generator
Description: Streamlines the creation of visual site maps in SVG format.
Version: 1.0
Author: Hacker Heroes
*/

// Enqueue styles and scripts
function sitemap_generator_enqueue_scripts() {
    // Enqueue main stylesheet
    wp_enqueue_style('sitemap-generator-style', plugins_url('assets/css/style.css', __FILE__));

    // Enqueue response stylesheet
    wp_enqueue_style('response-styles', plugins_url('assets/css/response-styles.css', __FILE__));
}
add_action('admin_enqueue_scripts', 'sitemap_generator_enqueue_scripts');

// Add a menu item in the admin dashboard
function sitemap_generator_menu() {
    add_menu_page(
        'Site Map Generator',
        'Site Map Generator',
        'manage_options',
        'sitemap-generator',
        'sitemap_generator_page'
    );
}
add_action('admin_menu', 'sitemap_generator_menu');

// Callback function to display plugin settings page
function sitemap_generator_page() {
    ?>
    <div class="wrap">
        <h2>Site Map Generator</h2>
        <form method="post" action="">
            <label for="website_url">Website URL:</label><br>
            <input type="text" id="website_url" name="website_url" required><br><br>
            <input type="submit" name="generate_sitemap" value="Generate Site Map" class="button button-primary">
        </form>
        <?php if (isset($_POST['generate_sitemap'])) {
            handle_form_submission($_POST['website_url']);
        } ?>
        
    </div>
    <?php
}


// Function to retrieve menu data from the website URL
function retrieve_menu_data($website_url) {
    // Initialize an empty array to store the menu data
    $menu_data = array();

    // Fetch the HTML content of the provided website URL
    $response = wp_remote_get($website_url);

    // Check for errors
    if (is_wp_error($response)) {
        return new WP_Error('site_map_error', 'Error retrieving menu data: ' . $response->get_error_message());
    }

    // Check if the response is successful
    $response_code = wp_remote_retrieve_response_code($response);
    if ($response_code !== 200) {
        return new WP_Error('site_map_error', 'Error retrieving menu data: HTTP response code ' . $response_code);
    }

    // Get the body of the response
    $html_content = wp_remote_retrieve_body($response);

    // Use DOMDocument to parse the HTML content
    $dom = new DOMDocument();
    @$dom->loadHTML($html_content);

    // Get all <a> elements
    $link_elements = $dom->getElementsByTagName('a');

    // Loop through each <a> element
    foreach ($link_elements as $link_element) {
        // Get the link text and href attribute
        $link_text = $link_element->nodeValue;
        $link_url = $link_element->getAttribute('href');
        
        // Append the link text and URL to the menu data array
        $menu_data[] = array(
            'text' => $link_text,
            'url' => $link_url
        );
    }

    // Get post categories
    $post_categories = get_categories();
    foreach ($post_categories as $category) {
        $menu_data[] = array(
            'text' => $category->name,
            'url' => get_category_link($category->term_id)
        );
    }

    // Get product categories (assuming WooCommerce)
    $product_categories = get_terms('product_cat');
    if (!empty($product_categories) && !is_wp_error($product_categories)) {
        foreach ($product_categories as $category) {
            $menu_data[] = array(
                'text' => $category->name,
                'url' => get_term_link($category->term_id, 'product_cat')
            );
        }
    }

    // Get pages
    $pages = get_pages();
    foreach ($pages as $page) {
        $menu_data[] = array(
            'text' => $page->post_title,
            'url' => get_permalink($page->ID)
        );
    }

    return $menu_data;
}

// Function to render table SVG output for main menu and sub-menu
function render_table_svg_output($menu_data) {
    // Start main table markup
    $table_output = '<table>';

    // Loop through each main menu item
    foreach ($menu_data as $main_item) {
        // Add table row for main menu item
        $table_output .= '<tr>';
        $table_output .= '<td style="background-color: #007bff; color: #ffffff; padding: 5px;">' . $main_item['text'] . '</td>';
        $table_output .= '</tr>';

        // Check if there are sub-menu items
        if (isset($main_item['submenu']) && is_array($main_item['submenu']) && !empty($main_item['submenu'])) {
            // Start nested table for sub-menu items
            $table_output .= '<tr><td><table style="margin-left: 20px;">';

            // Loop through each sub-menu item
            foreach ($main_item['submenu'] as $sub_item) {
                // Add table row for sub-menu item
                $table_output .= '<tr>';
                $table_output .= '<td style="background-color: #007bff; color: #ffffff; padding: 5px;">' . $sub_item['text'] . '</td>';
                $table_output .= '</tr>';
            }

            // Close nested table for sub-menu items
            $table_output .= '</table></td></tr>';
        }
    }

    // End main table markup
    $table_output .= '</table>';

    return $table_output;
}

// Callback function to handle form submission
function handle_form_submission($website_url) {
    // Normalize the website URL
    $website_url = normalize_website_url($website_url);

    $menu_data = retrieve_menu_data($website_url);

    // Check if there's an error
    if (is_wp_error($menu_data)) {
        $error_message = $menu_data->get_error_message();
        echo '<div class="error"><p>' . $error_message . '</p></div>';
        return;
    }

    // Output the retrieved menu data as table SVG
    echo '<div id="response-container" class="response-container">';
    echo '<h3>Site Map:</h3>';
    
    // Render table SVG output
    $table_svg_output = render_table_svg_output($menu_data);
    echo $table_svg_output;

    echo '</div>';
}


// Function to normalize website URL
function normalize_website_url($url) {
    // Check if the URL starts with 'http://' or 'https://'
    if (strpos($url, 'http://') !== 0 && strpos($url, 'https://') !== 0) {
        // Add 'http://' prefix if it's missing
        $url = 'http://' . $url;
    }

    // Check if the URL starts with 'www.'
    if (strpos($url, 'www.') !== 0) {
        // Add 'www.' prefix if it's missing
        $url = str_replace('http://', 'http://www.', $url);
    }

    return $url;
}


// Add settings page
function sitemap_generator_settings_page() {
    add_options_page(
        'Site Map Generator Settings',
        'Site Map Generator Settings',
        'manage_options',
        'sitemap-generator-settings',
        'sitemap_generator_settings_page_content'
    );
}
add_action('admin_menu', 'sitemap_generator_settings_page');

// Register plugin settings
function sitemap_generator_register_settings() {
    register_setting('sitemap-generator-settings-group', 'sitemap_generator_options');
}
add_action('admin_init', 'sitemap_generator_register_settings');

// Settings page content
function sitemap_generator_settings_page_content() {
    ?>
    <div class="wrap">
        <h2>Site Map Generator Settings</h2>
        <form method="post" action="options.php">
            <?php settings_fields('sitemap-generator-settings-group'); ?>
            <?php $options = get_option('sitemap_generator_options'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Main Menu Color:</th>
                    <td><input type="text" name="sitemap_generator_options[main_menu_color]" value="<?php echo esc_attr($options['main_menu_color']); ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Submenu Color:</th>
                    <td><input type="text" name="sitemap_generator_options[submenu_color]" value="<?php echo esc_attr($options['submenu_color']); ?>" /></td>
                </tr>
                <!-- Add more customization options as needed -->
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// Function to retrieve user-customized options
function sitemap_generator_get_options() {
    $default_options = array(
        'main_menu_color' => '#007bff', // Default main menu color
        'submenu_color' => '#007bff', // Default submenu color
        // Add more default options here
    );
    $options = get_option('sitemap_generator_options', $default_options);
    return $options;
}

// Function to render SVG output with user-customized options
function render_svg_output_with_options($menu_data) {
    $options = sitemap_generator_get_options();

    // Start SVG markup
    $svg_output = '<svg width="800" height="600" xmlns="http://www.w3.org/2000/svg">';

    // Loop through each main menu item
    foreach ($menu_data as $main_item) {
        // Add SVG text element for main menu item with customized color
        $svg_output .= '<text x="10" y="20" fill="' . esc_attr($options['main_menu_color']) . '">' . $main_item['text'] . '</text>';

        // Check if there are sub-menu items
        if (isset($main_item['submenu']) && is_array($main_item['submenu']) && !empty($main_item['submenu'])) {
            // Loop through each sub-menu item
            $y_offset = 40; // Initial y-offset for sub-menu items
            foreach ($main_item['submenu'] as $sub_item) {
                // Add SVG text element for sub-menu item with customized color
                $svg_output .= '<text x="30" y="' . $y_offset . '" fill="' . esc_attr($options['submenu_color']) . '">' . $sub_item['text'] . '</text>';
                // Increment y-offset for the next sub-menu item
                $y_offset += 20;
            }
        }
    }

    // Close SVG markup
    $svg_output .= '</svg>';

    return $svg_output;
}
