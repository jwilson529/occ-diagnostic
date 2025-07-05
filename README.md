OneClickContent Advanced Diagnostic
Overview
The OneClickContent Advanced Diagnostic plugin is a WordPress tool designed to help administrators diagnose connectivity and configuration issues with the OneClickContent API. It runs a series of tests to verify API endpoints, license key validity, and response data, providing detailed results that can be copied and shared with support for troubleshooting.
Features

License Key Management: Enter and save your OneClickContent API license key.
Diagnostic Tests: Run automated tests to check API connectivity, license validation, subscriber usage, and metadata generation.
Detailed Results: View test results in a clear, tabular format with pass/fail indicators and detailed response metadata.
Clipboard Support: Easily copy test results to the clipboard for sharing with the OneClickContent support team.
Secure and User-Friendly: Includes nonce verification for security and a simple interface integrated into the WordPress admin.

Requirements

WordPress 5.0 or higher
PHP 7.4 or higher
A valid OneClickContent API license key
HTTPS or localhost environment (recommended for clipboard functionality)

Installation

Download the Plugin:

Download the plugin ZIP file from the official source or repository.


Upload to WordPress:

In your WordPress admin dashboard, navigate to Plugins > Add New.
Click Upload Plugin and select the downloaded ZIP file.
Click Install Now.


Activate the Plugin:

After installation, click Activate Plugin to enable OneClickContent Advanced Diagnostic.


Alternative: Manual Installation:

Unzip the plugin file and upload the folder to /wp-content/plugins/.
Activate the plugin from the WordPress Plugins menu.



Usage

Access the Diagnostic Tool:

In the WordPress admin dashboard, go to Tools > OCC Advanced Diagnostic.


Enter License Key:

On the diagnostic page, enter your OneClickContent API license key in the provided field.
Click Save License Key to store it securely.


Run Diagnostic Tests:

Click the Run Diagnostic Tests button to initiate the tests.
The plugin will perform checks, including:
Step 0: Validate License: Verifies the license key with the OneClickContent API.
Step 1: Subscriber Check Usage: Checks subscription details, usage limits, and remaining counts.
Step 2: Subscriber Generate Meta: Tests metadata generation for subscribers.
Step 3: Free Trial Generate Meta: Tests metadata generation for free trial users.


Results are displayed in a table with columns for Test Name, Status (✅ PASSED or ❌ FAILED), and Details.


Copy Results:

After tests complete, click Copy Results to Clipboard to copy the results, including test names, statuses, details, and raw API responses.
Paste the results into an email and send to support@oneclickcontent.com for assistance.


Troubleshooting:

If tests fail, review the Details column for error messages (e.g., connection issues or invalid license).
Ensure your server allows outbound HTTPS requests to the OneClickContent API.



Example Clipboard Output
OneClickContent Diagnostic Results

Step 0: Validate License
✅ PASSED
Status: 200
Message: Site registered successfully.
Raw Response:
Status: 200, Response: Array
(
    [message] => Site registered successfully.
    [status] => success
)

Step 1: Subscriber Check Usage
✅ PASSED
Status: 200
Subscription: $4.99 - One Month - 100 Images|4.99
Usage Limit: 100
Used Count: 21
Remaining Count: 79
Raw Response:
Status: 200, Response: Array
(
    [success] => 1
    [license_key] => PK-OQZPUShcEIXaGS37fFrE
    [subscription] => $4.99 - One Month - 100 Images|4.99
    [usage_limit] => 100
    [addon_count] => 0
    [used_count] => 21
    [remaining_count] => 79
    [next_billing_date] => 
)
...

Frequently Asked Questions
Why are my test results showing errors?

Invalid License Key: Ensure your license key is correct and active.
Connection Issues: Check if your server’s firewall or hosting provider is blocking outbound HTTPS requests to the OneClickContent API.
Server Configuration: Verify that PHP cURL and HTTPS are enabled on your server.

Why can’t I copy results to the clipboard?

Ensure your site is running on HTTPS or localhost, as the modern Clipboard API requires a secure context.
If using HTTP, the plugin uses a fallback method, but some browsers may restrict it.
Check the browser console (F12) for errors and contact support if issues persist.

How do I contact support?

Copy the diagnostic results and email them to support@oneclickcontent.com with a description of your issue.

Support
For assistance, please:

Run the diagnostic tests and copy the results.
Email support@oneclickcontent.com with the results and a detailed description of your issue.
Include your WordPress version, PHP version, and any relevant server details.

Changelog
1.0.0

Initial release with license key management, diagnostic tests, and clipboard functionality.

License
This plugin is licensed under the GPLv2 or later.
Contributing
Contributions are welcome! Please submit pull requests or issues to the plugin’s repository (if available) or contact support@oneclickcontent.com.