/**
 * Payment Receipt Upload Module
 * Drag & drop + button file upload for bank transfer receipts.
 */
(function ($) {
	'use strict';

	var PDF_SVG =
		'<svg class="tf-receipt-upload__pdf-icon" xmlns="http://www.w3.org/2000/svg" width="40" height="40" ' +
		'viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" ' +
		'stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>' +
		'<polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/>' +
		'<line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>';

	function escapeHtml(str) {
		var div = document.createElement('div');
		div.appendChild(document.createTextNode(str));
		return div.innerHTML;
	}

	function init() {
		if (!$('#tf-receipt-upload').length || typeof tfReceipt === 'undefined') return;

		var C            = tfReceipt;
		var $dropzone    = $('#tf-receipt-dropzone');
		var $fileInput   = $('#tf-receipt-file');
		var $preview     = $('#tf-receipt-preview');
		var $previewBody = $('#tf-receipt-preview-inner');
		var $removeBtn   = $('#tf-receipt-remove');
		var $submitBtn   = $('#tf-receipt-submit');
		var $msg         = $('#tf-receipt-msg');
		var $remaining   = $('#tf-receipt-remaining');
		var selectedFile = null;

		// Set i18n texts.
		$dropzone.find('.tf-receipt-upload__drop-text').text(C.i18n.drop_text);
		$dropzone.find('.tf-receipt-upload__or-text').text(C.i18n.or_text);
		$('#tf-receipt-browse').text(C.i18n.browse_text);
		$submitBtn.text(C.existing_url ? C.i18n.replace_text : C.i18n.upload_text);

		// Show remaining uploads counter.
		updateRemaining(C.remaining_uploads);

		// If limit reached and no existing receipt, lock the form.
		if (C.remaining_uploads <= 0 && !C.existing_url) {
			$dropzone.hide();
			$submitBtn.hide();
			showMsg(C.i18n.error_limit, 'error');
			return;
		}

		// If a receipt was already uploaded, show it.
		if (C.existing_url) {
			showExisting(C.existing_url, C.existing_type);
		}

		// ── Events ───────────────────────────────────────────────

		$('#tf-receipt-browse').on('click', function () { $fileInput.trigger('click'); });

		$fileInput.on('change', function () {
			if (this.files && this.files[0]) handleFile(this.files[0]);
		});

		$dropzone
			.on('dragover dragenter', function (e) {
				e.preventDefault();
				e.stopPropagation();
				$(this).addClass('tf-receipt-upload__dropzone--active');
			})
			.on('dragleave drop', function (e) {
				e.preventDefault();
				e.stopPropagation();
				$(this).removeClass('tf-receipt-upload__dropzone--active');
			})
			.on('drop', function (e) {
				var files = e.originalEvent.dataTransfer.files;
				if (files && files[0]) handleFile(files[0]);
			});

		$removeBtn.on('click', function () {
			selectedFile = null;
			$preview.hide();
			$dropzone.show();
			$submitBtn.prop('disabled', true).text(C.existing_url ? C.i18n.replace_text : C.i18n.upload_text);
			$fileInput.val('');
			hideMsg();
		});

		$submitBtn.on('click', function () {
			if (selectedFile) upload(selectedFile);
		});

		// ── Helpers ──────────────────────────────────────────────

		function handleFile(file) {
			hideMsg();

			if (C.remaining_uploads <= 0) { showMsg(C.i18n.error_limit, 'error'); return; }
			if (file.size > C.max_size) { showMsg(C.i18n.error_size, 'error'); return; }

			var allowed = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];
			if (allowed.indexOf(file.type) === -1) { showMsg(C.i18n.error_type, 'error'); return; }

			selectedFile = file;
			previewFile(file);
			$submitBtn.prop('disabled', false).text(C.existing_url ? C.i18n.replace_text : C.i18n.upload_text);
		}

		function previewFile(file) {
			$previewBody.empty();
			$dropzone.hide();
			$removeBtn.show();

			if (file.type.indexOf('image/') === 0) {
				var reader = new FileReader();
				reader.onload = function (e) {
					$previewBody.html(
						'<img src="' + e.target.result + '" alt="Preview" class="tf-receipt-upload__thumb" />' +
						'<span class="tf-receipt-upload__fname">' + escapeHtml(file.name) + '</span>'
					);
				};
				reader.readAsDataURL(file);
			} else {
				$previewBody.html(
					PDF_SVG +
					'<span class="tf-receipt-upload__fname">' + escapeHtml(file.name) + '</span>'
				);
			}

			$preview.show();
		}

		function showExisting(url, type) {
			$previewBody.empty();
			$dropzone.hide();

			var inner = (type === 'image')
				? '<img src="' + url + '" alt="Comprobante" class="tf-receipt-upload__thumb" />'
				: PDF_SVG;
			inner += '<span class="tf-receipt-upload__fname">Comprobante actual</span>';

			$previewBody.html(inner);
			$preview.show();

			// Allow replacing only if uploads remain.
			if (C.remaining_uploads > 0) {
				$removeBtn.show();
				$submitBtn.text(C.i18n.replace_text).prop('disabled', true).show();
			} else {
				$removeBtn.hide();
				$submitBtn.hide();
			}
		}

		function upload(file) {
			var fd = new FormData();
			fd.append('action', 'tf_upload_receipt');
			fd.append('nonce', C.nonce);
			fd.append('order_id', C.order_id);
			fd.append('order_key', C.order_key);
			fd.append('receipt', file);

			$submitBtn.prop('disabled', true).text(C.i18n.uploading);
			hideMsg();

			$.ajax({
				url: C.ajax_url,
				type: 'POST',
				data: fd,
				processData: false,
				contentType: false,
				success: function (res) {
					if (res.success) {
						showMsg(res.data.message, 'success');
						C.existing_url  = res.data.url;
						C.existing_type = res.data.type;
						C.remaining_uploads = res.data.remaining_uploads;
						updateRemaining(C.remaining_uploads);
						showExisting(res.data.url + '&t=' + Date.now(), res.data.type);
						selectedFile = null;
						$fileInput.val('');
					} else {
						showMsg(res.data.message || C.i18n.error_generic, 'error');
						$submitBtn.prop('disabled', false).text(C.i18n.upload_text);
					}
				},
				error: function () {
					showMsg(C.i18n.error_generic, 'error');
					$submitBtn.prop('disabled', false).text(C.i18n.upload_text);
				}
			});
		}

		function updateRemaining(count) {
			if (count <= 0) {
				$remaining.text(C.i18n.error_limit).show();
			} else if (count === 1) {
				$remaining.text(C.i18n.remaining_one).show();
			} else {
				$remaining.text(C.i18n.remaining_many.replace('%d', count)).show();
			}
		}

		function showMsg(text, type) {
			$msg.text(text)
				.removeClass('tf-receipt-upload__msg--success tf-receipt-upload__msg--error')
				.addClass('tf-receipt-upload__msg--' + type)
				.show();
		}

		function hideMsg() { $msg.hide().text(''); }
	}

	$(init);

})(jQuery);
