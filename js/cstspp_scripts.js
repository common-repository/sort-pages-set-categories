// scripts.js
jQuery(document).ready(function ($) {
	$('#cstspp-the-list tbody').sortable({
		handle: '.cstspp-page-title',
		update: function (event, ui) {
			// Do nothing here, we'll save on button click
		}
	});

	$('#cstspp-save-changes-1, #cstspp-save-changes-2').on('click', function () {
		// Save button clicked, disable buttons and show waiting spinner.
		$('#cstspp-save-changes-1, #cstspp-save-changes-2').prop('disabled', true);
		$('#cstspp-save-changes-1, #cstspp-save-changes-2').html('<i class="fas fa-spinner fa-pulse"></i> Saving...');

		// Collect order and categories data
		let order = $('#cstspp-the-list tbody').sortable('toArray', { attribute: 'data-id' });
		let categories = {};

		$('#cstspp-the-list tbody tr').each(function () {
			let pageId = $(this).data('id');
			let category = $(this).find('.cstspp-category-selector').val();
			categories[pageId] = category;
		});

		// Send data to REST API
		$.ajax({
			url: cstsppRestApi.root + 'cstspp/v1/save-order-and-category',
			method: 'POST',
			beforeSend: function (xhr) {
				xhr.setRequestHeader('X-WP-Nonce', cstsppRestApi.nonce);
			},
			data: JSON.stringify({ order: order, categories: categories }),
			contentType: 'application/json',
			complete: function (response) {
				console.log(response);
				location.reload(); // Reload the page to reflect the new order
			}
		});
	});
});