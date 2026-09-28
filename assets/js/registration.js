(function () {
	'use strict';

	document.addEventListener('DOMContentLoaded', function () {
		document.querySelectorAll('.cr-registration__form').forEach(function (form) {
			var password = form.querySelector('[name="password"]');
			var confirmPassword = form.querySelector('[name="password_confirm"]');
			var submit = form.querySelector('.cr-submit');
			var strength = form.querySelector('.cr-password-strength');
			var strengthText = strength ? strength.querySelector('.cr-password-strength__text') : null;

			function updatePasswordState() {
				if (!password) {
					return;
				}

				var value = password.value;
				var score = 0;

				if (value.length >= 8) {
					score++;
				}
				if (/[a-z]/.test(value)) {
					score++;
				}
				if (/[A-Z]/.test(value)) {
					score++;
				}
				if (/\d/.test(value)) {
					score++;
				}
				if (/[^A-Za-z0-9]/.test(value)) {
					score++;
				}

				if (strength) {
					strength.setAttribute('data-strength', value ? String(score) : '0');
				}

				if (strengthText) {
					var labels = ['', 'Very weak', 'Weak', 'Fair', 'Good', 'Strong'];
					strengthText.textContent = value ? labels[score] : '';
				}

				if (confirmPassword) {
					confirmPassword.setCustomValidity(
						confirmPassword.value && password.value !== confirmPassword.value
							? 'Passwords do not match.'
							: ''
					);
				}
			}

			if (password) {
				password.addEventListener('input', updatePasswordState);
			}

			if (confirmPassword) {
				confirmPassword.addEventListener('input', updatePasswordState);
			}

			form.addEventListener('submit', function (event) {
				if (!window.crRegistration || !window.crRegistration.ajaxUrl) {
					return;
				}

				if (!form.checkValidity()) {
					return;
				}

				event.preventDefault();

				var formData = new FormData(form);
				formData.append('action', 'cr_register_user');
				formData.append('nonce', window.crRegistration.nonce);

				if (submit) {
					submit.disabled = true;
					submit.setAttribute('aria-disabled', 'true');
					submit.classList.add('is-loading');
				}

				fetch(window.crRegistration.ajaxUrl, {
					method: 'POST',
					credentials: 'same-origin',
					body: formData
				})
					.then(function (response) {
						return response.json().then(function (result) {
							if (!response.ok && !result) {
								throw new Error('Request failed.');
							}
							return result;
						});
					})
					.then(function (result) {
						if (result.success && result.data && result.data.redirect) {
							window.location.href = result.data.redirect;
							return;
						}

						var messages = result.data && result.data.errors
							? result.data.errors
							: [result.data && result.data.message ? result.data.message : 'Registration failed.'];

						var errorBox = form.querySelector('.cr-errors');

						if (!errorBox) {
							errorBox = document.createElement('div');
							errorBox.className = 'cr-errors';
							errorBox.setAttribute('role', 'alert');
							form.prepend(errorBox);
						}

						errorBox.innerHTML = '<ul>' + messages.map(function (message) {
							return '<li>' + String(message).replace(/[&<>"]/g, function (char) {
								return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[char];
							}) + '</li>';
						}).join('') + '</ul>';
						errorBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
					})
					.catch(function () {
						alert('Registration failed. Please try again.');
					})
					.finally(function () {
						if (submit) {
							submit.disabled = false;
							submit.removeAttribute('aria-disabled');
							submit.classList.remove('is-loading');
						}
					});
			});
		});

		document.querySelectorAll('[data-cr-toggle-password]').forEach(function (button) {
			button.addEventListener('click', function () {
				var targetId = button.getAttribute('data-cr-toggle-password');
				var input = document.getElementById(targetId);

				if (!input) {
					return;
				}

				var isPassword = input.type === 'password';
				input.type = isPassword ? 'text' : 'password';
				button.setAttribute('aria-pressed', isPassword ? 'true' : 'false');
				button.textContent = isPassword ? 'Hide' : 'Show';
			});
		});
	});
}());
