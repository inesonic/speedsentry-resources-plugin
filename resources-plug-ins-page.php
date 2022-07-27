<?php
 /**********************************************************************************************************************
 * Copyright 2021, Inesonic, LLC
 *
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public
 * License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later
 * version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more
 * details.
 *
 * You should have received a copy of the GNU General Public License along with this program.  If not, see
 * <https://www.gnu.org/licenses/>.
 ***********************************************************************************************************************
 */

namespace Inesonic\SpeedSentry;

    /**
     * Class that adds plug-in specific additions to the Plugins page.
     */
    class ResourcesPlugInsPage extends PlugInsPage {
        /**
         * Constructor
         *
         * \param $plugin_basename       The base name for the plug-in.
         *
         * \param $plugin_name           The user visible name for this plug-in.
         *
         * \param $plugin_slug           The slug assigned to this plug-in.
         *
         * \param $speedsentry_site_name The user friendly name of the SpeedSentry site.
         *
         * \param $options               The options handler.
         *
         * \param $signup_handler        The signup-redirect handler.
         *
         * \param $is_primary_plugin     If true, then this plug-in is the primary plug-in.  If false, then this
         *                               plug-in is secondary.
         */
        public function __construct(
                string        $plugin_basename,
                string        $plugin_name,
                string        $plugin_slug,
                string        $speedsentry_site_name,
                Options       $options,
                SignupHandler $signup_handler,
                bool          $is_primary_plugin
            ) {
            parent::__construct(
                $plugin_basename,
                $plugin_name,
                $plugin_slug,
                $speedsentry_site_name,
                $signup_handler,
                $is_primary_plugin
            );

            $this->options = $options;
        }

        /**
         * Method that is called to perform additional initialization.
         */
        public function additional_initialization() {
            add_action(
                'wp_ajax_inesonic_speedsentry_resources_get_settings',
                array($this, 'get_settings')
            );
            add_action(
                'wp_ajax_inesonic_speedsentry_resources_set_settings',
                array($this, 'set_settings')
            );
        }

        /**
         * Method that can be overloaded to update additional links.
         *
         * \param $links             The links to be updated.
         *
         * \param $is_primary_plugin If true, then this plug-in is the primary plug-in.  If false, then this plugin is
         *                           not the primary plugin.
         *
         * \return Returns the updated links.
         */
        public function add_additional_plugin_page_links(array $links, bool $is_primary_plugin) {
            $settings = "<a href=\"###\" id=\"inesonic-speedsentry-resources-settings-link\">" .
                          __("Settings", 'inesonic-speedsentry-resources') .
                      "</a>";
            array_unshift($links, $settings);
            
            return $links;
        }
                  
        /**
         * Method that adds additional content to the plug-ins page for this plug-in.
         *
         * \param $plugin_file       The plugin file.
         *
         * \param $plugin_data       The plugin data.
         *
         * \param $status            The plugin status.
         *
         * \param $is_primary_plugin If true, then this plug-in is the primary plug-in.  If false, then this plug-in is
         *                           not the primary plug-in.
         */
        public function add_additional_configuration_fields(string $plugin_file, array $plugin_data, string $status) {
            echo '<tr id="inesonic-speedsentry-resources-configuration-area-row"
                      class="inesonic-speedsentry-resources-configuration-area-row inesonic-row-hidden">
                    <th></th>
                    <td class="inesonic-speedsentry-configuration-area-column" colspan="2">
                      <div class="inesonic-speedsentry-mc-field">';
            if (PHP_OS_FAMILY != 'Windows') {
                echo   '<label class="inesonic-speedsentry-mc-label">
                          <input type="checkbox" id="inesonic-speedsentry-resources-monitor-cpu-usage"/>' .
                          __("Monitor CPU usage", 'inesonic-speedsentry-resources') . '
                        </label>
                        <label class="inesonic-speedsentry-mc-label">
                          <input type="checkbox" id="inesonic-speedsentry-resources-monitor-memory-usage"/>' .
                          __("Monitor memory usage", 'inesonic-speedsentry-resources') . '
                        </label>';
            }

            echo       '<div class="inesonic-speedsentry-resources-disk-usage-settings">
                          <div class="inesonic-speedsentry-resources-disk-usage-settings-1">
                            <label class="inesonic-speedsentry-mc-label">
                              <input type="checkbox" id="inesonic-speedsentry-resources-monitor-disk-usage"/>' .
                              __("Monitor disk usage", 'inesonic-speedsentry-resources') . '
                            </label>
                          </div>
                          <div class="inesonic-speedsentry-resources-disk-usage-settings-2"
                               id="inesonic-speedsentry-resources-disk-usage-settings-2"
                          >
                            <label class="inesonic-speedsentry-mc-label">' .
                              __("Report when free space below ", 'inesonic-speedsentry-resources') . '
                              <input type="text"
                                     class="inesonic-speedsentry-resources-disk-usage-threshold"
                                     id="inesonic-speedsentry-resources-disk-usage-threshold"
                              />' .
                              __("(specify % or GB)", 'inesonic-speedsentry-resources') .'
                            </label>
                          </div>
                        </div>
                        <div class="inesonic-speedsentry-button-wrapper">
                          <a id="inesonic-speedsentry-resources-settings-submit-button" class="inesonic-speedsentry-button-anchor">' .
                            __("Submit", 'inesonic-speedsentry') . '
                          </a>
                        </div>
                      </div>
                      <div class="inesonic-speedsentry-mc-documentation-wrapper">
                        <a href="https://speed-sentry.com/connecting-wordpress-manual/"
                           class="inesonic-speedsentry-mc-documentation-anchor"
                           target="_blank">' .
                          __("Click here for instructions", 'inesonic-speedsentry') . '
                        </a>
                      </div>
                    </td>
                  </tr>';

            wp_enqueue_script('jquery');
            wp_enqueue_script(
                'inesonic-speedsentry-resources-plugins-page',
                self::resources_javascript_url('speedsentry-resources-plugins-page'),
                array('jquery'),
                null,
                true
            );
            wp_localize_script(
                'inesonic-speedsentry-resources-plugins-page',
                'ajax_object',
                array('ajax_url' => admin_url('admin-ajax.php'))
            );
            wp_enqueue_style(
                'inesonic-speedsentry-styles',
                self::css_url('inesonic-speedsentry-styles', false),
                array(),
                null
            );
            wp_enqueue_style(
                'inesonic-speedsentry-resources-styles',
                self::resources_css_url('inesonic-speedsentry-resources-styles'),
                array(),
                null
            );                            
        }

        /**
         * Method that obtains the current plug-in settings.
         */
        public function get_settings() {
            if (current_user_can('activate_plugins')) {
                $cpu_usage = $this->options->cpu_usage();
                $memory_usage = $this->options->memory_usage();
                $disk_usage = $this->options->disk_usage();
                $disk_usage_threshold = $this->options->disk_usage_threshold();

                $result = array(
                    'status' => 'OK',
                    'cpu_usage' => $cpu_usage,
                    'memory_usage' => $memory_usage,
                    'disk_usage' => $disk_usage,
                    'disk_usage_threshold' => $disk_usage_threshold
                );

                echo json_encode($result);
            }

            wp_die();
        }

        /**
         * Method that updates the current plug-in settings.
         */
        public function set_settings() {
            if (current_user_can('activate_plugins')             &&
                array_key_exists('cpu_usage', $_POST)            &&
                array_key_exists('memory_usage', $_POST)         &&
                array_key_exists('disk_usage', $_POST)           &&
                array_key_exists('disk_usage_threshold', $_POST)    ) {
                $cpu_usage = sanitize_text_field($_POST['cpu_usage']);
                $memory_usage = sanitize_text_field($_POST['memory_usage']);
                $disk_usage = sanitize_text_field($_POST['disk_usage']);
                $disk_usage_threshold = sanitize_text_field($_POST['disk_usage_threshold']);

                $this->options->set_cpu_usage($cpu_usage == 'true');
                $this->options->set_disk_usage($disk_usage == 'true');
                $this->options->set_memory_usage($memory_usage == 'true');
                $this->options->set_disk_usage_threshold($disk_usage_threshold);
                
                $result = array('status' => 'OK', 'post' => $_POST);
                echo json_encode($result);
            }
            
            wp_die();
        }

        /**
         * Method that updates the CPU usage settings.
         */
        public function update_cpu_usage_setting() {
            if (current_user_can('activate_plugins')) {
                if (array_key_exists('cpu_usage', $_POST)) {
                    $cpu_usage = sanitize_text_field($_POST['cpu_usage']);
                    if ($cpu_usage == 'true') {
                        $this->options->set_cpu_usage(true);
                        $result = array('status' => 'OK');
                    } else if ($cpu_usage == 'false') {
                        $this->options->set_cpu_usage(false);
                        $result = array('status' => 'OK');
                    } else {
                        $result = array('status' => 'failed');
                    }
                } else {
                    $result = array('status' => 'failed');
                }

                echo json_encode($result);
            }

            wp_die();
        }

        /**
         * Method that updates the disk usage settings.
         */
        public function update_disk_usage_setting() {
            if (current_user_can('activate_plugins')) {
                if (array_key_exists('disk_usage', $_POST)) {
                    $disk_usage = sanitize_text_field($_POST['disk_usage']);
                    if ($disk_usage == 'true') {
                        $this->options->set_disk_usage(true);
                        $result = array('status' => 'OK');
                    } else if ($disk_usage == 'false') {
                        $this->options->set_disk_usage(false);
                        $result = array('status' => 'OK');
                    } else {
                        $result = array('status' => 'failed');
                    }
                } else {
                    $result = array('status' => 'failed');
                }

                echo json_encode($result);
            }

            wp_die();
        }
        
        /**
         * Method that updates the disk usage threshold settings.
         */
        public function update_disk_usage_threshold_setting() {
            if (current_user_can('activate_plugins')) {
                if (array_key_exists('disk_usage_threshold', $_POST)) {
                    $disk_usage_threshold = sanitize_text_field($_POST['disk_usage_threshold']);
                    $this->options->set_disk_usage_threshold($disk_usage_threshold);
                    $result = array('status' => 'OK');
                } else {
                    $result = array('status' => 'failed');
                }

                echo json_encode($result);
            }

            wp_die();
        }

        /**
         * Static method that obtains the correct JavaScript URL based on a JavaScript module name.
         *
         * \param $module_name  The name of the JavaScript module to be fetched.
         *
         * \return Returns the requested JavaScript URL.
         */
        static public function resources_javascript_url(string $module_name) {
            $d = dirname(__FILE__);
            $u = plugin_dir_url(__FILE__);
    
            if (self::DEBUG_JAVASCRIPT) {
                $unminified_file = $d . '/assets/js/' . $module_name . '.js';
                if (file_exists($unminified_file)) {
                    $extension = '.js';
                }
                else {
                    $extension = '.min.js';
                }
            } else {
                $minified_file = $d . '/assets/js/' . $module_name . '.min.js';
                if (file_exists($minified_file)) {
                    $extension = '.min.js';
                }
                else {
                    $extension = '.js';
                }
            }
    
            return $u . 'assets/js/' . $module_name . $extension;
        }

        /**
         * Function that obtains the correct CSS URL based on a CSS module name.
         *
         * \param $module_name The name of the JavaScript module to be fetched.
         *
         * \return Returns the requested JavaScript URL.
         */
        static public function resources_css_url(string $module_name) {
            $d = dirname(__FILE__);
            $u = plugin_dir_url(__FILE__);
    
            if (self::DEBUG_JAVASCRIPT) {
                $extension = '.css';
            } else {
                $minified_file = $d . '/assets/css/' . $module_name . '.min.css';
                if (file_exists($minified_file)) {
                    $extension = '.min.css';
                }
                else {
                    $extension = '.css';
                }
            }
    
            return $u . 'assets/css/' . $module_name . $extension;
        }
    };
