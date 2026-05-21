/* jshint esversion: 5 */
/* global WPRoboTools */
(function () {
	'use strict';

	var i18n = ( typeof WPRoboTools !== 'undefined' ) ? WPRoboTools.i18n : {};

	var form      = document.getElementById( 'wpr-import-form' );
	if ( ! form ) { return; }

	var dropZone  = document.getElementById( 'wpr-drop-zone' );
	var input     = document.getElementById( 'campaign_file' );
	var status    = document.getElementById( 'wpr-import-status' );
	var submitBtn = document.getElementById( 'wpr-import-submit' );
	var validName = '';

	function setStatus( state, message ) {
		if ( ! state ) {
			status.hidden        = true;
			status.textContent   = '';
			status.className     = 'wpr-import-status';
			dropZone.classList.remove( 'wpr-drop-valid', 'wpr-drop-invalid' );
			submitBtn.disabled   = true;
			return;
		}
		status.hidden      = false;
		status.textContent = message;
		status.className   = 'wpr-import-status wpr-status-' + state;
		dropZone.classList.remove( 'wpr-drop-valid', 'wpr-drop-invalid' );
		dropZone.classList.add( 'wpr-drop-' + state );
		submitBtn.disabled = ( state !== 'valid' );
	}

	function validateFile( file ) {
		if ( ! file ) {
			setStatus( null, '' );
			return;
		}
		if ( ! /\.json$/i.test( file.name ) ) {
			setStatus( 'invalid', i18n.notJsonFile );
			return;
		}
		var reader = new FileReader();
		reader.onload = function ( e ) {
			try {
				var data    = JSON.parse( e.target.result );
				var isBulk   = Array.isArray( data.campaigns );
				var isSingle = data.name && data.settings && typeof data.settings === 'object';

				if ( ! data || typeof data !== 'object' || ( ! isBulk && ! isSingle ) ) {
					setStatus( 'invalid', i18n.notExportFile );
					return;
				}
				if ( isBulk ) {
					var count = data.campaigns.length;
					validName = count + ' ' + ( count === 1 ? i18n.campaign : i18n.campaigns );
					setStatus( 'valid', i18n.readyToImport + validName + ' (' + Math.round( file.size / 1024 * 10 ) / 10 + ' KB)' );
				} else {
					validName = data.name || data.post_title || i18n.untitledCampaign;
					setStatus( 'valid', i18n.readyToImport + '"' + validName + '" (' + Math.round( file.size / 1024 * 10 ) / 10 + ' KB)' );
				}
			} catch ( err ) {
				setStatus( 'invalid', i18n.invalidJson );
			}
		};
		reader.readAsText( file );
	}

	// Clicking the drop zone (but not the Browse label) opens file picker.
	dropZone.addEventListener( 'click', function ( e ) {
		if ( e.target.closest( 'label' ) || e.target === input ) { return; }
		input.click();
	} );

	dropZone.addEventListener( 'keydown', function ( e ) {
		if ( e.key === 'Enter' || e.key === ' ' ) {
			e.preventDefault();
			input.click();
		}
	} );

	// Drag states.
	[ 'dragenter', 'dragover' ].forEach( function ( type ) {
		dropZone.addEventListener( type, function ( e ) {
			e.preventDefault();
			e.stopPropagation();
			dropZone.classList.add( 'wpr-drop-active' );
		} );
	} );

	[ 'dragleave', 'drop' ].forEach( function ( type ) {
		dropZone.addEventListener( type, function ( e ) {
			e.preventDefault();
			e.stopPropagation();
			dropZone.classList.remove( 'wpr-drop-active' );
		} );
	} );

	dropZone.addEventListener( 'drop', function ( e ) {
		var file = e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files[0];
		if ( file ) {
			// Copy dropped file into the input so form submit carries it.
			var dt = new DataTransfer();
			dt.items.add( file );
			input.files = dt.files;
			validateFile( file );
		}
	} );

	input.addEventListener( 'change', function () {
		validateFile( this.files && this.files[0] );
	} );

	// Confirmation step before submission.
	form.addEventListener( 'submit', function ( e ) {
		if ( submitBtn.disabled ) {
			e.preventDefault();
			return;
		}
		var displayLabel = ( validName.indexOf( ' ' ) === -1 ) ? '"' + validName + '"' : validName;
		if ( ! window.confirm( i18n.confirmImport.replace( '%s', displayLabel ) ) ) {
			e.preventDefault();
		}
	} );
} )();
