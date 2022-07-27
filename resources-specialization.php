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
     * Class that performs specialization of this plug-in.  This class will either build the entire plug-in framework
     * or bolt onto an already created framework from another SpeedSentry plug-in.
     */
    class ResourcesSpecialization extends Helpers {
        /**
         * Flag indicating if we should use the un-minified versions of our JavaScript and CSS in order to perform
         * debugging.
         */
        const DEBUG_JAVASCRIPT = false;

        /**
         * Table of plot parameters and required types.
         */
        const PLOT_PARAMETER_CONVERTERS = array(
            'value_type'      => 'integer',
            'start_timestamp' => 'double',
            'end_timestamp'   => 'double',
            'scale_factor'    => 'double',
            'title'           => 'string',
            'x_axis_label'    => 'string',
            'y_axis_label'    => 'string',
            'date_format'     => 'string',
            'title_font'      => 'string',
            'axis_title_font' => 'string',
            'axis_label_font' => 'string',
            'width'           => 'integer',
            'height'          => 'integer',
            'format'          => 'string',
        );

        /**
         * Table of storage units and scale factors.
         */
        const DISK_CONVERSIONS = array(
            'b' => 1.0,
            'kb' => 1.0E3,
            'mb' => 1.0E6,
            'gb' => 1.0E9,
            'tb' => 1.0E12,
            'kib' => 1024,
            'mib' => 1024 * 1024,
            'gib' => 1024 * 1024 * 1024,
            'tib' => 1024 * 1024 * 1024 * 1024
        );

        /**
         * Plug-in priority value.  Used to determine which plug-in renders key content.
         * A lower priority indicates a higher chance of being the key plug-in.
         */
        const PLUGIN_PRIORITY = 20;

        /**
         * Static method that is triggered when the plug-in is activated.
         *
         * \param $options The plug-in options instance.
         */
        public static function plugin_activated(Options $options) {}

        /**
         * Static method that is triggered when the plug-in is deactivated.
         *
         * \param $options The plug-in options instance.
         */
        public static function plugin_deactivated(Options $options) {}

        /**
         * Constructor
         *
         * \param $short_plugin_name A short version of the plug-in name to be used in the menus.
         *
         * \param $plugin_name       The user visible name for this plug-in.
         *
         * \param $plugin_slug       The slug used for the plug-in.  We use this slug as a prefix for slugs this class
         *                           may also require.
         *
         * \param $login_url         The URL to redirect to in order to login.
         *
         * \param $rest_api          The outbound REST API.
         *
         * \param $options           The plug-in options API.
         *
         * \param $signup_handler    The signup-redirect handler.
         */
        public function __construct(
                string        $short_plugin_name,
                string        $plugin_name,
                string        $plugin_slug,
                string        $login_url,
                RestApiV1     $rest_api,
                Options       $options,
                SignupHandler $signup_handler
            ) {
            $this->short_plugin_name = $short_plugin_name;
            $this->plugin_name = $plugin_name;
            $this->plugin_slug = $plugin_slug;
            $this->plugin_prefix = str_replace('-', '_', $plugin_slug);
            $this->login_url = $login_url;

            $this->rest_api = $rest_api;
            $this->options = $options;
            $this->signup_handler = $signup_handler;
    
            $this->admin_bar = new AdminBar(
                $options,
                $signup_handler,
                $rest_api
            );
            
            add_action('init', array($this, 'on_initialization'));
        }

        /**
         * Method that is triggered during initialization to bolt the plug-in settings UI into WordPress.
         */
        public function on_initialization() {
            /* Setup wp-cron */

            add_action('inesonic_speedsentry_check_resources', array($this, 'check_resources'));
            if (!wp_next_scheduled('inesonic_speedsentry_check_resources')) {
                $time = time() + rand(0, 86400);
                wp_schedule_event($time, 'twicedaily', 'inesonic_speedsentry_check_resources');
            }
            
            add_action(
                'inesonic-speedsentry-add-submenus',
                array($this, 'add_submenus'),
                self::PLUGIN_PRIORITY,
                2
            );
            
            add_action(
                'inesonic_speedsentry_status_panel_enqueue_scripts',
                array($this, 'enqueue_scripts'),
                self::PLUGIN_PRIORITY,
                2
            );
            add_action(
                'inesonic_speedsentry_status_panel_add_content',
                array($this, 'add_content'),
                self::PLUGIN_PRIORITY,
                2
            );
            
            add_action('wp_ajax_inesonic_speedsentry_resources_plot', array($this, 'resources_plot'));
        }

        /**
         * Method that is triggered to capture resource data.
         */
        public function check_resources() {
            $cpu_usage = $this->options->cpu_usage();
            $memory_usage = $this->options->memory_usage();
            $disk_usage = $this->options->disk_usage();

            if ($cpu_usage) {
                $this->report_cpu_usage();
            }

            if ($memory_usage) {
                $this->report_memory_usage();
            }

            if ($disk_usage) {
                $this->report_disk_usage();
            }
        }

        /**
         * Method that checks and reports CPU usage.
         */
        private function report_cpu_usage() {
            $cpu_loading = null;
            
            $fh = fopen('/proc/cpuinfo', 'r');
            if ($fh) {
                $number_cpus = 0;
                do {
                    $l = fgets($fh);
                    if ($l !== false && str_starts_with($l, 'processor')) {
                        ++$number_cpus;
                    }
                } while ($l !== false);

                fclose($fh);
            } else {
                $number_cpus = 0;
            }

            if ($number_cpus > 0) {
                $load_average = sys_getloadavg();

                if ($load_average !== false) {
                    $cpu_loading = $load_average[2] / $number_cpus;
                    $this->rest_api->resourceCreate($cpu_loading, RestApiV1::CPU_VALUE_TYPE);
                }
            }
        }
        
        /**
         * Method that checks and reports memory usage.
         */
        private function report_memory_usage() {
            $free_memory = null;
            
            $fh = fopen('/proc/meminfo', 'r');
            if ($fh) {
                do {
                    $l = fgets($fh);
                } while ($l !== false && !str_starts_with($l, 'MemFree:'));

                if ($l !== false) {
                    $sections = explode(':', $l);
                    if (count($sections) == 2) {
                        $free_space_data = explode(' ', trim($sections[1]));
                        if (count($free_space_data) == 2) {
                            $free_memory = floatval($free_space_data[0]);
                            $this->rest_api->resourceCreate($free_memory, RestApiV1::MEMORY_VALUE_TYPE);
                        }
                    }
                }

                fclose($fh);
            }
        }
        
        /**
         * Method that checks and reports memory usage.
         */
        private function report_disk_usage() {
            if (PHP_OS_FAMILY == 'Windows') {
                if (preg_match('/^[a-z]:.*/', __DIR__)) {
                    $disk_root = substr(__DIR__, 0, 2);
                } else {
                    $disk_root = 'c:';
                }
            } else {
                $disk_root = '/';
            }

            $disk_free_space = disk_free_space($disk_root);
            $disk_total_space = disk_total_space($disk_root);            
            if ($disk_free_space !== false && $disk_total_space !== false) {
                $this->rest_api->resourceCreate($disk_total_space - $disk_free_space, RestApiV1::STORAGE_VALUE_TYPE);

                $disk_usage_threshold_string = $this->options->disk_usage_threshold();
                $numeric_value = '';
                $units = '';
                $string_length = strlen($disk_usage_threshold_string);
                for ($i=0 ; $i<$string_length ; ++$i) {
                    $c = $disk_usage_threshold_string[$i];
                    if (ctype_digit($c)) {
                        $numeric_value .= $c;
                    } else {
                        $units .= $c;
                    }
                }
                
                $numeric_value = floatval($numeric_value);
                $units = strtolower($units);

                if ($units == '%') {
                    $threshold = $disk_total_space * $numeric_value / 100.0;
                } else if (array_key_exists($units, self::DISK_CONVERSIONS)) {
                    $threshold = $numeric_value * self::DISK_CONVERSIONS[$units];
                } else {
                    $threshold = $numeric_value;
                }

                if ($disk_free_space < $threshold) {
                    if ($disk_free_space > 2E12) {
                        $free_space_str = sprintf("%.1fTB", ($disk_free_space / 1.0E12));
                    } else if ($disk_free_space > 2E9) {
                        $free_space_str = sprintf("%.1fGB", ($disk_free_space / 1.0E9));
                    } else if ($disk_free_space > 2E6) {
                        $free_space_str = sprintf("%.1fMB", ($disk_free_space / 1.0E6));
                    } else if ($disk_free_space > 2E3) {
                        $free_space_str = sprintf("%.1fkB", ($disk_free_space / 1.0E3));
                    } else {
                        $free_space_str = sprintf("%.1f bytes", $disk_free_space);
                    }
                        
                    if ($units == '%') {
                        $msg = sprintf(
                            __(
                                "Less than %d%% disk space available (%s remaining)",
                                'inesonic-speedsentry-resources'
                            ),
                            $numeric_value,
                            $free_space_str
                        );
                    } else {
                        $msg = sprintf(
                            __(
                                "Low disk space, %s remaining",
                                'inesonic-speedsentry-resources'
                            ),
                            $free_space_str
                        );
                    }
                    
                    $this->rest_api->eventsCreate(RestApiV1::STORAGE_LIMIT_REACHED, $msg);
                }
            }
        }
        
        /**
         * Method that is triggered to add submenus.
         *
         * \param $main_plugin_page The main plugin page prefix.  Used to create submenus.
         *
         * \param $active_plugins   A list of currently active plugins.
         */
        public function add_submenus(string $main_plugin_page, array $active_plugins) {
            $this->main_plugin_page = $main_plugin_page;
            $this->active_plugins = $active_plugins;
            
            if (count($active_plugins) > 1 && $this->signup_handler->signup_completed()) {
                add_submenu_page(
                    $main_plugin_page,
                    __("Resources (Inesonic SpeedSentry)", 'inesonic-speedsentry-resources'),
                    __("Resources", 'inesonic-speedsentry'),
                    'manage_options',
                    $this->plugin_prefix,
                    array($this, 'build_resources_subpage')
                );
            }
        }

        /**
         * Method that is triggered to build the resources subpage.
         */
        public function build_resources_subpage() {
            $this->enqueue_script_helper();
            $this->add_content_helper();
        }                     
        
        /**
         * Method that adds scripts and styles to the admin page.
         *
         * \param $plugin_slug    The main plugin page prefix.  Used to create submenus.
         *
         * \param $active_plugins A list of currently active plugins.
         */
        public function enqueue_scripts(string $plugin_slug, array $active_plugins) {
            if ($plugin_slug == $this->plugin_slug) {
                $this->enqueue_script_helper();
            }
        }

        /**
         * Method that adds content to the status page.
         *
         * \param $plugin_slug    The slug of the primary plugin.
         *
         * \param $active_plugins The list of active plugins.
         */
        public function add_content(string $plugin_slug, array $active_plugins) {
            if ($plugin_slug == $this->plugin_slug) {
                $this->add_content_helper();
            }
        }

        /**
         * Method that enqueues content for the page.
         */
        public function enqueue_script_helper() {
            $cpu_usage = $this->options->cpu_usage();
            $memory_usage = $this->options->memory_usage();
            $disk_usage = $this->options->disk_usage();

            if ($disk_usage || $memory_usage || $cpu_usage) {
                $user_strings = array(
                    'show_all_monitors' => __("Show All Monitors", 'inesonic-speedsentry'),
                    'show_alerts_only' => __("Show Alerts Only", 'inesonic-speedsentry'),
                    'all_monitors_on' => __("All Monitors On {0}", 'inesonic-speedsentry'),
                    'all_monitors' => __("All Monitors", 'inesonic-speedsentry')
                );
    
                $default_plot_file = plugin_dir_url(__FILE__) . 'assets/img/default_plot.png';

                wp_enqueue_script('jquery');
                wp_enqueue_script('jquery-ui-datepicker');
                wp_enqueue_script(
                    'inesonic-speedsentry-resources-status-page',
                    self::resources_javascript_url('speedsentry-resources-status-page'),
                    array('jquery', 'jquery-ui-datepicker'),
                    null,
                    true
                );
                wp_localize_script(
                    'inesonic-speedsentry-resources-status-page',
                    'ajax_object',
                    array(
                        'ajax_url' => admin_url('admin-ajax.php'),
                        'user_strings' => $user_strings,
                        'cpu_usage' => $cpu_usage,
                        'disk_usage' => $disk_usage,
                        'memory_usage' => $memory_usage,
                        'cpu_value_type' => RestApiV1::CPU_VALUE_TYPE,
                        'storage_value_type' => RestApiV1::STORAGE_VALUE_TYPE,
                        'memory_value_type' => RestApiV1::MEMORY_VALUE_TYPE,
                        'default_plot_file' => $default_plot_file
                    )
                );
    
                wp_enqueue_style(
                    'inesonic-speedsentry-resources-styles',
                    self::resources_css_url('inesonic-speedsentry-resources-styles'),
                    array(),
                    null
                );
            }
        }
        
        /**
         * Method that adds content.
         */
        public function add_content_helper() {
            $cpu_usage = $this->options->cpu_usage();
            $memory_usage = $this->options->memory_usage();
            $disk_usage = $this->options->disk_usage();

            if ($disk_usage || $memory_usage || $cpu_usage) {
                echo '<div class="inesonic-speedsentry-resources-page-title">' .
                       '<h1 class="inesonic-speedsentry-resources-header">' .
                         __("Resources (Inesonic SpeedSentry)", 'inesonic-speedsentry-resources') .
                       '</h1>' .
                     '</div>' .
                     '<div class="inesonic-speedsentry-controls">' .
                       '<div class="inesonic-speedsentry-time-controls">' .
                         '<div class="inesonic-speedsentry-start-date-control">' .
                           '<span class="inesonic-speedsentry-control-name">' .
                             __("Start:", 'inesonic-speedsentry') .
                           '</span>' .
                           '<div class="inesonic-speedsentry-control-area">' .
                             '<span class="inesonic-speedsentry-start-control-span">' .
                               '<input type="text" class="inesonic-speedsentry-resources-start-control" ' .
                                      'id="inesonic-speedsentry-start-date"/>' .
                             '</span>' .
                             '<div class="inesonic-speedsentry-button-wrapper">' .
                               '<a id="inesonic-speedsentry-resources-start-clear-button" ' .
                                  'class="inesonic-speedsentry-button-anchor">' .
                                 __("Clear", 'inesonic-speedsentry') .
                               '</a>' .
                             '</div>' .
                           '</div>' .
                         '</div>' .
                         '<div class="inesonic-speedsentry-end-date-control">' .
                           '<span class="inesonic-speedsentry-control-name">' .
                             __("End", 'inesonic-speedsentry') .
                           '</span>' .
                           '<div class="inesonic-speedsentry-control-area">' .
                             '<span class="inesonic-speedsentry-end-control-span">' .
                               '<input type="text" class="inesonic-speedsentry-resources-end-control" ' .
                                      'id="inesonic-speedsentry-end-date"' .
                               '/>' .
                             '</span>' .
                             '<div class="inesonic-speedsentry-button-wrapper">' .
                               '<a id="inesonic-speedsentry-resources-end-clear-button" ' .
                                  'class="inesonic-speedsentry-button-anchor"' .
                               '>' .
                                 __("Clear", 'inesonic-speedsentry') .
                               '</a>' .
                             '</div>' .
                           '</div>' .
                         '</div>' .
                       '</div>' .
                     '</div>';
                
                if ($disk_usage) {
                    echo '<div class="inesonic-speedsentry-resources-disk-graph">' .
                           '<p class="inesonic-speedsentry-resources-disk-title">' .
                             __("Storage Used Over Time" , 'inesonic-speedsentry-resources') .
                           '</p>' .
                           '<div class="inesonic-speedsentry-resources-disk-inner" ' .
                                'id="inesonic-speedsentry-resources-disk-inner">' .
                             '<div class="inesonic-speedsentry-resources-disk-y-axis" ' .
                                  'id="inesonic-speedsentry-resources-disk-y-axis-label"' .
                             '>' .
                               '<p class="inesonic-speedsentry-resources-disk-y-axis-label">' .
                                 __("Storage (GB)", 'inesonic-speedsentry-resources') .
                               '</p>' .
                             '</div>' .
                             '<div class="inesonic-speedsentry-resources-disk-right">' .
                               '<div class="inesonic-speedsentry-resources-disk-image-wrapper">' .
                                 '<img class="inesonic-speedsentry-resources-image" ' .
                                      'id="inesonic-speedsentry-resources-disk-image" ' .
                                      'src="' . plugin_dir_url(__FILE__) . 'assets/img/default_plot.png"' .
                                 '/>' .
                               '</div>' .
                               '<div class="inesonic-speedsentry-resources-disk-x-axis">' .
                                 '<p class="inesonic-speedsentry-resources-disk-x-axis-label" ' .
                                    'id="inesonic-speedsentry-resources-disk-x-axis-label"' .
                                 '>' .
                                   __("Date/Time (UTC)", 'inesonic-speedsentry-resources') .
                                 '</p>' .
                               '</div>' .
                             '</div>' .
                           '</div>' .
                         '</div>';
                }

                if ($cpu_usage && $memory_usage) {
                    echo '<div class="inesonic-speedsentry-resources-two-plots">';
                } else if ($cpu_usage || $memory_usage) {
                    echo '<div class="inesonic-speedsentry-resources-one-plot">';
                }

                if ($cpu_usage) {
                    echo '<div class="inesonic-speedsentry-resources-cpu-graph">' .
                           '<p class="inesonic-speedsentry-resources-cpu-title">' .
                             __("Percent CPU Usage Over Time" , 'inesonic-speedsentry-resources') .
                           '</p>' .
                           '<div class="inesonic-speedsentry-resources-cpu-inner" ' .
                                'id="inesonic-speedsentry-resources-cpu-inner">' .
                             '<div class="inesonic-speedsentry-resources-cpu-y-axis" ' .
                                  'id="inesonic-speedsentry-resources-cpu-y-axis-label"' .
                             '>' .
                               '<p class="inesonic-speedsentry-resources-cpu-y-axis-label">' .
                                 __("Percent CPU Usage", 'inesonic-speedsentry-resources') .
                               '</p>' .
                             '</div>' .
                             '<div class="inesonic-speedsentry-resources-cpu-right">' .
                               '<div class="inesonic-speedsentry-resources-cpu-image-wrapper">' .
                                 '<img class="inesonic-speedsentry-resources-image" ' .
                                      'id="inesonic-speedsentry-resources-cpu-image" ' .
                                      'src="' . plugin_dir_url(__FILE__) . 'assets/img/default_plot.png"' .
                                 '/>' .
                               '</div>' .
                               '<div class="inesonic-speedsentry-resources-cpu-x-axis">' .
                                 '<p class="inesonic-speedsentry-resources-cpu-x-axis-label" ' .
                                    'id="inesonic-speedsentry-resources-cpu-x-axis-label"' .
                                 '>' .
                                   __("Date/Time (UTC)", 'inesonic-speedsentry-resources') .
                                 '</p>' .
                               '</div>' .
                             '</div>' .
                           '</div>' .
                         '</div>';
                }

                if ($memory_usage) {
                    echo '<div class="inesonic-speedsentry-resources-memory-graph">' .
                           '<p class="inesonic-speedsentry-resources-memory-title">' .
                             __("Memory Usage Over Time" , 'inesonic-speedsentry-resources') .
                           '</p>' .
                           '<div class="inesonic-speedsentry-resources-memory-inner" ' .
                                'id="inesonic-speedsentry-resources-memory-inner">' .
                             '<div class="inesonic-speedsentry-resources-memory-y-axis" ' .
                                  'id="inesonic-speedsentry-resources-memory-y-axis-label"' .
                             '>' .
                               '<p class="inesonic-speedsentry-resources-memory-y-axis-label">' .
                                 __("Memory (kB)", 'inesonic-speedsentry-resources') .
                               '</p>' .
                             '</div>' .
                             '<div class="inesonic-speedsentry-resources-memory-right">' .
                               '<div class="inesonic-speedsentry-resources-memory-image-wrapper">' .
                                 '<img class="inesonic-speedsentry-resources-image" ' .
                                      'id="inesonic-speedsentry-resources-memory-image" ' .
                                      'src="' . plugin_dir_url(__FILE__) . 'assets/img/default_plot.png"' .
                                 '/>' .
                               '</div>' .
                               '<div class="inesonic-speedsentry-resources-memory-x-axis">' .
                                 '<p class="inesonic-speedsentry-resources-memory-x-axis-label" ' .
                                    'id="inesonic-speedsentry-resources-memory-x-axis-label"' .
                                 '>' .
                                   __("Date/Time (UTC)", 'inesonic-speedsentry-resources') .
                                 '</p>' .
                               '</div>' .
                             '</div>' .
                           '</div>' .
                         '</div>';
                }
                
                if ($cpu_usage || $memory_usage) {
                    echo '</div>';
                }
            }
        }
        
        /**
         * Method that is triggered by AJAX to obtain a plot.
         */
        public function resources_plot() {
            foreach (self::PLOT_PARAMETER_CONVERTERS as $key => $type) {
                if (array_key_exists($key, $_POST)) {
                    $value = sanitize_text_field($_POST[$key]);
                    if (settype($value, $type)) {
                        $request[$key] = $value;
                    }
                }
            }

            $response = $this->rest_api->resourcePlot($request);
            if ($response !== null) {
                $content_type = $response['content_type'];
                if ($content_type == 'application/json') {
                    $response = array(
                        'status' => 'failed',
                        'response' => json_decode($response['body'])
                    );
                } else {
                    $response = array(
                        'status' => 'OK',
                        'image' => base64_encode($response['body']),
                        'content_type' => $content_type
                    );
                }
            } else {
                $response = array('status' => 'failed');
            }

            echo json_encode($response);
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
