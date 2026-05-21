(function ($) {
	'use strict';

	// Button loading state helpers
	function setButtonLoading(btn, loadingText) {
		var $btn = $(btn);
		$btn.data('wpr-original-html', $btn.html());
		$btn.prop('disabled', true).addClass('wpr-btn-loading');
		$btn.html('<span class="wpr-btn-spinner"></span> ' + loadingText);
	}

	function setButtonDone(btn) {
		var $btn = $(btn);
		$btn.prop('disabled', false).removeClass('wpr-btn-loading');
		$btn.html($btn.data('wpr-original-html'));
	}

		// Revert Changes — capture initial field values on load, restore on click
	var wprInitialState = {};

	function wprCaptureState() {
		wprInitialState = {};
		$('#wpr-tab-design input, #wpr-tab-design select, #wpr-tab-design textarea,' +
		  ' #wpr-tab-triggers input, #wpr-tab-triggers select,' +
		  ' #wpr-tab-urgency input, #wpr-tab-urgency select,' +
		  ' #wpr-tab-after-success input, #wpr-tab-after-success select, #wpr-tab-after-success textarea').each(function () {
			var el = $(this);
			var id = el.attr('id');
			if (!id) return;
			if (el.is(':checkbox, :radio')) {
				wprInitialState[id] = el.is(':checked');
			} else {
				wprInitialState[id] = el.val();
			}
		});
	}

	function wprRestoreState() {
		$.each(wprInitialState, function (id, val) {
			var el = $("#" + id);
			if (!el.length) return;
			if (el.is(':checkbox, :radio')) {
				el.prop('checked', val).trigger('change');
			} else {
				el.val(val).trigger('input').trigger('change');
			}
		});
	}

	$(document).ready(function () {
		setTimeout(wprCaptureState, 500);

		$("#wpr-revert-changes").on('click', function () {
			if (confirm(WPRoboEngage.i18n.revertConfirm)) {
				wprRestoreState();
				showToast(WPRoboEngage.i18n.changesReverted, 'info');
			}
		});
	});

		// Toast Notification System
	//
	// options: optional object { action: { label, onClick }, duration }
	//   - action.label: text for the inline button (e.g. 'Undo')
	//   - action.onClick: fired when the user clicks the action; toast
	//     dismisses immediately
	//   - duration: ms to show the toast (default 4000)
	function showToast(message, type = 'success', options = {}) {
		// Create toast container if it doesn't exist
		let $container = $('#wpr-toast-container');
		if (!$container.length) {
			$container = $('<div id="wpr-toast-container"></div>');
			$('body').append($container);
		}

		// Icon SVGs for different types
		const icons = {
			success: '<svg class="wpr-toast-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
			error: '<svg class="wpr-toast-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
			info: '<svg class="wpr-toast-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
			warning: '<svg class="wpr-toast-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>'
		};

		// Action button requires BOTH a label and a callable onClick —
		// rendering a button that does nothing would be worse than none.
		const hasAction = options.action
			&& typeof options.action.label === 'string'
			&& typeof options.action.onClick === 'function';

		// Shell HTML uses only trusted strings (icon SVGs defined above,
		// enum-typed `type`). User-supplied strings (message, action label)
		// are inserted via .text() below to prevent XSS from tainted content.
		const $toast = $(
			'<div class="wpr-toast wpr-toast-' + type + '">'
			+ (icons[type] || icons.info)
			+ '<div class="wpr-toast-content"></div>'
			+ (hasAction
				? '<button type="button" class="wpr-toast-action" style="margin-left: auto; background: none; border: 1px solid currentColor; color: inherit; padding: 4px 10px; border-radius: 4px; font-size: 12px; font-weight: 600; cursor: pointer; text-transform: uppercase; letter-spacing: 0.03em;"></button>'
				: '')
			+ '</div>'
		);
		$toast.find('.wpr-toast-content').text(message);

		if (hasAction) {
			$toast.find('.wpr-toast-action').text(options.action.label).on('click', function () {
				options.action.onClick();
				// Dismiss immediately on action click
				clearTimeout(dismissTimer);
				$toast.removeClass('wpr-toast-show').addClass('wpr-toast-hide');
				setTimeout(function () { $toast.remove(); }, 300);
			});
		}

		// Add to container
		$container.append($toast);

		// Trigger animation
		setTimeout(function () {
			$toast.addClass('wpr-toast-show');
		}, 10);

		// Auto remove after the configured duration
		const duration = options.duration || 4000;
		const dismissTimer = setTimeout(function () {
			$toast.removeClass('wpr-toast-show').addClass('wpr-toast-hide');
			setTimeout(function () {
				$toast.remove();
			}, 300);
		}, duration);
	}

	$(function () {
		// Only run on campaign edit screen
		if (!$('#wpr-tab-design').length) {
			return;
		}

		// --- Initialize WordPress Color Picker ---
		$('.wpr-color-picker').wpColorPicker({
			change: function (event, ui) {
				// Trigger color update when color is changed
				$(this).trigger('colorchange', ui.color.toString());
			}
		});

		// Toggle box shadow controls
		$('#wpr-box-shadow-enabled').on('change', function () {
			if ($(this).is(':checked')) {
				$('#wpr-box-shadow-controls').slideDown();
			} else {
				$('#wpr-box-shadow-controls').slideUp();
			}
			updateBoxShadow();
		});

		// Toggle close button visibility in preview
		$('#wpr-show-close-icon').on('change', function () {
			if ($(this).is(':checked')) {
				$('#wpr-preview-close').show();
			} else {
				$('#wpr-preview-close').hide();
			}
		});

		// Prevent close button from being clickable in admin preview
		$('#wpr-preview-close').on('click', function (e) {
			e.preventDefault();
			e.stopPropagation();
			return false;
		});

		// --- Tab switching functionality ---
		var $tablist = $('.wpr-builder-content [role="tablist"]');
		$tablist.find('.wpr-tab-button').on('click', function () {
			var $btn = $(this);
			var tab = $btn.data('tab');
			var $siblings = $tablist.find('.wpr-tab-button');
			var $panels = $tablist.closest('.wpr-builder-content').find('.wpr-tab-content');

			// Update button styles
			$siblings.removeClass('wpr-border-blue-500 wpr-text-gray-700 wpr-bg-white');
			$siblings.addClass('wpr-border-transparent wpr-text-gray-500');
			$btn.removeClass('wpr-border-transparent wpr-text-gray-500');
			$btn.addClass('wpr-border-blue-500 wpr-text-gray-700 wpr-bg-white');

			// Update ARIA attributes
			$siblings.attr('aria-selected', 'false').attr('tabindex', '-1');
			$btn.attr('aria-selected', 'true').removeAttr('tabindex');

			// Show/hide content
			$panels.addClass('wpr-hidden').hide();
			$('#wpr-tab-' + tab).removeClass('wpr-hidden').show();
		});

		// --- Design Tab Accordion ---
		$(document).on('click', '.wpr-accordion-header', function () {
			var $header = $(this);
			var targetId = $header.data('accordion');
			var $body = $('#' + targetId);
			var isOpen = $header.hasClass('open');

			$header.toggleClass('open', !isOpen);
			$body.toggleClass('open', !isOpen);
			$body.css('display', isOpen ? 'none' : 'flex');

			// Update ARIA attributes
			$header.attr('aria-expanded', !isOpen ? 'true' : 'false');
			$body.attr('aria-hidden', isOpen ? 'true' : 'false');
		});

		// Initialize ARIA on accordion headers
		$('.wpr-accordion-header').each(function () {
			var $header = $(this);
			var targetId = $header.data('accordion');
			var isOpen = $header.hasClass('open');
			$header.attr({
				'role': 'button',
				'aria-expanded': isOpen ? 'true' : 'false',
				'aria-controls': targetId
			});
			$('#' + targetId).attr({
				'role': 'region',
				'aria-hidden': isOpen ? 'false' : 'true',
				'aria-labelledby': $header.attr('id') || ''
			});
		});

		// --- Campaign Type switcher ---
		$('#wpr-engage-type').on('change', function () {
			const campaignType = $(this).val();

			// Show/hide bar position field based on campaign type
			if (campaignType === 'floating-bar') {
				$('#wpr-bar-position-row').show();
			} else {
				$('#wpr-bar-position-row').hide();
			}

			// Show/hide slide position field based on campaign type
			if (campaignType === 'slide-in') {
				$('#wpr-slide-position-row').show();
			} else {
				$('#wpr-slide-position-row').hide();
			}

			// Update preview based on campaign type
			updatePreviewStyle(campaignType);
		});

		// Initialize preview style on page load
		updatePreviewStyle($('#wpr-engage-type').val());

		function updatePreviewStyle(campaignType) {
			const $previewWrapper = $('#wpr-preview-wrapper');

			if (campaignType === 'floating-bar') {
				// Bar style: wider, less padding
				$previewWrapper.css({
					'max-width': '100%',
					'padding': '16px 32px',
					'border-radius': '0'
				});
			} else {
				// Popup style: centered, more padding
				$previewWrapper.css({
					'max-width': '500px',
					'padding': '32px',
					'border-radius': '8px'
				});
			}
		}
		// --- Trigger Type Management ---
		//
		// Each trigger type has an icon, title, description, and an optional
		// tip. Content is rendered into #wpr-trigger-info-box so users can see
		// at a glance what the selected trigger will do.
		const triggerDescriptions = {
			'': {
				icon: '✋',
				title: 'No Trigger — Manual',
				description: 'This campaign will not trigger automatically. Use a shortcode or the JS API to show it when you need to.',
				tips: 'Useful for thank-you pages, custom button clicks, or multi-step funnels.'
			},
			'timed_delay': {
				icon: '⏱',
				title: 'Timed Delay',
				description: 'Shows the popup after the visitor has been on the page for a specified number of seconds.',
				tips: 'Use 30–60s for content pages, 5–15s for high-intent landing pages.'
			},
			'scroll_depth': {
				icon: '📜',
				title: 'Scroll Depth',
				description: 'Shows the popup once the visitor has scrolled a set percentage down the page.',
				tips: '50–70% scroll depth indicates engaged readers — great for content upgrades.'
			},
			'exit_intent': {
				icon: '🚪',
				title: 'Exit-Intent',
				description: 'Shows the popup when the user moves their mouse towards the top of the browser, indicating they are about to leave.',
				tips: 'Pair with urgency messaging and offers for abandoning visitors.'
			}
		};

		function renderTriggerInfoBox(triggerType) {
			const info = triggerDescriptions[triggerType] || triggerDescriptions[''];
			$('#wpr-trigger-info-icon').text(info.icon);
			$('#wpr-trigger-info-title').text(info.title);
			$('#wpr-trigger-info-desc').text(info.description);
			$('#wpr-trigger-info-tips').text(info.tips || '');
		}

		$('#wpr-trigger-type').on('change', function () {
			const triggerType = $(this).val();

			// Hide all trigger value fields
			$('#wpr-trigger-value-container').hide();
			$('#wpr-timed-delay-field').hide();
			$('#wpr-scroll-depth-field').hide();

			// Show appropriate field based on trigger type
			if (triggerType === 'timed_delay') {
				$('#wpr-trigger-value-container').show();
				$('#wpr-timed-delay-field').show();
			} else if (triggerType === 'scroll_depth') {
				$('#wpr-trigger-value-container').show();
				$('#wpr-scroll-depth-field').show();
			}

			renderTriggerInfoBox(triggerType);
		});

		// Render the info box on initial page load based on current value.
		renderTriggerInfoBox($('#wpr-trigger-type').val() || '');

		// Sync trigger radio cards → hidden select so existing handlers work.
		$('input[name="wpr_trigger_type_radio"]').on('change', function () {
			$('#wpr-trigger-type').val($(this).val()).trigger('change');
		});

		// Reverse sync: hidden select → radio cards (for Revert Changes, etc.)
		$('#wpr-trigger-type').on('change', function () {
			var val = $(this).val();
			$('input[name="wpr_trigger_type_radio"]').each(function () {
				$(this).prop('checked', $(this).val() === val);
			});
		});

		// --- Timer Enable/Disable ---
		// When off, dim the settings rather than hiding them — keeps the
		// options discoverable so users know what will be available when
		// they enable the timer.
		$('#wpr-timer-enabled').on('change', function () {
			const enabled = $(this).is(':checked');
			$('#wpr-timer-settings')
				.toggleClass('wpr-settings-disabled', !enabled)
				.attr('aria-disabled', enabled ? 'false' : 'true');
		});

		// --- Timer Type Management ---
		$('#wpr-timer-type').on('change', function () {
			const timerType = $(this).val();

			// Hide all timer type settings
			$('#wpr-timer-fixed-settings').hide();
			$('#wpr-timer-evergreen-settings').hide();
			$('#wpr-timer-daily-settings').hide();
			$('#wpr-timer-session-settings').hide();
			$('#wpr-timer-stock-settings').hide();

			// Show appropriate settings based on timer type
			if (timerType === 'fixed') {
				$('#wpr-timer-fixed-settings').show();
			} else if (timerType === 'evergreen') {
				$('#wpr-timer-evergreen-settings').show();
			} else if (timerType === 'daily') {
				$('#wpr-timer-daily-settings').show();
			} else if (timerType === 'session') {
				$('#wpr-timer-session-settings').show();
			} else if (timerType === 'stock') {
				$('#wpr-timer-stock-settings').show();
			}
		});

		// --- Schedule Enable/Disable ---
		$('#wpr-schedule-enabled').on('change', function () {
			if ($(this).is(':checked')) {
				$('#wpr-schedule-settings').slideDown();
				$('#wpr-schedule-summary').slideDown();
			} else {
				$('#wpr-schedule-settings').slideUp();
				$('#wpr-schedule-summary').slideUp();
			}
			updateScheduleSummary();
		});

		// --- Schedule Time Range Enable/Disable ---
		$('#wpr-schedule-time-range-enabled').on('change', function () {
			if ($(this).is(':checked')) {
				$('#wpr-schedule-time-range-fields').slideDown();
			} else {
				$('#wpr-schedule-time-range-fields').slideUp();
			}
			updateScheduleSummary();
		});

		// --- Schedule Summary ---
		function updateScheduleSummary() {
			if (!$('#wpr-schedule-enabled').is(':checked')) return;

			var parts = [];
			var startDate = $('#wpr-schedule-start-date').val();
			var endDate = $('#wpr-schedule-end-date').val();
			var startTime = $('#wpr-schedule-start-time').val();
			var endTime = $('#wpr-schedule-end-time').val();

			if (startDate && endDate) {
				parts.push('Runs from <strong>' + startDate + '</strong> to <strong>' + endDate + '</strong>');
			} else if (startDate) {
				parts.push('Starts <strong>' + startDate + '</strong>');
			}

			if (startTime && endTime) {
				parts.push('between <strong>' + startTime + '</strong> and <strong>' + endTime + '</strong>');
			}

			var days = [];
			$('.wpr-schedule-day:checked').each(function () {
				var dayMap = { mon: 'Mon', tue: 'Tue', wed: 'Wed', thu: 'Thu', fri: 'Fri', sat: 'Sat', sun: 'Sun' };
				days.push(dayMap[$(this).val()] || $(this).val());
			});

			if (days.length > 0 && days.length < 7) {
				parts.push('on <strong>' + days.join(', ') + '</strong>');
			} else if (days.length === 7) {
				parts.push('every day');
			}

			if ($('#wpr-schedule-time-range-enabled').is(':checked')) {
				var timeStart = $('#wpr-schedule-time-start').val();
				var timeEnd = $('#wpr-schedule-time-end').val();
				if (timeStart && timeEnd) {
					parts.push('active hours <strong>' + timeStart + ' – ' + timeEnd + '</strong>');
				}
			}

			$('#wpr-schedule-summary-text').html(parts.length ? parts.join(', ') + '.' : 'Configure dates and days to see your schedule summary.');
		}

		// Update summary on any schedule input change
		$(document).on('change input', '#wpr-schedule-start-date, #wpr-schedule-end-date, #wpr-schedule-start-time, #wpr-schedule-end-time, .wpr-schedule-day, #wpr-schedule-time-start, #wpr-schedule-time-end', updateScheduleSummary);
		updateScheduleSummary();

		// --- Timer Expire Action Management ---
		$('#wpr-timer-expire-action').on('change', function () {
			const action = $(this).val();

			// Hide all conditional fields
			$('#wpr-timer-expire-message-field').hide();
			$('#wpr-timer-expire-redirect-field').hide();

			// Show appropriate field based on action
			if (action === 'message') {
				$('#wpr-timer-expire-message-field').show();
			} else if (action === 'redirect') {
				$('#wpr-timer-expire-redirect-field').show();
			}
		});

		// --- Timer Urgency Enable/Disable ---
		$('#wpr-timer-urgency-enabled').on('change', function () {
			if ($(this).is(':checked')) {
				$('#wpr-timer-urgency-settings').slideDown();
			} else {
				$('#wpr-timer-urgency-settings').slideUp();
			}
		});

		// --- Success Action Management ---
		$('input[name="wpr-success-action"]').on('change', function () {
			const successAction = $(this).val();
			if (successAction === 'redirect') {
				$('#wpr-redirect-url-container').removeClass('wpr-hidden').slideDown();
				$('#wpr-success-message-config').slideUp(function () {
					$(this).addClass('wpr-hidden');
				});
			} else if (successAction === 'message') {
				$('#wpr-success-message-config').removeClass('wpr-hidden').slideDown();
				$('#wpr-redirect-url-container').slideUp(function () {
					$(this).addClass('wpr-hidden');
				});
			}
		});

		// --- Auto-close Delay Management ---
		$('#wpr-success-auto-close').on('change', function () {
			if ($(this).is(':checked')) {
				$('#wpr-auto-close-delay-container').removeClass('wpr-hidden').slideDown();
			} else {
				$('#wpr-auto-close-delay-container').slideUp(function () {
					$(this).addClass('wpr-hidden');
				});
			}
		});

		// --- Success Icon Display Management ---
		$('#wpr-success-show-icon').on('change', function () {
			if ($(this).is(':checked')) {
				$('#wpr-success-icon-options').removeClass('wpr-hidden').slideDown();
			} else {
				$('#wpr-success-icon-options').slideUp(function () {
					$(this).addClass('wpr-hidden');
				});
			}
		});

		// --- Display Rules Management ---
		let displayRules = [];
		let ruleGroups = []; // New: Support for rule groups with AND/OR logic
		let customPostTypes = []; // Store custom post types for dynamic options

		// Load custom post types for display rules
		if (typeof WPRoboEngage !== 'undefined' && WPRoboEngage.displayRulesPostTypesUrl) {
			$.ajax({
				url: WPRoboEngage.displayRulesPostTypesUrl,
				method: 'GET',
				beforeSend: function (xhr) {
					xhr.setRequestHeader('X-WP-Nonce', WPRoboEngage.nonce);
				},
				success: function (response) {
					customPostTypes = response;
				}
			});
		}

		// Load existing rules from PHP
		if (typeof wprDisplayRules !== 'undefined' && Array.isArray(wprDisplayRules)) {
			displayRules = wprDisplayRules;
			renderDisplayRules();
		}

		// Load rule groups from PHP if available
		if (typeof wprRuleGroups !== 'undefined' && Array.isArray(wprRuleGroups) && wprRuleGroups.length > 0) {
			ruleGroups = wprRuleGroups;
			// If we have rule groups, switch to advanced mode
			$('#wpr-use-rule-groups').prop('checked', true);
			// Show advanced mode, hide simple mode
			$('.wpr-simple-mode-only').hide();
			$('.wpr-advanced-mode-only').show();
			renderRuleGroups();
			updateRulePreview();
		}

		// Toggle between simple and advanced mode
		$('#wpr-use-rule-groups').on('change', function () {
			if ($(this).is(':checked')) {
				// Switch to advanced mode
				$('.wpr-simple-mode-only').hide();
				$('.wpr-advanced-mode-only').show();

				// If no groups exist, create one from existing rules
				if (ruleGroups.length === 0 && displayRules.length > 0) {
					ruleGroups.push({
						action: 'show',
						logic: 'and', // Rules within this group use AND logic
						rules: displayRules.filter(r => r.action === 'show').map(r => ({ type: r.type, value: r.value }))
					});
					if (displayRules.some(r => r.action === 'hide')) {
						ruleGroups.push({
							action: 'hide',
							logic: 'and',
							rules: displayRules.filter(r => r.action === 'hide').map(r => ({ type: r.type, value: r.value }))
						});
					}
				}
				renderRuleGroups();
				updateRulePreview();
			} else {
				// Switch to simple mode
				$('.wpr-simple-mode-only').show();
				$('.wpr-advanced-mode-only').hide();

				// Convert groups back to simple rules if needed
				if (ruleGroups.length > 0) {
					displayRules = [];
					ruleGroups.forEach(group => {
						if (group.rules && Array.isArray(group.rules)) {
							// Add action property from group
							group.rules.forEach(rule => {
								displayRules.push({
									type: rule.type,
									value: rule.value,
									action: group.action
								});
							});
						}
					});
					// Clear rule groups when switching to simple mode
					ruleGroups = [];
				}
				renderDisplayRules();
				updateRulePreview();
			}
		});

		// Add rule group
		$('#wpr-add-show-group, #wpr-add-hide-group').on('click', function () {
			const action = $(this).attr('id') === 'wpr-add-show-group' ? 'show' : 'hide';
			ruleGroups.push({
				action: action,
				logic: 'and', // Default to AND logic within group
				rules: []
			});
			renderRuleGroups();
			updateRulePreview();
		});

		// Remove rule group
		$(document).on('click', '.wpr-remove-group', function () {
			const index = $(this).data('index');
			ruleGroups.splice(index, 1);
			renderRuleGroups();
			updateRulePreview();
		});

		// Change group logic
		$(document).on('change', '.wpr-group-logic', function () {
			const index = $(this).data('index');
			ruleGroups[index].logic = $(this).val();
			// No need to re-render, just update preview
			updateRulePreview();
		});

		// Add rule to group
		$(document).on('click', '.wpr-add-rule-to-group', function () {
			const groupIndex = $(this).data('group-index');
			if (!ruleGroups[groupIndex].rules) {
				ruleGroups[groupIndex].rules = [];
			}
			ruleGroups[groupIndex].rules.push({
				type: 'all_pages',
				value: ''
			});
			renderRuleGroups();
			updateRulePreview();
		});

		// Remove rule from group
		$(document).on('click', '.wpr-remove-group-rule', function () {
			const groupIndex = $(this).data('group-index');
			const ruleIndex = $(this).data('rule-index');
			ruleGroups[groupIndex].rules.splice(ruleIndex, 1);
			renderRuleGroups();
			updateRulePreview();
		});

		// Update rule type in group
		$(document).on('change', '.wpr-group-rule-type', function () {
			const groupIndex = $(this).data('group-index');
			const ruleIndex = $(this).data('rule-index');
			ruleGroups[groupIndex].rules[ruleIndex].type = $(this).val();
			ruleGroups[groupIndex].rules[ruleIndex].value = '';
			renderRuleGroups();
			updateRulePreview();
		});

		// Update rule value in group
		$(document).on('change keyup', '.wpr-group-rule-value', function () {
			const groupIndex = $(this).data('group-index');
			const ruleIndex = $(this).data('rule-index');
			ruleGroups[groupIndex].rules[ruleIndex].value = $(this).val();
			updateRulePreview();
		});

		function renderRuleGroups() {
			const container = $('#wpr-rule-groups-list');
			container.empty();

			if (ruleGroups.length === 0) {
				container.html('<p class="wpr-text-sm wpr-text-slate-500 wpr-italic" style="text-align: center; padding: 40px 20px;">' + WPRoboEngage.i18n.noRuleGroups + '</p>');
				return;
			}

			ruleGroups.forEach(function (group, groupIndex) {
				const isShow = group.action === 'show';
				const statusColor = isShow ? '#059669' : '#dc2626';
				const statusBg = isShow ? '#ecfdf5' : '#fef2f2';
				const groupLabel = isShow ? WPRoboEngage.i18n.showGroup : WPRoboEngage.i18n.hideGroup;

				let groupHtml = `
					<div class="wpr-rule-group" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); margin-bottom: 24px; position: relative;">
						<!-- Group Header -->
						<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; gap: 16px;">
							<div style="display: flex; align-items: center; gap: 12px; flex: 1;">
								<span style="color: ${statusColor}; font-weight: 700; font-size: 10px; letter-spacing: 0.05em; background: ${statusBg}; padding: 3px 8px; border-radius: 4px; white-space: nowrap;">
									${groupLabel}
								</span>
								<div style="position: relative; flex: 1; max-width: 240px;">
									<select class="wpr-group-logic" data-index="${groupIndex}" style="width: 100%; height: 32px; padding: 0 12px; font-size: 12px; font-weight: 500; color: #334155; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; cursor: pointer; outline: none; appearance: none; -webkit-appearance: none;">
										<option value="and" ${group.logic === 'and' ? 'selected' : ''}>${WPRoboEngage.i18n.allRulesAnd}</option>
										<option value="or" ${group.logic === 'or' ? 'selected' : ''}>${WPRoboEngage.i18n.anyRulesOr}</option>
									</select>
									<div style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); pointer-events: none; color: #64748b;">
										<svg xmlns="http://www.w3.org/2000/svg" style="width: 12px; height: 12px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
											<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
										</svg>
									</div>
								</div>
							</div>
							<button type="button" class="wpr-remove-group" data-index="${groupIndex}" style="width: 28px; height: 28px; border-radius: 50%; border: none; background: #f1f5f9; color: #64748b; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s;">
								<svg xmlns="http://www.w3.org/2000/svg" style="width: 14px; height: 14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
									<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
								</svg>
							</button>
						</div>

						<!-- Rules Container -->
						<div style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 20px;">
				`;

				// Render rules in this group
				if (group.rules && group.rules.length > 0) {
					group.rules.forEach(function (rule, ruleIndex) {
						groupHtml += getRuleHtmlForGroup(rule, groupIndex, ruleIndex);
					});
				} else {
					groupHtml += `
						<div style="text-align: center; padding: 24px; background: #f8fafc; border: 1px dashed #e2e8f0; border-radius: 8px;">
							<p style="font-size: 11px; color: #94a3b8; font-style: italic; margin: 0;">${WPRoboEngage.i18n.noRulesInGroup}</p>
						</div>
					`;
				}

				groupHtml += `
						</div>

						<!-- Group Actions -->
						<div style="display: flex; align-items: center; padding-top: 16px; border-top: 1px solid #f1f5f9;">
							<button type="button" class="wpr-add-rule-to-group" data-group-index="${groupIndex}" style="background: none; border: none; padding: 0; color: #3b82f6; font-size: 12px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 6px; transition: color 0.1s;">
								<svg xmlns="http://www.w3.org/2000/svg" style="width: 14px; height: 14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
									<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
								</svg>
								${WPRoboEngage.i18n.addTargetingRule}
							</button>
						</div>
					</div>
				`;

				container.append(groupHtml);

				// Add separator between groups
				if (groupIndex < ruleGroups.length - 1) {
					const separator = $(`
						<div style="display: flex; align-items: center; gap: 16px; margin: 24px 0;">
							<div style="flex: 1; height: 1px; background: #e2e8f0;"></div>
							<span style="font-size: 11px; font-weight: 800; color: #cbd5e1; letter-spacing: 0.1em;">OR</span>
							<div style="flex: 1; height: 1px; background: #e2e8f0;"></div>
						</div>
					`);
					container.append(separator);
				}
			});
		}

		function getRuleHtmlForGroup(rule, groupIndex, ruleIndex) {
			let html = `
				<div class="wpr-group-rule wpr-p-2 wpr-bg-white wpr-border wpr-border-gray-300 wpr-rounded" style="padding: 8px; background: white; border: 1px solid #d1d5db; border-radius: 4px;">
					<div class="wpr-flex wpr-items-start wpr-gap-2" style="display: flex; align-items: flex-start; gap: 8px;">
						<select class="wpr-group-rule-type wpr-flex-1 wpr-text-xs wpr-border wpr-border-gray-300 wpr-rounded wpr-p-1" data-group-index="${groupIndex}" data-rule-index="${ruleIndex}" style="flex: 1; font-size: 12px; padding: 4px; border: 1px solid #d1d5db; border-radius: 4px;">
							${getRuleTypeOptionsHtml(rule.type)}
						</select>
						<button type="button" class="wpr-remove-group-rule wpr-text-gray-400 hover:wpr-text-gray-600" data-group-index="${groupIndex}" data-rule-index="${ruleIndex}" style="cursor: pointer; font-size: 16px;">×</button>
					</div>
			`;

			// Add value input if needed
			const valueInput = getRuleValueInputForGroup(rule, groupIndex, ruleIndex);
			if (valueInput) {
				html += `<div class="wpr-mt-1" style="margin-top: 4px;">${valueInput}</div>`;
			}

			html += `</div>`;
			return html;
		}

		function getRuleTypeOptionsHtml(selectedType) {
			const optgroups = {
				'Page/Post': ['all_pages', 'homepage', 'specific_page', 'specific_post', 'all_posts', 'url_contains', 'url_starts_with', 'url_ends_with', 'url_regex', 'post_category', 'post_tag', 'custom_post_type', 'archive_page', 'search_page', 'error_404'],
				'User': ['user_logged_in', 'user_logged_out', 'user_role', 'user_not_role'],
				'Referral': ['referral_direct', 'referral_search', 'referral_social', 'referral_domain', 'referral_utm'],
				'Device': ['device_desktop', 'device_mobile', 'device_tablet', 'device_browser', 'device_os'],
				'Behavioral': ['time_on_site', 'scroll_depth', 'page_views_session', 'page_views_lifetime', 'visitor_new', 'visitor_returning'],
				'Advanced': ['custom_js']
			};

			const labels = {
				'all_pages': 'All Pages', 'homepage': 'Homepage', 'specific_page': 'Specific Page', 'specific_post': 'Specific Post',
				'all_posts': 'All Posts', 'url_contains': 'URL Contains', 'url_starts_with': 'URL Starts With', 'url_ends_with': 'URL Ends With',
				'url_regex': 'URL Regex', 'post_category': 'Post Category', 'post_tag': 'Post Tag', 'custom_post_type': 'Custom Post Type',
				'archive_page': 'Archive Page', 'search_page': 'Search Page', 'error_404': '404 Error',
				'user_logged_in': 'User Logged In', 'user_logged_out': 'User Logged Out', 'user_role': 'User Has Role', 'user_not_role': 'User NOT Has Role',
				'referral_direct': 'Direct Traffic', 'referral_search': 'From Search', 'referral_social': 'From Social', 'referral_domain': 'From Domain', 'referral_utm': 'UTM Parameters',
				'device_desktop': 'Desktop', 'device_mobile': 'Mobile', 'device_tablet': 'Tablet', 'device_browser': 'Browser', 'device_os': 'OS',
				'time_on_site': 'Time on Site', 'scroll_depth': 'Scroll Depth', 'page_views_session': 'Page Views (Session)', 'page_views_lifetime': 'Page Views (Total)', 'visitor_new': 'New Visitor', 'visitor_returning': 'Returning Visitor',
				'custom_js': 'Custom JavaScript'
			};

			// Add CPT options dynamically
			if (customPostTypes && customPostTypes.length > 0) {
				customPostTypes.forEach(function (cpt) {
					// Add "All {CPT}" option
					const allKey = 'all_cpt_' + cpt.slug;
					optgroups['Page/Post'].push(allKey);
					labels[allKey] = 'All ' + cpt.label;

					// Add "Specific {CPT}" option
					const specificKey = 'specific_cpt_' + cpt.slug;
					optgroups['Page/Post'].push(specificKey);
					labels[specificKey] = 'Specific ' + cpt.label;
				});
			}

			let html = '';
			Object.keys(optgroups).forEach(group => {
				html += `<optgroup label="${group}">`;
				optgroups[group].forEach(type => {
					html += `<option value="${type}" ${selectedType === type ? 'selected' : ''}>${labels[type]}</option>`;
				});
				html += '</optgroup>';
			});

			return html;
		}

		function getRuleValueInputForGroup(rule, groupIndex, ruleIndex) {
			const needsValue = ['specific_page', 'specific_post', 'url_contains', 'url_starts_with', 'url_ends_with', 'url_regex',
				'post_category', 'post_tag', 'custom_post_type', 'user_role', 'user_not_role', 'referral_domain',
				'device_browser', 'device_os', 'time_on_site', 'scroll_depth', 'page_views_session', 'page_views_lifetime',
				'custom_js'];

			// Check if this is a CPT-specific rule
			const isCptSpecific = rule.type && rule.type.startsWith('specific_cpt_');
			const isCptAll = rule.type && rule.type.startsWith('all_cpt_');

			if (!needsValue.includes(rule.type) && rule.type !== 'archive_page' && !isCptSpecific && !isCptAll) {
				return null;
			}

			// Handle specific page with Select2 dropdown
			if (rule.type === 'specific_page') {
				const uniqueId = `wpr-select2-page-${groupIndex}-${ruleIndex}`;
				setTimeout(function () {
					initSelect2ForPages(uniqueId, rule.value, groupIndex, ruleIndex);
				}, 100);
				return `<select id="${uniqueId}" class="wpr-group-rule-value wpr-select2-page wpr-w-full wpr-text-xs" data-group-index="${groupIndex}" data-rule-index="${ruleIndex}" style="width: 100%;"></select>`;
			}

			// Handle specific post with Select2 dropdown
			if (rule.type === 'specific_post') {
				const uniqueId = `wpr-select2-post-${groupIndex}-${ruleIndex}`;
				setTimeout(function () {
					initSelect2ForPosts(uniqueId, rule.value, groupIndex, ruleIndex);
				}, 100);
				return `<select id="${uniqueId}" class="wpr-group-rule-value wpr-select2-post wpr-w-full wpr-text-xs" data-group-index="${groupIndex}" data-rule-index="${ruleIndex}" style="width: 100%;"></select>`;
			}

			// Handle post category with Select2 dropdown
			if (rule.type === 'post_category') {
				const uniqueId = `wpr-select2-category-${groupIndex}-${ruleIndex}`;
				setTimeout(function () {
					initSelect2ForCategories(uniqueId, rule.value, groupIndex, ruleIndex);
				}, 100);
				return `<select id="${uniqueId}" class="wpr-group-rule-value wpr-select2-category wpr-w-full wpr-text-xs" data-group-index="${groupIndex}" data-rule-index="${ruleIndex}" style="width: 100%;"></select>`;
			}

			// Handle post tag with Select2 dropdown
			if (rule.type === 'post_tag') {
				const uniqueId = `wpr-select2-tag-${groupIndex}-${ruleIndex}`;
				setTimeout(function () {
					initSelect2ForTags(uniqueId, rule.value, groupIndex, ruleIndex);
				}, 100);
				return `<select id="${uniqueId}" class="wpr-group-rule-value wpr-select2-tag wpr-w-full wpr-text-xs" data-group-index="${groupIndex}" data-rule-index="${ruleIndex}" style="width: 100%;"></select>`;
			}

			// Handle specific CPT with Select2 dropdown
			if (isCptSpecific) {
				const postType = rule.type.replace('specific_cpt_', '');
				const uniqueId = `wpr-select2-cpt-${postType}-${groupIndex}-${ruleIndex}`;
				setTimeout(function () {
					initSelect2ForCPT(uniqueId, postType, rule.value, groupIndex, ruleIndex);
				}, 100);
				return `<select id="${uniqueId}" class="wpr-group-rule-value wpr-select2-cpt wpr-w-full wpr-text-xs" data-group-index="${groupIndex}" data-rule-index="${ruleIndex}" data-post-type="${postType}" style="width: 100%;"></select>`;
			}

			// Archive page dropdown
			if (rule.type === 'archive_page') {
				return `
					<select class="wpr-group-rule-value wpr-w-full wpr-text-xs wpr-border wpr-border-gray-300 wpr-rounded wpr-p-1" data-group-index="${groupIndex}" data-rule-index="${ruleIndex}" style="width: 100%; font-size: 11px; padding: 4px; border: 1px solid #d1d5db; border-radius: 4px;">
						<option value="">Any Archive</option>
						<option value="category" ${rule.value === 'category' ? 'selected' : ''}>Category</option>
						<option value="tag" ${rule.value === 'tag' ? 'selected' : ''}>Tag</option>
						<option value="author" ${rule.value === 'author' ? 'selected' : ''}>Author</option>
						<option value="date" ${rule.value === 'date' ? 'selected' : ''}>Date</option>
					</select>
				`;
			}

			// Custom JS textarea
			if (rule.type === 'custom_js') {
				return `<textarea class="wpr-group-rule-value wpr-w-full wpr-text-xs wpr-border wpr-border-gray-300 wpr-rounded wpr-p-1 wpr-font-mono" placeholder="return true;" data-group-index="${groupIndex}" data-rule-index="${ruleIndex}" rows="2" style="width: 100%; font-size: 11px; padding: 4px; border: 1px solid #d1d5db; border-radius: 4px; font-family: monospace;">${rule.value || ''}</textarea>`;
			}

			// Other text/number inputs remain as before
			let inputType = 'text';
			let placeholder = 'Value';

			if (['time_on_site', 'scroll_depth', 'page_views_session', 'page_views_lifetime'].includes(rule.type)) {
				inputType = 'number';
				placeholder = 'Number';
			}

			return `<input type="${inputType}" class="wpr-group-rule-value wpr-w-full wpr-text-xs wpr-border wpr-border-gray-300 wpr-rounded wpr-p-1" placeholder="${placeholder}" value="${rule.value || ''}" data-group-index="${groupIndex}" data-rule-index="${ruleIndex}" style="width: 100%; font-size: 11px; padding: 4px; border: 1px solid #d1d5db; border-radius: 4px;">`;
		}

		// Select2 initialization functions
		function initSelect2ForPages(selectId, selectedValue, groupIndex, ruleIndex) {
			const $select = $('#' + selectId);
			if (!$select.length) return;

			$select.select2({
				placeholder: 'Select a page...',
				allowClear: true,
				width: '100%',
				ajax: {
					url: WPRoboEngage.displayRulesPagesUrl,
					dataType: 'json',
					delay: 250,
					headers: {
						'X-WP-Nonce': WPRoboEngage.nonce
					},
					processResults: function (data) {
						return {
							results: data
						};
					},
					cache: true
				}
			});

			// Set initial value if exists
			if (selectedValue) {
				$.ajax({
					url: WPRoboEngage.displayRulesPagesUrl,
					headers: {
						'X-WP-Nonce': WPRoboEngage.nonce
					},
					success: function (data) {
						const selectedItem = data.find(item => item.id == selectedValue);
						if (selectedItem) {
							const option = new Option(selectedItem.text, selectedItem.id, true, true);
							$select.append(option).trigger('change');
						}
					}
				});
			}

			// Update rule value on change
			$select.on('change', function () {
				if (ruleGroups[groupIndex] && ruleGroups[groupIndex].rules[ruleIndex]) {
					ruleGroups[groupIndex].rules[ruleIndex].value = $(this).val();
					updateRulePreview();
				}
			});
		}

		function initSelect2ForPosts(selectId, selectedValue, groupIndex, ruleIndex) {
			const $select = $('#' + selectId);
			if (!$select.length) return;

			$select.select2({
				placeholder: 'Select a post...',
				allowClear: true,
				width: '100%',
				ajax: {
					url: WPRoboEngage.displayRulesPostsUrl,
					dataType: 'json',
					delay: 250,
					headers: {
						'X-WP-Nonce': WPRoboEngage.nonce
					},
					processResults: function (data) {
						return {
							results: data
						};
					},
					cache: true
				}
			});

			// Set initial value if exists
			if (selectedValue) {
				$.ajax({
					url: WPRoboEngage.displayRulesPostsUrl,
					headers: {
						'X-WP-Nonce': WPRoboEngage.nonce
					},
					success: function (data) {
						const selectedItem = data.find(item => item.id == selectedValue);
						if (selectedItem) {
							const option = new Option(selectedItem.text, selectedItem.id, true, true);
							$select.append(option).trigger('change');
						}
					}
				});
			}

			// Update rule value on change
			$select.on('change', function () {
				if (ruleGroups[groupIndex] && ruleGroups[groupIndex].rules[ruleIndex]) {
					ruleGroups[groupIndex].rules[ruleIndex].value = $(this).val();
					updateRulePreview();
				}
			});
		}

		function initSelect2ForCategories(selectId, selectedValue, groupIndex, ruleIndex) {
			const $select = $('#' + selectId);
			if (!$select.length) return;

			$select.select2({
				placeholder: 'Select a category...',
				allowClear: true,
				width: '100%',
				ajax: {
					url: WPRoboEngage.displayRulesCategoriesUrl,
					dataType: 'json',
					delay: 250,
					headers: {
						'X-WP-Nonce': WPRoboEngage.nonce
					},
					processResults: function (data) {
						return {
							results: data
						};
					},
					cache: true
				}
			});

			// Set initial value if exists
			if (selectedValue) {
				$.ajax({
					url: WPRoboEngage.displayRulesCategoriesUrl,
					headers: {
						'X-WP-Nonce': WPRoboEngage.nonce
					},
					success: function (data) {
						const selectedItem = data.find(item => item.id === selectedValue);
						if (selectedItem) {
							const option = new Option(selectedItem.text, selectedItem.id, true, true);
							$select.append(option).trigger('change');
						}
					}
				});
			}

			// Update rule value on change
			$select.on('change', function () {
				if (ruleGroups[groupIndex] && ruleGroups[groupIndex].rules[ruleIndex]) {
					ruleGroups[groupIndex].rules[ruleIndex].value = $(this).val();
					updateRulePreview();
				}
			});
		}

		function initSelect2ForTags(selectId, selectedValue, groupIndex, ruleIndex) {
			const $select = $('#' + selectId);
			if (!$select.length) return;

			$select.select2({
				placeholder: 'Select a tag...',
				allowClear: true,
				width: '100%',
				ajax: {
					url: WPRoboEngage.displayRulesTagsUrl,
					dataType: 'json',
					delay: 250,
					headers: {
						'X-WP-Nonce': WPRoboEngage.nonce
					},
					processResults: function (data) {
						return {
							results: data
						};
					},
					cache: true
				}
			});

			// Set initial value if exists
			if (selectedValue) {
				$.ajax({
					url: WPRoboEngage.displayRulesTagsUrl,
					headers: {
						'X-WP-Nonce': WPRoboEngage.nonce
					},
					success: function (data) {
						const selectedItem = data.find(item => item.id === selectedValue);
						if (selectedItem) {
							const option = new Option(selectedItem.text, selectedItem.id, true, true);
							$select.append(option).trigger('change');
						}
					}
				});
			}

			// Update rule value on change
			$select.on('change', function () {
				if (ruleGroups[groupIndex] && ruleGroups[groupIndex].rules[ruleIndex]) {
					ruleGroups[groupIndex].rules[ruleIndex].value = $(this).val();
					updateRulePreview();
				}
			});
		}

		function initSelect2ForCPT(selectId, postType, selectedValue, groupIndex, ruleIndex) {
			const $select = $('#' + selectId);
			if (!$select.length) return;

			$select.select2({
				placeholder: 'Select an item...',
				allowClear: true,
				width: '100%',
				ajax: {
					url: WPRoboEngage.displayRulesPostsByTypeUrl + postType,
					dataType: 'json',
					delay: 250,
					headers: {
						'X-WP-Nonce': WPRoboEngage.nonce
					},
					processResults: function (data) {
						return {
							results: data
						};
					},
					cache: true
				}
			});

			// Set initial value if exists
			if (selectedValue) {
				$.ajax({
					url: WPRoboEngage.displayRulesPostsByTypeUrl + postType,
					headers: {
						'X-WP-Nonce': WPRoboEngage.nonce
					},
					success: function (data) {
						const selectedItem = data.find(item => item.id == selectedValue);
						if (selectedItem) {
							const option = new Option(selectedItem.text, selectedItem.id, true, true);
							$select.append(option).trigger('change');
						}
					}
				});
			}

			// Update rule value on change
			$select.on('change', function () {
				if (ruleGroups[groupIndex] && ruleGroups[groupIndex].rules[ruleIndex]) {
					ruleGroups[groupIndex].rules[ruleIndex].value = $(this).val();
					updateRulePreview();
				}
			});
		}

		// Select2 initialization functions for Simple Mode
		function initSelect2ForPagesSimple(selectId, selectedValue, ruleIndex) {
			const $select = $('#' + selectId);
			if (!$select.length) return;

			$select.select2({
				placeholder: 'Select a page...',
				allowClear: true,
				width: '100%',
				ajax: {
					url: WPRoboEngage.displayRulesPagesUrl,
					dataType: 'json',
					delay: 250,
					headers: {
						'X-WP-Nonce': WPRoboEngage.nonce
					},
					processResults: function (data) {
						return {
							results: data
						};
					},
					cache: true
				}
			});

			// Set initial value if exists
			if (selectedValue) {
				$.ajax({
					url: WPRoboEngage.displayRulesPagesUrl,
					headers: {
						'X-WP-Nonce': WPRoboEngage.nonce
					},
					success: function (data) {
						const selectedItem = data.find(item => item.id == selectedValue);
						if (selectedItem) {
							const option = new Option(selectedItem.text, selectedItem.id, true, true);
							$select.append(option).trigger('change');
						}
					}
				});
			}

			// Update rule value on change
			$select.on('change', function () {
				if (displayRules[ruleIndex]) {
					displayRules[ruleIndex].value = $(this).val();
				}
			});
		}

		function initSelect2ForPostsSimple(selectId, selectedValue, ruleIndex) {
			const $select = $('#' + selectId);
			if (!$select.length) return;

			$select.select2({
				placeholder: 'Select a post...',
				allowClear: true,
				width: '100%',
				ajax: {
					url: WPRoboEngage.displayRulesPostsUrl,
					dataType: 'json',
					delay: 250,
					headers: {
						'X-WP-Nonce': WPRoboEngage.nonce
					},
					processResults: function (data) {
						return {
							results: data
						};
					},
					cache: true
				}
			});

			// Set initial value if exists
			if (selectedValue) {
				$.ajax({
					url: WPRoboEngage.displayRulesPostsUrl,
					headers: {
						'X-WP-Nonce': WPRoboEngage.nonce
					},
					success: function (data) {
						const selectedItem = data.find(item => item.id == selectedValue);
						if (selectedItem) {
							const option = new Option(selectedItem.text, selectedItem.id, true, true);
							$select.append(option).trigger('change');
						}
					}
				});
			}

			// Update rule value on change
			$select.on('change', function () {
				if (displayRules[ruleIndex]) {
					displayRules[ruleIndex].value = $(this).val();
				}
			});
		}

		function initSelect2ForCategoriesSimple(selectId, selectedValue, ruleIndex) {
			const $select = $('#' + selectId);
			if (!$select.length) return;

			$select.select2({
				placeholder: 'Select a category...',
				allowClear: true,
				width: '100%',
				ajax: {
					url: WPRoboEngage.displayRulesCategoriesUrl,
					dataType: 'json',
					delay: 250,
					headers: {
						'X-WP-Nonce': WPRoboEngage.nonce
					},
					processResults: function (data) {
						return {
							results: data
						};
					},
					cache: true
				}
			});

			// Set initial value if exists
			if (selectedValue) {
				$.ajax({
					url: WPRoboEngage.displayRulesCategoriesUrl,
					headers: {
						'X-WP-Nonce': WPRoboEngage.nonce
					},
					success: function (data) {
						const selectedItem = data.find(item => item.id === selectedValue);
						if (selectedItem) {
							const option = new Option(selectedItem.text, selectedItem.id, true, true);
							$select.append(option).trigger('change');
						}
					}
				});
			}

			// Update rule value on change
			$select.on('change', function () {
				if (displayRules[ruleIndex]) {
					displayRules[ruleIndex].value = $(this).val();
				}
			});
		}

		function initSelect2ForTagsSimple(selectId, selectedValue, ruleIndex) {
			const $select = $('#' + selectId);
			if (!$select.length) return;

			$select.select2({
				placeholder: 'Select a tag...',
				allowClear: true,
				width: '100%',
				ajax: {
					url: WPRoboEngage.displayRulesTagsUrl,
					dataType: 'json',
					delay: 250,
					headers: {
						'X-WP-Nonce': WPRoboEngage.nonce
					},
					processResults: function (data) {
						return {
							results: data
						};
					},
					cache: true
				}
			});

			// Set initial value if exists
			if (selectedValue) {
				$.ajax({
					url: WPRoboEngage.displayRulesTagsUrl,
					headers: {
						'X-WP-Nonce': WPRoboEngage.nonce
					},
					success: function (data) {
						const selectedItem = data.find(item => item.id === selectedValue);
						if (selectedItem) {
							const option = new Option(selectedItem.text, selectedItem.id, true, true);
							$select.append(option).trigger('change');
						}
					}
				});
			}

			// Update rule value on change
			$select.on('change', function () {
				if (displayRules[ruleIndex]) {
					displayRules[ruleIndex].value = $(this).val();
				}
			});
		}


		// Add Show Rule button
		$('#wpr-add-show-rule').on('click', function () {
			addRule('show');
		});

		// Add Hide Rule button
		$('#wpr-add-hide-rule').on('click', function () {
			addRule('hide');
		});

		// Remove rule
		$(document).on('click', '.wpr-remove-rule', function () {
			const index = $(this).data('index');
			displayRules.splice(index, 1);
			renderDisplayRules();
		});

		// Update rule when type changes
		$(document).on('change', '.wpr-rule-type', function () {
			const index = $(this).data('index');
			const type = $(this).val();
			displayRules[index].type = type;
			displayRules[index].value = '';
			renderDisplayRules();
		});

		// Update rule value
		$(document).on('change keyup', '.wpr-rule-value', function () {
			const index = $(this).data('index');
			displayRules[index].value = $(this).val();
		});

		function addRule(action) {
			displayRules.push({
				type: 'all_pages',
				action: action,
				value: ''
			});
			renderDisplayRules();
		}

		function renderDisplayRules() {
			const container = $('#wpr-display-rules-container');
			container.empty();

			if (displayRules.length === 0) {
				// No redundant message — the Live Targeting Summary below handles the empty state.
				return;
			}

			displayRules.forEach(function (rule, index) {
				const isShow = rule.action === 'show';
				const borderColor = isShow ? 'wpr-border-green-300' : 'wpr-border-red-300';
				const bgColor = isShow ? 'wpr-bg-green-50' : 'wpr-bg-red-50';
				const actionLabel = isShow ? 'Show' : 'Hide';

				let ruleHtml = `
					<div class="wpr-rule-item wpr-p-4 wpr-border ${borderColor} ${bgColor} wpr-rounded-md wpr-space-y-3" style="padding: 16px; border: 1px solid; border-radius: 4px; margin-bottom: 12px;">
						<div class="wpr-flex wpr-justify-between wpr-items-center" style="display: flex; justify-content: space-between; align-items: center;">
							<span class="wpr-text-xs wpr-font-semibold wpr-uppercase ${isShow ? 'wpr-text-green-700' : 'wpr-text-red-700'}" style="font-size: 12px; font-weight: 600; text-transform: uppercase;">${actionLabel}</span>
							<button type="button" class="wpr-remove-rule wpr-text-gray-400 hover:wpr-text-gray-600" data-index="${index}" style="cursor: pointer; font-size: 20px;">
								<span class="wpr-text-xl">&times;</span>
							</button>
						</div>
						<div>
							<select class="wpr-rule-type wpr-w-full wpr-border wpr-border-gray-300 wpr-rounded-md wpr-p-2 wpr-text-sm" data-index="${index}" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px;">
								<optgroup label="Page/Post Targeting">
									<option value="all_pages" ${rule.type === 'all_pages' ? 'selected' : ''}>All Pages</option>
									<option value="homepage" ${rule.type === 'homepage' ? 'selected' : ''}>Homepage</option>
									<option value="specific_page" ${rule.type === 'specific_page' ? 'selected' : ''}>Specific Page</option>
									<option value="specific_post" ${rule.type === 'specific_post' ? 'selected' : ''}>Specific Post</option>
									<option value="all_posts" ${rule.type === 'all_posts' ? 'selected' : ''}>All Posts</option>
									<option value="url_contains" ${rule.type === 'url_contains' ? 'selected' : ''}>URL Contains</option>
									<option value="url_starts_with" ${rule.type === 'url_starts_with' ? 'selected' : ''}>URL Starts With</option>
									<option value="url_ends_with" ${rule.type === 'url_ends_with' ? 'selected' : ''}>URL Ends With</option>
									<option value="url_regex" ${rule.type === 'url_regex' ? 'selected' : ''}>URL Regex Pattern</option>
									<option value="post_category" ${rule.type === 'post_category' ? 'selected' : ''}>Post Category</option>
									<option value="post_tag" ${rule.type === 'post_tag' ? 'selected' : ''}>Post Tag</option>
									<option value="custom_post_type" ${rule.type === 'custom_post_type' ? 'selected' : ''}>Custom Post Type</option>
									<option value="archive_page" ${rule.type === 'archive_page' ? 'selected' : ''}>Archive Page</option>
									<option value="search_page" ${rule.type === 'search_page' ? 'selected' : ''}>Search Page</option>
									<option value="error_404" ${rule.type === 'error_404' ? 'selected' : ''}>404 Error Page</option>
								</optgroup>
								<optgroup label="User Targeting">
									<option value="user_logged_in" ${rule.type === 'user_logged_in' ? 'selected' : ''}>User Logged In</option>
									<option value="user_logged_out" ${rule.type === 'user_logged_out' ? 'selected' : ''}>User Logged Out</option>
									<option value="user_role" ${rule.type === 'user_role' ? 'selected' : ''}>User Has Role</option>
									<option value="user_not_role" ${rule.type === 'user_not_role' ? 'selected' : ''}>User Does NOT Have Role</option>
								</optgroup>
								<optgroup label="Referral Source">
									<option value="referral_direct" ${rule.type === 'referral_direct' ? 'selected' : ''}>Direct Traffic (No Referrer)</option>
									<option value="referral_search" ${rule.type === 'referral_search' ? 'selected' : ''}>From Search Engines</option>
									<option value="referral_social" ${rule.type === 'referral_social' ? 'selected' : ''}>From Social Media</option>
									<option value="referral_domain" ${rule.type === 'referral_domain' ? 'selected' : ''}>From Specific Domain</option>
									<option value="referral_utm" ${rule.type === 'referral_utm' ? 'selected' : ''}>UTM Parameters</option>
								</optgroup>
								<optgroup label="Device Type">
									<option value="device_desktop" ${rule.type === 'device_desktop' ? 'selected' : ''}>Desktop Only</option>
									<option value="device_mobile" ${rule.type === 'device_mobile' ? 'selected' : ''}>Mobile Only</option>
									<option value="device_tablet" ${rule.type === 'device_tablet' ? 'selected' : ''}>Tablet Only</option>
									<option value="device_browser" ${rule.type === 'device_browser' ? 'selected' : ''}>Specific Browser</option>
									<option value="device_os" ${rule.type === 'device_os' ? 'selected' : ''}>Specific OS</option>
								</optgroup>
								<optgroup label="Behavioral">
									<option value="time_on_site" ${rule.type === 'time_on_site' ? 'selected' : ''}>Time on Site</option>
									<option value="scroll_depth" ${rule.type === 'scroll_depth' ? 'selected' : ''}>Scroll Depth</option>
									<option value="page_views_session" ${rule.type === 'page_views_session' ? 'selected' : ''}>Page Views (Session)</option>
									<option value="page_views_lifetime" ${rule.type === 'page_views_lifetime' ? 'selected' : ''}>Page Views (Lifetime)</option>
									<option value="visitor_new" ${rule.type === 'visitor_new' ? 'selected' : ''}>New Visitor</option>
									<option value="visitor_returning" ${rule.type === 'visitor_returning' ? 'selected' : ''}>Returning Visitor</option>
								</optgroup>
								<optgroup label="Advanced">
									<option value="custom_js" ${rule.type === 'custom_js' ? 'selected' : ''}>Custom JavaScript Condition</option>
								</optgroup>
							</select>
						</div>
				`;

				// Add value input for rules that need it
				// Handle specific page with Select2
				if (rule.type === 'specific_page') {
					const uniqueId = `wpr-select2-simple-page-${index}`;
					ruleHtml += `
						<div>
							<select id="${uniqueId}" class="wpr-rule-value wpr-select2-simple-page wpr-w-full wpr-text-sm" data-index="${index}" style="width: 100%;"></select>
						</div>
					`;
					setTimeout(function () {
						initSelect2ForPagesSimple(uniqueId, rule.value, index);
					}, 100);
				}
				// Handle specific post with Select2
				else if (rule.type === 'specific_post') {
					const uniqueId = `wpr-select2-simple-post-${index}`;
					ruleHtml += `
						<div>
							<select id="${uniqueId}" class="wpr-rule-value wpr-select2-simple-post wpr-w-full wpr-text-sm" data-index="${index}" style="width: 100%;"></select>
						</div>
					`;
					setTimeout(function () {
						initSelect2ForPostsSimple(uniqueId, rule.value, index);
					}, 100);
				}
				// URL pattern rules
				else if (rule.type === 'url_contains' || rule.type === 'url_starts_with' || rule.type === 'url_ends_with') {
					const labels = {
						'url_contains': 'URL pattern to match',
						'url_starts_with': 'URL starting pattern',
						'url_ends_with': 'URL ending pattern'
					};
					ruleHtml += `
						<div>
							<input type="text" class="wpr-rule-value wpr-w-full wpr-border wpr-border-gray-300 wpr-rounded-md wpr-p-2 wpr-text-sm" 
								placeholder="${labels[rule.type]}" value="${rule.value || ''}" data-index="${index}" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px;">
						</div>
					`;
				}
				// Regex pattern
				else if (rule.type === 'url_regex') {
					ruleHtml += `
						<div>
							<input type="text" class="wpr-rule-value wpr-w-full wpr-border wpr-border-gray-300 wpr-rounded-md wpr-p-2 wpr-text-sm" 
								placeholder="Regular expression pattern" value="${rule.value || ''}" data-index="${index}" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px;">
							<p class="wpr-text-xs wpr-text-gray-500 wpr-mt-1" style="font-size: 11px; color: #6b7280; margin-top: 4px;">Example: /product/[0-9]+/</p>
						</div>
					`;
				}
				// Category with Select2
				else if (rule.type === 'post_category') {
					const uniqueId = `wpr-select2-simple-category-${index}`;
					ruleHtml += `
						<div>
							<select id="${uniqueId}" class="wpr-rule-value wpr-select2-simple-category wpr-w-full wpr-text-sm" data-index="${index}" style="width: 100%;"></select>
						</div>
					`;
					setTimeout(function () {
						initSelect2ForCategoriesSimple(uniqueId, rule.value, index);
					}, 100);
				}
				// Tag with Select2
				else if (rule.type === 'post_tag') {
					const uniqueId = `wpr-select2-simple-tag-${index}`;
					ruleHtml += `
						<div>
							<select id="${uniqueId}" class="wpr-rule-value wpr-select2-simple-tag wpr-w-full wpr-text-sm" data-index="${index}" style="width: 100%;"></select>
						</div>
					`;
					setTimeout(function () {
						initSelect2ForTagsSimple(uniqueId, rule.value, index);
					}, 100);
				}
				// Custom post type
				else if (rule.type === 'custom_post_type') {
					ruleHtml += `
						<div>
							<input type="text" class="wpr-rule-value wpr-w-full wpr-border wpr-border-gray-300 wpr-rounded-md wpr-p-2 wpr-text-sm" 
								placeholder="Post type slug" value="${rule.value || ''}" data-index="${index}" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px;">
						</div>
					`;
				}
				// Archive page type
				else if (rule.type === 'archive_page') {
					ruleHtml += `
						<div>
							<select class="wpr-rule-value wpr-w-full wpr-border wpr-border-gray-300 wpr-rounded-md wpr-p-2 wpr-text-sm" data-index="${index}" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px;">
								<option value="" ${!rule.value ? 'selected' : ''}>Any Archive</option>
								<option value="category" ${rule.value === 'category' ? 'selected' : ''}>Category Archive</option>
								<option value="tag" ${rule.value === 'tag' ? 'selected' : ''}>Tag Archive</option>
								<option value="author" ${rule.value === 'author' ? 'selected' : ''}>Author Archive</option>
								<option value="date" ${rule.value === 'date' ? 'selected' : ''}>Date Archive</option>
							</select>
						</div>
					`;
				}
				// User role
				else if (rule.type === 'user_role' || rule.type === 'user_not_role') {
					ruleHtml += `
						<div>
							<input type="text" class="wpr-rule-value wpr-w-full wpr-border wpr-border-gray-300 wpr-rounded-md wpr-p-2 wpr-text-sm" 
								placeholder="Role slug (e.g., subscriber, editor, administrator)" value="${rule.value || ''}" data-index="${index}" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px;">
							<p class="wpr-text-xs wpr-text-gray-500 wpr-mt-1" style="font-size: 11px; color: #6b7280; margin-top: 4px;">For multiple roles, separate with commas</p>
						</div>
					`;
				}
				// Referral domain
				else if (rule.type === 'referral_domain') {
					ruleHtml += `
						<div>
							<input type="text" class="wpr-rule-value wpr-w-full wpr-border wpr-border-gray-300 wpr-rounded-md wpr-p-2 wpr-text-sm" 
								placeholder="Domain (e.g., facebook.com, google.com)" value="${rule.value || ''}" data-index="${index}" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px;">
							<p class="wpr-text-xs wpr-text-gray-500 wpr-mt-1" style="font-size: 11px; color: #6b7280; margin-top: 4px;">For multiple domains, separate with commas</p>
						</div>
					`;
				}
				// UTM parameters (not implemented in UI for now - will be complex)
				else if (rule.type === 'referral_utm') {
					ruleHtml += `
						<div>
							<p class="wpr-text-xs wpr-text-gray-500" style="font-size: 12px; color: #6b7280;">UTM parameter matching (Advanced feature - coming soon)</p>
						</div>
					`;
				}
				// Device browser
				else if (rule.type === 'device_browser') {
					ruleHtml += `
						<div>
							<input type="text" class="wpr-rule-value wpr-w-full wpr-border wpr-border-gray-300 wpr-rounded-md wpr-p-2 wpr-text-sm" 
								placeholder="Browser (chrome, firefox, safari, edge)" value="${rule.value || ''}" data-index="${index}" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px;">
							<p class="wpr-text-xs wpr-text-gray-500 wpr-mt-1" style="font-size: 11px; color: #6b7280; margin-top: 4px;">For multiple browsers, separate with commas</p>
						</div>
					`;
				}
				// Device OS
				else if (rule.type === 'device_os') {
					ruleHtml += `
						<div>
							<input type="text" class="wpr-rule-value wpr-w-full wpr-border wpr-border-gray-300 wpr-rounded-md wpr-p-2 wpr-text-sm" 
								placeholder="OS (windows, macos, ios, android, linux)" value="${rule.value || ''}" data-index="${index}" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px;">
							<p class="wpr-text-xs wpr-text-gray-500 wpr-mt-1" style="font-size: 11px; color: #6b7280; margin-top: 4px;">For multiple OS, separate with commas</p>
						</div>
					`;
				}
				// Time on site
				else if (rule.type === 'time_on_site') {
					ruleHtml += `
						<div>
							<input type="number" class="wpr-rule-value wpr-w-full wpr-border wpr-border-gray-300 wpr-rounded-md wpr-p-2 wpr-text-sm" 
								placeholder="Seconds" value="${rule.value || ''}" data-index="${index}" min="0" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px;">
							<p class="wpr-text-xs wpr-text-gray-500 wpr-mt-1" style="font-size: 11px; color: #6b7280; margin-top: 4px;">Show after visitor has been on site for X seconds</p>
						</div>
					`;
				}
				// Scroll depth
				else if (rule.type === 'scroll_depth') {
					ruleHtml += `
						<div>
							<input type="number" class="wpr-rule-value wpr-w-full wpr-border wpr-border-gray-300 wpr-rounded-md wpr-p-2 wpr-text-sm" 
								placeholder="Percentage (0-100)" value="${rule.value || ''}" data-index="${index}" min="0" max="100" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px;">
							<p class="wpr-text-xs wpr-text-gray-500 wpr-mt-1" style="font-size: 11px; color: #6b7280; margin-top: 4px;">Show after visitor has scrolled X% of the page</p>
						</div>
					`;
				}
				// Page views session
				else if (rule.type === 'page_views_session') {
					ruleHtml += `
						<div>
							<input type="number" class="wpr-rule-value wpr-w-full wpr-border wpr-border-gray-300 wpr-rounded-md wpr-p-2 wpr-text-sm" 
								placeholder="Number of pages" value="${rule.value || ''}" data-index="${index}" min="1" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px;">
							<p class="wpr-text-xs wpr-text-gray-500 wpr-mt-1" style="font-size: 11px; color: #6b7280; margin-top: 4px;">Show after X page views in current session</p>
						</div>
					`;
				}
				// Page views lifetime
				else if (rule.type === 'page_views_lifetime') {
					ruleHtml += `
						<div>
							<input type="number" class="wpr-rule-value wpr-w-full wpr-border wpr-border-gray-300 wpr-rounded-md wpr-p-2 wpr-text-sm" 
								placeholder="Number of pages" value="${rule.value || ''}" data-index="${index}" min="1" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px;">
							<p class="wpr-text-xs wpr-text-gray-500 wpr-mt-1" style="font-size: 11px; color: #6b7280; margin-top: 4px;">Show after X total page views (lifetime)</p>
						</div>
					`;
				}
				// Custom JavaScript Condition
				else if (rule.type === 'custom_js') {
					ruleHtml += `
						<div>
							<textarea class="wpr-rule-value wpr-w-full wpr-border wpr-border-gray-300 wpr-rounded-md wpr-p-2 wpr-text-sm wpr-font-mono" 
								placeholder="return true; // Your JavaScript condition" data-index="${index}" rows="3" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px; font-family: monospace;">${rule.value || ''}</textarea>
							<p class="wpr-text-xs wpr-text-gray-500 wpr-mt-1" style="font-size: 11px; color: #6b7280; margin-top: 4px;">
								<strong>⚠️ Advanced:</strong> Write JavaScript that returns <code>true</code> or <code>false</code>. Example: <code>return window.location.pathname.includes('/shop/');</code>
							</p>
							<p class="wpr-text-xs wpr-text-orange-600 wpr-mt-1" style="font-size: 11px; color: #ea580c; margin-top: 4px;">
								Security: Code is sandboxed and cannot access sensitive data.
							</p>
						</div>
					`;
				}

				ruleHtml += `</div>`;
				container.append(ruleHtml);
			});
		}

		// --- Rule Templates ---
		// Load and render rule templates
		if (typeof wprRuleTemplates !== 'undefined') {
			renderRuleTemplates();
		}

		function renderRuleTemplates() {
			const container = $('#wpr-rule-templates-list');
			container.empty();

			const templates = wprRuleTemplates;
			const categories = {
				traffic: '🌐 Traffic',
				engagement: '⚡ Engagement',
				visitor: '👤 Visitor',
				user: '🔐 User',
				content: '📄 Content',
				device: '📱 Device'
			};

			// Group templates by category
			const grouped = {};
			Object.keys(templates).forEach(key => {
				const template = templates[key];
				const category = template.category;
				if (!grouped[category]) {
					grouped[category] = [];
				}
				grouped[category].push({ key: key, ...template });
			});

			// Render templates by category
			Object.keys(categories).forEach(categoryKey => {
				if (grouped[categoryKey] && grouped[categoryKey].length > 0) {
					grouped[categoryKey].forEach(template => {
						const templateBtn = $(`
							<button type="button" class="wpr-rule-template-btn wpr-text-left wpr-p-2 wpr-border wpr-border-gray-300 wpr-rounded wpr-text-xs hover:wpr-bg-blue-50 hover:wpr-border-blue-500 wpr-transition-colors" 
								data-template-key="${template.key}" 
								style="padding: 8px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 12px; cursor: pointer; text-align: left; background: white;">
								<div class="wpr-font-semibold wpr-mb-1" style="font-weight: 600; margin-bottom: 4px; color: #374151;">${template.name}</div>
								<div class="wpr-text-gray-500" style="color: #6b7280; font-size: 11px;">${template.description}</div>
							</button>
						`);
						container.append(templateBtn);
					});
				}
			});
		}

		// Apply template when clicked
		$(document).on('click', '.wpr-rule-template-btn', function () {
			const templateKey = $(this).data('template-key');
			const template = wprRuleTemplates[templateKey];

			if (template && template.rules) {
				// Replace existing rules with template rules
				displayRules = template.rules;
				renderDisplayRules();
				showToast(`Template "${template.name}" applied successfully!`, 'success');

				// Close the details element
				$('.wpr-rule-templates details').prop('open', false);
			}
		});

		// --- Visual Rule Preview ---
		function updateRulePreview() {
			const previewContainer = $('#wpr-rule-preview-content');

			// Check if we're in advanced mode
			const isAdvancedMode = $('#wpr-use-rule-groups').is(':checked');

			if (isAdvancedMode) {
				// Preview for rule groups
				if (ruleGroups.length === 0) {
					previewContainer.html('<p class="wpr-italic" style="color: #94a3b8;">' + WPRoboEngage.i18n.noRuleGroupsConfigured + '</p>');
					return;
				}

				const showGroups = ruleGroups.filter(g => g.action === 'show');
				const hideGroups = ruleGroups.filter(g => g.action === 'hide');

				let previewHtml = '';

				// Show groups preview
				if (showGroups.length > 0) {
					previewHtml += '<div style="margin-bottom: 20px;"><div style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px;"><span style="color: #059669; font-weight: 700; font-size: 11px; letter-spacing: 0.05em; text-transform: uppercase; background: #ecfdf5; padding: 2px 8px; border-radius: 4px;">✓ SHOW WHEN</span></div>';
					showGroups.forEach((group, index) => {
						const logicText = group.logic === 'and' ? 'ALL conditions must match' : 'ANY condition must match';
						previewHtml += `
							<div style="margin-left: 8px; margin-bottom: 16px; padding-left: 16px; border-left: 2px solid #e2e8f0;">
								<div style="font-weight: 700; color: #334155; font-size: 12px; margin-bottom: 6px;">Group ${index + 1} <span style="font-weight: 400; color: #64748b;">(${logicText})</span></div>
								<ul style="margin: 0; padding: 0; list-style: none;">`;
						if (group.rules && group.rules.length > 0) {
							group.rules.forEach(rule => {
								previewHtml += `<li style="display: flex; align-items: start; gap: 8px; margin-bottom: 4px; color: #475569;"><span style="color: #94a3b8; font-size: 14px; line-height: 1;">•</span> ${getRuleDescription(rule)}</li>`;
							});
						}
						previewHtml += '</ul></div>';
					});
					if (showGroups.length > 1) {
						previewHtml += '<p style="margin-left: 24px; font-size: 11px; color: #64748b; font-style: italic;">Matches show if ANY group matches (OR logic between groups)</p>';
					}
					previewHtml += '</div>';
				}

				// Hide groups preview
				if (hideGroups.length > 0) {
					previewHtml += '<div style="margin-bottom: 12px;"><div style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px;"><span style="color: #dc2626; font-weight: 700; font-size: 11px; letter-spacing: 0.05em; text-transform: uppercase; background: #fef2f2; padding: 2px 8px; border-radius: 4px;">✗ HIDE WHEN</span></div>';
					hideGroups.forEach((group, index) => {
						const logicText = group.logic === 'and' ? 'ALL conditions must match' : 'ANY condition must match';
						previewHtml += `
							<div style="margin-left: 8px; margin-bottom: 16px; padding-left: 16px; border-left: 2px solid #fecaca;">
								<div style="font-weight: 700; color: #334155; font-size: 12px; margin-bottom: 6px;">Group ${index + 1} <span style="font-weight: 400; color: #64748b;">(${logicText})</span></div>
								<ul style="margin: 0; padding: 0; list-style: none;">`;
						if (group.rules && group.rules.length > 0) {
							group.rules.forEach(rule => {
								previewHtml += `<li style="display: flex; align-items: start; gap: 8px; margin-bottom: 4px; color: #475569;"><span style="color: #fca5a5; font-size: 14px; line-height: 1;">•</span> ${getRuleDescription(rule)}</li>`;
							});
						}
						previewHtml += '</ul></div>';
					});
					if (hideGroups.length > 1) {
						previewHtml += '<p style="margin-left: 24px; font-size: 11px; color: #64748b; font-style: italic;">Campaign hidden if ANY group matches</p>';
					}
					previewHtml += '</div>';
				}

				// Priority note
				if (showGroups.length > 0 && hideGroups.length > 0) {
					previewHtml += '<div style="margin-top: 16px; padding-top: 12px; border-top: 1px solid #f1f5f9;"><p style="font-size: 11px; color: #64748b;"><strong>Note:</strong> Exclusion (Hide) rules always take priority over Show rules.</p></div>';
				}

				previewContainer.html(previewHtml);
			} else {
				// Preview for simple rules
				if (displayRules.length === 0) {
					previewContainer.html('<p class="wpr-italic" style="color: #94a3b8;">No rules configured. Campaign will show everywhere.</p>');
					return;
				}

				// Separate show and hide rules
				const showRules = displayRules.filter(r => r.action === 'show');
				const hideRules = displayRules.filter(r => r.action === 'hide');

				let previewHtml = '';

				// Show rules preview
				if (showRules.length > 0) {
					previewHtml += `<div style="margin-bottom: 20px;"><div style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px;"><span style="color: #059669; font-weight: 700; font-size: 11px; letter-spacing: 0.05em; text-transform: uppercase; background: #ecfdf5; padding: 2px 8px; border-radius: 4px;">✓ SHOW WHEN</span></div><ul style="margin: 0 0 0 8px; padding: 0; list-style: none;">`;
					showRules.forEach(rule => {
						previewHtml += `<li style="display: flex; align-items: start; gap: 8px; margin-bottom: 6px; color: #475569;"><span style="color: #059669; font-size: 14px; line-height: 1.2;">•</span> ${getRuleDescription(rule)}</li>`;
					});
					previewHtml += '</ul>';
					if (showRules.length > 1) {
						previewHtml += '<p style="margin-top: 8px; margin-left: 18px; font-size: 11px; color: #64748b; font-style: italic;">(Matches if ANY condition matches - OR logic)</p>';
					}
					previewHtml += '</div>';
				}

				// Hide rules preview
				if (hideRules.length > 0) {
					previewHtml += `<div style="margin-bottom: 12px;"><div style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px;"><span style="color: #dc2626; font-weight: 700; font-size: 11px; letter-spacing: 0.05em; text-transform: uppercase; background: #fef2f2; padding: 2px 8px; border-radius: 4px;">✗ HIDE WHEN</span></div><ul style="margin: 0 0 0 8px; padding: 0; list-style: none;">`;
					hideRules.forEach(rule => {
						previewHtml += `<li style="display: flex; align-items: start; gap: 8px; margin-bottom: 6px; color: #475569;"><span style="color: #dc2626; font-size: 14px; line-height: 1.2;">•</span> ${getRuleDescription(rule)}</li>`;
					});
					previewHtml += '</ul>';
					if (hideRules.length > 1) {
						previewHtml += '<p style="margin-top: 8px; margin-left: 18px; font-size: 11px; color: #64748b; font-style: italic;">(Campaign hidden if ANY condition matches)</p>';
					}
					previewHtml += '</div>';
				}

				// Priority note
				if (showRules.length > 0 && hideRules.length > 0) {
					previewHtml += '<div style="margin-top: 16px; padding-top: 12px; border-top: 1px solid #f1f5f9;"><p style="font-size: 11px; color: #64748b;"><strong>Note:</strong> Exclusion (Hide) rules always take priority over Show rules.</p></div>';
				}

				previewContainer.html(previewHtml);
			}
		}

		function getRuleDescription(rule) {
			const ruleDescriptions = {
				// Page/Post Targeting
				'all_pages': 'On all pages',
				'homepage': 'On homepage only',
				'specific_page': `On page ID: <span style="background: #f1f5f9; padding: 1px 4px; border-radius: 3px; font-family: monospace;">${rule.value || '(not set)'}</span>`,
				'specific_post': `On post ID: <span style="background: #f1f5f9; padding: 1px 4px; border-radius: 3px; font-family: monospace;">${rule.value || '(not set)'}</span>`,
				'all_posts': 'On all blog posts',
				'url_contains': `URL contains: <span style="color: #3b82f6;">"${rule.value || '(not set)'}"</span>`,
				'url_starts_with': `URL starts with: <span style="color: #3b82f6;">"${rule.value || '(not set)'}"</span>`,
				'url_ends_with': `URL ends with: <span style="color: #3b82f6;">"${rule.value || '(not set)'}"</span>`,
				'url_regex': `URL matches pattern: <code style="background: #f8fafc; color: #ec4899; padding: 1px 4px; border-radius: 3px; font-size: 11px;">${rule.value || '(not set)'}</code>`,
				'post_category': `Posts in category: <span style="font-weight: 600;">${rule.value || '(not set)'}</span>`,
				'post_tag': `Posts with tag: <span style="font-weight: 600;">${rule.value || '(not set)'}</span>`,
				'custom_post_type': `Custom post type: <span style="font-weight: 600;">${rule.value || '(not set)'}</span>`,
				'archive_page': rule.value && typeof rule.value === 'string' ? `<span style="font-weight: 600;">${rule.value.charAt(0).toUpperCase() + rule.value.slice(1)}</span> archive pages` : 'All archive pages',
				'search_page': 'On search results page',
				'error_404': 'On 404 error page',

				// User Targeting
				'user_logged_in': 'User is logged in',
				'user_logged_out': 'User is logged out (guest)',
				'user_role': `User has role: ${rule.value || '(not set)'}`,
				'user_not_role': `User does NOT have role: ${rule.value || '(not set)'}`,

				// Referral Source
				'referral_direct': 'Direct traffic (no referrer)',
				'referral_search': 'From search engines',
				'referral_social': 'From social media',
				'referral_domain': `From domain: ${rule.value || '(not set)'}`,
				'referral_utm': 'Has UTM parameters',

				// Device Type
				'device_desktop': 'On desktop devices',
				'device_mobile': 'On mobile devices',
				'device_tablet': 'On tablets',
				'device_browser': `Browser: ${rule.value || '(not set)'}`,
				'device_os': `Operating system: ${rule.value || '(not set)'}`,

				// Behavioral
				'time_on_site': `After ${rule.value || '(not set)'} seconds on site`,
				'scroll_depth': `After scrolling ${rule.value || '(not set)'}% of page`,
				'page_views_session': `After ${rule.value || '(not set)'} page views (session)`,
				'page_views_lifetime': `After ${rule.value || '(not set)'} total page views`,
				'visitor_new': 'First-time visitors only',
				'visitor_returning': 'Returning visitors only',

				// Custom JavaScript
				'custom_js': `Custom JS condition: ${rule.value && typeof rule.value === 'string' ? (rule.value.substring(0, 50) + '...') : '(not set)'}`
			};

			return ruleDescriptions[rule.type] || `${rule.type}: ${rule.value}`;
		}

		// Update preview whenever rules change
		const originalRenderDisplayRules = renderDisplayRules;
		renderDisplayRules = function () {
			originalRenderDisplayRules();
			updateRulePreview();
		};

		// Initial preview
		updateRulePreview();

		// --- Save functionality via AJAX when WordPress publishes/updates ---
		// Hook into WordPress's post save
		$('#publish, #save-post').on('click', function () {
			var $btn = $(this);
			$btn.data('wpr-original-value', $btn.val());
			$btn.val('Saving...').addClass('wpr-btn-loading');
			// Sync schedule fields to hidden inputs for form submission
			$('#wpr_engage_schedule_enabled_hidden').val($('#wpr-schedule-enabled').is(':checked') ? '1' : '0');
			$('#wpr_engage_schedule_start_date_hidden').val($('#wpr-schedule-start-date').val());
			$('#wpr_engage_schedule_start_time_hidden').val($('#wpr-schedule-start-time').val());
			$('#wpr_engage_schedule_end_date_hidden').val($('#wpr-schedule-end-date').val());
			$('#wpr_engage_schedule_end_time_hidden').val($('#wpr-schedule-end-time').val());
			$('#wpr_engage_schedule_time_range_enabled_hidden').val($('#wpr-schedule-time-range-enabled').is(':checked') ? '1' : '0');
			$('#wpr_engage_schedule_time_start_hidden').val($('#wpr-schedule-time-start').val());
			$('#wpr_engage_schedule_time_end_hidden').val($('#wpr-schedule-time-end').val());

			// Sync days of week
			$('.wpr-schedule-day-hidden').remove();
			$('.wpr-schedule-day:checked').each(function () {
				$('<input>').attr({
					type: 'hidden',
					name: 'wpr_engage_schedule_days_of_week[]',
					value: $(this).val(),
					class: 'wpr-schedule-day-hidden'
				}).appendTo('#wpr-tab-schedule');
			});

			// Save display rules via AJAX just before the post saves
			if (typeof WPRoboEngage !== 'undefined' && WPRoboEngage.campaignId > 0) {
				// Get trigger data
				const triggerType = $('#wpr-trigger-type').val();
				let triggerValue = '';

				if (triggerType === 'timed_delay') {
					triggerValue = $('#wpr-trigger-value-delay').val();
				} else if (triggerType === 'scroll_depth') {
					triggerValue = $('#wpr-trigger-value-scroll').val();
				}

				const data = {
					headline: $('#wpr-headline').val(),
					content: $('#wpr-content').val(),
					button: $('#wpr-button').val(),
					emailPlaceholder: $('#wpr-email-placeholder').val(),
					displayRules: $('#wpr-use-rule-groups').is(':checked') ? [] : displayRules,
					ruleGroups: $('#wpr-use-rule-groups').is(':checked') ? ruleGroups : [],
					bgColor: $('#wpr-bg-color').val(),
					headlineColor: $('#wpr-headline-color').val(),
					contentColor: $('#wpr-content-color').val(),
					buttonBgColor: $('#wpr-button-bg-color').val(),
					buttonTextColor: $('#wpr-button-text-color').val(),
					triggerType: triggerType,
					triggerValue: triggerValue,
					borderRadius: $('#wpr-border-radius').val(),
					borderWidth: $('#wpr-border-width').val(),
					borderColor: $('#wpr-border-color').val(),
					boxShadowEnabled: $('#wpr-box-shadow-enabled').is(':checked') ? '1' : '0',
					boxShadowColor: $('#wpr-box-shadow-color').val(),
					boxShadowX: $('#wpr-box-shadow-x').val(),
					boxShadowY: $('#wpr-box-shadow-y').val(),
					boxShadowBlur: $('#wpr-box-shadow-blur').val(),
					boxShadowSpread: $('#wpr-box-shadow-spread').val(),
					bgImageUrl: $('#wpr-bg-image-url').val(),
					bgImageRepeat: $('#wpr-bg-image-repeat').val(),
					bgImagePosition: $('#wpr-bg-image-position').val(),
					bgImageSize: $('#wpr-bg-image-size').val(),
					bgMediaType: $('#wpr-bg-media-type').val(),
					bgVideoUrl: $('#wpr-bg-video-url').val(),
					bgVideoAutoplay: $('#wpr-bg-video-autoplay').is(':checked') ? '1' : '0',
					bgVideoLoop: $('#wpr-bg-video-loop').is(':checked') ? '1' : '0',
					bgVideoMuted: $('#wpr-bg-video-muted').is(':checked') ? '1' : '0',
					bgYoutubeUrl: $('#wpr-bg-youtube-url').val(),
					bgVimeoUrl: $('#wpr-bg-vimeo-url').val(),
					closeBtnColor: $('#wpr-close-btn-color').val(),
					closeBtnHoverColor: $('#wpr-close-btn-hover-color').val(),
					closeBtnBgColor: $('#wpr-close-btn-bg-color').val(),
					closeBtnShape: $('#wpr-close-btn-shape').val(),
					escToClose: $('#wpr-esc-to-close').is(':checked') ? '1' : '0',
					showCloseIcon: $('#wpr-show-close-icon').is(':checked') ? '1' : '0',
					successAction: $('input[name="wpr-success-action"]:checked').val(),
					successRedirectUrl: $('#wpr-success-redirect-url').val(),
					successMessageHeadline: $('#wpr-success-message-headline').val(),
					successMessageContent: $('#wpr-success-message-content').val(),
					successAutoClose: $('#wpr-success-auto-close').is(':checked') ? '1' : '0',
					successAutoCloseDelay: $('#wpr-success-auto-close-delay').val(),
					successRedirectDelay: $('#wpr-success-redirect-delay').val(),
					successRedirectNewTab: $('#wpr-success-redirect-new-tab').is(':checked') ? '1' : '0',
					successShowIcon: $('#wpr-success-show-icon').is(':checked') ? '1' : '0',
					successIconType: $('#wpr-success-icon-type').val(),
					successIconColor: $('#wpr-success-icon-color').val(),
					successTitleColor: $('#wpr-success-title-color').val(),
					successContentColor: $('#wpr-success-content-color').val(),
					successTitleFontSize: $('#wpr-success-title-font-size').val(),
					successContentFontSize: $('#wpr-success-content-font-size').val(),
					successTitleFontWeight: $('#wpr-success-title-font-weight').val(),
					successContentFontWeight: $('#wpr-success-content-font-weight').val(),
					scheduleEnabled: $('#wpr-schedule-enabled').is(':checked') ? '1' : '0',
					scheduleStartDate: $('#wpr-schedule-start-date').val(),
					scheduleStartTime: $('#wpr-schedule-start-time').val(),
					scheduleEndDate: $('#wpr-schedule-end-date').val(),
					scheduleEndTime: $('#wpr-schedule-end-time').val(),
					scheduleDaysOfWeek: $('.wpr-schedule-day:checked').map(function () { return $(this).val(); }).get(),
					scheduleTimeRangeEnabled: $('#wpr-schedule-time-range-enabled').is(':checked') ? '1' : '0',
					scheduleTimeStart: $('#wpr-schedule-time-start').val(),
					scheduleTimeEnd: $('#wpr-schedule-time-end').val()
				};

				// Save via REST API
				$.ajax({
					url: WPRoboEngage.apiUrl + WPRoboEngage.campaignId,
					method: 'POST',
					async: false, // Make it synchronous so it completes before post save
					beforeSend: function (xhr) {
						xhr.setRequestHeader('X-WP-Nonce', WPRoboEngage.nonce);
					},
					contentType: 'application/json',
					data: JSON.stringify(data)
				})
					.fail(function (response) {
						console.error('Failed to save campaign data:', response);
					});
			}
		});

		// --- Preview state toggle (Default / Success) ---
		$('[data-preview-state]').on('click', function () {
			var state = $(this).data('preview-state');
			var $defaultEls = $('#wpr-preview-headline, #wpr-preview-content, #wpr-preview-email, #wpr-preview-button, .wpr-timer-preview-container').closest('div:not(#wpr-preview-wrapper)').first().parent().find('#wpr-preview-headline, #wpr-preview-content, .wpr-mt-6, .wpr-timer-preview-container');

			// Toggle the main content vs success state
			if (state === 'success') {
				$('#wpr-preview-wrapper').find('#wpr-preview-headline, #wpr-preview-content').hide();
				$('#wpr-preview-wrapper').find('.wpr-mt-6').first().hide();
				$('.wpr-timer-preview-container').hide();
				$('#wpr-preview-success-state').show();
			} else {
				$('#wpr-preview-wrapper').find('#wpr-preview-headline, #wpr-preview-content').show();
				$('#wpr-preview-wrapper').find('.wpr-mt-6').first().show();
				$('#wpr-preview-success-state').hide();
			}

			// Update toggle button styles
			$('[data-preview-state]').css({ background: 'transparent', color: '#64748b' });
			$(this).css({ background: '#3b82f6', color: '#ffffff' });
		});

		// Update success preview when Success tab fields change
		$('#wpr-success-message-headline').on('input', function () {
			$('#wpr-preview-success-headline').text($(this).val() || 'Thank you!');
		});
		$('#wpr-success-message-content').on('input', function () {
			$('#wpr-preview-success-content').text($(this).val() || 'Your subscription has been confirmed.');
		});
		$('#wpr-success-title-color').on('colorchange', function (e, color) {
			$('#wpr-preview-success-headline').css('color', color);
		});
		$('#wpr-success-content-color').on('colorchange', function (e, color) {
			$('#wpr-preview-success-content').css('color', color);
		});
		$('#wpr-success-icon-color').on('colorchange', function (e, color) {
			$('#wpr-preview-success-icon').css('color', color);
		});
		// Icon type live preview
		$('#wpr-success-icon-type').on('change', function () {
			var iconMap = {
				'checkmark': '✓',
				'star': '★',
				'heart': '♥',
				'thumbs-up': '👍',
				'celebration': '🎉'
			};
			$('#wpr-preview-success-icon').text(iconMap[$(this).val()] || '✓');
		});
		// Show/hide icon toggle
		$('#wpr-success-show-icon').on('change', function () {
			$('#wpr-preview-success-icon')[$(this).is(':checked') ? 'show' : 'hide']();
		});
		// Font size live preview
		$('#wpr-success-title-font-size').on('input change', function () {
			$('#wpr-preview-success-headline').css('font-size', $(this).val() + 'px');
		});
		$('#wpr-success-content-font-size').on('input change', function () {
			$('#wpr-preview-success-content').css('font-size', $(this).val() + 'px');
		});
		// Font weight live preview
		$('#wpr-success-title-font-weight').on('change', function () {
			$('#wpr-preview-success-headline').css('font-weight', $(this).val());
		});
		$('#wpr-success-content-font-weight').on('change', function () {
			$('#wpr-preview-success-content').css('font-weight', $(this).val());
		});
		$('#wpr-discount-code').on('input', function () {
			var code = $(this).val();
			$('#wpr-preview-discount-code').text(code || '');
			$('#wpr-preview-discount-wrapper')[code ? 'show' : 'hide']();
		});
		$('#wpr-discount-code-label').on('input', function () {
			$('#wpr-preview-discount-label').text($(this).val() || 'Use discount code:');
		});

		// --- Keyboard Shortcuts ---
		$(document).on('keydown', function (e) {
			if (!$('.wpr-builder-content').length) return;

			var isMac = navigator.platform.indexOf('Mac') > -1;
			var mod = isMac ? e.metaKey : e.ctrlKey;

			if (!mod) return;

			// Ctrl+S / Cmd+S — Save campaign
			if (e.key === 's' || e.key === 'S') {
				e.preventDefault();
				$('#publish').trigger('click');
				return;
			}

			// Ctrl+1 through Ctrl+9 — Tab navigation
			var tabKeys = ['1', '2', '3', '4', '5', '6', '7', '8', '9'];
			var keyIndex = tabKeys.indexOf(e.key);
			if (keyIndex > -1) {
				e.preventDefault();
				var $tabs = $tablist.find('.wpr-tab-button');
				if (keyIndex < $tabs.length) {
					$tabs.eq(keyIndex).trigger('click');
				}
			}
		});

		// Escape — Close any open modal or picker
		$(document).on('keydown', function (e) {
			if (e.key !== 'Escape') return;
			$('.wpr-field-type-picker').remove();
		});

		// --- Real-time preview functionality ---
		const $headlineInput = $('#wpr-headline');
		const $contentInput = $('#wpr-content');
		const $buttonInput = $('#wpr-button');
		const $emailPlaceholderInput = $('#wpr-email-placeholder');

		const $headlinePreview = $('#wpr-preview-headline');
		const $contentPreview = $('#wpr-preview-content');
		let $buttonPreview = $('#wpr-preview-button');
		let $emailPreview = $('#wpr-preview-email');
		const $customCssField = $('#wpr-custom-css');
		let customCssEditor = null;

		// Function to update the preview from an input
		function updatePreview(input, previewElement) {
			const text = input.val();
			previewElement.text(text);
		}

		// Function to update email placeholder
		function updateEmailPlaceholder(input, previewElement) {
			const text = input.val() || 'Enter your email';
			previewElement.attr('placeholder', text);
		}

		// Set initial state on page load
		updatePreview($headlineInput, $headlinePreview);
		updatePreview($contentInput, $contentPreview);
		updatePreview($buttonInput, $buttonPreview);
		updateEmailPlaceholder($emailPlaceholderInput, $emailPreview);

		// Add real-time listeners
		$headlineInput.on('input change keyup', function () {
			updatePreview($(this), $headlinePreview);
		});

		$contentInput.on('input change keyup', function () {
			updatePreview($(this), $contentPreview);
		});

		$buttonInput.on('input change keyup', function () {
			updatePreview($(this), $buttonPreview);
		});

		$emailPlaceholderInput.on('input change keyup', function () {
			updateEmailPlaceholder($(this), $emailPreview);
		});

		// --- Color picker live preview ---
		const $previewWrapper = $('#wpr-preview-wrapper');
		const $previewClose = $('#wpr-preview-close');

		function setStyleWithImportant($element, property, value) {
			if (!$element.length || !value) {
				return;
			}

			const element = $element.get(0);
			if (element && element.style && typeof element.style.setProperty === 'function') {
				element.style.setProperty(property, value, 'important');
			} else {
				$element.css(property, value);
			}
		}

		// Background color
		$('#wpr-bg-color').on('colorchange change input', function (event, color) {
			const selectedColor = color || $(this).val();
			if (!selectedColor) {
				return;
			}

			$previewWrapper.css('background-color', selectedColor);
		});

		// Headline color
		$('#wpr-headline-color').on('colorchange change input', function (event, color) {
			const selectedColor = color || $(this).val();
			if (!selectedColor) {
				return;
			}

			setStyleWithImportant($headlinePreview, 'color', selectedColor);
		});

		// Content color
		$('#wpr-content-color').on('colorchange change input', function (event, color) {
			const selectedColor = color || $(this).val();
			if (!selectedColor) {
				return;
			}

			setStyleWithImportant($contentPreview, 'color', selectedColor);
		});

		// Button background color
		$('#wpr-button-bg-color').on('colorchange change input', function (event, color) {
			const selectedColor = color || $(this).val();
			if (!selectedColor) {
				return;
			}

			setStyleWithImportant($buttonPreview, 'background-color', selectedColor);
			setStyleWithImportant($buttonPreview, 'border-color', selectedColor);
		});

		// Button text color
		$('#wpr-button-text-color').on('colorchange change input', function (event, color) {
			const selectedColor = color || $(this).val();
			if (!selectedColor) {
				return;
			}

			setStyleWithImportant($buttonPreview, 'color', selectedColor);
		});

		// Border color
		$('#wpr-border-color').on('colorchange', function (event, color) {
			updateBorder();
		});

		// Close button color
		$('#wpr-close-btn-color').on('colorchange', function (event, color) {
			$previewClose.css('color', color);
		});

		// Close button background color
		$('#wpr-close-btn-bg-color').on('colorchange', function (event, color) {
			$previewClose.css('background-color', color);
		});

		// Close button hover color - add hover simulation
		$('#wpr-close-btn-hover-color').on('colorchange', function (event, color) {
			// Store the hover color for future reference
			$previewClose.data('hover-color', color);
		});

		// Close button shape
		$('#wpr-close-btn-shape').on('change', function () {
			var shape = $(this).val();
			var radius = '4px';
			if (shape === 'square') { radius = '0'; }
			else if (shape === 'circle') { radius = '50%'; }
			$previewClose.css('border-radius', radius);
		});

		// Add hover effect to preview close button
		$previewClose.hover(
			function () {
				const hoverColor = $(this).data('hover-color') || $('#wpr-close-btn-hover-color').val();
				if (hoverColor) {
					$(this).css('color', hoverColor);
				}
			},
			function () {
				const normalColor = $('#wpr-close-btn-color').val() || '#6b7280';
				$(this).css('color', normalColor);
			}
		);

		// Border radius and width
		$('#wpr-border-radius, #wpr-border-width').on('input change', function () {
			updateBorder();
		});

		// Box shadow inputs
		$('#wpr-box-shadow-color').on('colorchange', function (event, color) {
			updateBoxShadow();
		});

		$('#wpr-box-shadow-x, #wpr-box-shadow-y, #wpr-box-shadow-blur, #wpr-box-shadow-spread').on('input change', function () {
			updateBoxShadow();
		});

		// Background media type switcher
		$('#wpr-bg-media-type').on('change', function () {
			const mediaType = $(this).val();

			// Hide all media settings
			$('#wpr-bg-image-settings').hide();
			$('#wpr-bg-video-settings').hide();
			$('#wpr-bg-youtube-settings').hide();
			$('#wpr-bg-vimeo-settings').hide();

			// Show relevant settings
			if (mediaType === 'image' || mediaType === '') {
				$('#wpr-bg-image-settings').show();
				$previewWrapper.css('overflow', '');
				updateBackgroundImage();
			} else if (mediaType === 'video') {
				$('#wpr-bg-video-settings').show();
				$previewWrapper.css('overflow', 'hidden');
				updateBackgroundVideo();
			} else if (mediaType === 'youtube') {
				$('#wpr-bg-youtube-settings').show();
				$previewWrapper.css('overflow', 'hidden');
				updateBackgroundYouTube();
			} else if (mediaType === 'vimeo') {
				$('#wpr-bg-vimeo-settings').show();
				$previewWrapper.css('overflow', 'hidden');
				updateBackgroundVimeo();
			} else if (mediaType === 'none') {
				// Remove all backgrounds
				$previewWrapper.css({'background-image': 'none', 'overflow': ''});
				$previewWrapper.find('.wpr-bg-video').remove();
			}
		});

		// Background image
		$('#wpr-bg-image-url, #wpr-bg-image-repeat, #wpr-bg-image-position, #wpr-bg-image-size').on('input change', function () {
			updateBackgroundImage();
		});

		// Background video
		$('#wpr-bg-video-url, #wpr-bg-video-autoplay, #wpr-bg-video-loop, #wpr-bg-video-muted').on('input change', function () {
			updateBackgroundVideo();
		});

		// Background YouTube
		$('#wpr-bg-youtube-url').on('input change', function () {
			updateBackgroundYouTube();
		});

		// Background Vimeo
		$('#wpr-bg-vimeo-url').on('input change', function () {
			updateBackgroundVimeo();
		});

		// Helper functions for complex style updates
		function updateBorder() {
			const radius = $('#wpr-border-radius').val() || '8';
			const width = $('#wpr-border-width').val() || '0';
			const color = $('#wpr-border-color').val() || '#d1d5db';

			$previewWrapper.css({
				'border-radius': radius + 'px',
				'border-width': width + 'px',
				'border-style': 'solid',
				'border-color': color
			});
		}

		function updateBoxShadow() {
			if ($('#wpr-box-shadow-enabled').is(':checked')) {
				const x = $('#wpr-box-shadow-x').val() || '0';
				const y = $('#wpr-box-shadow-y').val() || '10';
				const blur = $('#wpr-box-shadow-blur').val() || '15';
				const spread = $('#wpr-box-shadow-spread').val() || '-3';
				const color = $('#wpr-box-shadow-color').val() || '#000000';

				const shadow = `${x}px ${y}px ${blur}px ${spread}px ${color}`;
				$previewWrapper.css('box-shadow', shadow);
			} else {
				$previewWrapper.css('box-shadow', 'none');
			}
		}

		function updateBackgroundImage() {
			const url = $('#wpr-bg-image-url').val();
			const repeat = $('#wpr-bg-image-repeat').val() || 'no-repeat';
			const position = $('#wpr-bg-image-position').val() || 'center';
			const size = $('#wpr-bg-image-size').val() || 'cover';

			// Remove any video backgrounds
			$previewWrapper.find('.wpr-bg-video').remove();

			if (url) {
				$previewWrapper.css({
					'background-image': 'url(' + url + ')',
					'background-repeat': repeat,
					'background-position': position,
					'background-size': size
				});
			} else {
				$previewWrapper.css('background-image', 'none');
			}
		}

		function updateBackgroundVideo() {
			const url = $('#wpr-bg-video-url').val();

			// Remove existing video and background image
			$previewWrapper.find('.wpr-bg-video').remove();
			$previewWrapper.css('background-image', 'none');

			if (url) {
				const autoplay = $('#wpr-bg-video-autoplay').is(':checked');
				const loop = $('#wpr-bg-video-loop').is(':checked');
				const muted = $('#wpr-bg-video-muted').is(':checked');

				const videoHtml = `
					<video class="wpr-bg-video" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; z-index: 0; pointer-events: none;" 
						${autoplay ? 'autoplay' : ''} 
						${loop ? 'loop' : ''} 
						${muted ? 'muted' : ''} 
						playsinline>
						<source src="${url}" type="video/mp4">
					</video>
				`;
				$previewWrapper.prepend(videoHtml);
				// Ensure content is above video
				$previewWrapper.css('position', 'relative');
				$previewWrapper.children().not('.wpr-bg-video').css('position', 'relative').css('z-index', '1');
			}
		}

		function updateBackgroundYouTube() {
			const url = $('#wpr-bg-youtube-url').val();

			// Remove existing video and background image
			$previewWrapper.find('.wpr-bg-video').remove();
			$previewWrapper.css('background-image', 'none');

			if (url) {
				// Extract YouTube video ID
				let videoId = '';
				const regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|\&v=)([^#\&\?]*).*/;
				const match = url.match(regExp);
				if (match && match[2].length === 11) {
					videoId = match[2];
				}

				if (videoId) {
					const iframeHtml = `
						<iframe class="wpr-bg-video" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 0; pointer-events: none; border: 0;"
							src="https://www.youtube.com/embed/${videoId}?autoplay=1&loop=1&mute=1&controls=0&showinfo=0&rel=0&modestbranding=1&playlist=${videoId}"
							frameborder="0"
							allow="autoplay; encrypted-media"
							allowfullscreen>
						</iframe>
					`;
					$previewWrapper.prepend(iframeHtml);
					// Ensure content is above video
					$previewWrapper.css('position', 'relative');
					$previewWrapper.children().not('.wpr-bg-video').css('position', 'relative').css('z-index', '1');
				}
			}
		}

		function updateBackgroundVimeo() {
			const url = $('#wpr-bg-vimeo-url').val();

			// Remove existing video and background image
			$previewWrapper.find('.wpr-bg-video').remove();
			$previewWrapper.css('background-image', 'none');

			if (url) {
				// Extract Vimeo video ID
				let videoId = '';
				const regExp = /vimeo.com\/(\d+)/;
				const match = url.match(regExp);
				if (match && match[1]) {
					videoId = match[1];
				}

				if (videoId) {
					const iframeHtml = `
						<iframe class="wpr-bg-video" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 0; pointer-events: none; border: 0;"
							src="https://player.vimeo.com/video/${videoId}?autoplay=1&loop=1&muted=1&background=1"
							frameborder="0"
							allow="autoplay; fullscreen"
							allowfullscreen>
						</iframe>
					`;
					$previewWrapper.prepend(iframeHtml);
					// Ensure content is above video
					$previewWrapper.css('position', 'relative');
					$previewWrapper.children().not('.wpr-bg-video').css('position', 'relative').css('z-index', '1');
				}
			}
		}

		function hexToRgba(hex, alpha) {
			const r = parseInt(hex.slice(1, 3), 16);
			const g = parseInt(hex.slice(3, 5), 16);
			const b = parseInt(hex.slice(5, 7), 16);
			return `rgba(${r}, ${g}, ${b}, ${alpha})`;
		}

		// Set initial colors on page load
		if ($('#wpr-bg-color').val()) {
			$previewWrapper.css('background-color', $('#wpr-bg-color').val());
		}
		if ($('#wpr-headline-color').val()) {
			setStyleWithImportant($headlinePreview, 'color', $('#wpr-headline-color').val());
		}
		if ($('#wpr-content-color').val()) {
			setStyleWithImportant($contentPreview, 'color', $('#wpr-content-color').val());
		}
		if ($('#wpr-button-bg-color').val()) {
			setStyleWithImportant($buttonPreview, 'background-color', $('#wpr-button-bg-color').val());
			setStyleWithImportant($buttonPreview, 'border-color', $('#wpr-button-bg-color').val());
		}
		if ($('#wpr-button-text-color').val()) {
			setStyleWithImportant($buttonPreview, 'color', $('#wpr-button-text-color').val());
		}
		if ($('#wpr-close-btn-color').val()) {
			$previewClose.css('color', $('#wpr-close-btn-color').val());
		}
		if ($('#wpr-close-btn-bg-color').val()) {
			$previewClose.css('background-color', $('#wpr-close-btn-bg-color').val());
		}

		// Set initial hover color
		if ($('#wpr-close-btn-hover-color').val()) {
			$previewClose.data('hover-color', $('#wpr-close-btn-hover-color').val());
		}

		// Set initial show/hide state of close button
		if (!$('#wpr-show-close-icon').is(':checked')) {
			$previewClose.hide();
		}

		// Initialize border, shadow and background
		updateBorder();
		updateBoxShadow();

		// Initialize background based on media type
		const initialMediaType = $('#wpr-bg-media-type').val();
		if (initialMediaType === 'video') {
			$('#wpr-bg-video-settings').show();
			$('#wpr-bg-image-settings').hide();
			$previewWrapper.css('overflow', 'hidden');
			updateBackgroundVideo();
		} else if (initialMediaType === 'youtube') {
			$('#wpr-bg-youtube-settings').show();
			$('#wpr-bg-image-settings').hide();
			$previewWrapper.css('overflow', 'hidden');
			updateBackgroundYouTube();
		} else if (initialMediaType === 'vimeo') {
			$('#wpr-bg-vimeo-settings').show();
			$('#wpr-bg-image-settings').hide();
			$previewWrapper.css('overflow', 'hidden');
			updateBackgroundVimeo();
		} else if (initialMediaType === 'image' || initialMediaType === '') {
			$('#wpr-bg-image-settings').show();
			updateBackgroundImage();
		}

		// --- Copy embed code to clipboard ---
		$('#wpr-copy-embed-code').on('click', function () {
			const $button = $(this);
			const $textarea = $('#wpr-embed-code');
			const originalText = $button.text();

			// Select and copy the text
			$textarea.select();
			document.execCommand('copy');

			// Show success feedback
			$button.text('Copied!').addClass('wpr-bg-green-500').removeClass('wpr-bg-blue-500');

			// Reset button after 2 seconds
			setTimeout(function () {
				$button.text(originalText).removeClass('wpr-bg-green-500').addClass('wpr-bg-blue-500');
			}, 2000);
		});


		// --- Success Tab Functionality ---
		
		$('input[name="wpr-success-action"]').on('change', function () {
			const action = $(this).val();

			if (action === 'message') {
				$('#wpr-success-message-config').show();
				$('#wpr-redirect-url-container').hide();
			} else {
				$('#wpr-success-message-config').hide();
				$('#wpr-redirect-url-container').show();
			}
		});

		// Toggle auto-close delay field
		$('#wpr-success-auto-close').on('change', function () {
			if ($(this).is(':checked')) {
				$('#wpr-auto-close-delay-container').show();
			} else {
				$('#wpr-auto-close-delay-container').hide();
			}
		});

		// Toggle success icon options
		$('#wpr-success-show-icon').on('change', function () {
			if ($(this).is(':checked')) {
				$('#wpr-success-icon-options').css('display', 'grid');
			} else {
				$('#wpr-success-icon-options').hide();
			}
		});

		// Initialize Success UI states
		if ($('#wpr-success-auto-close').is(':checked')) {
			$('#wpr-auto-close-delay-container').show();
		}
		if ($('#wpr-success-show-icon').is(':checked')) {
			$('#wpr-success-icon-options').css('display', 'grid');
		}

		// Toggle between native and embed form sections
		$('input[name="wpr_engage_form_type"]').on('change', function () {
			const formType = $(this).val();

			if (formType === 'native') {
				$('#wpr-native-form-builder').show();
				$('#wpr-embed-form-section').hide();
			} else {
				$('#wpr-native-form-builder').hide();
				$('#wpr-embed-form-section').show();
			}
			updatePreviewFormFields();
		});

		// Live preview on label/placeholder/required changes
		$(document).on('input', '.wpr-field-label-input, .wpr-field-placeholder-input', function () {
			updatePreviewFormFields();
		});
		$(document).on('change', '.wpr-field-required-input', function () {
			updatePreviewFormFields();
		});

		// Update preview form fields
		function updatePreviewFormFields() {
			const formType = $('input[name="wpr_engage_form_type"]:checked').val();
			const $previewForm = $('#wpr-preview-wrapper').find('.wpr-mt-6');

			if (formType === 'embed') {
				// Show message for embedded forms
				$previewForm.html('<p style="font-size: 13px; color: #6b7280;">Third-party form will be rendered here</p>');
				return;
			}

			// Capture current button colors before rebuilding
			const currentButtonBg = $('#wpr-button-bg-color').val() || '#3b82f6';
			const currentButtonText = $('#wpr-button-text-color').val() || '#ffffff';
			const currentEmailPlaceholder = $('#wpr-email-placeholder').val() || 'Enter your email';

			// Build preview for native form
			let previewHTML = '';
			let emailIdAssigned = false;
			$('.wpr-form-field-item').each(function () {
				const $item = $(this);
				const type = $item.find('.wpr-field-type').val();
				const placeholder = $item.find('.wpr-field-placeholder-input').val() || 'Enter ' + type;
				const label = $item.find('.wpr-field-label-input').length ? $item.find('.wpr-field-label-input').val() : '';

				if (type === 'checkbox') {
					previewHTML += `<label style="display: flex; align-items: center; margin-bottom: 1rem;">
						<input type="checkbox" style="margin-right: 8px;">
						<span style="font-size: 14px;">${label || placeholder}</span>
					</label>`;
				} else {
					// Preserve #wpr-preview-email ID on the first email-type input only
					const isEmail = (type === 'email' && !emailIdAssigned);
					const emailId = isEmail ? ' id="wpr-preview-email"' : '';
					const emailPlaceholderVal = isEmail ? currentEmailPlaceholder : placeholder;
					if (isEmail) {
						emailIdAssigned = true;
					}
					previewHTML += `<input${emailId} type="${type}" placeholder="${emailPlaceholderVal}" style="width: 100%; max-width: 20rem; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 0.25rem; margin-bottom: 1rem; display: block; margin-left: auto; margin-right: auto;">`;
				}
			});

			previewHTML += `<button id="wpr-preview-button" type="button" style="padding: 10px 24px; border-radius: 6px; border: 1px solid ${currentButtonBg}; background: ${currentButtonBg}; color: ${currentButtonText}; font-size: 14px; font-weight: 600; line-height: 1.2; cursor: default; box-shadow: 0 1px 2px rgba(15, 23, 42, 0.15); margin-top: 8px;">
				${$('#wpr-button').val() || 'Subscribe'}
			</button>`;

			$previewForm.html(previewHTML);

			// Re-cache references after DOM rebuild
			$buttonPreview = $('#wpr-preview-button');
			$emailPreview = $('#wpr-preview-email');
		}

		// Add new form field
		let fieldIndex = $('.wpr-form-field-item').length;
		// Field type picker — show type options before adding a field.
		var fieldTypes = [
			{ value: 'email', icon: '✉', label: 'Email', desc: 'Email address field' },
			{ value: 'text', icon: 'T', label: 'Text', desc: 'Single-line text input' },
			{ value: 'phone', icon: '☎', label: 'Phone', desc: 'Phone number field' },
			{ value: 'checkbox', icon: '☑', label: 'Checkbox', desc: 'Yes/no checkbox option' }
		];

		$('#wpr-add-form-field').on('click', function (e) {
			e.stopPropagation();
			var $btn = $(this);
			// Remove any existing picker
			$('.wpr-field-type-picker').remove();

			var pickerHtml = '<div class="wpr-field-type-picker" style="position: absolute; right: 0; top: 100%; margin-top: 8px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.12); padding: 8px; z-index: 100; min-width: 240px;">';
			pickerHtml += '<div style="padding: 8px 12px 6px; font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em;">Choose field type</div>';
			fieldTypes.forEach(function(ft) {
				pickerHtml += '<button type="button" class="wpr-field-type-option" data-type="' + ft.value + '" style="display: flex; align-items: center; gap: 12px; width: 100%; padding: 10px 12px; border: none; background: none; cursor: pointer; border-radius: 8px; text-align: left; transition: background 0.15s;"><span style="width: 32px; height: 32px; background: #f1f5f9; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 16px; flex-shrink: 0;">' + ft.icon + '</span><div><span style="display: block; font-size: 13px; font-weight: 600; color: #1e293b;">' + ft.label + '</span><span style="display: block; font-size: 11px; color: #94a3b8;">' + ft.desc + '</span></div></button>';
			});
			pickerHtml += '</div>';

			var $picker = $(pickerHtml);
			$btn.closest('div').css('position', 'relative').append($picker);

			// Hover effect on options
			$picker.find('.wpr-field-type-option').on('mouseenter', function() {
				$(this).css('background', '#f1f5f9');
			}).on('mouseleave', function() {
				$(this).css('background', 'none');
			});

			// Close picker on outside click
			setTimeout(function() {
				$(document).one('click', function() { $('.wpr-field-type-picker').remove(); });
			}, 0);

			// On type selection, add the field with that type
			$picker.find('.wpr-field-type-option').on('click', function (ev) {
				ev.stopPropagation();
				var selectedType = $(this).data('type');
				$('.wpr-field-type-picker').remove();
				addFormField(selectedType);
			});
		});

		function addFormField(type) {
			const newField = `
				<div class="wpr-form-field-item" draggable="true" style="padding: 16px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; transition: border-color 0.2s, opacity 0.2s;" data-index="${fieldIndex}">
					<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; border-bottom: 1px dashed #e2e8f0; padding-bottom: 12px;">
						<div style="display: flex; align-items: center; gap: 10px;">
							<span class="wpr-drag-handle" style="cursor: grab; color: #cbd5e1; font-size: 16px; line-height: 1; user-select: none;" title="Drag to reorder">⠗</span><span class="wpr-field-number" style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.025em;">Field #${fieldIndex + 1}</span>
							<select name="wpr_engage_form_fields[${fieldIndex}][type]" class="wpr-field-type" style="height: 34px; font-size: 13px; border-radius: 6px; padding: 0 10px; border: 1px solid #cbd5e1; outline: none; background: white; color: #334155;">
								<option value="email" ${type === "email" ? "selected" : ""}>Email</option>
								<option value="text" ${type === "text" ? "selected" : ""}>Text</option>
								<option value="phone" ${type === "phone" ? "selected" : ""}>Phone</option>
								<option value="checkbox" ${type === "checkbox" ? "selected" : ""}>Checkbox</option>
							</select>
						</div>
						<button type="button" class="wpr-remove-field" style="color: #ef4444; background: none; border: none; font-size: 11px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 4px; text-transform: uppercase; letter-spacing: 0.025em;">
							<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
							Remove
						</button>
					</div>
					<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 12px;">
						<div>
							<label style="display: block; font-size: 11px; font-weight: 700; color: #64748b; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.025em;">Label</label>
							<input type="text" name="wpr_engage_form_fields[${fieldIndex}][label]" value="" placeholder="e.g. Full Name" class="wpr-field-label-input" style="width: 100%; height: 40px; font-size: 13px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 12px; background: white; color: #334155;">
						</div>
						<div>
							<label style="display: block; font-size: 11px; font-weight: 700; color: #64748b; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.025em;">Placeholder</label>
							<input type="text" name="wpr_engage_form_fields[${fieldIndex}][placeholder]" value="" placeholder="e.g. Enter your name" class="wpr-field-placeholder-input" style="width: 100%; height: 40px; font-size: 13px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 12px; background: white; color: #334155;">
						</div>
					</div>
					<label style="display: flex; align-items: center; gap: 10px; font-size: 13px; cursor: pointer; color: #475569; font-weight: 500;">
						<input type="checkbox" name="wpr_engage_form_fields[${fieldIndex}][required]" value="1" style="width: 18px; height: 18px; border-radius: 4px; border-color: #cbd5e1;">
						Required Field
					</label>
				</div>
			`;

			$('#wpr-form-fields-container').append(newField);
			fieldIndex++;
			updatePreviewFormFields();
		}

		// --- Drag-to-reorder form fields ---
		function reindexFields() {
			$('#wpr-form-fields-container .wpr-form-field-item').each(function (i) {
				var $item = $(this);
				$item.attr('data-index', i);
				$item.find('.wpr-field-number').text('Field #' + (i + 1));
				$item.find('[name]').each(function () {
					var name = $(this).attr('name');
					$(this).attr('name', name.replace(/wpr_engage_form_fields\[\d+\]/, 'wpr_engage_form_fields[' + i + ']'));
				});
			});
			fieldIndex = $('#wpr-form-fields-container .wpr-form-field-item').length;
			updatePreviewFormFields();
		}

		var dragSrcEl = null;

		$(document).on('dragstart', '.wpr-form-field-item', function (e) {
			dragSrcEl = this;
			$(this).css('opacity', '0.4');
			e.originalEvent.dataTransfer.effectAllowed = 'move';
		});

		$(document).on('dragend', '.wpr-form-field-item', function () {
			$(this).css('opacity', '1');
			$('.wpr-form-field-item').css('border-top', '');
		});

		$(document).on('dragover', '.wpr-form-field-item', function (e) {
			e.preventDefault();
			e.originalEvent.dataTransfer.dropEffect = 'move';
			$('.wpr-form-field-item').css('border-top', '');
			$(this).css('border-top', '3px solid #3b82f6');
		});

		$(document).on('drop', '.wpr-form-field-item', function (e) {
			e.preventDefault();
			if (dragSrcEl !== this) {
				$(this).before(dragSrcEl);
				reindexFields();
			}
			$('.wpr-form-field-item').css('border-top', '');
		});

		// Remove form field with undo — captures the field's DOM and index,
		// removes it immediately, and shows a toast with an Undo button that
		// restores it if clicked within the timeout.
		$(document).on('click', '.wpr-remove-field', function () {
			const $items = $('.wpr-form-field-item');
			if ($items.length <= 1) {
				showToast('You must have at least one form field.', 'warning');
				return;
			}

			const $field = $(this).closest('.wpr-form-field-item');
			const $next = $field.next('.wpr-form-field-item');
			const $container = $field.parent();
			const labelInput = $field.find('.wpr-field-label-input').val()
				|| $field.find('.wpr-field-type').val()
				|| 'Field';

			// .detach() removes from DOM while preserving the element, its
			// current input/select values, checked state, and jQuery event
			// handlers — so an Undo inserts back exactly what the user had
			// configured, not a stale outerHTML snapshot that loses live state.
			$field.detach();
			updatePreviewFormFields();

			showToast(
				'Field "' + labelInput + '" removed',
				'info',
				{
					duration: 6000,
					action: {
						label: 'Undo',
						onClick: function () {
							// Re-insert before the captured next sibling, or append
							// at the end if this was the last field.
							if ($next.length && $.contains(document.body, $next[0])) {
								$next.before($field);
							} else {
								$container.append($field);
							}
							updatePreviewFormFields();
						}
					}
				}
			);
		});

		// Update preview when field properties change
		$(document).on('change', '.wpr-field-type, .wpr-field-placeholder-input', function () {
			updatePreviewFormFields();
		});

		// Initialize preview on page load
		if ($('.wpr-form-field-item').length > 0) {
			updatePreviewFormFields();
		}

		// Live server date/time clock — ticks the UTC and site-local server
		// times on the Urgency tab. Parses the PHP-rendered initial values
		// and increments by 1s per tick, so no browser-local assumptions and
		// no timezone math is needed.
		function parseStamp(str) {
			// "YYYY-MM-DD HH:MM:SS" → Date object (treated as UTC to avoid
			// drift — the relative display is what we care about).
			const m = /^(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})$/.exec(str);
			if (!m) return null;
			return new Date(Date.UTC(+m[1], +m[2] - 1, +m[3], +m[4], +m[5], +m[6]));
		}

		function formatStamp(d) {
			const pad = n => String(n).padStart(2, '0');
			return d.getUTCFullYear() + '-' + pad(d.getUTCMonth() + 1) + '-' + pad(d.getUTCDate())
				+ ' ' + pad(d.getUTCHours()) + ':' + pad(d.getUTCMinutes()) + ':' + pad(d.getUTCSeconds());
		}

		const $utcClock   = $('#wpr-server-time-display');
		const $localClock = $('#wpr-server-time-local');

		if ($utcClock.length) {
			let utcDate   = parseStamp($utcClock.text().trim());
			let localDate = $localClock.length ? parseStamp($localClock.text().trim()) : null;

			if (utcDate) {
				setInterval(function () {
					utcDate = new Date(utcDate.getTime() + 1000);
					$utcClock.text(formatStamp(utcDate));
					if (localDate) {
						localDate = new Date(localDate.getTime() + 1000);
						$localClock.text(formatStamp(localDate));
					}
				}, 1000);
			}
		}

});


	let customCssEditorInstance = null;

	$(document).ready(function () {

		const $customCssField = $('#wpr-custom-css');

		// Initialize code editor for custom CSS textarea
		if (
			typeof wp !== 'undefined' &&
			wp.codeEditor &&
			typeof WPRoboEngage !== 'undefined' &&
			WPRoboEngage.codeEditorSettings &&
			$customCssField.length
		) {
			const editorSettings = $.extend(true, {}, WPRoboEngage.codeEditorSettings);
			if (!editorSettings.codemirror) {
				editorSettings.codemirror = {};
			}

			editorSettings.codemirror.lineNumbers = false;
			editorSettings.codemirror.indentUnit = 2;
			editorSettings.codemirror.tabSize = 2;
			editorSettings.codemirror.viewportMargin = Infinity;

			customCssEditorInstance = wp.codeEditor.initialize($customCssField.get(0), editorSettings);

			// Sync CodeMirror content back to textarea when it changes
			if (customCssEditorInstance && customCssEditorInstance.codemirror) {
				customCssEditorInstance.codemirror.on('change', function (cm) {
					$customCssField.val(cm.getValue());
				});

				setTimeout(function () {
					customCssEditorInstance.codemirror.refresh();
				}, 50);
			}
		}
	});

	$(document).on('click', '.wpr-accordion-header', function () {
		const targetId = $(this).data('accordion');
		if (targetId === 'wpr-acc-css' && customCssEditorInstance && customCssEditorInstance.codemirror) {
			setTimeout(function () {
				customCssEditorInstance.codemirror.refresh();
			}, 30);
		}
	});

})(jQuery);
