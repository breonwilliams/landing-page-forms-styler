<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Admin functionality for Landing Page Forms Styler
 */
class LPFS_Admin
{
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
        add_action('admin_init',           [$this, 'handle_export']);
        add_action('admin_init',           [$this, 'handle_import']);
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
            __('Landing Page Forms', LPFS_Constants::TEXT_DOMAIN),
            __('Landing Page Forms', LPFS_Constants::TEXT_DOMAIN),
            LPFS_Constants::MENU_CAPABILITY,
            LPFS_Constants::MENU_SLUG,
            [$this, 'render_admin_page'],
            'dashicons-feedback',
            LPFS_Constants::MENU_POSITION
        );
    }

    /**
     * Register the wp_option to store presets
     */
    public function register_settings(): void
    {
        register_setting(
            'lpfs_styles_group',
            LPFS_Constants::OPTION_KEY,
            [
                'sanitize_callback' => [$this, 'sanitize_and_validate_presets']
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
            'Inter' => 'Inter',
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
     * Handle cache clearing
     * 
     * @return void
     */
    private function handle_cache_clear(): void
    {
        if (!isset($_GET['clear_cache'])) {
            return;
        }

        // Verify nonce
        if (!wp_verify_nonce($_GET['_wpnonce'] ?? '', 'lpfs_clear_cache')) {
            return;
        }

        // Verify permissions
        if (!current_user_can('manage_options')) {
            return;
        }

        // Clear all caches
        delete_transient(LPFS_Constants::STYLES_CACHE_KEY);
        delete_transient(LPFS_Constants::FONTS_CACHE_KEY);
        
        // Delete the CSS file to force regeneration
        $css_generator = new LPFS_CSS_Generator();
        $css_generator->delete_css_file();
        
        // Regenerate CSS file with new timestamp
        $css_generator->generate_css_file();
        
        // Add timestamp to force browser cache refresh
        update_option(LPFS_Constants::CSS_FILE_KEY . '_bust', time());
        
        LPFS_Logger::info('Cache cleared manually by user');
        
        // Redirect with success message
        wp_redirect(add_query_arg(['cache_cleared' => '1'], remove_query_arg('clear_cache', wp_get_referer())));
        exit;
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
        $presets = get_option(LPFS_Constants::OPTION_KEY, []);

        if (isset($presets[$idx])) {
            $deleted_preset = $presets[$idx];
            unset($presets[$idx]);
            update_option(LPFS_Constants::OPTION_KEY, array_values($presets));
            
            LPFS_Logger::info('Style preset deleted', [
                'title' => $deleted_preset['title'] ?? 'Unknown',
                'class' => $deleted_preset['custom_class'] ?? 'Unknown'
            ]);
            
            // Clear the styles cache when presets are deleted
            delete_transient(LPFS_Constants::STYLES_CACHE_KEY);
            delete_transient(LPFS_Constants::FONTS_CACHE_KEY);
            // Regenerate CSS file
            $css_generator = new LPFS_CSS_Generator();
            $css_generator->generate_css_file();
            wp_redirect(add_query_arg('deleted', '1', admin_url('admin.php?page=lpfs-styles')));
            exit;
        }
    }

    /**
     * Handle preset duplication
     * 
     * @return void
     */
    private function handle_preset_duplication(): void
    {
        if (!isset($_GET['duplicate'])) {
            return;
        }

        // Verify user has proper permissions
        if (!current_user_can(LPFS_Constants::MENU_CAPABILITY)) {
            wp_die(__('You do not have sufficient permissions to access this page.', LPFS_Constants::TEXT_DOMAIN));
        }

        // Verify nonce
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'duplicate_preset')) {
            wp_die(__('Security check failed.', LPFS_Constants::TEXT_DOMAIN));
        }

        $idx = absint($_GET['duplicate']);
        $presets = get_option(LPFS_Constants::OPTION_KEY, []);

        if (isset($presets[$idx])) {
            $original = $presets[$idx];
            
            // Create duplicate with modified title and class
            $duplicate = $original;
            $duplicate['title'] = $original['title'] . ' - Copy';
            
            // Generate unique class name
            $base_class = $original['custom_class'] . '-copy';
            $class = $base_class;
            $counter = 1;
            
            // Check for existing classes
            $existing_classes = array_column($presets, 'custom_class');
            while (in_array($class, $existing_classes)) {
                $class = $base_class . '-' . $counter;
                $counter++;
            }
            
            $duplicate['custom_class'] = $class;
            
            // Add to presets
            $presets[] = $duplicate;
            update_option(LPFS_Constants::OPTION_KEY, $presets);
            
            LPFS_Logger::info('Style preset duplicated', [
                'original_title' => $original['title'],
                'original_class' => $original['custom_class'],
                'duplicate_title' => $duplicate['title'],
                'duplicate_class' => $duplicate['custom_class']
            ]);
            
            // Clear caches and regenerate CSS
            delete_transient(LPFS_Constants::STYLES_CACHE_KEY);
            delete_transient(LPFS_Constants::FONTS_CACHE_KEY);
            $css_generator = new LPFS_CSS_Generator();
            $css_generator->generate_css_file();
            
            // Redirect to edit the duplicate
            $new_index = count($presets) - 1;
            wp_redirect(add_query_arg(['preset' => $new_index, 'duplicated' => '1'], admin_url('admin.php?page=lpfs-styles')));
            exit;
        }
    }

    /**
     * Render a collapsible section
     */
    private function render_section_start($section_id, $title) {
        ?>
        <div class="lpfs-section" data-section-id="<?php echo esc_attr($section_id); ?>">
            <div class="lpfs-section-header">
                <h3 class="lpfs-section-title"><?php echo esc_html($title); ?></h3>
                <span class="lpfs-section-toggle dashicons dashicons-arrow-down"></span>
            </div>
            <div class="lpfs-section-content">
                <table class="form-table">
        <?php
    }
    
    /**
     * Close a collapsible section
     */
    private function render_section_end() {
        ?>
                </table>
            </div>
        </div>
        <?php
    }

    /**
     * Render the admin page: list table, add/edit form & live preview
     */
    public function render_admin_page(): void
    {
        // Handle cache clearing
        $this->handle_cache_clear();
        
        // Handle deletion
        $this->handle_preset_deletion();
        
        // Handle duplication
        $this->handle_preset_duplication();

        // Load existing presets
        $presets = get_option(LPFS_Constants::OPTION_KEY, []);
        $edit_index = isset($_GET['preset']) ? absint($_GET['preset']) : null;
        $current = (isset($edit_index, $presets[$edit_index]))
            ? $presets[$edit_index]
            : self::DEFAULT_PRESET;
?>
        <div class="wrap">
            <h1><?php esc_html_e('Landing Page Form Styles', 'landing-page-forms-styler'); ?></h1>

            <!-- Import/Export Actions -->
            <div style="margin-bottom: 20px;">
                <a href="<?php echo esc_url(admin_url('admin.php?page=lpfs-styles&action=export')); ?>" class="button">
                    <?php esc_html_e('Export All Styles', LPFS_Constants::TEXT_DOMAIN); ?>
                </a>
                <button type="button" class="button" onclick="document.getElementById('lpfs-import-file').click();">
                    <?php esc_html_e('Import Styles', LPFS_Constants::TEXT_DOMAIN); ?>
                </button>
                <form id="lpfs-import-form" method="post" enctype="multipart/form-data" style="display: none;">
                    <?php wp_nonce_field('lpfs_import_styles', 'lpfs_import_nonce'); ?>
                    <input type="file" id="lpfs-import-file" name="lpfs_import_file" accept=".json" onchange="if(confirm('<?php esc_attr_e('Are you sure you want to import? This will merge with existing styles.', LPFS_Constants::TEXT_DOMAIN); ?>')) { document.getElementById('lpfs-import-form').submit(); }">
                </form>
            </div>

            <!-- 1. Presets List -->
            <h2><?php esc_html_e('All Styles', LPFS_Constants::TEXT_DOMAIN); ?></h2>
            <table class="widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Title', LPFS_Constants::TEXT_DOMAIN); ?></th>
                        <th><?php esc_html_e('CSS Class', LPFS_Constants::TEXT_DOMAIN); ?></th>
                        <th><?php esc_html_e('Preview', LPFS_Constants::TEXT_DOMAIN); ?></th>
                        <th><?php esc_html_e('Actions', LPFS_Constants::TEXT_DOMAIN); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($presets) : foreach ($presets as $i => $p) : ?>
                            <tr>
                                <td><?php echo esc_html($p['title']); ?></td>
                                <td><code><?php echo esc_html($p['custom_class']); ?></code></td>
                                <td>
                                    <?php $this->render_preset_preview($p); ?>
                                </td>
                                <td>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=lpfs-styles&preset=' . $i)); ?>">
                                        <?php esc_html_e('Edit', LPFS_Constants::TEXT_DOMAIN); ?>
                                    </a> |
                                    <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=lpfs-styles&duplicate=' . $i), 'duplicate_preset'); ?>">
                                        <?php esc_html_e('Duplicate', LPFS_Constants::TEXT_DOMAIN); ?>
                                    </a> |
                                    <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=lpfs-styles&delete=' . $i), 'delete_preset'); ?>"
                                        onclick="return confirm('<?php esc_attr_e('Delete this style?', LPFS_Constants::TEXT_DOMAIN); ?>');">
                                        <?php esc_html_e('Delete', LPFS_Constants::TEXT_DOMAIN); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach;
                    else: ?>
                        <tr>
                            <td colspan="4"><?php esc_html_e('No styles yet.', LPFS_Constants::TEXT_DOMAIN); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- 2. Add/Edit Form and Preview Side by Side -->
            <div class="lpfs-layout-container">
                <div class="lpfs-form-container">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: <?php echo LPFS_Constants::GOLDEN_SPACING['md']; ?>px; margin-bottom: <?php echo LPFS_Constants::GOLDEN_SPACING['lg']; ?>px;">
                        <h2 style="margin: 0;">
                            <?php
                            echo isset($edit_index)
                                ? esc_html__('Edit Style', 'landing-page-forms-styler')
                                : esc_html__('Add New Style', 'landing-page-forms-styler');
                            ?>
                        </h2>
                        <?php if (isset($edit_index)) : ?>
                            <div style="display: flex; gap: <?php echo LPFS_Constants::GOLDEN_SPACING['base']; ?>px;">
                                <a href="<?php echo esc_url(admin_url('admin.php?page=' . LPFS_Constants::MENU_SLUG)); ?>" 
                                   class="button button-secondary">
                                    <?php esc_html_e('Create New Form', 'landing-page-forms-styler'); ?>
                                </a>
                                <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=' . LPFS_Constants::MENU_SLUG . '&clear_cache=1'), 'lpfs_clear_cache')); ?>" 
                                   class="button button-secondary">
                                    <?php esc_html_e('Clear Cache', 'landing-page-forms-styler'); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
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
                        <input type="hidden" name="<?php echo esc_attr(LPFS_Constants::OPTION_KEY); ?>[<?php echo $index; ?>]" value="1">

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
                                    name="<?php echo LPFS_Constants::OPTION_KEY; ?>[<?php echo $i; ?>][title]"
                                    value="<?php echo esc_attr($p['title']); ?>">
                                <input type="hidden"
                                    name="<?php echo LPFS_Constants::OPTION_KEY; ?>[<?php echo $i; ?>][custom_class]"
                                    value="<?php echo esc_attr($p['custom_class']); ?>">
                                <?php
                                // All settings
                                if (! empty($p['settings']) && is_array($p['settings'])) {
                                    foreach ($p['settings'] as $key => $val) {
                                ?>
                                        <input type="hidden"
                                            name="<?php echo LPFS_Constants::OPTION_KEY; ?>[<?php echo $i; ?>][settings][<?php echo $key; ?>]"
                                            value="<?php echo esc_attr($val); ?>">
                        <?php
                                    }
                                }
                            }
                        }
                        ?>

                        <!-- Template Selection -->
                        <div class="lpfs-templates-section">
                            <div class="lpfs-templates-header">
                                <h3 class="lpfs-templates-title"><?php esc_html_e('Choose a Template', 'landing-page-forms-styler'); ?></h3>
                                <button type="button" class="button lpfs-apply-template-btn" id="lpfs-apply-template">
                                    <?php esc_html_e('Apply Selected Template', 'landing-page-forms-styler'); ?>
                                </button>
                            </div>
                            <div class="lpfs-templates-grid">
                                <?php foreach (LPFS_Constants::STYLE_TEMPLATES as $template_key => $template) : ?>
                                    <div class="lpfs-template-card" 
                                         data-template="<?php echo esc_attr($template_key); ?>"
                                         data-settings='<?php echo esc_attr(json_encode($template['settings'])); ?>'>
                                        <h4 class="lpfs-template-name"><?php echo esc_html($template['name']); ?></h4>
                                        <p class="lpfs-template-description"><?php echo esc_html($template['description']); ?></p>
                                        <div class="lpfs-template-preview">
                                            <div class="lpfs-template-color" 
                                                 style="background-color: <?php echo esc_attr($template['settings']['input_bg_color']); ?>"
                                                 title="Input Background"></div>
                                            <div class="lpfs-template-color" 
                                                 style="background-color: <?php echo esc_attr($template['settings']['input_border_color']); ?>"
                                                 title="Input Border"></div>
                                            <div class="lpfs-template-color" 
                                                 style="background-color: <?php echo esc_attr($template['settings']['button_bg_color']); ?>"
                                                 title="Button Background"></div>
                                            <div class="lpfs-template-color" 
                                                 style="background-color: <?php echo esc_attr($template['settings']['label_color']); ?>"
                                                 title="Label Color"></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <?php $this->render_section_start('general', __('General Settings', 'landing-page-forms-styler')); ?>
                            <!-- Title -->
                            <tr>
                                <th><label for="lpfs-title"><?php esc_html_e('Title', 'landing-page-forms-styler'); ?></label></th>
                                <td>
                                    <input
                                        required
                                        type="text"
                                        id="lpfs-title"
                                        class="regular-text"
                                        name="<?php echo LPFS_Constants::OPTION_KEY; ?>[<?php echo $index; ?>][title]"
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
                                        name="<?php echo LPFS_Constants::OPTION_KEY; ?>[<?php echo $index; ?>][custom_class]"
                                        value="<?php echo esc_attr($current['custom_class']); ?>">
                                </td>
                            </tr>
                        <?php $this->render_section_end(); ?>

                        <?php $this->render_section_start('input_styles', __('Input Field Styles', 'landing-page-forms-styler')); ?>
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
                                        name="<?php echo LPFS_Constants::OPTION_KEY; ?>[<?php echo $index; ?>][settings][input_border_radius]"
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
                                        name="<?php echo LPFS_Constants::OPTION_KEY; ?>[<?php echo $index; ?>][settings][input_border_width]"
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
                                        name="<?php echo LPFS_Constants::OPTION_KEY; ?>[<?php echo $index; ?>][settings][input_border_color]"
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
                                        name="<?php echo LPFS_Constants::OPTION_KEY; ?>[<?php echo $index; ?>][settings][input_text_color]"
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
                                        name="<?php echo LPFS_Constants::OPTION_KEY; ?>[<?php echo $index; ?>][settings][input_bg_color]"
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
                                        name="<?php echo LPFS_Constants::OPTION_KEY; ?>[<?php echo $index; ?>][settings][input_focus_border_color]"
                                        value="<?php echo esc_attr($current['settings']['input_focus_border_color'] ?? ''); ?>">
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
                                        name="<?php echo LPFS_Constants::OPTION_KEY; ?>[<?php echo $index; ?>][settings][button_border_color]"
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
                                        name="<?php echo LPFS_Constants::OPTION_KEY; ?>[<?php echo $index; ?>][settings][button_bg_color]"
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
                                        name="<?php echo LPFS_Constants::OPTION_KEY; ?>[<?php echo $index; ?>][settings][button_text_color]"
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
                                        name="<?php echo LPFS_Constants::OPTION_KEY; ?>[<?php echo $index; ?>][settings][button_hover_bg_color]"
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
                                        name="<?php echo LPFS_Constants::OPTION_KEY; ?>[<?php echo $index; ?>][settings][button_hover_text_color]"
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
                                        name="<?php echo LPFS_Constants::OPTION_KEY; ?>[<?php echo $index; ?>][settings][button_hover_border_color]"
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
                                        name="<?php echo LPFS_Constants::OPTION_KEY; ?>[<?php echo $index; ?>][settings][button_font_size]"
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
                                        name="<?php echo LPFS_Constants::OPTION_KEY; ?>[<?php echo $index; ?>][settings][button_font_weight]">
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
                                        name="<?php echo LPFS_Constants::OPTION_KEY; ?>[<?php echo $index; ?>][settings][button_line_height]"
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
                                        name="<?php echo LPFS_Constants::OPTION_KEY; ?>[<?php echo $index; ?>][settings][input_font_family]">
                                        <?php foreach ($this->get_google_fonts() as $value => $label) : ?>
                                            <option value="<?php echo esc_attr($value); ?>" <?php selected($current['settings']['input_font_family'] ?? '', $value); ?>>
                                                <?php echo esc_html($label); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <p class="description"><?php esc_html_e('Select a Google Font for input fields, textareas, and select elements.', 'landing-page-forms-styler'); ?></p>
                                </td>
                            </tr>
                        <?php $this->render_section_end(); ?>

                        <?php $this->render_section_start('label_styles', __('Label Styles', 'landing-page-forms-styler')); ?>
                            <!-- Label Color -->
                            <tr>
                                <th><?php esc_html_e('Label Color', 'landing-page-forms-styler'); ?></th>
                                <td>
                                    <input
                                        type="text"
                                        class="lpfs-color-field"
                                        data-var="label-color"
                                        name="<?php echo LPFS_Constants::OPTION_KEY; ?>[<?php echo $index; ?>][settings][label_color]"
                                        value="<?php echo esc_attr($current['settings']['label_color'] ?? ''); ?>">
                                </td>
                            </tr>

                            <!-- Label Font Family -->
                            <tr>
                                <th><?php esc_html_e('Label Font Family', 'landing-page-forms-styler'); ?></th>
                                <td>
                                    <select
                                        class="lpfs-select-field"
                                        data-var="label-font-family"
                                        name="<?php echo LPFS_Constants::OPTION_KEY; ?>[<?php echo $index; ?>][settings][label_font_family]">
                                        <?php foreach ($this->get_google_fonts() as $value => $label) : ?>
                                            <option value="<?php echo esc_attr($value); ?>" <?php selected($current['settings']['label_font_family'] ?? '', $value); ?>>
                                                <?php echo esc_html($label); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <p class="description"><?php esc_html_e('Select a Google Font for form labels.', 'landing-page-forms-styler'); ?></p>
                                </td>
                            </tr>
                        <?php $this->render_section_end(); ?>

                        <?php $this->render_section_start('button_styles', __('Button Styles', 'landing-page-forms-styler')); ?>
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
                                        name="<?php echo LPFS_Constants::OPTION_KEY; ?>[<?php echo $index; ?>][settings][button_border_radius]"
                                        value="<?php echo esc_attr($current['settings']['button_border_radius'] ?? ''); ?>">
                                </td>
                            </tr>

                            <!-- Button Font Family -->
                            <tr>
                                <th><?php esc_html_e('Button Font Family', 'landing-page-forms-styler'); ?></th>
                                <td>
                                    <select
                                        class="lpfs-select-field"
                                        data-var="button-font-family"
                                        name="<?php echo LPFS_Constants::OPTION_KEY; ?>[<?php echo $index; ?>][settings][button_font_family]">
                                        <?php foreach ($this->get_google_fonts() as $value => $label) : ?>
                                            <option value="<?php echo esc_attr($value); ?>" <?php selected($current['settings']['button_font_family'] ?? '', $value); ?>>
                                                <?php echo esc_html($label); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <p class="description"><?php esc_html_e('Select a Google Font for buttons.', 'landing-page-forms-styler'); ?></p>
                                </td>
                            </tr>
                        <?php $this->render_section_end(); ?>

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
 * Sanitize and validate the presets array with security checks
 * 
 * @param array $input The input array to sanitize
 * @return array The sanitized array
 */
public function sanitize_and_validate_presets($input)
{
    // First verify permissions and nonce
    $verified_input = $this->verify_nonce($input);
    if ($verified_input !== $input) {
        return $verified_input; // Return early if verification failed
    }
    
    return $this->sanitize_presets($input);
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
    $existing = get_option(LPFS_Constants::OPTION_KEY, []);
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

        // Process numeric fields with stricter validation
        foreach ($numeric_fields as $field) {
            if (isset($raw[$field]) && is_numeric($raw[$field])) {
                $value = intval($raw[$field]);
                // Set reasonable limits for each field
                switch ($field) {
                    case 'input_border_radius':
                    case 'button_border_radius':
                        // Border radius
                        $s[$field] = max(LPFS_Constants::BORDER_RADIUS_MIN, min(LPFS_Constants::BORDER_RADIUS_MAX, $value));
                        break;
                    case 'input_border_width':
                        // Border width
                        $s[$field] = max(LPFS_Constants::BORDER_WIDTH_MIN, min(LPFS_Constants::BORDER_WIDTH_MAX, $value));
                        break;
                    case 'button_font_size':
                        // Font size
                        $s[$field] = max(LPFS_Constants::FONT_SIZE_MIN, min(LPFS_Constants::FONT_SIZE_MAX, $value));
                        break;
                    default:
                        $s[$field] = max(0, $value);
                }
            }
        }

        // Process color fields with enhanced validation
        foreach ($color_fields as $field) {
            if (isset($raw[$field]) && !empty($raw[$field])) {
                $color = $this->sanitize_color_value($raw[$field]);
                if ($color !== false) {
                    $s[$field] = $color;
                }
            }
        }

        // Process font weight (string field)
        if (isset($raw['button_font_weight']) && !empty($raw['button_font_weight'])) {
            $allowed_weights = ['100', '200', '300', '400', '500', '600', '700', '800', '900'];
            if (in_array($raw['button_font_weight'], $allowed_weights)) {
                $s['button_font_weight'] = $raw['button_font_weight'];
            }
        }

        // Handle line height as decimal with validation
        if (isset($raw['button_line_height']) && is_numeric($raw['button_line_height'])) {
            $line_height = floatval($raw['button_line_height']);
            // Line height validation
            if ($line_height >= LPFS_Constants::LINE_HEIGHT_MIN && $line_height <= LPFS_Constants::LINE_HEIGHT_MAX) {
                $s['button_line_height'] = $line_height;
            }
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

    // Add success message and log
    if ($is_update) {
        LPFS_Logger::info('Style preset updated', [
            'preset_index' => $edit_index,
            'title' => $title ?? 'Unknown',
            'class' => $custom_class ?? 'Unknown'
        ]);
        add_settings_error(
            'lpfs_settings',
            'style_updated',
            __('Style updated successfully.', 'landing-page-forms-styler'),
            'success'
        );
    } else {
        LPFS_Logger::info('New style preset added', [
            'title' => $title ?? 'Unknown',
            'class' => $custom_class ?? 'Unknown'
        ]);
        add_settings_error(
            'lpfs_settings',
            'style_added',
            __('Style added successfully.', 'landing-page-forms-styler'),
            'success'
        );
    }

    // Clear the styles cache when presets are updated
    delete_transient(LPFS_Constants::STYLES_CACHE_KEY);
    delete_transient(LPFS_Constants::FONTS_CACHE_KEY);
    
    // Generate static CSS file
    $css_generator = new LPFS_CSS_Generator();
    $css_generator->generate_css_file();

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
        // Verify user has proper permissions
        if (!current_user_can('manage_options')) {
            add_settings_error(
                'lpfs_settings',
                'permission_error',
                __('You do not have sufficient permissions to perform this action.', 'landing-page-forms-styler'),
                'error'
            );
            return get_option(LPFS_Constants::OPTION_KEY, []);
        }

        // Verify the nonce
        if (!isset($_POST['lpfs_nonce']) || !wp_verify_nonce($_POST['lpfs_nonce'], 'lpfs_save_preset')) {
            // Nonce verification failed
            LPFS_Logger::warning('Nonce verification failed for preset save', [
                'user_id' => get_current_user_id(),
                'nonce_provided' => isset($_POST['lpfs_nonce'])
            ]);
            add_settings_error(
                'lpfs_settings',
                'nonce_error',
                __('Security check failed. Please try again.', 'landing-page-forms-styler'),
                'error'
            );
            return get_option(LPFS_Constants::OPTION_KEY, []);
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
                <p><?php esc_html_e('Style deleted successfully.', LPFS_Constants::TEXT_DOMAIN); ?></p>
            </div>
        <?php
        }
        
        // Display duplication success message
        if (isset($_GET['duplicated']) && $_GET['duplicated'] == '1') {
        ?>
            <div class="notice notice-success is-dismissible">
                <p><?php esc_html_e('Style duplicated successfully. You are now editing the duplicate.', LPFS_Constants::TEXT_DOMAIN); ?></p>
            </div>
        <?php
        }
        
        // Display cache cleared message
        if (isset($_GET['cache_cleared']) && $_GET['cache_cleared'] == '1') {
        ?>
            <div class="notice notice-success is-dismissible">
                <p><?php esc_html_e('Cache cleared successfully. CSS file regenerated with fresh styles.', LPFS_Constants::TEXT_DOMAIN); ?></p>
            </div>
        <?php
        }

        // Display settings errors (including any from our nonce verification)
        settings_errors('lpfs_settings');
    }

    /**
     * Sanitize color values (supports hex, rgb, rgba)
     * 
     * @param string $color The color value to sanitize
     * @return string|false The sanitized color or false if invalid
     */
    private function sanitize_color_value($color) {
        $color = trim($color);
        
        // Check for hex color (3 or 6 digits)
        if (preg_match('/^#([A-Fa-f0-9]{3}){1,2}$/', $color)) {
            return $color;
        }
        
        // Check for rgb/rgba
        if (preg_match('/^rgba?\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*(?:,\s*([01]?\.?\d*))?\s*\)$/i', $color, $matches)) {
            $r = intval($matches[1]);
            $g = intval($matches[2]);
            $b = intval($matches[3]);
            
            // Validate RGB values
            if ($r > 255 || $g > 255 || $b > 255) {
                return false;
            }
            
            if (isset($matches[4]) && $matches[4] !== '') {
                // RGBA
                $a = floatval($matches[4]);
                if ($a < 0 || $a > 1) {
                    return false;
                }
                return "rgba($r, $g, $b, $a)";
            } else {
                // RGB
                return "rgb($r, $g, $b)";
            }
        }
        
        // Check for color keywords
        $valid_keywords = ['transparent', 'inherit', 'initial', 'unset', 'currentcolor'];
        if (in_array(strtolower($color), $valid_keywords)) {
            return strtolower($color);
        }
        
        return false;
    }

    /**
     * Handle export of styles
     */
    public function handle_export() {
        // Check if export action is requested
        if (!isset($_GET['page']) || $_GET['page'] !== LPFS_Constants::MENU_SLUG) {
            return;
        }
        
        if (!isset($_GET['action']) || $_GET['action'] !== 'export') {
            return;
        }
        
        // Verify user permissions
        if (!current_user_can(LPFS_Constants::MENU_CAPABILITY)) {
            wp_die(__('You do not have sufficient permissions to export styles.', LPFS_Constants::TEXT_DOMAIN));
        }
        
        // Get all presets
        $presets = get_option(LPFS_Constants::OPTION_KEY, []);
        
        // Prepare export data
        $export_data = [
            'plugin' => 'landing-page-forms-styler',
            'version' => LPFS_Constants::PLUGIN_VERSION,
            'exported_at' => current_time('mysql'),
            'site_url' => get_site_url(),
            'presets' => $presets
        ];
        
        // Set headers for download
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="lpfs-styles-export-' . date('Y-m-d-His') . '.json"');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Output JSON
        echo wp_json_encode($export_data, JSON_PRETTY_PRINT);
        
        // Log export
        LPFS_Logger::info('Styles exported', [
            'preset_count' => count($presets)
        ]);
        
        exit;
    }

    /**
     * Handle import of styles
     */
    public function handle_import() {
        // Check if import is being submitted
        if (!isset($_FILES['lpfs_import_file'])) {
            return;
        }
        
        // Verify nonce
        if (!isset($_POST['lpfs_import_nonce']) || !wp_verify_nonce($_POST['lpfs_import_nonce'], 'lpfs_import_styles')) {
            add_settings_error(
                'lpfs_settings',
                'import_nonce_error',
                __('Security check failed. Please try again.', LPFS_Constants::TEXT_DOMAIN),
                'error'
            );
            return;
        }
        
        // Verify user permissions
        if (!current_user_can(LPFS_Constants::MENU_CAPABILITY)) {
            wp_die(__('You do not have sufficient permissions to import styles.', LPFS_Constants::TEXT_DOMAIN));
        }
        
        $file = $_FILES['lpfs_import_file'];
        
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            add_settings_error(
                'lpfs_settings',
                'import_upload_error',
                __('File upload failed. Please try again.', LPFS_Constants::TEXT_DOMAIN),
                'error'
            );
            return;
        }
        
        // Validate file type
        if ($file['type'] !== 'application/json' && !preg_match('/\.json$/i', $file['name'])) {
            add_settings_error(
                'lpfs_settings',
                'import_file_type',
                __('Please upload a valid JSON file.', LPFS_Constants::TEXT_DOMAIN),
                'error'
            );
            return;
        }
        
        // Read file contents
        $json_data = file_get_contents($file['tmp_name']);
        $import_data = json_decode($json_data, true);
        
        // Validate JSON
        if (json_last_error() !== JSON_ERROR_NONE) {
            add_settings_error(
                'lpfs_settings',
                'import_json_error',
                __('Invalid JSON file. Please check the file and try again.', LPFS_Constants::TEXT_DOMAIN),
                'error'
            );
            return;
        }
        
        // Validate structure
        if (!isset($import_data['plugin']) || $import_data['plugin'] !== 'landing-page-forms-styler') {
            add_settings_error(
                'lpfs_settings',
                'import_invalid_file',
                __('This does not appear to be a valid Landing Page Forms Styler export file.', LPFS_Constants::TEXT_DOMAIN),
                'error'
            );
            return;
        }
        
        if (!isset($import_data['presets']) || !is_array($import_data['presets'])) {
            add_settings_error(
                'lpfs_settings',
                'import_no_presets',
                __('No valid presets found in the import file.', LPFS_Constants::TEXT_DOMAIN),
                'error'
            );
            return;
        }
        
        // Get existing presets
        $existing_presets = get_option(LPFS_Constants::OPTION_KEY, []);
        $imported_count = 0;
        $updated_count = 0;
        
        // Process imported presets
        foreach ($import_data['presets'] as $preset) {
            if (!isset($preset['title']) || !isset($preset['custom_class'])) {
                continue;
            }
            
            // Check if preset with same class already exists
            $exists = false;
            foreach ($existing_presets as $key => $existing) {
                if ($existing['custom_class'] === $preset['custom_class']) {
                    $existing_presets[$key] = $preset;
                    $updated_count++;
                    $exists = true;
                    break;
                }
            }
            
            if (!$exists) {
                $existing_presets[] = $preset;
                $imported_count++;
            }
        }
        
        // Save merged presets
        update_option(LPFS_Constants::OPTION_KEY, $existing_presets);
        
        // Clear caches and regenerate CSS
        delete_transient(LPFS_Constants::STYLES_CACHE_KEY);
        delete_transient(LPFS_Constants::FONTS_CACHE_KEY);
        $css_generator = new LPFS_CSS_Generator();
        $css_generator->generate_css_file();
        
        // Log import
        LPFS_Logger::info('Styles imported', [
            'imported' => $imported_count,
            'updated' => $updated_count,
            'total' => count($existing_presets)
        ]);
        
        // Add success message
        $message = sprintf(
            __('Import successful! %d new styles imported, %d existing styles updated.', LPFS_Constants::TEXT_DOMAIN),
            $imported_count,
            $updated_count
        );
        add_settings_error(
            'lpfs_settings',
            'import_success',
            $message,
            'success'
        );
    }

    /**
     * Render a mini preview of the preset styles
     * 
     * @param array $preset The preset to preview
     */
    private function render_preset_preview($preset) {
        $settings = $preset['settings'] ?? [];
        
        // Create a preview with visual indicators
        $styles = [];
        
        // Button styles
        $button_styles = [];
        if (!empty($settings['button_bg_color'])) {
            $button_styles[] = 'background-color: ' . $settings['button_bg_color'];
        }
        if (!empty($settings['button_text_color'])) {
            $button_styles[] = 'color: ' . $settings['button_text_color'];
        }
        if (!empty($settings['button_border_radius'])) {
            $button_styles[] = 'border-radius: ' . $settings['button_border_radius'] . 'px';
        }
        
        // Input styles
        $input_styles = [];
        if (!empty($settings['input_border_color'])) {
            $input_styles[] = 'border-color: ' . $settings['input_border_color'];
        }
        if (!empty($settings['input_border_radius'])) {
            $input_styles[] = 'border-radius: ' . $settings['input_border_radius'] . 'px';
        }
        if (!empty($settings['input_border_width'])) {
            $input_styles[] = 'border-width: ' . $settings['input_border_width'] . 'px';
        }
        
        ?>
        <div class="lpfs-preset-preview" style="display: inline-flex; gap: 10px; align-items: center;">
            <div style="<?php echo esc_attr(implode('; ', $input_styles)); ?>; border-style: solid; width: 60px; height: 20px; background: #fff;"></div>
            <div style="<?php echo esc_attr(implode('; ', $button_styles)); ?>; padding: 3px 10px; font-size: 11px;">Button</div>
            <?php if (!empty($settings['label_color'])) : ?>
                <div style="width: 16px; height: 16px; background-color: <?php echo esc_attr($settings['label_color']); ?>; border: 1px solid #ccc; border-radius: 2px;" title="<?php esc_attr_e('Label Color', LPFS_Constants::TEXT_DOMAIN); ?>"></div>
            <?php endif; ?>
        </div>
        <?php
    }
}
