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
- Email verification with expiring one-time tokens
- Registration blocked from login until email verification
- Resend verification email with rate limiting
- Login brute-force throttling
- Password reset request throttling
- Generic resend/reset responses to reduce account enumeration
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

## Email verification setup

Create a page and add:

```text
[custom_verify_email]
[custom_resend_verification]
```

Then point the verification email to that page using the `cr_email_verification_url` filter. The verification token expires after 24 hours and is removed after successful verification.

## Development roadmap

- Conditional asset loading
- Password strength indicator
- Email verification
- Admin settings
- Elementor integration
- Automated tests and additional security hardening
