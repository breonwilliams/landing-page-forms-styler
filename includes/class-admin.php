<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Admin functionality for Landing Page Forms Styler
 */
class LPFS_Admin
{
    const OPTION_KEY = 'lp_forms_styles';
    // Default values used throughout the code
    const DEFAULT_PRESET = [
        'title' => '',
        'custom_class' => '',
        'settings' => []
    ];

    /**
     * Constructor: hook into admin actions
     */
    public function __construct()
    {
        add_action('admin_menu',           [$this, 'register_admin_menu']);
        add_action('admin_init',           [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_filter('redirect_post_location', [$this, 'maintain_edit_context']);
        add_action('admin_notices', [$this, 'display_admin_notices']);
    }

    /**
     * Add top-level menu for form styles
     */
    public function register_admin_menu(): void
    {
        add_menu_page(
            __('Landing Page Forms', 'landing-page-forms-styler'),
            __('Landing Page Forms', 'landing-page-forms-styler'),
            'manage_options',
            'lpfs-styles',
            [$this, 'render_admin_page'],
            'dashicons-feedback',
            60
        );
    }

    /**
     * Register the wp_option to store presets
     */
    public function register_settings(): void
    {
        register_setting(
            'lpfs_styles_group',
            self::OPTION_KEY,
            [
                'sanitize_callback' => [$this, 'sanitize_presets'],
                'pre_update_callback' => [$this, 'verify_nonce']
            ]
        );
    }

    /**
     * Enqueue admin CSS & JS on our plugin page
     */
    public function enqueue_assets(string $hook): void
    {
        // Only load on our plugin's admin page
        if ($hook !== 'toplevel_page_lpfs-styles') {
            return;
        }
        // WP Color Picker assets
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        // Our custom CSS & JS
        wp_enqueue_style('lpfs-admin-style', LPFS_PLUGIN_URL . 'assets/css/admin.css');
        wp_enqueue_script('lpfs-admin-script', LPFS_PLUGIN_URL . 'assets/js/admin.js', ['jquery', 'wp-color-picker'], false, true);
    }

    /**
     * Get list of popular Google Fonts
     * 
     * @return array Array of Google Fonts
     */
    private function get_google_fonts(): array
    {
        return [
            '' => __('Default', 'landing-page-forms-styler'),
            'Open Sans' => 'Open Sans',
            'Roboto' => 'Roboto',
            'Lato' => 'Lato',
            'Montserrat' => 'Montserrat',
            'Oswald' => 'Oswald',
            'Source Sans Pro' => 'Source Sans Pro',
            'Raleway' => 'Raleway',
            'Poppins' => 'Poppins',
            'Nunito' => 'Nunito',
            'Ubuntu' => 'Ubuntu',
            'Playfair Display' => 'Playfair Display',
            'Merriweather' => 'Merriweather',
            'PT Sans' => 'PT Sans',
            'Roboto Condensed' => 'Roboto Condensed',
            'Noto Sans' => 'Noto Sans',
            'Fira Sans' => 'Fira Sans',
            'Rubik' => 'Rubik',
            'Work Sans' => 'Work Sans',
            'Inter' => 'Inter',
            'Crimson Text' => 'Crimson Text',
            'Libre Baskerville' => 'Libre Baskerville',
            'Roboto Slab' => 'Roboto Slab',
            'Oxygen' => 'Oxygen',
            'Titillium Web' => 'Titillium Web'
        ];
    }

    /**
     * Handle preset deletion
     * 
     * @return void
     */
    private function handle_preset_deletion(): void
    {
        if (!isset($_GET['delete'])) {
            return;
        }

        // Verify user has proper permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'landing-page-forms-styler'));
        }

        // Verify nonce
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'delete_preset')) {
            wp_die(__('Security check failed.', 'landing-page-forms-styler'));
        }

        $idx = absint($_GET['delete']);
        $presets = get_option(self::OPTION_KEY, []);

        if (isset($presets[$idx])) {
            unset($presets[$idx]);
            update_option(self::OPTION_KEY, array_values($presets));
            wp_redirect(add_query_arg('deleted', '1', admin_url('admin.php?page=lpfs-styles')));
            exit;
        }
    }

    /**
     * Render the admin page: list table, add/edit form & live preview
     */
    public function render_admin_page(): void
    {
        // Handle deletion
        $this->handle_preset_deletion();

        // Load existing presets
        $presets = get_option(self::OPTION_KEY, []);
        $edit_index = isset($_GET['preset']) ? absint($_GET['preset']) : null;
        $current = (isset($edit_index, $presets[$edit_index]))
            ? $presets[$edit_index]
            : self::DEFAULT_PRESET;
?>
        <div class="wrap">
            <h1><?php esc_html_e('Landing Page Form Styles', 'landing-page-forms-styler'); ?></h1>

            <!-- 1. Presets List -->
            <h2><?php esc_html_e('All Styles', 'landing-page-forms-styler'); ?></h2>
            <table class="widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Title', 'landing-page-forms-styler'); ?></th>
                        <th><?php esc_html_e('CSS Class', 'landing-page-forms-styler'); ?></th>
                        <th><?php esc_html_e('Actions', 'landing-page-forms-styler'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($presets) : foreach ($presets as $i => $p) : ?>
                            <tr>
                                <td><?php echo esc_html($p['title']); ?></td>
                                <td><code><?php echo esc_html($p['custom_class']); ?></code></td>
                                <td>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=lpfs-styles&preset=' . $i)); ?>">
                                        <?php esc_html_e('Edit', 'landing-page-forms-styler'); ?>
                                    </a> |
                                    <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=lpfs-styles&delete=' . $i), 'delete_preset'); ?>"
                                        onclick="return confirm('<?php esc_attr_e('Delete this style?', 'landing-page-forms-styler'); ?>');">
                                        <?php esc_html_e('Delete', 'landing-page-forms-styler'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach;
                    else: ?>
                        <tr>
                            <td colspan="3"><?php esc_html_e('No styles yet.', 'landing-page-forms-styler'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- 2. Add/Edit Form and Preview Side by Side -->
            <div class="lpfs-layout-container">
                <div class="lpfs-form-container">
                    <h2>
                        <?php
                        echo isset($edit_index)
                            ? esc_html__('Edit Style', 'landing-page-forms-styler')
                            : esc_html__('Add New Style', 'landing-page-forms-styler');
                        ?>
                    </h2>
                    <form id="lpfs-form" method="post" action="options.php">
                        <?php settings_fields('lpfs_styles_group'); ?>
                        <?php wp_nonce_field('lpfs_save_preset', 'lpfs_nonce'); ?>
                        <?php
                        // Force WP to come back here (with ?preset=…)
                        printf(
                            '<input type="hidden" name="_wp_http_referer" value="%s" />',
                            esc_url_raw($_SERVER['REQUEST_URI'])
                        );
                        ?>

                        <?php $index = isset($edit_index) ? $edit_index : count($presets); ?>
                        <input type="hidden" name="lpfs_edit_index" value="<?php echo esc_attr($index); ?>">
                        <input type="hidden" name="<?php echo esc_attr(self::OPTION_KEY); ?>[<?php echo $index; ?>]" value="1">

                        <?php
                        // Preserve all other existing presets so they don't get wiped out
                        if (is_array($presets)) {
                            foreach ($presets as $i => $p) {
                                // skip the one we are editing / adding
                                if ($i === $index) {
                                    continue;
                                }
                                // Title & Class
                        ?>
                                <input type="hidden"
                                    name="<?php echo self::OPTION_KEY; ?>[<?php echo $i; ?>][title]"
                                    value="<?php echo esc_attr($p['title']); ?>">
                                <input type="hidden"
                                    name="<?php echo self::OPTION_KEY; ?>[<?php echo $i; ?>][custom_class]"
                                    value="<?php echo esc_attr($p['custom_class']); ?>">
                                <?php
                                // All settings
                                if (! empty($p['settings']) && is_array($p['settings'])) {
                                    foreach ($p['settings'] as $key => $val) {
                                ?>
                                        <input type="hidden"
                                            name="<?php echo self::OPTION_KEY; ?>[<?php echo $i; ?>][settings][<?php echo $key; ?>]"
                                            value="<?php echo esc_attr($val); ?>">
                        <?php
                                    }
                                }
                            }
                        }
                        ?>

                        <table class="form-table">

                            <!-- Title -->
                            <tr>
                                <th><label for="lpfs-title"><?php esc_html_e('Title', 'landing-page-forms-styler'); ?></label></th>
                                <td>
                                    <input
                                        required
                                        type="text"
                                        id="lpfs-title"
                                        class="regular-text"
                                        name="<?php echo self::OPTION_KEY; ?>[<?php echo $index; ?>][title]"
                                        value="<?php echo esc_attr($current['title']); ?>">
                                </td>
                            </tr>

                            <!-- Custom Class -->
                            <tr>
                                <th><label for="lpfs-class"><?php esc_html_e('Custom Class', 'landing-page-forms-styler'); ?></label></th>
                                <td>
                                    <input
                                        required
                                        type="text"
                                        id="lpfs-class"
                                        class="regular-text"
                                        name="<?php echo self::OPTION_KEY; ?>[<?php echo $index; ?>][custom_class]"
                                        value="<?php echo esc_attr($current['custom_class']); ?>">
                                </td>
                            </tr>

                            <!-- Field Border Radius -->
                            <tr>
                                <th><?php esc_html_e('Field Border Radius (px)', 'landing-page-forms-styler'); ?></th>
                                <td>
                                    <input
                                        type="number"
                                        min="0"
                                        class="lpfs-number-field"
                                        data-var="input-border-radius"
                                        data-unit="px"
                                        name="<?php echo self::OPTION_KEY; ?>[<?php echo $index; ?>][settings][input_border_radius]"
                                        value="<?php echo esc_attr($current['settings']['input_border_radius'] ?? ''); ?>">
                                </td>
                            </tr>

                            <!-- Field Border Width -->
                            <tr>
                                <th><?php esc_html_e('Field Border Width (px)', 'landing-page-forms-styler'); ?></th>
                                <td>
                                    <input
                                        type="number"
                                        min="0"
                                        class="lpfs-number-field"
                                        data-var="input-border-width"
                                        data-unit="px"
                                        name="<?php echo self::OPTION_KEY; ?>[<?php echo $index; ?>][settings][input_border_width]"
                                        value="<?php echo esc_attr($current['settings']['input_border_width'] ?? ''); ?>">
                                </td>
                            </tr>

                            <!-- Field Border Color -->
                            <tr>
                                <th><?php esc_html_e('Field Border Color', 'landing-page-forms-styler'); ?></th>
                                <td>
                                    <input
                                        type="text"
                                        class="lpfs-color-field"
                                        data-var="input-border-color"
                                        name="<?php echo self::OPTION_KEY; ?>[<?php echo $index; ?>][settings][input_border_color]"
                                        value="<?php echo esc_attr($current['settings']['input_border_color'] ?? ''); ?>">
                                </td>
                            </tr>

                            <!-- Input Text Color -->
                            <tr>
                                <th><?php esc_html_e('Input Text Color', 'landing-page-forms-styler'); ?></th>
                                <td>
                                    <input
                                        type="text"
                                        class="lpfs-color-field"
                                        data-var="input-text-color"
                                        name="<?php echo self::OPTION_KEY; ?>[<?php echo $index; ?>][settings][input_text_color]"
                                        value="<?php echo esc_attr($current['settings']['input_text_color'] ?? ''); ?>">
                                </td>
                            </tr>

                            <!-- Input Background Color -->
                            <tr>
                                <th><?php esc_html_e('Input Background Color', 'landing-page-forms-styler'); ?></th>
                                <td>
                                    <input
                                        type="text"
                                        class="lpfs-color-field"
                                        data-var="input-bg-color"
                                        name="<?php echo self::OPTION_KEY; ?>[<?php echo $index; ?>][settings][input_bg_color]"
                                        value="<?php echo esc_attr($current['settings']['input_bg_color'] ?? ''); ?>">
                                </td>
                            </tr>

                            <!-- Input Focus Border Color -->
                            <tr>
                                <th><?php esc_html_e('Input Focus Border Color', 'landing-page-forms-styler'); ?></th>
                                <td>
                                    <input
                                        type="text"
                                        class="lpfs-color-field"
                                        data-var="input-focus-border-color"
                                        name="<?php echo self::OPTION_KEY; ?>[<?php echo $index; ?>][settings][input_focus_border_color]"
                                        value="<?php echo esc_attr($current['settings']['input_focus_border_color'] ?? ''); ?>">
                                </td>
                            </tr>

                            <!-- Label Color -->
                            <tr>
                                <th><?php esc_html_e('Label Color', 'landing-page-forms-styler'); ?></th>
                                <td>
                                    <input
                                        type="text"
                                        class="lpfs-color-field"
                                        data-var="label-color"
                                        name="<?php echo self::OPTION_KEY; ?>[<?php echo $index; ?>][settings][label_color]"
                                        value="<?php echo esc_attr($current['settings']['label_color'] ?? ''); ?>">
                                </td>
                            </tr>

                            <!-- Button Border Radius -->
                            <tr>
                                <th><?php esc_html_e('Button Border Radius (px)', 'landing-page-forms-styler'); ?></th>
                                <td>
                                    <input
                                        type="number"
                                        min="0"
                                        class="lpfs-number-field"
                                        data-var="button-border-radius"
                                        data-unit="px"
                                        name="<?php echo self::OPTION_KEY; ?>[<?php echo $index; ?>][settings][button_border_radius]"
                                        value="<?php echo esc_attr($current['settings']['button_border_radius'] ?? ''); ?>">
                                </td>
                            </tr>

                            <!-- Button Border Color -->
                            <tr>
                                <th><?php esc_html_e('Button Border Color', 'landing-page-forms-styler'); ?></th>
                                <td>
                                    <input
                                        type="text"
                                        class="lpfs-color-field"
                                        data-var="button-border-color"
                                        name="<?php echo self::OPTION_KEY; ?>[<?php echo $index; ?>][settings][button_border_color]"
                                        value="<?php echo esc_attr($current['settings']['button_border_color'] ?? ''); ?>">
                                </td>
                            </tr>

                            <!-- Button Background Color -->
                            <tr>
                                <th><?php esc_html_e('Button Background Color', 'landing-page-forms-styler'); ?></th>
                                <td>
                                    <input
                                        type="text"
                                        class="lpfs-color-field"
                                        data-var="button-bg-color"
                                        name="<?php echo self::OPTION_KEY; ?>[<?php echo $index; ?>][settings][button_bg_color]"
                                        value="<?php echo esc_attr($current['settings']['button_bg_color'] ?? ''); ?>">
                                </td>
                            </tr>

                            <!-- Button Text Color -->
                            <tr>
                                <th><?php esc_html_e('Button Text Color', 'landing-page-forms-styler'); ?></th>
                                <td>
                                    <input
                                        type="text"
                                        class="lpfs-color-field"
                                        data-var="button-text-color"
                                        name="<?php echo self::OPTION_KEY; ?>[<?php echo $index; ?>][settings][button_text_color]"
                                        value="<?php echo esc_attr($current['settings']['button_text_color'] ?? ''); ?>">
                                </td>
                            </tr>

                            <!-- Button Hover Background Color -->
                            <tr>
                                <th><?php esc_html_e('Button Hover Background Color', 'landing-page-forms-styler'); ?></th>
                                <td>
                                    <input
                                        type="text"
                                        class="lpfs-color-field"
                                        data-var="button-hover-bg-color"
                                        name="<?php echo self::OPTION_KEY; ?>[<?php echo $index; ?>][settings][button_hover_bg_color]"
                                        value="<?php echo esc_attr($current['settings']['button_hover_bg_color'] ?? ''); ?>">
                                </td>
                            </tr>

                            <!-- Button Hover Text Color -->
                            <tr>
                                <th><?php esc_html_e('Button Hover Text Color', 'landing-page-forms-styler'); ?></th>
                                <td>
                                    <input
                                        type="text"
                                        class="lpfs-color-field"
                                        data-var="button-hover-text-color"
                                        name="<?php echo self::OPTION_KEY; ?>[<?php echo $index; ?>][settings][button_hover_text_color]"
                                        value="<?php echo esc_attr($current['settings']['button_hover_text_color'] ?? ''); ?>">
                                </td>
                            </tr>

                            <!-- Button Hover Border Color -->
                            <tr>
                                <th><?php esc_html_e('Button Hover Border Color', 'landing-page-forms-styler'); ?></th>
                                <td>
                                    <input
                                        type="text"
                                        class="lpfs-color-field"
                                        data-var="button-hover-border-color"
                                        name="<?php echo self::OPTION_KEY; ?>[<?php echo $index; ?>][settings][button_hover_border_color]"
                                        value="<?php echo esc_attr($current['settings']['button_hover_border_color'] ?? ''); ?>">
                                </td>
                            </tr>

                            <!-- Button Text Font Size -->
                            <tr>
                                <th><?php esc_html_e('Button Text Font Size (px)', 'landing-page-forms-styler'); ?></th>
                                <td>
                                    <input
                                        type="number"
                                        min="0"
                                        class="lpfs-number-field"
                                        data-var="button-font-size"
                                        data-unit="px"
                                        name="<?php echo self::OPTION_KEY; ?>[<?php echo $index; ?>][settings][button_font_size]"
                                        value="<?php echo esc_attr($current['settings']['button_font_size'] ?? ''); ?>">
                                </td>
                            </tr>

                            <!-- Button Text Font Weight -->
                            <tr>
                                <th><?php esc_html_e('Button Text Font Weight', 'landing-page-forms-styler'); ?></th>
                                <td>
                                    <select
                                        class="lpfs-select-field"
                                        data-var="button-font-weight"
                                        name="<?php echo self::OPTION_KEY; ?>[<?php echo $index; ?>][settings][button_font_weight]">
                                        <option value=""><?php esc_html_e('Default', 'landing-page-forms-styler'); ?></option>
                                        <option value="100" <?php selected($current['settings']['button_font_weight'] ?? '', '100'); ?>>100 (Thin)</option>
                                        <option value="200" <?php selected($current['settings']['button_font_weight'] ?? '', '200'); ?>>200 (Extra Light)</option>
                                        <option value="300" <?php selected($current['settings']['button_font_weight'] ?? '', '300'); ?>>300 (Light)</option>
                                        <option value="400" <?php selected($current['settings']['button_font_weight'] ?? '', '400'); ?>>400 (Normal)</option>
                                        <option value="500" <?php selected($current['settings']['button_font_weight'] ?? '', '500'); ?>>500 (Medium)</option>
                                        <option value="600" <?php selected($current['settings']['button_font_weight'] ?? '', '600'); ?>>600 (Semi Bold)</option>
                                        <option value="700" <?php selected($current['settings']['button_font_weight'] ?? '', '700'); ?>>700 (Bold)</option>
                                        <option value="800" <?php selected($current['settings']['button_font_weight'] ?? '', '800'); ?>>800 (Extra Bold)</option>
                                        <option value="900" <?php selected($current['settings']['button_font_weight'] ?? '', '900'); ?>>900 (Black)</option>
                                    </select>
                                </td>
                            </tr>

                            <!-- Button Text Line Height -->
                            <tr>
                                <th><?php esc_html_e('Button Text Line Height', 'landing-page-forms-styler'); ?></th>
                                <td>
                                    <input
                                        type="number"
                                        min="0"
                                        step="0.1"
                                        class="lpfs-number-field"
                                        data-var="button-line-height"
                                        data-unit=""
                                        name="<?php echo self::OPTION_KEY; ?>[<?php echo $index; ?>][settings][button_line_height]"
                                        value="<?php echo esc_attr($current['settings']['button_line_height'] ?? ''); ?>">
                                    <p class="description"><?php esc_html_e('Use values like 1.2, 1.5, etc. Leave empty for default.', 'landing-page-forms-styler'); ?></p>
                                </td>
                            </tr>

                            <!-- Input Font Family -->
                            <tr>
                                <th><?php esc_html_e('Input Font Family', 'landing-page-forms-styler'); ?></th>
                                <td>
                                    <select
                                        class="lpfs-select-field"
                                        data-var="input-font-family"
                                        name="<?php echo self::OPTION_KEY; ?>[<?php echo $index; ?>][settings][input_font_family]">
                                        <?php foreach ($this->get_google_fonts() as $value => $label) : ?>
                                            <option value="<?php echo esc_attr($value); ?>" <?php selected($current['settings']['input_font_family'] ?? '', $value); ?>>
                                                <?php echo esc_html($label); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <p class="description"><?php esc_html_e('Select a Google Font for input fields, textareas, and select elements.', 'landing-page-forms-styler'); ?></p>
                                </td>
                            </tr>

                            <!-- Label Font Family -->
                            <tr>
                                <th><?php esc_html_e('Label Font Family', 'landing-page-forms-styler'); ?></th>
                                <td>
                                    <select
                                        class="lpfs-select-field"
                                        data-var="label-font-family"
                                        name="<?php echo self::OPTION_KEY; ?>[<?php echo $index; ?>][settings][label_font_family]">
                                        <?php foreach ($this->get_google_fonts() as $value => $label) : ?>
                                            <option value="<?php echo esc_attr($value); ?>" <?php selected($current['settings']['label_font_family'] ?? '', $value); ?>>
                                                <?php echo esc_html($label); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <p class="description"><?php esc_html_e('Select a Google Font for form labels.', 'landing-page-forms-styler'); ?></p>
                                </td>
                            </tr>

                            <!-- Button Font Family -->
                            <tr>
                                <th><?php esc_html_e('Button Font Family', 'landing-page-forms-styler'); ?></th>
                                <td>
                                    <select
                                        class="lpfs-select-field"
                                        data-var="button-font-family"
                                        name="<?php echo self::OPTION_KEY; ?>[<?php echo $index; ?>][settings][button_font_family]">
                                        <?php foreach ($this->get_google_fonts() as $value => $label) : ?>
                                            <option value="<?php echo esc_attr($value); ?>" <?php selected($current['settings']['button_font_family'] ?? '', $value); ?>>
                                                <?php echo esc_html($label); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <p class="description"><?php esc_html_e('Select a Google Font for buttons.', 'landing-page-forms-styler'); ?></p>
                                </td>
                            </tr>
                        </table>

                        <?php submit_button(); ?>
                    </form>
                </div>

                <div class="lpfs-preview-container">
                    <h2><?php esc_html_e('Live Preview', 'landing-page-forms-styler'); ?></h2>
                    <div id="lpfs-preview"
                        class="<?php echo esc_attr($current['custom_class']); ?>"
                        style="max-width:100%;">

                        <form>
                            <div class="form-group">
                                <label><?php esc_html_e('Text Input', 'landing-page-forms-styler'); ?></label>
                                <input type="text" placeholder="">
                            </div>

                            <div class="form-group">
                                <label><?php esc_html_e('Email Input', 'landing-page-forms-styler'); ?></label>
                                <input type="email" placeholder="">
                            </div>

                            <div class="form-group">
                                <label><?php esc_html_e('Textarea', 'landing-page-forms-styler'); ?></label>
                                <textarea rows="3"></textarea>
                            </div>

                            <div class="form-group">
                                <label><?php esc_html_e('Select', 'landing-page-forms-styler'); ?></label>
                                <select>
                                    <option><?php esc_html_e('Option 1', 'landing-page-forms-styler'); ?></option>
                                    <option><?php esc_html_e('Option 2', 'landing-page-forms-styler'); ?></option>
                                </select>
                            </div>

                            <div class="form-group form-check">
                                <input type="checkbox" id="lpfs-check">
                                <label for="lpfs-check"><?php esc_html_e('Checkbox', 'landing-page-forms-styler'); ?></label>
                            </div>

                            <div class="form-actions">
                                <button type="submit"><?php esc_html_e('Submit', 'landing-page-forms-styler'); ?></button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
<?php
    }

/**
 * Sanitize the presets array
 * 
 * @param array $input The input array to sanitize
 * @return array The sanitized array
 */
public function sanitize_presets($input)
{
    if (!is_array($input)) {
        return [];
    }

    $clean = [];
    $numeric_fields = [
        'input_border_radius',
        'input_border_width',
        'button_border_radius',
        'button_font_size'
    ];

    $color_fields = [
        'input_border_color',
        'label_color',
        'input_text_color',
        'input_bg_color',
        'input_focus_border_color',
        'button_bg_color',
        'button_border_color',
        'button_text_color',
        'button_hover_bg_color',
        'button_hover_text_color',
        'button_hover_border_color'
    ];

    // Check if we're adding or updating
    $is_update = false;
    $edit_index = isset($_POST['lpfs_edit_index']) ? absint($_POST['lpfs_edit_index']) : null;
    $existing = get_option(self::OPTION_KEY, []);
    if ($edit_index !== null && isset($existing[$edit_index])) {
        $is_update = true;
    }

    foreach ($input as $idx => $preset) {
        $title = sanitize_text_field($preset['title'] ?? '');
        $custom_class = sanitize_html_class($preset['custom_class'] ?? '');

        // Skip any blank preset (must have both title AND custom class)
        if ('' === $title || '' === $custom_class) {
            continue;
        }

        $raw = is_array($preset['settings']) ? $preset['settings'] : [];
        $s = [];

        // Process numeric fields
        foreach ($numeric_fields as $field) {
            if (isset($raw[$field])) {
                $s[$field] = absint($raw[$field]);
            }
        }

        // Process color fields
        foreach ($color_fields as $field) {
            if (isset($raw[$field])) {
                $s[$field] = sanitize_hex_color($raw[$field]);
            }
        }

        // Process font weight (string field)
        if (isset($raw['button_font_weight']) && !empty($raw['button_font_weight'])) {
            $allowed_weights = ['100', '200', '300', '400', '500', '600', '700', '800', '900'];
            if (in_array($raw['button_font_weight'], $allowed_weights)) {
                $s['button_font_weight'] = $raw['button_font_weight'];
            }
        }

        // Handle line height as decimal
        if (isset($raw['button_line_height']) && !empty($raw['button_line_height'])) {
            $s['button_line_height'] = floatval($raw['button_line_height']);
        }

        // Process font family fields
        $font_fields = ['input_font_family', 'label_font_family', 'button_font_family'];
        $allowed_fonts = array_keys($this->get_google_fonts());
        
        foreach ($font_fields as $field) {
            if (isset($raw[$field]) && in_array($raw[$field], $allowed_fonts)) {
                $s[$field] = sanitize_text_field($raw[$field]);
            }
        }

        $clean[$idx] = [
            'title' => $title,
            'custom_class' => $custom_class,
            'settings' => $s,
        ];
    }

    // Add success message
    if ($is_update) {
        add_settings_error(
            'lpfs_settings',
            'style_updated',
            __('Style updated successfully.', 'landing-page-forms-styler'),
            'success'
        );
    } else {
        add_settings_error(
            'lpfs_settings',
            'style_added',
            __('Style added successfully.', 'landing-page-forms-styler'),
            'success'
        );
    }

    // Re-index to 0,1,2… so new entries append properly
    return array_values($clean);
}

    /**
     * Redirect back to the same preset after saving
     */
    public function maintain_edit_context($location)
    {
        // Only process our plugin's settings page
        if (
            strpos($location, 'options-general.php?page=lpfs') === false
            && strpos($location, 'admin.php?page=lpfs') === false
        ) {
            return $location;
        }

        // Check if we were editing a specific preset
        if (isset($_POST['lpfs_edit_index'])) {
            $edit_index = absint($_POST['lpfs_edit_index']);
            // Ensure we redirect back to the same preset
            $location = add_query_arg('preset', $edit_index, $location);
        }

        return $location;
    }

    /**
     * Verify nonce before saving options
     * 
     * @param array $input The input array
     * @return array The sanitized input array
     */
    public function verify_nonce($input)
    {
        // Verify the nonce
        if (!isset($_POST['lpfs_nonce']) || !wp_verify_nonce($_POST['lpfs_nonce'], 'lpfs_save_preset')) {
            // Nonce verification failed
            add_settings_error(
                'lpfs_settings',
                'nonce_error',
                __('Security check failed. Please try again.', 'landing-page-forms-styler'),
                'error'
            );
            return get_option(self::OPTION_KEY, []);
        }

        return $input;
    }

    /**
     * Display admin notices after operations
     */
    public function display_admin_notices()
    {
        // Only show on our admin page
        $screen = get_current_screen();
        if (!$screen || $screen->id !== 'toplevel_page_lpfs-styles') {
            return;
        }

        // Display delete success message
        if (isset($_GET['deleted']) && $_GET['deleted'] == '1') {
        ?>
            <div class="notice notice-success is-dismissible">
                <p><?php esc_html_e('Style deleted successfully.', 'landing-page-forms-styler'); ?></p>
            </div>
        <?php
        }

        // Display settings errors (including any from our nonce verification)
        settings_errors('lpfs_settings');
    }
}
