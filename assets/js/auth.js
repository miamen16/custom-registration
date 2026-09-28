(function () {
	'use strict';

	document.addEventListener('DOMContentLoaded', function () {
		function escapeHtml(value) {
			return String(value).replace(/[&<>"]/g, function (char) {
				return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[char];
			});
		}

		function handleAjaxForm(form, action, nonce, successHandler) {
			form.addEventListener('submit', function (event) {
			if (!window.crAuth || !window.crAuth.ajaxUrl || !form.checkValidity()) return;
			event.preventDefault();

			var submit = form.querySelector('.cr-auth-submit');
			var messages = form.querySelector('.cr-auth__messages');
			var data = new FormData(form);
			data.append('action', action);
			data.append('nonce', nonce);

			if (submit) {
				submit.disabled = true;
				submit.classList.add('is-loading');
			}

			fetch(window.crAuth.ajaxUrl, {
				method: 'POST',
				credentials: 'same-origin',
				body: data
			})
			.then(function (response) { return response.json(); })
			.then(function (result) {
				if (result.success) {
					if (successHandler) successHandler(result.data || {});
					return;
				}

				var errors = result.data && result.data.errors
					? result.data.errors
					: [result.data && result.data.message ? result.data.message : 'Request failed.'];

				if (messages) {
					messages.innerHTML = '<div class="cr-auth-errors" role="alert"><ul>' +
						errors.map(function (message) {
							return '<li>' + escapeHtml(message) + '</li>';
						}).join('') +
					'</ul></div>';
					messages.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
				}
			})
			.catch(function () {
				if (messages) {
					messages.innerHTML = '<div class="cr-auth-errors" role="alert"><p>Request failed. Please try again.</p></div>';
				}
			})
			.finally(function () {
				if (submit) {
					submit.disabled = false;
					submit.classList.remove('is-loading');
				}
			});
		});

		return form;
		}

		document.querySelectorAll('.cr-login-form').forEach(function (form) {
			handleAjaxForm(form, 'cr_login_user', window.crAuth.loginNonce, function (data) {
				if (data.redirect) window.location.href = data.redirect;
			});
		});

		document.querySelectorAll('.cr-forgot-form').forEach(function (form) {
			handleAjaxForm(form, 'cr_request_password_reset', window.crAuth.forgotNonce, function (data) {
			var messages = form.querySelector('.cr-auth__messages');
			if (messages) {
				messages.innerHTML = '<div class="cr-auth-success" role="status">' +
					escapeHtml(data.message || 'If an account matches that information, a password reset email has been sent.') +
					'</div>';
			}
		});
		});

		document.querySelectorAll('.cr-reset-form').forEach(function (form) {
			var password = form.querySelector('[name="password"]');
			var confirm = form.querySelector('[name="password_confirm"]');

			function validatePasswords() {
				if (confirm && password) {
					confirm.setCustomValidity(
						confirm.value && confirm.value !== password.value ? 'Passwords do not match.' : ''
					);
				}
			}

			if (password) password.addEventListener('input', validatePasswords);
			if (confirm) confirm.addEventListener('input', validatePasswords);

			handleAjaxForm(form, 'cr_reset_password', window.crAuth.resetNonce, function (data) {
				if (data.redirect) window.location.href = data.redirect;
			});
		});

		document.querySelectorAll('[data-cr-auth-toggle]').forEach(function (button) {
			button.addEventListener('click', function () {
				var input = document.getElementById(button.getAttribute('data-cr-auth-toggle'));
				if (!input) return;

				var visible = input.type === 'password';
				input.type = visible ? 'text' : 'password';
				button.setAttribute('aria-pressed', visible ? 'true' : 'false');
				button.textContent = visible ? 'Hide' : 'Show';
			});
		});
	});
}());
