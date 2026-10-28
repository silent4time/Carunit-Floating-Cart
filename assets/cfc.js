(function ($) {
	function box() { return $('#cfc'); }
	function open() { box().addClass('is-open'); $('body').addClass('cfc-lock'); $('#cfc-btn').attr('aria-expanded', 'true'); }
	function close() { box().removeClass('is-open'); $('body').removeClass('cfc-lock'); $('#cfc-btn').attr('aria-expanded', 'false'); }
	$(document)
		.on('click', '#cfc-btn', function (e) { e.preventDefault(); box().hasClass('is-open') ? close() : open(); })
		.on('click', '.cfc-overlay, .cfc-close', close)
		.on('keydown', function (e) { if (e.key === 'Escape') close(); })
		.on('added_to_cart', function () {
			if (window.cfcData && window.cfcData.openOnAdd) { open(); }
			var b = $('#cfc-btn');
			if (b.length) { b.removeClass('cfc-bump'); void b[0].offsetWidth; b.addClass('cfc-bump'); }
		});
})(jQuery);
