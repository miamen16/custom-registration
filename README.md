# Custom Registration

A lightweight WordPress registration form built from scratch without third-party plugins.

## Current version

**1.0.0**

## Features

- Custom registration shortcode: `[custom_register_form]`
- Custom login shortcode: `[custom_login_form]`
- Custom forgot/reset password flow via `[custom_forgot_password_form]`
- Custom account shortcode: `[custom_account]`
- First name and last name
- Username
- Email
- Password and confirmation
- WordPress nonce protection
- Server-side validation
- Duplicate username/email checks
- Subscriber role for newly registered users
- Automatic login after registration
- Redirect after successful registration
- AJAX login
- Remember Me support
- AJAX forgot password request
- Custom password reset form
- Password reset email with one-time WordPress reset keys
- AJAX profile updates
- Change password with current-password verification
- AJAX logout
- CSS and JS assets for the authentication/account flows

## Installation

1. Clone or download this repository into `wp-content/plugins/custom-registration`.
2. Activate **Custom Registration** from WordPress admin.
3. Create pages for the forms you need.
4. Use the shortcodes:

```
[custom_register_form]
[custom_login_form]
[custom_forgot_password_form]
[custom_account]
```

5. Publish the pages.

## Development roadmap

- Conditional asset loading
- Password strength indicator
- Email verification
- Admin settings
- Elementor integration
- Automated tests and additional security hardening
