(function () {
	'use strict';

	document.addEventListener('DOMContentLoaded', function () {
		if (!window.crAccount || !window.crAccount.ajaxUrl) {
			return;
		}

		function escapeHtml(value) {
			return String(value).replace(/[&<>"]/g, function (char) {
				return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[char];
			});
		}

		function showMessage(form, message, success) {
			var container = document.querySelector('.cr-account__messages');
			if (!container) return;

			container.innerHTML = '<div class="' + (success ? 'cr-account-success' : 'cr-account-errors') + '" role="' + (success ? 'status' : 'alert') + '">' +
				'<p>' + escapeHtml(message) + '</p></div>';
			container.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
		}

		function showErrors(form, errors) {
			var container = document.querySelector('.cr-account__messages');
			if (!container) return;

			container.innerHTML = '<div class="cr-account-errors" role="alert"><ul>' +
				errors.map(function (message) {
					return '<li>' + escapeHtml(message) + '</li>';
				}).join('') +
				'</ul></div>';
			container.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
		}

		function handleForm(form, action, nonce, onSuccess) {
			form.addEventListener('submit', function (event) {
				if (!form.checkValidity()) return;

				event.preventDefault();

				var submit = form.querySelector('.cr-account-submit, .cr-account-logout');
				var data = new FormData(form);
				data.append('action', action);
				data.append('nonce', nonce);

				if (submit) {
					submit.disabled = true;
					if (submit.classList.contains('cr-account-submit')) {
						submit.classList.add('is-loading');
					}
				}

				fetch(window.crAccount.ajaxUrl, {
					method: 'POST',
					credentials: 'same-origin',
					body: data
				})
				.then(function (response) { return response.json(); })
				.then(function (result) {
					if (result.success) {
						if (onSuccess) onSuccess(result.data || {});
						return;
					}

					var errors = result.data && result.data.errors
						? result.data.errors
						: [result.data && result.data.message ? result.data.message : 'Request failed.'];

					showErrors(form, errors);
				})
				.catch(function () {
					showMessage(form, 'Request failed. Please try again.', false);
				})
				.finally(function () {
					if (submit) {
						submit.disabled = false;
						submit.classList.remove('is-loading');
					}
				});
			});
		}

		document.querySelectorAll('.cr-profile-form').forEach(function (form) {
			handleForm(form, 'cr_update_profile', window.crAccount.profileNonce, function (data) {
			showMessage(form, data.message || 'Profile updated successfully.', true);

			if (data.user) {
				var firstName = form.querySelector('[name="first_name"]');
				var lastName = form.querySelector('[name="last_name"]');
				var email = form.querySelector('[name="email"]');

				if (firstName) firstName.value = data.user.first_name || '';
				if (lastName) lastName.value = data.user.last_name || '';
				if (email) email.value = data.user.email || '';
			}
		});
		});

		document.querySelectorAll('.cr-password-form').forEach(function (form) {
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

			handleForm(form, 'cr_change_password', window.crAccount.passwordNonce, function (data) {
			showMessage(form, data.message || 'Password changed successfully.', true);
			form.reset();
			if (confirm) confirm.setCustomValidity('');
			});
		});

		document.querySelectorAll('.cr-account__logout-form').forEach(function (form) {
			handleForm(form, 'cr_logout_user', window.crAccount.logoutNonce, function (data) {
			if (data.redirect) {
				window.location.href = data.redirect;
			}
		});
		});
	});
}());
