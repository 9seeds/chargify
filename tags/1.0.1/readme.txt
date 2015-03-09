=== WP-Chargify ===
Contributors: toddhuish, vegasgeek, stastic, jasonglaspey
Website Link: http://9seeds.com
Tags: Chargify, subscription, registration, tools, membership
Requires: Wordpress 2.9
Tested up to: Wordpress 3.1 RC3
Stable Tag: 1.0.1

=== Description ===

WP-Chargify allows users to integrate the Chargify service with Wordpress. Giving you the ability to run a membership or subscription-based website, controlling access to content based on paid or free subscriptions.

=== Instructions ===

1. Create a Wordpress page where people will sign up for access to your site. This is where a user will be taken to register and begin the signup process.
  a. Place the shortcode [chargify] in the body of your page somewhere to create the sign up form.
     If you are using the accounting code functionality on your products and want to limit by them you'd change the shortcode to something like [chargify accountingcodes=20,22]
  b. You can fill the rest of the page in with whatever else you want, any content that makes sense for you.
  c. Note the URL of this page (full URL)
2. Create a Wordpress page where your users will return to after a successful signup. 
  a. This will be after they complete a successful transaction
  b. This will be useful for tracking analytics. You can use Google Analytics to make this the Goal page, and track signups and conversions as goals.
  c. You should also consider using this page to give information to your users once they've finished paying for their memberships, such as welcome to our site, here's a link to our FAQ. Here are some pages you may want to check out.
  d. The user's login information will come to them in an email. It is helpful to tell them to check their email to get that information and allow them to login.
  e. This email is the typical New User email, and you can modify it via other plugins.
3. Got to the Chargify settings section of the Wordpress admin area.
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
  a. This is CRITICAL. DO NOT SKIP THIS STEP!!! It is the information that is passed after a successful transaction that tells Wordpress to create the new account.
7. Now that you have a product identified, your Wordpress account will use that information and allow you to set pages or posts to private. Go create a test post and look for the Chargify settings within that post edit page, and check that it's for members only (or whatever you named your product).
8. Now, when you try and access that product, you'll be told you have to be logged in to view, and it should give you a link to sign up for an account.
9. Logout of Wordpress and try it. Go through every step and make sure it's working before your turn off the Test function.
10. If that works, you'll need to continue setting up your Chargify account, inputting whatever information you need for your merchant account, payment gateway, or PayPal account. See chargify's support for more information on that.

== Changelog ==

= 1.0.1 =
* Remove hard coded image call
* Continue hoping no one noticed hard coded image call
