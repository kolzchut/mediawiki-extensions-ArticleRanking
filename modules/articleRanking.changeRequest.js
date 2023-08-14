$(function () {
	mw.loader.using(['mediawiki.api'], function () {
		$('.ranking-btn.changerequest').click(function (e) {
			e.preventDefault();
			// Modal markup
			$('body').append(
				'<div class="modal fade" id="changeRequestModal" tabindex="-1" role="dialog">'
				+ '<div class="modal-dialog" role="document">'
				+ '  <div class="modal-content">'
				+ '    <div class="modal-header">'
				+ '      <button type="button" class="close" id="changeRequestModalCloseButton" data-dismiss="modal" aria-label="Close">'
				+ '        <span aria-hidden="true">&times;</span>'
				+ '      </button>'
				+ '    </div>'
				+ '  <div class="modal-body" id="changeRequestModalBody">'
				+ '    <div class="kzcr-spinner"><span class="kzcr-spin">&#10227;</span></div>'
				+ '  </div>'
				+ '</div>'
				+ '<style>.kzcr-spinner{text-align:center;}.kzcr-spin{font-size:3em;position:relative;display:inline-block;-webkit-animation:spin 2s infinite linear;-moz-animation:spin 2s infinite linear;-o-animation:spin 2s infinite linear;animation:spin 2s infinite linear;}@-moz-keyframes spin{0%{-moz-transform:rotate(0deg);}100%{-moz-transform:rotate(359deg);}}@-webkit-keyframes spin{0%{-webkit-transform:rotate(0deg);}100%{-webkit-transform:rotate(359deg);}}@-o-keyframes spin{0%{-o-transform:rotate(0deg);}100%{-o-transform:rotate(359deg);}}@keyframes spin{0%{-webkit-transform:rotate(0deg);transform:rotate(0deg);}100%{-webkit-transform:rotate(359deg);transform:rotate(359deg);}}</style>'
				+ '</div>'
			);
			// onClose handler
			var onClose = function (e) {
				$('#changeRequestModal').modal('hide');
			};
			// onReady handler
			var onReady = function () {
				$('#changeRequestModal').modal('handleUpdate');
			};
			// Open the modal.
			$('#changeRequestModal').modal();
			mw.loader.using('ext.KZChangeRequest.modal', function () {
				window.kzcrAjax($('#changeRequestModalBody'), onClose, onReady);
			});
			// Dispose of the modal whenever it's closed.
			$('#changeRequestModal').on('hidden.bs.modal', function (e) {
				$('#changeRequestModal').remove();
			});
		});
	});
});