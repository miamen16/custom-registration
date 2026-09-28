(function () {
	'use strict';

	document.addEventListener('DOMContentLoaded', function () {
		document.querySelectorAll('.cr-registration__form').forEach(function (form) {
			var password = form.querySelector('[name="password"]');
			var confirmPassword = form.querySelector('[name="password_confirm"]');
			var submit = form.querySelector('.cr-submit');

			if (password && confirmPassword) {
				confirmPassword.addEventListener('input', function () {
					if (confirmPassword.value && password.value !== confirmPassword.value) {
						confirmPassword.setCustomValidity('Passwords do not match.');
					} else {
						confirmPassword.setCustomValidity('');
					}
				});

				password.addEventListener('input', function () {
					confirmPassword.dispatchEvent(new Event('input'));
				});
			}

			form.addEventListener('submit', function () {
				if (submit) {
					submit.disabled = true;
					submit.setAttribute('aria-disabled', 'true');
					submit.classList.add('is-loading');
				}
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
