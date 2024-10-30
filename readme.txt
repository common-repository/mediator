=== Mediator ===
Contributors: mediatortech
Donate link: http://mediator.tech/donate-us/
Tags: adblock, anti adblock, adsense, facebook, mailchimp
Requires at least: 4.0
Tested up to: 4.7
Stable tag: trunk
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Detect adblock visitors and convert them to useful marketing goals. Block content to adblockers and show them antiadblock message. Full analytics.

== Description ==

Mediator identifies visitors with active AdBlock software on your website and convert them to useful MailChimp subscribers or Facebook likes without loss in ad revenue. It’s easy to use plugin that helps you increase social sharing and traffic or just replace AdSense with custom html code when AdBlock is detected. Also it is possible to block content to ad-blocker users and ask them to deactivate AdBlock. Detailed day-to-day statistics and analytics is included.

**NB!** To make plugin working you need to fill out Settings form AND then insert one AdSense banner through plugin. Please, read Installation quide or watch quick start quide video here: https://www.youtube.com/watch?v=smvMq2I589Q

**Mediator’s features:**

*   Detect adblock software (AdBlock Plus, Ghostery, Brave, Ad Muncher etc.)
*   Replace blocked ads with Facebook like button option
*   Replace blocked ads with MailChimp subscription box option
*   Replace blocked ads with any custom html code
*   Block content by gradually blurring text to visitors with adblock and notify them with custom or default message
*   Supports Google AdSense or any other ads
*   Works in all modern browsers
*   Embed your ad using widgets or shortcode
*   Compatible with other shortcode plugins
*   Measure the conversion for each channel
*   SEO friendly (does not increase bounce rate)
*   Responsive design friendly

**Built-in analytics and statistics measure:**

*   How many of your visitors use adblocking software
*   How many of them were converted to Facebook likes
*   How many of them were converted to MailChimp subscribers
*   How many of them disabled AdBlock after showing and alert

Your feedback is more than welcome! Please report if anything goes wrong at hello@mediator.tech. We will get back to you ASAP.

Support in English, Spanish and Russian is also available here: http://mediator.tech/wp-support/

== Installation ==

**Automatic installation**

Go to WordPress Plugins menu, click Add New button, search for "Mediator" and click Install Now and Activate it.

**Manual installation**

Download the plugin as .zip file, then go to WordPress Plugins and click Add New, upload Mediator plugin. Choose zip file with plugin, click on Install Now and Activate it.

**Quick start guide video**

https://www.youtube.com/watch?v=smvMq2I589Q

**How to configure it and how it works?**

To make plugin working you need to make two steps:

1) After installing and activating plugin the Mediator tab will appear on the left sidebar of your WordPress dashboard. Open it and click ‘Settings’.

There you should paste AdSense or other ad code. Then select alternative content, that will be shown instead of ad, if it will be blocked:

*   Showing your adblock visitors MailChimp subscription form. You can use our preset form or customize it with custom CSS class mediator_tech_mailchimp. If you need any help with it please let us know by email hello@mediator.tech
*   Showing your adblock visitors Facebook like button, button with counts, like box with counts or just standard like button. 
*   Showing your adblock visitors your custom ads or text. You can use any html code or shortcode here.
*   Block content with blurring the content and show visitors our default or custom message asking to disable adblock. You can choose after what number of page show alert image.

After you finish filling out settings, make sure that ‘Enabled’ check box is checked and click ‘Save settings’.

2) Now you can add ad to your website (you need to do it to make plugin working!). There are different ways to do it:

*   Embed it using widget (Appearance -> Widgets) to your sidebar or any other areas, supported by your theme.
*   Embed it using shortcode to your posts or pages (click on the button with $ sign on toolbar)
*   Embed it to php template using following code:
	&lt;?php echo do_shortcode( '[mediator_tech]' ); ?>
*   If you are already using some plugin or built-in theme settings to add AdSense on your website, you can just use [mediator_tech] instead of AdSense code.

In all cases, plugin will try to show an ad. If it will be blocked by any adblocking software, visitor will see chosen alternative content instead of it.

Too see the statistics open Mediator -> Statistics.

Your feedback is more than welcome! Please report if anything goes wrong at hello@mediator.tech. We will get back to you ASAP.

Support in English, Spanish and Russian is also available here: http://mediator.tech/wp-support/

== Frequently Asked Questions ==

= Can I use Mediator plugin with in Newspaper theme by tagdiv? =

Yes. In that case you should fill the settings form of Mediator Plugin. Then go to Newspaper -> Theme Panel -> ADS. And type [mediator_tech] instead of AdSense code. Do not type anything else except it.

== Screenshots ==

1. Screenshot of statistics dashboard
2. Example of blocking content from adblock user and asking him to disable AdBlock.
3. Example of MailChimp subscription form apperead instead of banner
4. Settings page.

== Changelog ==

= 1.0 =
* Initial commit.

== Upgrade Notice ==

= 1.0 =
* Initial commit.

== Arbitrary section ==

*	Plugin counts not real facebook likes, but clicks-over-the-button. Since the majority of users are logged in on facebook, such statistics is 99% accurate.
*	In case of mailchimp subscribers, plugin automaticaly put the 'Subscribed from' field into your subscribers list. It allows you track and create segments of only adblocking subscribers. Also it helps to understand which page of your website is more effective
*	In both cases Mediator counts only its clicks and subscribers. For example, if you have other mailchimp subscription form somewhere on your websites, Mediator will not count users, who subscribed there. In other words, Mediator plugin is designed to track only extra actions and goals, that were achieved by virtue of it. So you can track how efficient the plugin is and which alternative content is better.