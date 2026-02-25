=== Fluent Forms for Brightdoor CRM ===
Contributors: undfine
Tags: fluent forms, fluent forms pro, fluent forms pro integration, Brightdoor
Requires at least: 6.2
Tested up to: 6.2
Requires PHP: 5.6
Stable tag: 1.2.7
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

An integration for Fluent Forms Pro to allow bulk import of contacts using repeater fields

== Description ==

An integration for Fluent Forms to allow bulk import of contacts through repeater fields

= Installation =
Install and activate Fluent Forms
Install and activate plugin
Enable BrightDoor Integration and connect your API key

= Integration Instructions =
Create a new Brighdoor feed for your form.
Select the corresponding fields in Brightdoor

= Hooks & Filters =

**ff_brightdoor_lead_source**
Filter the lead source ID before it is sent to BrightDoor.

```php
add_filter('ff_brightdoor_lead_source', function($lead_source, $feed, $formData, $entry, $form) {
    // Return a different lead source ID based on form data
    return $lead_source;
}, 10, 5);
```

Parameters:
- `$lead_source` (int) - The current lead source ID
- `$feed` (array) - The feed configuration
- `$formData` (array) - The raw submitted form data
- `$entry` (array) - The form entry
- `$form` (object) - The form object

Example — map a UTM value to a specific lead source ID:

```php
add_filter('ff_brightdoor_lead_source', function($lead_source, $feed, $formData, $entry, $form) {
    $utm_source = strtolower($formData['last_utm_source'] ?? '');
    $utm_medium = strtolower($formData['last_utm_medium'] ?? '');

    if ($utm_source === 'meta' || $utm_medium === 'meta') {
        return 48;
    }

    return $lead_source;
}, 10, 5);
```

**fluentform_integration_data_brightdoor**
Filter the full contact payload immediately before it is sent to the BrightDoor API. Useful for adding, removing, or overriding any field.

```php
add_filter('fluentform_integration_data_brightdoor', function($contactData, $feed, $entry) {
    // Modify $contactData before submission
    return $contactData;
}, 10, 3);
```

Parameters:
- `$contactData` (array) - The assembled contact payload
- `$feed` (array) - The feed configuration
- `$entry` (array) - The form entry

== Changelog ==

= 1.2.7 =
* Added `ff_brightdoor_lead_source` filter to allow dynamic lead source mapping
* Added `fluentform_integration_data_brightdoor` filter for full payload control