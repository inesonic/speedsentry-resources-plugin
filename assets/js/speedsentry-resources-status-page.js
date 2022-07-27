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
 * \file status-page.js
 *
 * JavaScript module that manages the site status page.
 */

/***********************************************************************************************************************
 * Parameters:
 */

/**
 * The period to wait between plot refreshes.
 */
const REFRESH_PERIOD = 6 * 3600 * 1000;

/**
 * Window resize idle time before adjusting for window size changes.
 */
const RESIZE_UPDATE_DELAY = 500;

/***********************************************************************************************************************
 * Script Scope Globals:
 */

/**
 * Timer used to update the status page content at period intervals.
 */
let updateTimer = null;

/**
 * Timer used to update plots after a small delay.
 */
let resizeUpdateTimer = null;

/**
 * The last start timestamp entered by the user.  A value of null indicates no start timestamp.
 */
let startTimestamp = null;

/**
 * The last end timestamp entered by the user.  A value of null indicates no end timestamp.
 */
let endTimestamp = null;

/**
 * A dictionary of miscellaneous user strings.
 */
let userStrings = ajax_object.user_strings;

/**
 * Flag indicating if we should display CPU usage data.
 */
let cpuUsageEnabled = ajax_object.cpu_usage;

/**
 * Flag indicating if we should display disk usage data.
 */
let diskUsageEnabled = ajax_object.disk_usage;

/**
 * Flag indicting if we should display memory usage data.
 */
let memoryUsageEnabled = ajax_object.memory_usage;

/**
 * Value holding the CPU value type.
 */
let cpuValueType = ajax_object.cpu_value_type;

/**
 * Value holding the storage value type.
 */
let storageValueType = ajax_object.storage_value_type;

/**
 * Value holding the memory value type.
 */
let memoryValueType = ajax_object.memory_value_type;

/**
 * The default image to use if we can not obtain a plot.
 */
let defaultPlotFile = ajax_object.default_plot_file;

/**
 * Flag indicating if the last capabilities update was active or inactive.
 */
let lastWasActive = false;

/***********************************************************************************************************************
 * Functions:
 */

/**
 * Function that enables/disables elements by class name.
 *
 * \param className  The class name used to locate elements to be updated.
 *
 * \param nowVisible A flag holding true if elements of the class should be made visible.  A value of false will hide
 *                   the elements.
 */
function inesonicSpeedSentryEnableByClass(className, nowVisible) {
    if (nowVisible) {
        jQuery("." + className).attr("class", className + " inesonic-speedsentry-visible");
    } else {
        jQuery("." + className).attr("class", className + " inesonic-speedsentry-hidden");
    }
}

/**
 * Function that calculates plot dimensions and updates the plot.
 *
 * \param totalAreaDivision The ID of the division holding the plot and Y axis label.
 *
 * \param xAxisLabel        The X axis label.
 * 
 * \param yAxisLabel        The Y axis label.
 *
 * \param plotObject        The name of the plot object.
 *
 * \param valueType         The plot value type.  Used to identify the plot data.
 *
 * \param scaleFactor       Scale factor to be applied to the plot Y axis.
 */
function inesonicSpeedSentryResourcesUpdatePlot(
	    totalAreaDivision,
     	xAxisLabel,
	    yAxisLabel,
	    plotObject,
	    valueType,
	    scaleFactor
    ) {
    let totalWidth = jQuery(totalAreaDivision).width();
    let totalHeight = jQuery(window).height() / 4;
    let yAxisLabelWidth = jQuery(yAxisLabel).width();
    let xAxisLabelHeight = jQuery(xAxisLabel).height();

    let imageWidth = Math.min(2047, Math.round(totalWidth - yAxisLabelWidth));
    let imageHeight = Math.max(375, Math.min(1536, Math.round(totalHeight - xAxisLabelHeight)));

    request = {
		"action" : "inesonic_speedsentry_resources_plot",
		"value_type" : valueType,
		"scale_factor" : scaleFactor,
		"title" : "",
		"x_axis_label" : "",
		"y_axis_label" : "",
		"width" : imageWidth,
		"height" : imageHeight,
		"format" : "PNG"
	}

    jQuery.ajax(
        {
            type: "POST",
            url: ajax_object.ajax_url,
            data: request,
            dataType: "json",
            success: function(response) {
                let image = jQuery(plotObject);
                if (response["status"] == "OK") {
                    base64Image = response["image"]
                    image.attr(
                        "src",
                        "data:image/png;base64," + base64Image
                    );
                } else {
                    image.attr("src", defaultPlotFile);
                }

                let currentTimestamp = (new Date()).getTime();
				image.prop("class", "inesonic-speedsentry-resources-image dummy_class_" + currentTimestamp);
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.log("Could not retrieve plot: " + errorThrown);
            }
        }
    );
}

/**
 * Function that updates the CPU usage plot.
 */
function inesonicSpeedSentryResourcesUpdateCpuUsagePlot() {
	inesonicSpeedSentryResourcesUpdatePlot(
		"#inesonic-speedsentry-resources-cpu-inner",
		"#inesonic-speedsentry-resources-cpu-x-axis-label",
		"#inesonic-speedsentry-resources-cpu-y-axis-label",
		"#inesonic-speedsentry-resources-cpu-image",
		cpuValueType,
		100.0
	);
}

/**
 * Function that updates the disk usage plot.
 */
function inesonicSpeedSentryResourcesUpdateDiskUsagePlot() {
	inesonicSpeedSentryResourcesUpdatePlot(
		"#inesonic-speedsentry-resources-disk-inner",
		"#inesonic-speedsentry-resources-disk-x-axis-label",
		"#inesonic-speedsentry-resources-disk-y-axis-label",
		"#inesonic-speedsentry-resources-disk-image",
		storageValueType,
		1.0E-9
	);
}

/**
 * Function that updates the memory usage plot.
 */
function inesonicSpeedSentryResourcesUpdateMemoryUsagePlot() {
	inesonicSpeedSentryResourcesUpdatePlot(
		"#inesonic-speedsentry-resources-memory-inner",
		"#inesonic-speedsentry-resources-memory-x-axis-label",
		"#inesonic-speedsentry-resources-memory-y-axis-label",
		"#inesonic-speedsentry-resources-memory-image",
		memoryValueType,
		1.0
	);
}

/**
 * Function that is triggered to update the plots.
 */
function inesonicSpeedSentryResourcesUpdatePlots() {
	if (cpuUsageEnabled) {
		inesonicSpeedSentryResourcesUpdateCpuUsagePlot();
	}

	if (diskUsageEnabled) {
		inesonicSpeedSentryResourcesUpdateDiskUsagePlot();
	}

	if (memoryUsageEnabled) {
		inesonicSpeedSentryResourcesUpdateMemoryUsagePlot();
	}
}

/***********************************************************************************************************************
 * Main:
 */

jQuery(window).resize(
    function() {
        if (resizeUpdateTimer !== null) {
            clearTimeout(resizeUpdateTimer);
        }

        resizeUpdateTimer = setTimeout(function() { jQuery(this).trigger('windowResized'); }, RESIZE_UPDATE_DELAY);
    }
);

jQuery(document).ready(function($) {
    $("#inesonic-speedsentry-resources-start-date").datepicker();
    $("#inesonic-speedsentry-resources-end-date").datepicker();

    jQuery("#inesonic-speedsentry-capabilities").on(
            "inesonic-speedsentry-capabilities-changed",
            function(event, capabilities) {
		let isActive = false;
        if (capabilities !== null) {
			isActive = capabilities.connected && capabilities.customer_active;
		}

        inesonicSpeedSentryEnableByClass("inesonic-speedsentry-active", isActive);
		if (isActive != lastWasActive) {
			lastWasActive = isActive;
			inesonicSpeedSentryResourcesUpdatePlots();			
		}
	});
	
    $("#inesonic-speedsentry-resources-start-date").on("change paste", function() {
        let newValue = $(this).val();
        startTimestamp = moment(moment(newValue).tz(moment.tz.guess()).utc().format()).unix();
        inesonicSpeedSentryResourcesUpdatePlots();
    });

    $("#inesonic-speedsentry-resources-end-date").on("change paste", function() {
        let newValue = $(this).val();
        endTimestamp = moment(moment(newValue).tz(moment.tz.guess()).utc().format()).unix();
        inesonicSpeedSentryResourcesUpdatePlots();
    });

    $("#inesonic-speedsentry-resources-start-clear-button").click(function(event) {
        startTimestamp = null;
        $("#inesonic-speedsentry-resources-start-date").val("");
        inesonicSpeedSentryResourcesUpdatePlots();
    });

    $("#inesonic-speedsentry-resources-end-clear-button").click(function(event) {
        endTimestamp = null;
        $("#inesonic-speedsentry-resources-end-date").val("");
        inesonicSpeedSentryResourcesUpdatePlots();
    });

    jQuery(window).bind("windowResized", inesonicSpeedSentryResourcesUpdatePlots);
});
