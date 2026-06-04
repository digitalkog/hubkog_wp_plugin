# HK HubKOG Integration Plugin

## Purpose

This repository is a WordPress plugin that submits Gravity Forms enquiries to the HubKOG API.

The plugin:

- Adds a WordPress settings page for HubKOG API URL, API key, disabled Gravity Forms, and product-interest terms.
- Hooks `gform_after_submission` and formats submitted Gravity Forms entries into the HubKOG payload shape.
- Stores every attempted HubKOG payload in a custom WordPress database table, `${wpdb->prefix}hubkog`.
- Updates stored rows with `hubkog_uid` when HubKOG returns a successful UID.
- Retries failed/unconfirmed sends with a custom WP-Cron schedule and an admin "Retry" button.
- Displays failed HubKOG submissions in a WP admin list table.

## Important Files

- `hk-hubkog-integration.php` is the plugin entrypoint and configuration bootstrap.
- `classes/HkHubkogIntegrationCore.php` contains activation, hooks, submission formatting, HubKOG API calls, retry logic, cron, and lead-source metadata collection.
- `classes/admin/HkHubkogSettingsPage.php` builds the settings page under WordPress Settings.
- `classes/admin/HubkogResultsPage.php` builds the failed-submissions results page and retry table.
- `classes/admin/js/dk-integration.js` calls `admin-ajax.php` with `action=retryhubkog`.
- `classes/admin/css/dk-integration.css` currently has no CSS rules.
- `composer.json` configures PSR-4 autoloading for the `HkHubkog\\` namespace from `classes/`.

## Runtime Dependencies

- WordPress plugin environment.
- Gravity Forms plugin, checked with `is_plugin_active('gravityforms/gravityforms.php')`.
- Composer autoload generated in `vendor/`.
- A global class named `additional_email_notification_using_dk_fm_tool`, used by `format_for_hubkog()` for spam detection.
- cURL extension for HubKOG API requests.
- WordPress database access through `$wpdb`.

## Data Flow

1. On activation, `HkHubkogIntegrationCore::create_database_table()` creates `${wpdb->prefix}hubkog`.
2. During init, if Gravity Forms is active, the plugin registers `post_to_third_party()` on `gform_after_submission`.
3. `post_to_third_party()` skips forms checked in the settings as "Select forms to not integrate".
4. Submission data is transformed by `format_for_hubkog()`.
5. The transformed payload is inserted into `${wpdb->prefix}hubkog` before the external API call.
6. HubKOG is called with a `PUT` request to the configured API URL, with `Authorization: Bearer {api_key}` and `http_build_query($data)` as the body.
7. If the response includes `data.uid`, the database row is updated with `hubkog_uid` and `updated_at`.
8. Rows without `hubkog_uid` are considered failed or pending and are retried by cron after 5 minutes, or manually from the results page.

## Payload Mapping Notes

`format_for_hubkog()` maps form fields mostly by matching Gravity Forms field labels with regular expressions. Common extracted fields include:

- title, first name, surname, telephone, email
- house number or address line 1
- postcode
- product interests
- enquiry type
- further information/comments
- appointment date, time, and location
- opt-out status
- source URL, user agent, Gravity Forms entry ID, created date
- UTM and click metadata from `gf_entry_meta`

Unconsumed numeric Gravity Forms entry fields are appended to the payload using their form field label.

## Admin Options

The main option name is `hk_hubkog_options`.

Important keys include:

- `api_url`
- `api_key`
- `grouped_product_interest`
- `hk_gravity_form_{form_id}` for forms that should not send to HubKOG

## Known Cautions

- Several SQL queries interpolate IDs or order fields directly. Keep input validation strict before modifying those areas.
- `retry_hubkog()` allows both authenticated and unauthenticated AJAX actions via `wp_ajax_retryhubkog` and `wp_ajax_nopriv_retryhubkog`.
- The retry table display tries to `unserialize()` stored data, while current inserts use `json_encode()`. Manual display may not show the stored name as intended for JSON rows.
- `custom_logs()` creates/checks one log path but writes to `includes/custom_logs.log`.
- `check_and_return_product_ab_term()` instantiates `HkHubkogSettingsPage` from inside core formatting, which may trigger admin-related setup if changed carelessly.
- The cron schedule is custom `everyminute`; avoid creating duplicate schedules or unscheduling unrelated cron events.
- `get_city_from_postcode()` calls `postcodes.io`, but town lookup is currently disabled in `format_for_hubkog()`.

## Development Notes

- Preserve the existing namespace `HkHubkog` and PSR-4 autoload mapping.
- Prefer WordPress APIs for sanitization, options, admin pages, AJAX, cron, and database access.
- Be careful with Gravity Forms field matching because behavior depends on live form labels.
- Do not commit generated logs from `classes/custom_logs.log` or `includes/custom_logs.log`.
- Validate syntax with `php -l` on changed PHP files when PHP is available.
