=== WP-Chargify ===
Contributors: 9seeds, toddhuish, vegasgeek, jasonglaspey
Website Link: http://9seeds.com
Tags: Chargify, subscription, registration, tools, membership
Requires at least: 2.9
Tested up to: 4.7.4
Stable Tag: 2.0.10

WP-Chargify allows users to integrate the Chargify service with WordPress.

== Description ==

WP-Chargify allows users to integrate the Chargify service with WordPress. Giving you the ability to run a membership or subscription-based website, controlling access to content based on paid or free subscriptions.

== Installation ==

1. Create a WordPress page where people will sign up for access to your site. This is where a user will be taken to register and begin the signup process.
  a. Place the shortcode [chargify] in the body of your page somewhere to create the sign up form.
     If you are using the accounting code functionality on your products and want to limit by them you'd change the shortcode to something like [chargify accountingcodes=20,22]
  b. You can fill the rest of the page in with whatever else you want, any content that makes sense for you.
  c. Note the URL of this page (full URL)
2. Create a WordPress page where your users will return to after a successful signup.
  a. This will be after they complete a successful transaction
  b. This will be useful for tracking analytics. You can use Google Analytics to make this the Goal page, and track signups and conversions as goals.
  c. You should also consider using this page to give information to your users once they've finished paying for their memberships, such as welcome to our site, here's a link to our FAQ. Here are some pages you may want to check out.
  d. The user's login information will come to them in an email. It is helpful to tell them to check their email to get that information and allow them to login.
  e. This email is the typical New User email, and you can modify it via other plugins.
3. Got to the Chargify settings section of the WordPress admin area.
  a. Add your API Key (you get this from Chargify.com when logged in)
  b. Your Test API Key is probably the same.
  c. Your Domain is the subdomain you get when creating a new site inside of the Chargify system
  d. Your Test Domain is probably the same as above.
  e. Mode, leave it Test until you're ready to make it live.
  f. Sign-up Type should be left at Default unless you know what you're doing.
  g. Signup Link - Place the URL to the page you created in step 1.
4. Go to Chargify.com and login. 
5. Create your products at Chargify.com You'll create a product family and products below that. (these will be the different access and product levels). Feel free to just have one product - standard access.
6. While creating your Product, add the following line to your return parameters: subscription_id={subscription_id}&customer_reference={customer_reference}
  a. This is CRITICAL. DO NOT SKIP THIS STEP!!! It is the information that is passed after a successful transaction that tells WordPress to create the new account.
7. Now that you have a product identified, your WordPress account will use that information and allow you to set pages or posts to private. Go create a test post and look for the Chargify settings within that post edit page, and check that it's for members only (or whatever you named your product).
8. Now, when you try and access that product, you'll be told you have to be logged in to view, and it should give you a link to sign up for an account.
9. Logout of WordPress and try it. Go through every step and make sure it's working before your turn off the Test function.
10. If that works, you'll need to continue setting up your Chargify account, inputting whatever information you need for your merchant account, payment gateway, or PayPal account. See chargify's support for more information on that.

== Changelog ==
= 2.0.10 =
* Fix versioning

= 2.0.9 =
* Fix for TLS 1.2 - Force 1.2 and display error if system doesn't support it

= 2.0.8 =
* Fix inifinite slashes in chargify product forms

= 2.0.7 =
* Remove ALL debug code

= 2.0.6 =
* Remove debug code

= 2.0.5 =
* Add filter to change the signup form 
* Add filter to disable automatic login after purchase

= 2.0.4 =
* Make the plugin safe to use with Ampersands in names

= 2.0.3 =
* Add the ability to pass in pre-selected plan to order form

= 2.0.2 =
* Fix cancellations so they are effective no matter where they happen

= 2.0.1 =
* Fix an naked API key
* Fix some warnings

= 2.0 =
* Partially protect content with shortcodes
* Drip content based on number of days after purchase date
* Clean up settings page and simplify the steps for setup
* Added an easy to access help section

= 1.0.4 =
* Refactor for WP 4.1.1
* Automatically create and login user on subscription creation
* Prepare for the new hotness which will be dubbed...2.0

= 1.0.3 =
* Fix API style call

= 1.0.2 =
* Remove default accountingcodes
* Improve email address/username checking
* Improve error handling before posting to chargify
* Clean up display if no subscriptions are found

= 1.0.1 =
* Remove hard coded image call
* Continue hoping no one noticed hard coded image call

== Frequently Asked Questions ==
= What are the new fangled filters I keep hearing about? =
Add extra fields to the bottom of the register forms
`
filter: 'chargify_signup_form_extra'
Input: HTML Form, chargify options, $_REQUEST
Return: Form appended with extra fields
`

Turn off the redirect/login call after successful payment
`
filter: 'chargify_auto_login'
Input: true
Return: true/false
`

Allows the username to be set from the new filtered form
`
filter: 'chargify_signup_username'
Input: email address
Return: Alternate username
`

Allows the password to be set via the new form.
`
filter: 'chargify_signup_pass'
Input: Random standard 12 character WP password
Return: Alternate defined password
`
