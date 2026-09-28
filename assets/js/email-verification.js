(function () {
	'use strict';

	document.addEventListener('DOMContentLoaded', function () {
		if (!window.crEmailVerification || !window.crEmailVerification.ajaxUrl) {
			return;
		}

		document.querySelectorAll('.cr-verification-resend__form').forEach(function (form) {
			form.addEventListener('submit', function (event) {
				if (!form.checkValidity()) return;
				event.preventDefault();

				var button = form.querySelector('button');
				var message = form.querySelector('.cr-verification-resend__message');
				var data = new FormData(form);
				data.append('action', 'cr_resend_verification');
				data.append('nonce', window.crEmailVerification.nonce);

				if (button) button.disabled = true;

				fetch(window.crEmailVerification.ajaxUrl, {
					method: 'POST',
					credentials: 'same-origin',
					body: data
				})
				.then(function (response) { return response.json(); })
				.then(function (result) {
					if (!message) return;
					var text = result.success && result.data && result.data.message
						? result.data.message
						: (result.data && result.data.message ? result.data.message : 'Request failed. Please try again.');
					message.textContent = text;
					message.setAttribute('data-state', result.success ? 'success' : 'error');
				})
				.catch(function () {
					if (message) {
						message.textContent = 'Request failed. Please try again.';
						message.setAttribute('data-state', 'error');
					}
				})
				.finally(function () {
					if (button) button.disabled = false;
				});
			});
		});
	});
}());
