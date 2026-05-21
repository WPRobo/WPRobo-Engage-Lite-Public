(function ($) {
	'use strict';

	// Helper function to replace variables in success messages
	function replaceVariables(text, formData) {
		if (!text || !formData) return text;

		// Find the first non-email field value for name variables
		var nameValue = '';
		for (var key in formData) {
			if (key !== 'email' && formData[key] && key.indexOf('wpr_field_') === 0) {
				nameValue = formData[key];
				break;
			}
		}
		var firstName = nameValue ? nameValue.split(' ')[0] : '';

		var email = formData.email || '';

		text = text.replace(/{first_name}/g, firstName);
		text = text.replace(/{email}/g, email);

		return text;
	}
	
	// Helper function to safely evaluate custom JavaScript conditions
	function evaluateCustomJS(code) {
		if (!code || typeof code !== 'string') {
			return true; // No code means no restriction
		}
		
		try {
			// Create a sandboxed function
			// The code should return true or false
			// eslint-disable-next-line no-new-func
			const func = new Function('window', 'document', 'location', code);
			
			// Execute with limited context (no access to sensitive data)
			const result = func.call(null, window, document, window.location);
			
			// Ensure result is boolean
			return Boolean(result);
		} catch (error) {
			// If custom JS has an error, log it and return true (fail safe - show campaign)
			console.warn('WPRobo Engage: Custom JS rule error:', error);
			return true;
		}
	}
	
	// Helper function to evaluate behavioral display rules
	function evaluateBehavioralRules() {
		// If no display rules, allow display
		if (!WPREngagePublic.display_rules || WPREngagePublic.display_rules.length === 0) {
			return true;
		}
		
		// Get visitor data
		const visitorData = window.wprVisitorData || {};
		
		// Evaluate each rule
		for (let i = 0; i < WPREngagePublic.display_rules.length; i++) {
			const rule = WPREngagePublic.display_rules[i];
			const ruleType = rule.type;
			const ruleValue = parseInt(rule.value) || 0;
			const isShowRule = rule.action === 'show';
			
			let rulePasses = false;
			
			// Evaluate based on rule type
			switch (ruleType) {
				case 'time_on_site':
					rulePasses = (visitorData.timeOnSite || 0) >= ruleValue;
					break;
					
				case 'scroll_depth':
					rulePasses = (visitorData.maxScrollDepth || 0) >= ruleValue;
					break;
					
				case 'page_views_session':
					rulePasses = (visitorData.sessionPageViews || 0) >= ruleValue;
					break;
					
				case 'page_views_lifetime':
					rulePasses = (visitorData.totalPageViews || 0) >= ruleValue;
					break;
					
				case 'visitor_new':
					rulePasses = visitorData.isNewVisitor === true;
					break;
					
				case 'visitor_returning':
					rulePasses = visitorData.isReturningVisitor === true;
					break;
				
				case 'custom_js':
					// Evaluate custom JavaScript condition with sandboxing
					rulePasses = evaluateCustomJS(rule.value);
					break;
					
				default:
					rulePasses = true; // Unknown rule types pass by default
			}
			
			// If it's a show rule and it doesn't pass, campaign shouldn't show
			if (isShowRule && !rulePasses) {
				return false;
			}
			
			// If it's a hide rule and it passes, campaign shouldn't show
			if (!isShowRule && rulePasses) {
				return false;
			}
		}
		
		// All rules passed
		return true;
	}
	
	// Helper function to handle success actions (redirect or show message with auto-close)
	function handleSuccessAction($mainView, $successView, closeCallback, formData) {
		const successAction = WPREngagePublic.success_action || 'message';
		
		// Replace variables in success message content
		if (formData) {
			const $successHeadline = $successView.find('h2, .wpr-font-bold, div.wpr-font-bold').first();
			const $successContent = $successView.find('p, div.wpr-text-sm, div.wpr-text-gray-600').not('#wpr-auto-close-countdown').first();
			
			if ($successHeadline.length) {
				$successHeadline.html(replaceVariables($successHeadline.html(), formData));
			}
			if ($successContent.length) {
				$successContent.html(replaceVariables($successContent.html(), formData));
			}
		}
		
		if (successAction === 'redirect' && WPREngagePublic.success_redirect_url) {
			// Get redirect settings
			const redirectDelay = parseInt(WPREngagePublic.success_redirect_delay) || 0;
			const redirectNewTab = WPREngagePublic.success_redirect_new_tab === '1';
			
			// Show success message briefly if there's a delay
			if (redirectDelay > 0) {
				$mainView.addClass('wpr-hidden');
				$successView.removeClass('wpr-hidden');
				
				// Show countdown
				let countdown = redirectDelay;
				const $countdown = $('#wpr-auto-close-countdown');
				$countdown.text(WPREngagePublic.i18n.redirectingIn + ' ' + countdown + ' ' + (countdown !== 1 ? WPREngagePublic.i18n.seconds : WPREngagePublic.i18n.second) + '...').removeClass('wpr-hidden');
				
				const countdownInterval = setInterval(function() {
					countdown--;
					if (countdown > 0) {
						$countdown.text(WPREngagePublic.i18n.redirectingIn + ' ' + countdown + ' ' + (countdown !== 1 ? WPREngagePublic.i18n.seconds : WPREngagePublic.i18n.second) + '...');
					} else {
						clearInterval(countdownInterval);
						// Perform redirect
						if (redirectNewTab) {
							window.open(WPREngagePublic.success_redirect_url, '_blank');
							// Close the popup/bar after opening in new tab
							closeCallback();
						} else {
							window.location.href = WPREngagePublic.success_redirect_url;
						}
					}
				}, 1000);
			} else {
				// Immediate redirect
				if (redirectNewTab) {
					window.open(WPREngagePublic.success_redirect_url, '_blank');
					closeCallback();
				} else {
					window.location.href = WPREngagePublic.success_redirect_url;
				}
			}
		} else {
			// Show success message (default behavior)
			$mainView.addClass('wpr-hidden');
			$successView.removeClass('wpr-hidden');
			
			// Check for auto-close
			if (WPREngagePublic.success_auto_close === '1') {
				const autoCloseDelay = parseInt(WPREngagePublic.success_auto_close_delay) || 5;
				let countdown = autoCloseDelay;
				const $countdown = $('#wpr-auto-close-countdown');
				$countdown.text(WPREngagePublic.i18n.closingIn + ' ' + countdown + ' ' + (countdown !== 1 ? WPREngagePublic.i18n.seconds : WPREngagePublic.i18n.second) + '...').removeClass('wpr-hidden');
				
				const closeInterval = setInterval(function() {
					countdown--;
					if (countdown > 0) {
						$countdown.text(WPREngagePublic.i18n.closingIn + ' ' + countdown + ' ' + (countdown !== 1 ? WPREngagePublic.i18n.seconds : WPREngagePublic.i18n.second) + '...');
					} else {
						clearInterval(closeInterval);
						closeCallback();
					}
				}, 1000);
			}
		}
	}
	
	// Get per-campaign settings from WPREngagePublic.campaigns or fall back to global defaults.
	function getCampaignSettings(campaignId) {
		if (typeof WPREngagePublic.campaigns !== "undefined" && WPREngagePublic.campaigns[campaignId]) {
			return WPREngagePublic.campaigns[campaignId];
		}
		// Backward compatibility: return top-level settings.
		return WPREngagePublic;
	}

		$(function () {
		// Check for popup overlay
		const $overlay = $('#wpr-engage-popup-overlay');
		if ($overlay.length) {
			handlePopup($overlay);
		}

		// Check for floating bar
		const $bar = $('#wpr-engage-bar-container');
		if ($bar.length) {
			handleFloatingBar($bar);
		}
		
		// Check for slide-in
		const $slideIn = $('#wpr-engage-slide-in-container');
		if ($slideIn.length) {
			handleSlideIn($slideIn);
		}
	});

	// Handle popup campaign
	function handlePopup($overlay) {
		if (!$overlay.length) return;

		// Scope all selectors to this campaign's container to avoid conflicts with other campaigns
		const $closeButton = $overlay.find('#wpr-engage-popup-close');
		const $form = $overlay.find('#wpr-engage-form');
		const $mainView = $overlay.find('#wpr-engage-view-main');
		const $successView = $overlay.find('#wpr-engage-view-success');
		const $errorMsg = $overlay.find('#wpr-engage-error');

		let popupShown = false;

		// Function to show the popup and track impression
		function showPopup() {
			if (popupShown) return;
			
			// Check behavioral rules before showing
			if (!evaluateBehavioralRules()) {
				return; // Rules don't pass, don't show popup
			}
			
			popupShown = true;
			
			const campaignId = $('#wpr-engage-popup-wrapper').data('campaign-id');

			// Show the overlay
			$overlay.removeClass('wpr-hidden');

			// Track impression
			if (campaignId) {
				$.ajax({
					url: WPREngagePublic.ajax_url,
					type: 'POST',
					data: {
						action: 'wprobo_engage_track_impression',
						nonce: WPREngagePublic.nonce,
						campaign_id: campaignId
					}
				});
			}
		}

		// Get per-campaign trigger settings
		var popupCampaignId = $overlay.find("#wpr-engage-popup-wrapper").data("campaign-id");
		var popupSettings = getCampaignSettings(popupCampaignId);
		const triggerType = popupSettings.trigger_type || '';
		const triggerValue = popupSettings.trigger_value || '';

		// Apply trigger logic based on trigger type
		if (triggerType === 'timed_delay') {
			// Timed delay trigger
			const delaySeconds = parseInt(triggerValue) || 5;
			setTimeout(function() {
				showPopup();
			}, delaySeconds * 1000);

		} else if (triggerType === 'scroll_depth') {
			// Scroll depth trigger
			const scrollPercentage = parseInt(triggerValue) || 50;

			$(window).on('scroll', function() {
				if (popupShown) return;

				const scrollTop = $(window).scrollTop();
				const docHeight = $(document).height();
				const winHeight = $(window).height();
				const scrollPercent = (scrollTop / (docHeight - winHeight)) * 100;

				if (scrollPercent >= scrollPercentage) {
					showPopup();
				}
			});

		} else if (triggerType === 'exit_intent') {
			// Exit-intent trigger
			let exitIntentTriggered = false;

			$(document).on('mouseleave', function(e) {
				if (popupShown || exitIntentTriggered) return;

				// Only trigger if mouse is moving towards the top of the page
				if (e.clientY < 50) {
					exitIntentTriggered = true;
					showPopup();
				}
			});
		} else {
			showPopup();
		}

		// Close popup function
		function closePopup() {
			$overlay.addClass('wpr-hidden');
		}

		// Close button click
		$closeButton.on('click', closePopup);

		// ESC key to close (if enabled)
		const escEnabled = $overlay.data('esc-close') === '1' || $overlay.data('esc-close') === 1;
		if (escEnabled) {
			$(document).on('keydown.wpr-popup', function(e) {
				if (e.key === 'Escape' && !$overlay.hasClass('wpr-hidden')) {
					closePopup();
					$(document).off('keydown.wpr-popup');
				}
			});
		}

		// Close button hover effect
		if ($closeButton.length) {
			const hoverColor = $closeButton.data('hover-color');
			const originalColor = $closeButton.css('color');

			if (hoverColor) {
				$closeButton.on('mouseenter', function() {
					$(this).css('color', hoverColor);
				}).on('mouseleave', function() {
					$(this).css('color', originalColor);
				});
			}
		}

		$form.on('submit', function (e) {
			e.preventDefault();
			const $submitButton = $(this).find('button[type="submit"]');
			const originalButtonText = $submitButton.text();
			$submitButton.text(WPREngagePublic.i18n.submitting).prop('disabled', true);
			$errorMsg.addClass('wpr-hidden').text('');

			const campaignId = $('#wpr-engage-popup-wrapper').data('campaign-id');
			
			// Collect all form fields
			const formData = {};
			const $emailField = $(this).find('#wpr-engage-email');
			
			// Check for legacy single email field
			if ($emailField.length) {
				formData.email = $emailField.val();
			} else {
				// Collect all form fields with .wpr-form-field class from THIS form only
				$(this).find('.wpr-form-field').each(function() {
					const $field = $(this);
					const name = $field.attr('name');
					
					if ($field.attr('type') === 'checkbox') {
						formData[name] = $field.is(':checked') ? '1' : '0';
					} else {
						formData[name] = $field.val();
					}
					
					// If this is an email field, also set it as the main email
					if ($field.data('field-type') === 'email') {
						formData.email = $field.val();
					}
				});
			}

			// Prepare AJAX data
			const ajaxData = {
				action: 'wprobo_engage_submission',
				nonce: WPREngagePublic.nonce,
				campaign_id: campaignId
			};
			
			// Add form data - use legacy format if single email, otherwise use new format
			if (formData.email && Object.keys(formData).length === 1) {
				ajaxData.email = formData.email;
			} else {
				ajaxData.form_data = formData;
			}

			$.ajax({
				url: WPREngagePublic.ajax_url,
				type: 'POST',
				data: ajaxData
			})
				.done(function(response) {
					if (response.success) {
						// Use the helper function to handle success action
						handleSuccessAction($mainView, $successView, closePopup, formData);
					} else {
						$errorMsg.text(response.data.message || WPREngagePublic.i18n.anError).removeClass('wpr-hidden');
						$submitButton.text(originalButtonText).prop('disabled', false);
					}
				})
				.fail(function() {
					$errorMsg.text(WPREngagePublic.i18n.networkError).removeClass('wpr-hidden');
					$submitButton.text(originalButtonText).prop('disabled', false);
				});
		});
	}

	// Handle floating bar campaign
	function handleFloatingBar($bar) {
		// Scope all selectors to this campaign's container to avoid conflicts with other campaigns
		const $closeButton = $bar.find('#wpr-engage-bar-close, #wpr-engage-bar-close-success');
		const $form = $bar.find('#wpr-engage-form');
		const $mainView = $bar.find('#wpr-engage-view-main');
		const $successView = $bar.find('#wpr-engage-view-success');
		const $errorMsg = $bar.find('#wpr-engage-error');

		let barShown = false;
		var barCampaignId = $bar.data("campaign-id");
		var barSettings = getCampaignSettings(barCampaignId);

		// Function to show the bar and track impression
		function showBar() {
			if (barShown) return;
			
			// Check behavioral rules before showing
			if (!evaluateBehavioralRules()) {
				return; // Rules don't pass, don't show bar
			}
			
			barShown = true;
			
			const campaignId = $bar.data('campaign-id');

			// Show the bar
			$bar.removeClass('wpr-hidden');

			// Track impression
			if (campaignId) {
				$.ajax({
					url: WPREngagePublic.ajax_url,
					type: 'POST',
					data: {
						action: 'wprobo_engage_track_impression',
						nonce: WPREngagePublic.nonce,
						campaign_id: campaignId
					}
				});
			}
		}

		// Get per-campaign trigger settings
		const triggerType = barSettings.trigger_type || '';
		const triggerValue = barSettings.trigger_value || '';

		// Apply trigger logic based on trigger type
		if (triggerType === 'timed_delay') {
			// Timed delay trigger
			const delaySeconds = parseInt(triggerValue) || 5;
			setTimeout(function() {
				showBar();
			}, delaySeconds * 1000);

		} else if (triggerType === 'scroll_depth') {
			// Scroll depth trigger
			const scrollPercentage = parseInt(triggerValue) || 50;

			$(window).on('scroll', function() {
				if (barShown) return;

				const scrollTop = $(window).scrollTop();
				const docHeight = $(document).height();
				const winHeight = $(window).height();
				const scrollPercent = (scrollTop / (docHeight - winHeight)) * 100;

				if (scrollPercent >= scrollPercentage) {
					showBar();
				}
			});

		} else if (triggerType === 'exit_intent') {
			// Exit-intent trigger
			let exitIntentTriggered = false;

			$(document).on('mouseleave', function(e) {
				if (barShown || exitIntentTriggered) return;

				// Only trigger if mouse is moving towards the top of the page
				if (e.clientY < 50) {
					exitIntentTriggered = true;
					showBar();
				}
			});
		} else {
			showBar();
		}


		// Close bar function
		function closeBar() {
			$bar.addClass('wpr-hidden');
		}

		// Close button click
		$closeButton.on('click', closeBar);

		// Close button hover effect
		$closeButton.each(function() {
			var $btn = $(this);
			var hoverColor = $btn.data('hover-color');
			if (hoverColor) {
				var normalColor = $btn.css('color');
				$btn.on('mouseenter', function() {
					$(this).css('color', hoverColor);
				}).on('mouseleave', function() {
					$(this).css('color', normalColor);
				});
			}
		});

		// ESC key to close (if enabled)
		const escEnabled = $bar.data('esc-close') === '1' || $bar.data('esc-close') === 1;
		if (escEnabled) {
			$(document).on('keydown.wpr-bar', function(e) {
				if (e.key === 'Escape' && !$bar.hasClass('wpr-hidden')) {
					closeBar();
					$(document).off('keydown.wpr-bar');
				}
			});
		}

		$form.on('submit', function (e) {
			e.preventDefault();
			const $submitButton = $(this).find('button[type="submit"]');
			const originalButtonText = $submitButton.text();
			$submitButton.text(WPREngagePublic.i18n.submitting).prop('disabled', true);
			$errorMsg.addClass('wpr-hidden').text('');

			const campaignId = $bar.data('campaign-id');
			
			// Collect all form fields
			const formData = {};
			const $emailField = $(this).find('#wpr-engage-email');
			
			// Check for legacy single email field
			if ($emailField.length) {
				formData.email = $emailField.val();
			} else {
				// Collect all form fields with .wpr-form-field class from THIS form only
				$(this).find('.wpr-form-field').each(function() {
					const $field = $(this);
					const name = $field.attr('name');
					
					if ($field.attr('type') === 'checkbox') {
						formData[name] = $field.is(':checked') ? '1' : '0';
					} else {
						formData[name] = $field.val();
					}
					
					// If this is an email field, also set it as the main email
					if ($field.data('field-type') === 'email') {
						formData.email = $field.val();
					}
				});
			}

			// Prepare AJAX data
			const ajaxData = {
				action: 'wprobo_engage_submission',
				nonce: WPREngagePublic.nonce,
				campaign_id: campaignId
			};
			
			// Add form data - use legacy format if single email, otherwise use new format
			if (formData.email && Object.keys(formData).length === 1) {
				ajaxData.email = formData.email;
			} else {
				ajaxData.form_data = formData;
			}

			$.ajax({
				url: WPREngagePublic.ajax_url,
				type: 'POST',
				data: ajaxData
			})
				.done(function(response) {
					if (response.success) {
						// Use the helper function to handle success action
						handleSuccessAction($mainView, $successView, closeBar, formData);
					} else {
						$errorMsg.text(response.data.message || 'An error occurred.').removeClass('wpr-hidden');
						$submitButton.text(originalButtonText).prop('disabled', false);
					}
				})
				.fail(function() {
					$errorMsg.text(WPREngagePublic.i18n.networkError).removeClass('wpr-hidden');
					$submitButton.text(originalButtonText).prop('disabled', false);
				});
		});
	}

	// Handle slide-in campaign
	function handleSlideIn($slideIn) {
		// Scope all selectors to this campaign's container to avoid conflicts with other campaigns
		const $closeButton = $slideIn.find('#wpr-engage-slide-in-close');
		const $form = $slideIn.find('#wpr-engage-form');
		const $mainView = $slideIn.find('#wpr-engage-view-main');
		const $successView = $slideIn.find('#wpr-engage-view-success');
		const $errorMsg = $slideIn.find('#wpr-engage-error');

		let slideInShown = false;
		var slideInCampaignId = $slideIn.data("campaign-id");
		var slideInSettings = getCampaignSettings(slideInCampaignId);

		// Function to show the slide-in and track impression
		function showSlideIn() {
			if (slideInShown) return;

			// Check behavioral rules before showing
			if (!evaluateBehavioralRules()) {
				return; // Rules don't pass, don't show slide-in
			}
			
			slideInShown = true;
			$slideIn.removeClass('wpr-hidden');

			// Track impression when slide-in is shown
			const campaignId = $slideIn.data('campaign-id');
			if (campaignId) {
				$.ajax({
					url: WPREngagePublic.ajax_url,
					type: 'POST',
					data: {
						action: 'wprobo_engage_track_impression',
						nonce: WPREngagePublic.nonce,
						campaign_id: campaignId
					}
				});
			}
		}

		// Get per-campaign trigger settings
		const triggerType = slideInSettings.trigger_type || '';
		const triggerValue = slideInSettings.trigger_value || '';

		// Apply trigger logic based on trigger type
		if (triggerType === 'timed_delay') {
			// Timed delay trigger
			const delaySeconds = parseInt(triggerValue) || 5;
			setTimeout(function() {
				showSlideIn();
			}, delaySeconds * 1000);

		} else if (triggerType === 'scroll_depth') {
			// Scroll depth trigger
			const scrollPercentage = parseInt(triggerValue) || 50;

			$(window).on('scroll', function() {
				if (slideInShown) return;

				const scrollTop = $(window).scrollTop();
				const docHeight = $(document).height();
				const winHeight = $(window).height();
				const scrollPercent = (scrollTop / (docHeight - winHeight)) * 100;

				if (scrollPercent >= scrollPercentage) {
					showSlideIn();
				}
			});

		} else if (triggerType === 'exit_intent') {
			// Exit-intent trigger
			let exitIntentTriggered = false;

			$(document).on('mouseleave', function(e) {
				if (slideInShown || exitIntentTriggered) return;
				// Only trigger if mouse is moving towards the top of the page
				if (e.clientY < 50) {
					exitIntentTriggered = true;
					showSlideIn();
				}
			});
		} else {
			showSlideIn();
		}

		// Close slide-in function
		function closeSlideIn() {
			$slideIn.addClass('wpr-hidden');
		}

		// Close button click
		$closeButton.on('click', closeSlideIn);

		// ESC key to close (if enabled)
		const escEnabled = $slideIn.data('esc-close') === '1' || $slideIn.data('esc-close') === 1;
		if (escEnabled) {
			$(document).on('keydown.wpr-slide-in', function(e) {
				if (e.key === 'Escape' && !$slideIn.hasClass('wpr-hidden')) {
					closeSlideIn();
					$(document).off('keydown.wpr-slide-in');
				}
			});
		}

		// Close button hover effect
		if ($closeButton.length) {
			const hoverColor = $closeButton.data('hover-color');
			const originalColor = $closeButton.css('color');

			if (hoverColor) {
				$closeButton.on('mouseenter', function() {
					$(this).css('color', hoverColor);
				}).on('mouseleave', function() {
					$(this).css('color', originalColor);
				});
			}
		}

		$form.on('submit', function (e) {
			e.preventDefault();
			const $submitButton = $(this).find('button[type="submit"]');
			const originalButtonText = $submitButton.text();
			$submitButton.text(WPREngagePublic.i18n.submitting).prop('disabled', true);
			$errorMsg.addClass('wpr-hidden').text('');

			const campaignId = $slideIn.data('campaign-id');
			
			// Collect all form fields
			const formData = {};
			const $emailField = $(this).find('#wpr-engage-email');
			
			// Check for legacy single email field
			if ($emailField.length) {
				formData.email = $emailField.val();
			} else {
				// Collect all form fields with .wpr-form-field class from THIS form only
				$(this).find('.wpr-form-field').each(function() {
					const $field = $(this);
					const name = $field.attr('name');
					
					if ($field.attr('type') === 'checkbox') {
						formData[name] = $field.is(':checked') ? '1' : '0';
					} else {
						formData[name] = $field.val();
					}
					
					// If this is an email field, also set it as the main email
					if ($field.data('field-type') === 'email') {
						formData.email = $field.val();
					}
				});
			}

			// Prepare AJAX data
			const ajaxData = {
				action: 'wprobo_engage_submission',
				nonce: WPREngagePublic.nonce,
				campaign_id: campaignId
			};
			
			// Add form data - use legacy format if single email, otherwise use new format
			if (formData.email && Object.keys(formData).length === 1) {
				ajaxData.email = formData.email;
			} else {
				ajaxData.form_data = formData;
			}

			$.ajax({
				url: WPREngagePublic.ajax_url,
				type: 'POST',
				data: ajaxData
			})
				.done(function(response) {
					if (response.success) {
						// Use the helper function to handle success action
						handleSuccessAction($mainView, $successView, closeSlideIn, formData);
					} else {
						$errorMsg.text(response.data.message || WPREngagePublic.i18n.anError).removeClass('wpr-hidden');
						$submitButton.text(originalButtonText).prop('disabled', false);
					}
				})
				.fail(function() {
					$errorMsg.text(WPREngagePublic.i18n.networkError).removeClass('wpr-hidden');
					$submitButton.text(originalButtonText).prop('disabled', false);
				});
		});
	}

})(jQuery);
