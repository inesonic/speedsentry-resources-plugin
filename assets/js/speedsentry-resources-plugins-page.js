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
 * JavaScript module that manages manual configuration via the WordPress Plug-Ins page.
 */

/***********************************************************************************************************************
 * Constants:
 */

/**
 * Constant used to check the disk usage threshold.
 */
const DISK_USAGE_THRESHOLD_RE = new RegExp("^[0-9]+(b|kb|mb|gb|tb|kib|mib|gib|tib|%)?$", "i");

/***********************************************************************************************************************
 * Functions:
 */

/**
 * Function that validates a disk usage threshold value.
 *
 * \param diskUsageThreshold The disk usage threshold value to be validated.
 *
 * \return Returns true if the value is acceptable.  Returns false if the value is not acceptable.
 */
function inesonicSpeedSentryResourcesCheckDiskUsageThreshold(diskUsageThreshold) {
  	return DISK_USAGE_THRESHOLD_RE.test(diskUsageThreshold);
}

/**
 * Function that validates the current user input.
 */
function inesonicSpeedSentryResourcesValidateEntry() {
	let diskUsageThresholdEdit = jQuery("#inesonic-speedsentry-resources-disk-usage-threshold");
	let diskUsageThresholdArea = jQuery("#inesonic-speedsentry-resources-disk-usage-settings-2");
    let diskUsage = jQuery("#inesonic-speedsentry-resources-monitor-disk-usage").prop("checked");
	let diskUsageThreshold = diskUsageThresholdEdit.val();
	
	diskUsageThresholdEdit.prop("disabled", !diskUsage);
	if (diskUsage) {
        diskUsageThresholdArea.removeClass("inesonic-speedsentry-anchor-disable");
        diskUsageThresholdArea.addClass("inesonic-speedsentry-anchor-enable");
	} else {
        diskUsageThresholdArea.addClass("inesonic-speedsentry-anchor-disable");
        diskUsageThresholdArea.removeClass("inesonic-speedsentry-anchor-enable");
	}
	
    let submitButton = jQuery("#inesonic-speedsentry-resources-settings-submit-button");
	if (!diskUsage || inesonicSpeedSentryResourcesCheckDiskUsageThreshold(diskUsageThreshold)) {
        submitButton.removeClass("inesonic-speedsentry-anchor-disable");
        submitButton.addClass("inesonic-speedsentry-anchor-enable");
	} else {
        submitButton.addClass("inesonic-speedsentry-anchor-disable");
        submitButton.removeClass("inesonic-speedsentry-anchor-enable");
	}
}

/**
 * Function that displays the manual configuration fields.
 */
function inesonicSpeedSentryResourcesSettingsToggle() {
    let areaRow = jQuery("#inesonic-speedsentry-resources-configuration-area-row");
    if (areaRow.hasClass("inesonic-row-hidden")) {
        areaRow.prop("class", "inesonic-speedsentry-resources-configuraon-area-row inesonic-row-visible");
    } else {
        areaRow.prop("class", "inesonic-speedsentry-resources-configuration-area-row inesonic-row-hidden");
    }
}

/**
 * Function that is triggered when the CPU usage checkbox is changed.
 */
function inesonicSpeedSentryResourcesCpuChangeToggled() {}

/**
 * Function that is triggered when the memory usage checkbox is changed.
 */
function inesonicSpeedSentryResourcesMemoryChangeToggled() {}

/**
 * Function that is triggered when the disk usage checkbox is changed.
 */
function inesonicSpeedSentryResourcesDiskChangeToggled() {
	inesonicSpeedSentryResourcesValidateEntry();
}

/**
 * Function that is triggered when the disk usage threshold is updated.
 */
function inesonicSpeedSentryResourcesDiskThresholdChanged() {
	inesonicSpeedSentryResourcesValidateEntry();
}

/**
 * Function that is triggered when the submit button is clicked.
 */
function inesonicSpeedSentryResourcesSettingsSubmit() {
    let cpuUsage = jQuery("#inesonic-speedsentry-resources-monitor-cpu-usage").prop("checked");
    let memoryUsage = jQuery("#inesonic-speedsentry-resources-monitor-memory-usage").prop("checked");
    let diskUsage = jQuery("#inesonic-speedsentry-resources-monitor-disk-usage").prop("checked");
	let diskUsageThreshold = jQuery("#inesonic-speedsentry-resources-disk-usage-threshold").val();
	
    jQuery.ajax(
        {
            type: "POST",
            url: ajax_object.ajax_url,
            data: {
                "action" : "inesonic_speedsentry_resources_set_settings",
				"cpu_usage" : cpuUsage,
				"memory_usage" : memoryUsage,
				"disk_usage" : diskUsage,
				"disk_usage_threshold" : diskUsageThreshold
            },
            dataType: "json",
            success: function(response) {
                if (response !== null || response.status == 'OK') {
					inesonicSpeedSentryResourcesSettingsToggle();
				} else {
                    alert("Update failed");
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.log("Could not update settings: " + errorThrown);
            }
        }
    );
}

/**
 * Function that updates the resource settings.
 *
 * \param cpuUsage           Flag indicating if we should monitor CPU usage.
 *
 * \param memoryUsage        Flag indicating if we should monitor memory usage.
 *
 * \param diskUsage          Flag indicating if we should monitor disk usage.
 *
 * \param diskUsageThreshold The threshold to show for disk usage.
 */
function inesonicSpeedSentryResourcesUpdateUi(cpuUsage, memoryUsage, diskUsage, diskUsageThreshold) {
	jQuery("#inesonic-speedsentry-resources-monitor-cpu-usage").prop("checked", cpuUsage);
	jQuery("#inesonic-speedsentry-resources-monitor-memory-usage").prop("checked", memoryUsage);
	jQuery("#inesonic-speedsentry-resources-monitor-disk-usage").prop("checked", diskUsage);
	jQuery("#inesonic-speedsentry-resources-disk-usage-threshold").val(diskUsageThreshold);
	
	inesonicSpeedSentryResourcesValidateEntry();
}

/**
 * Function that updates the currently displayed settings.
 */
function inesonicSpeedSentryResourcesUpdateSettings() {
    jQuery.ajax(
        {
            type: "POST",
            url: ajax_object.ajax_url,
            data: {
                "action" : "inesonic_speedsentry_resources_get_settings"
            },
            dataType: "json",
            success: function(response) {
                if (response !== null && response.status == 'OK') {
					let cpuUsage = response.cpu_usage;
					let memoryUsage = response.memory_usage;
					let diskUsage = response.disk_usage;
					let diskUsageThreshold = response.disk_usage_threshold;

					inesonicSpeedSentryResourcesUpdateUi(cpuUsage, memoryUsage, diskUsage, diskUsageThreshold);
                } else {
                    alert("Status failed");
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.log("Could not get settings: " + errorThrown);
            }
        }
    );
}

///**
// * Function that checks if an access code is valid.
// *
// * \param accessCode The access code to be checked.
// *
// * \return Returns true if the access code is valid, returns false if the access code is invalid.  Note that this
// *         method does not confirm that the access code will authenticate, only that the format is good.
// */
//function inesonicSpeedSentryIsValidAccessCode(accessCode) {
//    let isValid = false;
//
//    let commaIndex = accessCode.indexOf(",");
//    if (commaIndex > 0) {
//        let cid    = accessCode.substring(0, commaIndex);
//        let secret = accessCode.substring(commaIndex + 1);
//
//        isValid = (cid.match(/^[a-zA-Z0-9]{16}$/) && secret.match(/^[a-zA-Z0-9+/]{75}=$/));
//    }
//
//    return isValid;
//}
//
//
///**
// * Function that is triggered whenever an access code changes to validate its content.
// */
//function inesonicSpeedSentryValidateAccessCode() {
//    let inputField = jQuery("#inesonic-speedsentry-mc-input");
//    let submitButton = jQuery("#inesonic-speedsentry-mc-submit-button");
//
//    let accessCode = inputField.val();
//
//    if (inesonicSpeedSentryIsValidAccessCode(accessCode)) {
//        inputField.prop("class", "inesonic-speedsentry-mc-input inesonic-speedsentry-input-valid");
//        submitButton.removeClass("inesonic-speedsentry-anchor-disable");
//        submitButton.addClass("inesonic-speedsentry-anchor-enable");
//    } else {
//        inputField.prop("class", "inesonic-speedsentry-mc-input inesonic-speedsentry-input-invalid");
//        submitButton.addClass("inesonic-speedsentry-anchor-disable");
//        submitButton.removeClass("inesonic-speedsentry-anchor-enable");
//    }
//}
//
///**
// * Function that broadcasts changes to the customer capabilities.
// *
// * \param capabilities The capabilities dictionary to be broadcast.
// */
//function inesonicSpeedSentryBroadcastCapabilityChange(capabilities) {
//    // For this to work we must have an element somewhere with the id 'inesonic-speedsentry-capabilities' that everyone
//    // can listen to for events.  For now, this item is placed in the admin bar.
//    jQuery("#inesonic-speedsentry-capabilities").trigger("inesonic-speedsentry-capabilities-changed", [ capabilities ]);
//}
//
///**
// * Funciton that is triggered when the submit button is clicked.
// */
//function inesonicSpeedSentryAccessCodeSubmit() {
//    let inputField = jQuery("#inesonic-speedsentry-mc-input");
//    let submitButton = jQuery("#inesonic-speedsentry-mc-submit-button");
//
//    let accessCode = inputField.val();
//    if (inesonicSpeedSentryIsValidAccessCode(accessCode)) {
//        let commaIndex = accessCode.indexOf(",");
//        if (commaIndex > 0) {
//            let cid    = accessCode.substring(0, commaIndex);
//            let secret = accessCode.substring(commaIndex + 1);
//
//            inesonicSpeedSentryLastCapabilities = null;
//            jQuery.ajax(
//                {
//                    type: "POST",
//                    url: ajax_object.ajax_url,
//                    data: {
//                        "action" : "inesonic_speedsentry_access_codes",
//                        "cid" : cid,
//                        "secret" : secret
//                    },
//                    dataType: "json",
//                    success: function(response) {
//                        if (response !== null && response.status == 'OK') {
//                            jQuery.ajax(
//                                {
//                                    type: "POST",
//                                    url: ajax_object.ajax_url,
//                                    data: { "action" : "inesonic_speedsentry_get_capabilities" },
//                                    dataType: "json",
//                                    success: function(response) {
//                                        if (response != null && response.status == 'OK') {
//                                            let capabilities = response.capabilities;
//                                            inesonicSpeedSentryLastCapabilities = capabilities;
//                                            inesonicSpeedSentryBroadcastCapabilityChange(capabilities);
//                                            if (capabilities !== null) {
//						window.location.reload();
//                                            }
//                                        }
//                                    },
//                                    error: function(jqXHR, textStatus, errorThrown) {
//                                        inesonicSpeedSentryBroadcastCapabilityChange(null);
//                                        console.log("Could not determine status: " + errorThrown);
//                                    }
//                                }
//                            );
//                        } else {
//                            alert("Status failed");
//                        }
//                    },
//                    error: function(jqXHR, textStatus, errorThrown) {
//                        inesonicSpeedSentryBroadcastCapabilityChange(null);
//                        console.log("Could not update access codes: " + errorThrown);
//                    }
//                }
//            );
//        }
//    }
//}

/***********************************************************************************************************************
 * Main:
 */

jQuery(document).ready(function($) {
    $("#inesonic-speedsentry-resources-settings-link").click(function(event) {
        inesonicSpeedSentryResourcesSettingsToggle();
    });

	$("#inesonic-speedsentry-resources-monitor-cpu-usage").on("change", function() {
		inesonicSpeedSentryResourcesCpuChangeToggled();
	});
															  
	$("#inesonic-speedsentry-resources-monitor-memory-usage").on("change", function() {
		inesonicSpeedSentryResourcesMemoryChangeToggled();
	});
															  
	$("#inesonic-speedsentry-resources-monitor-disk-usage").on("change", function() {
		inesonicSpeedSentryResourcesDiskChangeToggled();
	});
															  
    $("#inesonic-speedsentry-resources-disk-usage-threshold").on("change keyup paste", function() {
        inesonicSpeedSentryResourcesDiskThresholdChanged();
    });

    let submitButton = $("#inesonic-speedsentry-resources-settings-submit-button");
    submitButton.addClass("inesonic-speedsentry-anchor-disable");
    submitButton.click(function(event) {
        inesonicSpeedSentryResourcesSettingsSubmit();
    });

    inesonicSpeedSentryResourcesUpdateSettings();
});
