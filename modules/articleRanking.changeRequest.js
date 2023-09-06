$(function () {
	mw.loader.using(['mediawiki.api', 'oojs-ui-core', 'oojs-ui-widgets', 'oojs-ui-windows'], function () {
		// When the Change Request button is pressed open the form in a modal dialog.
		$('.ranking-btn.changerequest').click(function (e) {
			e.preventDefault();

			// Subclass OOUI's ProcessDialog for our modal.
			function ModalDialog(config) {
				ModalDialog.super.call(this, config);
			}
			OO.inheritClass(ModalDialog, OO.ui.ProcessDialog);
			ModalDialog.static.name = 'changeRequestModal';

			// Minimal dialog chrome, just a close button.
			// Title and submit button will appear (in the user's language) in the content area.
			ModalDialog.static.title = '';
			ModalDialog.static.actions = [
				{ 
					label: 'Cancel', 
					flags: [ 'safe', 'close' ]
				}
			];

			// Initialize the modal's content with a spinning hourglass.
			ModalDialog.prototype.initialize = function () {
				ModalDialog.super.prototype.initialize.apply( this, arguments );
				this.panel = new OO.ui.PanelLayout( { padded: true, expanded: false } );
				this.panel.$element.append( 
					'  <div id="changeRequestModalBody">'
					+ '  <div class="kzcr-spinner"><span class="kzcr-spin">&#9203;</span></div>'
					+ '</div>'
					+ '<style>.kzcr-spinner{text-align:center;}.kzcr-spin{font-size:3em;position:relative;display:inline-block;-webkit-animation:spin 2s infinite linear;-moz-animation:spin 2s infinite linear;-o-animation:spin 2s infinite linear;animation:spin 2s infinite linear;}@-moz-keyframes spin{0%{-moz-transform:rotate(0deg);}100%{-moz-transform:rotate(359deg);}}@-webkit-keyframes spin{0%{-webkit-transform:rotate(0deg);}100%{-webkit-transform:rotate(359deg);}}@-o-keyframes spin{0%{-o-transform:rotate(0deg);}100%{-o-transform:rotate(359deg);}}@keyframes spin{0%{-webkit-transform:rotate(0deg);transform:rotate(0deg);}100%{-webkit-transform:rotate(359deg);transform:rotate(359deg);}}</style>'
				);
				this.$body.append(this.panel.$element);
			};

			// Get modal height.
			ModalDialog.prototype.getBodyHeight = function () {
				return this.panel.$element.outerHeight(true);
			};

			// Instantiate and append the window manager.
			var windowManager = new OO.ui.WindowManager();
			$(document.body).append(windowManager.$element);

			// Instantiate the modal dialog and add in to the window manager.
			var modalDialog = new ModalDialog({
				size: 'medium'
			});
			windowManager.addWindows([modalDialog]);

			// Handle window onClosing.
			windowManager.on('closing', function(win, closed, data) {
				closed.done(function(e) {
					// Clean up the DOM. If the form is re-opened we'll start from the beginning.
					windowManager.destroy();
				});
			});

			// Handle window onOpening.
			windowManager.on('opening', function(win, opening, data) {
				// If the form loaded during the window-opening transition, restore focus to the first element.
				opening.done(function(e) {
					$('textarea[name=wpkzcrRequest]').trigger('focus');
				});
			});

			// Cancel from within the form closes the modal.
			var onClose = function(e) {
				modalDialog.close();
			};

			// Resize the modal when content is updated.
			var onReady = function() {
				modalDialog.updateSize();
				// Scroll to the top of form content.
				$('#changeRequestModalBody').parents('.oo-ui-window-body').scrollTop(0);
			};

			// Open the modal.
			windowManager.openWindow(modalDialog);

			// Load the form into the modal.
			mw.loader.using('ext.KZChangeRequest.modal', function () {
				window.kzcrAjax($('#changeRequestModalBody'), onClose, onReady);
			});
		});
	});
});