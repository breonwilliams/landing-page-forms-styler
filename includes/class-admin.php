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

    /**
     * Constructor: hook into admin actions
     */
    public function __construct()
    {
        add_action('admin_menu',           [$this, 'register_admin_menu']);
        add_action('admin_init',           [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_filter('redirect_post_location', [$this, 'maintain_edit_context']);
    }

    /**
     * Add top-level menu for form styles
     */
    public function register_admin_menu()
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
    public function register_settings()
    {
        register_setting(
            'lpfs_styles_group',
            self::OPTION_KEY,
            ['sanitize_callback' => [$this, 'sanitize_presets']]
        );
    }

    /**
     * Enqueue admin CSS & JS on our plugin page
     */
    public function enqueue_assets($hook)
    {
        // Only load on our plugin’s admin page
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
     * Render the admin page: list table, add/edit form & live preview
     */
    public function render_admin_page()
    {
        // Handle deletion
        if (isset($_GET['delete'])) {
            $idx     = absint($_GET['delete']);
            $presets = get_option(self::OPTION_KEY, []);
            if (isset($presets[$idx])) {
                unset($presets[$idx]);
                update_option(self::OPTION_KEY, array_values($presets));
                wp_redirect(admin_url('admin.php?page=lpfs-styles'));
                exit;
            }
        }

        // Load existing presets
        $presets    = get_option(self::OPTION_KEY, []);
        $edit_index = isset($_GET['preset']) ? absint($_GET['preset']) : null;
        $current    = (isset($edit_index, $presets[$edit_index]))
            ? $presets[$edit_index]
            : ['title' => '', 'custom_class' => '', 'settings' => []];
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
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=lpfs-styles&delete=' . $i)); ?>"
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

            <!-- 2. Add/Edit Form -->
            <h2>
                <?php
                echo isset($edit_index)
                    ? esc_html__('Edit Style', 'landing-page-forms-styler')
                    : esc_html__('Add New Style', 'landing-page-forms-styler');
                ?>
            </h2>
            <form id="lpfs-form" method="post" action="options.php">
                <?php settings_fields('lpfs_styles_group'); ?>
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


                </table>

                <?php submit_button(); ?>
            </form>

            <!-- Live Preview -->
            <h2><?php esc_html_e('Live Preview', 'landing-page-forms-styler'); ?></h2>
            <div id="lpfs-preview"
                class="<?php echo esc_attr($current['custom_class']); ?>"
                style="padding:1rem;
            background:#f1f1f1;
            max-width:400px;
            border:1px solid #ccc;
            border-radius:.5rem;">

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
<?php
    }
    /**
     * Sanitize the presets array
     */
    public function sanitize_presets($input)
    {
        if (! is_array($input)) {
            return [];
        }
        $clean = [];
        foreach ($input as $idx => $preset) {
            $title        = sanitize_text_field($preset['title'] ?? '');
            $custom_class = sanitize_html_class($preset['custom_class'] ?? '');
            // skip any blank preset (must have both title AND custom class)
            if ('' === $title || '' === $custom_class) {
                continue;
            }
            $raw          = is_array($preset['settings']) ? $preset['settings'] : [];
            $s            = [];

            // Numeric fields
            if (isset($raw['input_border_radius']))    $s['input_border_radius']    = absint($raw['input_border_radius']);
            if (isset($raw['input_border_width']))     $s['input_border_width']     = absint($raw['input_border_width']);
            if (isset($raw['button_border_radius']))   $s['button_border_radius']   = absint($raw['button_border_radius']);

            // Color fields
            if (isset($raw['input_border_color']))       $s['input_border_color']       = sanitize_hex_color($raw['input_border_color']);
            if (isset($raw['label_color']))              $s['label_color']              = sanitize_hex_color($raw['label_color']);
            if (isset($raw['input_text_color']))         $s['input_text_color']         = sanitize_hex_color($raw['input_text_color']);
            if (isset($raw['input_bg_color']))           $s['input_bg_color']           = sanitize_hex_color($raw['input_bg_color']);
            if (isset($raw['input_focus_border_color'])) $s['input_focus_border_color'] = sanitize_hex_color($raw['input_focus_border_color']);
            if (isset($raw['button_bg_color']))          $s['button_bg_color']          = sanitize_hex_color($raw['button_bg_color']);
            if (isset($raw['button_border_color']))      $s['button_border_color']      = sanitize_hex_color($raw['button_border_color']);
            if (isset($raw['button_text_color']))        $s['button_text_color']        = sanitize_hex_color($raw['button_text_color']);
            if (isset($raw['button_hover_bg_color']))    $s['button_hover_bg_color']    = sanitize_hex_color($raw['button_hover_bg_color']);
            if (isset($raw['button_hover_text_color']))  $s['button_hover_text_color']  = sanitize_hex_color($raw['button_hover_text_color']);

            $clean[$idx] = [
                'title'        => $title,
                'custom_class' => $custom_class,
                'settings'     => $s,
            ];
        }
        // re-index to 0,1,2… so new entries append properly
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
}
