=== Nelio A/B Testing ===
Contributors: Nelio
Tags: a/b testing, a/b test, a b test, a b testing, split testing, website optimization, conversion optimization
Requires at least: 3.3
Tested up to: 3.6
Stable tag: 1.0.15
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Nelio A/B Testing is a WordPress service that helps you optimize your site
based on data, not opinions.


== Description ==

[Nelio A/B Testing](http://wp-abtesting.com) is an optimization service for
your WordPres site. It helps you define, manage, and keep track of A/B-testing
experiments from within your WordPress' dashboard, creating a lovely,
integrated, and well-known user experience.

On the technical side, **any gathered information about experiments and
statistical calculus is performed and stored in Nelio's backend servers**. As a
result, the load in your WordPress server does not increase and can focus on
what matters to you: serving information to your visitors as quickly as
possible.

**Note:** Please keep in mind that [you will need an
account](http://wp-abtesting.com) to use our plugin (the service includes a
15-day free trial period).


= Features =

* A/B and multivariate test of WordPress pages (title, content, page style,
page theme options...)
* A/B and multivariate test of WordPress posts (title, content, page style,
post theme options...)
* Fresh information about the evolution of the experiment every day
* Nice graphics about visitors, conversions, improvements, ...
* Definition, management, and tracking of experiments integrated in WordPress
* Gathered information about experiments and statistical calculus stored and
performed in Nelio's backend servers.


== Installation ==

**Before installing the plugin...**

Sign up at our [Nelio A/B Testing service](http://wp-abtesting.com). Once you
are registered, you will be sent an e-mail with your account information, which
is necessary for using the plugin.


**Installation through WordPress admin from plugin repository:**

1. Login to your WordPress admin.
2. Click on the plugins tab.
3. Click the Add New button.
4. Search for Nelio AB Testing or AB Testing
5. Click "Install Now", then Activate, then head to the new menu item on the
left labeled "Nelio A/B Testing".


**Alternative installation methods:**

1. Download this plugin.
2. Login to your WordPress admin.
3. Click on the plugins tab.
4. Click the Add New button.
5. Click the Upload button.
6. Click "Install Now", then Activate, then head to the new menu item on the
left labeled "Nelio A/B Testing".


== Frequently Asked Questions ==

= Why do you need an A/B testing native WordPress solution? =

* **Easier learning curve.** No need to learn yet another tool. Everything
(from the creation of tests to the visualization of the results) is done from
the WordPress dashboard using the same interface you’re already familiar with.
* **Automatic improvement of the WP site.** A native WordPress solution is able
to automatically evolve your WordPress site. For instance, it can update the
site to reflect the winner alternative as soon as the results are statistically
significant. Instead, with a generic tool, once you have the winner, you´ll
need to back to WordPress and manually modify the posts to implement the
changes yourself.
* **Fine-grained testing.** The “testing unit” in a generic tool is the web
page since this the HTML of the page is the only thing those tools have access
to. Instead a native WordPress solution can access all your WordPress
components, including the menu, your widget configuration, theme,… so you can
choose to test a combination of these components (e.g. testing two different
menus across the site) instead a specific page.
* **More control.** What about showing the tests only to (un)registered users?
Or users with a certain role? This kind of control on the testing process can
only be done when the AB Testing tool has access to the internals of your
WordPress installation.


= What are the features implemented so far? =

You can create A/B and multivariate tests for pages, setting the visit to
another page of your site as conversion goal.  You can then see the evolution
and results of the test (so far, results are refreshed every 2 hours but note
that we can change the frequency during the test). Apart from the raw numbers
(number of visits, conversions,…), we have included some easy-to-understand
information about the statistical significance of the results.


= And the rest? =

We'll keep adding more features in the following weeks.


= Can I use this service in different domains? =

Yes. For this version, we permit up to three different simultaneous
domains/websites.


= Any limitation on the number of visitors that can be part of a test? =

There’s no limitation for this version. Note that, this is one of the values
that we will be closely monitoring and we may decide to cap the number of
visits per day.


= How does the service work? =

In short, the service requires you to install a plug-in on your WP site. This
plug-in will monitor the visitors of your site and send anonymous information
(e.g. navigations, conversions, ...) to our backend servers where the processing
of all the data takes place.


= How does the plug-in work (in case you care about technical stuff)? =

We understand that before installing our plugin you may want to know some more
details about how it internally works. Our plug-in is not using redirections or
shortcodes and it is fully compatible with caching plugins. So, then, how does
the plugin component of the service work? In short:

* A small JavaScript code is added to all the pages under test. This JavaScript
is used to replace the content of the original page with the content of an
alternative one when the visitor is (randomly) assigned to an alternative page.
Note that even if the original page was cached the javascript code will be able
to replace the content since it executes on the client side.  Obviously, since
the alternative versions won’t be cached (since they are not real published
pages) and must be served by WP, the loading time for them will be slightly
worse.
* Every time the visitor navigates to another page, the plug-in checks whether
this navigation is relevant (for the purpose of the test) and if so, sends the
details to our cloud service asynchronously.
* From the WP admin area you can see the results of the test. When opening the
results page, the plug-in will request the processed data from the cloud
back-end and display using nice graphics.


== Screenshots ==

1. **Relevant info with a quick glance**. When you have an experiment running,
you can see how it is performing in the Results of the Experiment page.
2. **Data made easy**. Additional graphics help you understand what is going
on.
3. **Experiment Management**. Manage your experiments without leaving
WordPress: creation, deletion, and monitoring!
4. **Experiment Creation**. Creating new experiments has never been so easy!
Just set a name, a description, and the page you want to test!
5. **Alternatives are Regular Pages**. For each page to test, you can create
alternatives quickly and easily, from scratch or from already-existing pages!
6. **Multisite support**. You can use our service in multiple sites.


== Changelog ==

= 1.0.15 =
* Page for selecting new experiment has been redesigned
* Settings page has been redesigned
* Improved sites management
* Some internal improvements

= 1.0.14 =
* Improved plugin stability
* Bug fix: experiments without a goal or alternatives cannot be started
* Bug fix: JS scripts are properly loaded in IE

= 1.0.13 =
* Bug fix: view results page does not freeze when no results are available

= 1.0.12 =
* Stability improvements
* Bug fix: comment count for alternatives is OK

= 1.0.11 =
* Bug fix: showing the winner of an experiment quickly

= 1.0.10 =
* "Progress of the Experiment" page has been redesigned and improved
* Bug fix: alternative posts do no longer appear in the list of posts
* Bug fix: when creating an a/b or multivariate test for posts, we can now
select any post (not only the last five)
* Bug fix: distributing users to different alternatives does no longer fail

= 1.0.9 =
* New feature: experiments can now set either a Page or a Post as its goal

= 1.0.8 =
* Bug fix: add media is working again
* Bug fix: renaming an alternative does no longer remove it

= 1.0.7 =
* New feature: creation of A/B tests for posts too (originally, only for
pages).
* Some minor bug fixes

= 1.0.6 =
* Bug fix: the warning "headers already sent by" does no longer appear

= 1.0.5 =
* Bug fix: assets are properly loaded from the plugin

= 1.0.4 =
* Bug fix: titles are no longer wrapped using SPAN tags. We now use a jQuery
  replaceText function

= 1.0.3 =
* Bug fix: no more redirections using PHP's "header" function

= 1.0.2 =
* Bug fix: statistical info is now working (Strings are properly shown)

= 1.0.1 =
* A few code tweaks

= 1.0.0 (beta) =
* First release of our beta
* Permits the creation of Alternative Experiments for WordPress pages
* Includes a feedback form


== Upgrade Notice ==

= 1.0.15
A few pages have been redesigned. The overall plugin is more robust now.

