# Hop PushEE

## Usage

The add-on will contact OneSignal API to send a push notifications to your subscribers when certain conditions are met.

You have to define custom field(s) that will trigger the push notification.

OneSignal (and other services) uses _segments_ to categorize users. The add-on will only send notifications to specific segments (not to all the subscribers), so make sure you have segments in place in your OneSignal dashboard.

To define which segments should receive the notification when submitting a new entry, we're using categories. The category must have a custom field containing the corresponding segment name.

You have to select which entry statuses would allow a notification to be triggered, in the add-on settings.

Note that the add-on doesn't provide any frontend tags or tools to offer visitors to subscribe to your site notifications. To do that, please read the [OneSignal Web Push SDK documentation](https://documentation.onesignal.com/docs/web-push-sdk) and use your own javascript code.

### 1. Create segments

Go to your OneSignal Dashboard and create segments to categorize your subscribers.

Let's say we have 2 segments for 2 different news categories: `Subscribers News Economy` and `Subscribers News Environment`.

### 2. Create categories and category custom fields for segments

You need to make sure to have categories for each corresponding user segments.

We already have some categories setup for our news entries (Economy, Environment, Social...). We'll create a custom category field named `OneSignal Segment`. For categories that have a corresponding segment, we'll just enter the segment name into that field and save the category.

We edit the Economy category, and enter `Subscribers News Economy` in the newly created `OneSignal Segment` field, and hit save. We do the same for our Environment category.

Note: OneSignal has a built-in segment called `All` that contains all subscribers. It can be used to send notifications to all your subscribers.

### 3. Create a custom field to trigger notifications

Create a custom field that will trigger notifications. The add-on only accepts **Select Dropdown** or **Radio Buttons** fields.

The field needs to have 3 values:

* Don't send
* Send
* Sent

When the field is set to `Send`, the add-on will try to trigger a notification if all the conditions are met.

If the notification is properly sent, the value of the field will be automatically changed to `Sent`.

### 4. Setup comment form URL in channels settings

The default notification URL is generated using the Comment form URL set in the channel settings, just like `{comment_url_title_auto_path}` does. Make sure it's correctly set, even if you set the URL in the template (see below).

### 5. Create a template for notifications

To allow you to easily customize the notification, you can setup custom logic for every element of the notification directly in a template, using the same EE tags as in any template.

Here's a very short example:

```
{!-- Icon, title and url are set using a specific tag --}
{exp:hop_pushee:set_icon}https://site.com/images/icon.png{/exp:hop_pushee:set_icon}
{exp:hop_pushee:set_title}{title} - from {site_name}{/exp:hop_pushee:set_title}
{exp:hop_pushee:set_url}{url_title_path="news/item"}{/exp:hop_pushee:set_url}

{!-- Everything else that's directly outputed in the template is used as the notification content --}
{if summary == ''}Click here to read the article{if:else}{summary}{/if}
```

Note how you don't need any `{exp:channel:entries}` tag in the template.

The notification content cannot contain any HTML, it needs to be text only.

### 6. Setup the add-on

Go to the add-on settings.

* Enter your One Signal app id and API key
* Select which entry custom field is used to trigger the notification
* Select which category custom field is used for defining user segments (in our example, the field is called `OneSignal Segment`)
* Select which entry statuses would allow a notification to be triggered (usually `open`, and more depending on your custom statuses)
* Enter an icon URL (must be an absolute URL, using https)
* Select the template for the notification content

### 7. Publish an entry and trigger push notification

When publishing an entry that needs to be send to your subscribers :

* Choose the proper value in the dropdown or readio button `Send`
* Select the proper categories, to let the add-on know what segment(s) to send that notification to

## Support

Having issues ? Found a bug ? Suggestions ? Contact us at [tech@hopstudios.com](mailto:tech@hopstudios.com)


## Changelog

### 2.0.1

* Removing channel short name restriction

### 2.0.0

* Renamed to Hop Pushee

### 1.0.5

* UI improvements

### 1.0.4

* Several bug fixes for EE3 and EE4

### 1.0.3

* EE2 version added
* Small bug fixes

### 1.0.2

* UI improvement
* Small bug fixes

### 1.0.1

* Change hook to trigger notifications on entry creation or entry edit

### 1.0.0

* Initial Release


## License
Updated: Jan. 6, 2009

#### Permitted Use

One license grants the right to perform one installation of the Software. Each additional installation of the Software requires an additional purchased license. For free Software, no purchase is necessary, but this license still applies.

#### Restrictions

Unless you have been granted prior, written consent from Hop Studios, you may not:

* Reproduce, distribute, or transfer the Software, or portions thereof, to any third party.
* Sell, rent, lease, assign, or sublet the Software or portions thereof.
* Grant rights to any other person.
* Use the Software in violation of any U.S. or international law or regulation.

#### Display of Copyright Notices

All copyright and proprietary notices and logos in the Control Panel and within the Software files must remain intact.
Making Copies

You may make copies of the Software for back-up purposes, provided that you reproduce the Software in its original form and with all proprietary notices on the back-up copy.

#### Software Modification

You may alter, modify, or extend the Software for your own use, or commission a third-party to perform modifications for you, but you may not resell, redistribute or transfer the modified or derivative version without prior written consent from Hop Studios. Components from the Software may not be extracted and used in other programs without prior written consent from Hop Studios.

#### Technical Support

Technical support is available through e-mail, at sales@hopstudios.com. Hop Studios does not provide direct phone support. No representations or guarantees are made regarding the response time in which support questions are answered.

#### Refunds

Hop Studios offers refunds on software within 30 days of purchase. Contact sales@hopstudios.com for assistance. This does not apply if the Software is free.

#### Indemnity

You agree to indemnify and hold harmless Hop Studios for any third-party claims, actions or suits, as well as any related expenses, liabilities, damages, settlements or fees arising from your use or misuse of the Software, or a violation of any terms of this license.

#### Disclaimer Of Warranty

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESSED OR IMPLIED, INCLUDING, BUT NOT LIMITED TO, WARRANTIES OF QUALITY, PERFORMANCE, NON-INFRINGEMENT, MERCHANTABILITY, OR FITNESS FOR A PARTICULAR PURPOSE. FURTHER, HOP STUDIOS DOES NOT WARRANT THAT THE SOFTWARE OR ANY RELATED SERVICE WILL ALWAYS BE AVAILABLE.

#### Limitations Of Liability

YOU ASSUME ALL RISK ASSOCIATED WITH THE INSTALLATION AND USE OF THE SOFTWARE. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS OF THE SOFTWARE BE LIABLE FOR CLAIMS, DAMAGES OR OTHER LIABILITY ARISING FROM, OUT OF, OR IN CONNECTION WITH THE SOFTWARE. LICENSE HOLDERS ARE SOLELY RESPONSIBLE FOR DETERMINING THE APPROPRIATENESS OF USE AND ASSUME ALL RISKS ASSOCIATED WITH ITS USE, INCLUDING BUT NOT LIMITED TO THE RISKS OF PROGRAM ERRORS, DAMAGE TO EQUIPMENT, LOSS OF DATA OR SOFTWARE PROGRAMS, OR UNAVAILABILITY OR INTERRUPTION OF OPERATIONS.