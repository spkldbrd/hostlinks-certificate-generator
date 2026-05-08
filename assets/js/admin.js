(function ($) {
	'use strict';

	function nonceData() {
		return { _ajax_nonce: hlcAdmin.nonce };
	}

	$(document).on('click', '.hlc-pick-media', function (e) {
		e.preventDefault();
		var target = $('#' + $(this).data('target'));
		var preview = $('#' + $(this).data('preview'));
		var frame = wp.media({ title: 'Select logo', multiple: false, library: { type: 'image' } });
		frame.on('select', function () {
			var att = frame.state().get('selection').first().toJSON();
			target.val(att.id);
			preview.html(att.sizes && att.sizes.medium ? '<img src="' + att.sizes.medium.url + '" alt="" />' : '<img src="' + att.url + '" alt="" />');
		});
		frame.open();
	});

	$(document).on('click', '.hlc-clear-media', function (e) {
		e.preventDefault();
		$('#' + $(this).data('target')).val('');
		$('#' + $(this).data('preview')).empty();
	});

	var searchTimer;
	$('#hlc-user-search').on('input', function () {
		var q = $(this).val().trim();
		clearTimeout(searchTimer);
		if (q.length < 2) {
			$('#hlc-user-suggest').empty();
			return;
		}
		searchTimer = setTimeout(function () {
			$.getJSON(ajaxurl, $.extend({ action: 'hlc_search_users', q: q }, nonceData()))
				.done(function (res) {
					if (!res.success || !res.data) return;
					var html = '';
					res.data.forEach(function (u) {
						if ($('#hlc-user-picked li[data-id="' + u.id + '"]').length) return;
						html += '<button type="button" class="button hlc-suggest-user" style="margin:2px;" data-id="' + u.id + '" data-name="' + $('<div/>').text(u.name).html() + '" data-email="' + $('<div/>').text(u.email).html() + '">' + $('<div/>').text(u.name + ' — ' + u.email).html() + '</button>';
					});
					$('#hlc-user-suggest').html(html);
				});
		}, 300);
	});

	$(document).on('click', '.hlc-suggest-user', function () {
		var id = $(this).data('id');
		var name = $(this).data('name');
		var email = $(this).data('email');
		if ($('#hlc-user-picked li[data-id="' + id + '"]').length) return;
		var li = $('<li/>').attr('data-id', id);
		li.append(document.createTextNode(name + ' — ' + email + ' '));
		var rm = $('<button type="button" class="button-link hlc-remove-user"/>').css('color', '#b32d2e').text('Remove');
		li.append(rm);
		li.append($('<input/>').attr({ type: 'hidden', name: 'hlc_user_ids[]', value: id }));
		$('#hlc-user-picked').append(li);
		$(this).remove();
	});

	$(document).on('click', '.hlc-remove-user', function () {
		$(this).closest('li').remove();
	});
})(jQuery);
