/* jshint esversion: 5 */
/* global WPRoboTemplates */
(function () {
	'use strict';

	var i18n  = ( typeof WPRoboTemplates !== 'undefined' ) ? WPRoboTemplates.i18n : {};
	var modal = document.getElementById( 'wpr-template-preview-modal' );
	if ( ! modal ) { return; }

	var title     = document.getElementById( 'wpr-tpm-title' );
	var desc      = document.getElementById( 'wpr-tpm-desc' );
	var typeChip  = document.getElementById( 'wpr-tpm-type' );
	var catChip   = document.getElementById( 'wpr-tpm-category' );
	var preview   = document.getElementById( 'wpr-tpm-preview' );
	var headline  = document.getElementById( 'wpr-tpm-headline' );
	var emailEl   = document.getElementById( 'wpr-tpm-email' );
	var btn       = document.getElementById( 'wpr-tpm-btn' );
	var useLink   = document.getElementById( 'wpr-tpm-use' );
	var lastFocus = null;

	function openModal( data ) {
		lastFocus               = document.activeElement;
		title.textContent       = data.name;
		desc.textContent        = data.description;
		typeChip.textContent    = data.campaignType;
		catChip.textContent     = data.category;
		preview.style.background = data.previewBg;
		preview.style.color      = data.previewText;
		headline.textContent    = data.headline;
		emailEl.textContent     = i18n.emailPlaceholder;
		btn.style.background    = data.previewBtnBg;
		btn.style.color         = data.previewBtnText;
		btn.textContent         = data.buttonText;
		useLink.href            = data.useUrl;
		modal.classList.remove( 'wpr-tpm-hidden' );
		document.body.style.overflow = 'hidden';
		// Move focus to the close button for keyboard users.
		var closeBtn = modal.querySelector( '.wpr-tpm-close' );
		if ( closeBtn ) { closeBtn.focus(); }
	}

	function closeModal() {
		modal.classList.add( 'wpr-tpm-hidden' );
		document.body.style.overflow = '';
		if ( lastFocus && typeof lastFocus.focus === 'function' ) {
			lastFocus.focus();
		}
	}

	document.addEventListener( 'click', function ( e ) {
		var opener = e.target.closest( '.wpr-template-preview-btn' );
		if ( opener ) {
			e.preventDefault();
			openModal( {
				name:           opener.dataset.name          || '',
				description:    opener.dataset.description   || '',
				campaignType:   opener.dataset.campaignType  || '',
				category:       opener.dataset.category      || '',
				previewBg:      opener.dataset.previewBg     || '#f8fafc',
				previewText:    opener.dataset.previewText   || '#0f172a',
				previewBtnBg:   opener.dataset.previewBtnBg  || '#1d4ed8',
				previewBtnText: opener.dataset.previewBtnText || '#ffffff',
				headline:       opener.dataset.headline      || '',
				buttonText:     opener.dataset.buttonText    || '',
				useUrl:         opener.dataset.useUrl        || '#',
			} );
			return;
		}
		if ( e.target.closest( '[data-tpm-close]' ) ) {
			closeModal();
		}
	} );

	document.addEventListener( 'keydown', function ( e ) {
		if ( e.key === 'Escape' && ! modal.classList.contains( 'wpr-tpm-hidden' ) ) {
			closeModal();
		}
	} );
} )();
