<?php
/***********************************************************************************************************************
 * Copyright 2022, Inesonic, LLC
 *
 * This program is free software; you can redistribute it and/or modify it under the terms of the GNU Lesser General
 * Public License as published by the Free Software Foundation; either version 3 of the License, or (at your option)
 * any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU Lesser General Public License for
 * more details.
 *
 * You should have received a copy of the GNU Lesser General Public License along with this program; if not, write to
 * the Free Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 ***********************************************************************************************************************
 */

namespace Inesonic\SpeedSentry;

    /**
     * Trivial class that provides an API to plug-in specific options.
     */
    class ResourcesOptions extends Options {
        /**
         * Method that is triggered when the plug-in is uninstalled.
         */
        public function additional_plugin_uninstalled() {
            $this->delete_option('cpu_usage');
            $this->delete_option('disk_usage');
            $this->delete_option('disk_usage_threshold');
            $this->delete_option('memory_usage');
        }

        /**
         * Method you can use to determine if we should monitor CPU usage.
         *
         * \return Returns true if we should monitor CPU usage.  Returns false if we should not monitor CPU usage.
         */
        public function cpu_usage() {
            return $this->get_option('cpu_usage', true);
        }

        /**
         * Method you can use to indicate if we should enable or disable CPU usage checking.
         *
         * \param $now_monitor_cpu_usage If true, we should monitor CPU usage.  If false, we should not monitor CPU
         *                               usage.
         *
         * \return Returns true on success.  Returns false on error.
         */
        public function set_cpu_usage(bool $now_monitor_cpu_usage) {
            return $this->update_option('cpu_usage', $now_monitor_cpu_usage);
        }

        /**
         * Method you can use to determine if we should monitor disk usage.
         *
         * \return Returns true if we should monitor disk usage.  Returns false if we should not monitor disk usage.
         */
        public function disk_usage() {
            return $this->get_option('disk_usage', true);
        }

        /**
         * Method you can use to indicate if we should enable or disable disk usage checking.
         *
         * \param $now_monitor_disk_usage If true, we should monitor disk usage.  If false, we should not monitor disk
         *                                usage.
         *
         * \return Returns true on success.  Returns false on error.
         */
        public function set_disk_usage(bool $now_monitor_disk_usage) {
            return $this->update_option('disk_usage', $now_monitor_disk_usage);
        }

        /**
         * Method you can use to determine the disk usage threshold.
         *
         * \return Returns a string indicating the current disk usage threshold.
         */
        public function disk_usage_threshold() {
            return $this->get_option('disk_usage_threshold', '10%');
        }

        /**
         * Method you can use to change the current disk usage threshold.
         *
         * \param $new_disk_usage_threshold The new disk usage threshold to be used.
         *
         * \return Returns true on success.  Returns false on error.
         */
        public function set_disk_usage_threshold(string $new_disk_usage_threshold) {
            return $this->update_option('disk_usage_threshold', $new_disk_usage_threshold);
        }
        
        /**
         * Method you can use to determine if we should monitor memory usage.
         *
         * \return Returns true if we should monitor memory usage.  Returns false if we should not monitor memory
         *         usage.
         */
        public function memory_usage() {
            return $this->get_option('memory_usage', true);
        }

        /**
         * Method you can use to indicate if we should enable or disable memory usage checking.
         *
         * \param $now_monitor_memory_usage If true, we should monitor memory usage.  If false, we should not monitor
         *                                  memory usage.
         *
         * \return Returns true on success.  Returns false on error.
         */
        public function set_memory_usage(bool $now_monitor_memory_usage) {
            return $this->update_option('memory_usage', $now_monitor_memory_usage);
        }
    }
