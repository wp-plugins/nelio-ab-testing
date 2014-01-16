=== Nelio AB Testing ===
Contributors: nelio
Tags: ab testing, ab test, a/b testing, a/b test, a b testing, a b test, split testing, website optimization, conversion optimization, optimization, conversion
Requires at least: 3.3
Tested up to: 3.8
Stable tag: 1.5.6
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
statistical calculus are performed and stored in Nelio's backend servers**. As
a result, the load in your WordPress server does not increase and can focus on
what matters to you: serving information to your visitors as quickly as
possible.

**Note:** Please keep in mind that [you will need an
account](http://wp-abtesting.com/subscription-plans/) to use our plugin (the
service includes a 15-day free trial period).


= Features =

* A/B and multivariate test of WordPress pages (title, content, page style,
page theme options...)
* A/B and multivariate test of WordPress posts (title, content, page style,
post theme options...)
* Select one or more alternative themes among the installed ones and test
which one works better!
* Fresh information about the evolution of the experiment every day
* Nice graphics about visitors, conversions, improvements, ...
* Definition, management, and tracking of experiments integrated in WordPress
* Gathered information about experiments and statistical calculus stored and
performed in Nelio's backend servers.


== Installation ==

**Before installing the plugin...**

Sign up at our [Nelio A/B Testing service](http://wp-abtesting.com/subscription-plans/). Once you
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


** I successfully installed the plugin. What should I do now? **

If you have successfully installed our plugin using one of the aforementioned
methods, now it is just time to use it! Take a look at our [Getting Started
Guide](http://wp-abtesting.com/getting-started-guide/) to cover the basics.


== Frequently Asked Questions ==

= Why do I need an A/B testing native WordPress solution? =

* **Easier learning curve.** No need to learn yet another tool. Everything
(from the creation of tests to the visualization of the results) is done from
the WordPress dashboard using the same interface you’re already familiar with.
* **Automatic improvement of the WP site.** A native WordPress solution is able
to automatically evolve your WordPress site. For instance, it can update the
site to reflect the winner alternative as soon as the results are statistically
significant. Instead, with a generic tool, once you have the winner, you´ll
need to back to WordPress and manually modify the posts to implement the
changes yourself.
* **Fine-grained testing.** The "testing unit" in a generic tool is the web
page since this the HTML of the page is the only thing those tools have access
to. Instead a native WordPress solution can access all your WordPress
components, including the menu, your widget configuration, theme,… so you can
choose to test a combination of these components (e.g. testing two different
menus across the site) instead a specific page.
* **More control.** What about showing the tests only to (un)registered users?
Or users with a certain role? This kind of control on the testing process can
only be done when the AB Testing tool has access to the internals of your
WordPress installation.


= More questions? Take a look at our site!=

We keep an [up-to-date FAQs page in our site](http://wp-abtesting.com/faqs/).


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

= 1.5.6 =
* UI Improvement: list of experiments is now sortable
* Bug fix: when starting an experiment with multiple goals, the error "Too
few parameters" does no longer appear

= 1.5.5 =
* InApp Quota Payment: you can now increase the available quota from within
the plugin!

= 1.5.4 =
* Buf fix: compatibility issuse with JetPack and IE10 are now fixed

= 1.5.3 =
* Compatibility with WordPress 3.8
* Alternative pages/post are no longer visible when disabling the plugin
* Some other minor tweaks

= 1.5.2 =
* Improved quality for all graphical assets
* The "Feedback" page has been changed to a "Share & Comment" page
* Bug fix: endless loading loop when viewing the progress of some experiments
* Some minor tweaks

= 1.5.1 =
* Bug fix: compatibility with the WordPress hosting service "WPonFire"
* Some minor tweaks

= 1.5 =
* **New Feature!** Improved view for the progress/results of an experiment.
When setting more than one goal, you can now see the aggregated conversion
rate for all goals or the conversion rates for each goal
* **New Feature!** Simplified UI for testing only changes in the title of
a page/post

= 1.4.1 =
* Bug fix: version 1.4 was not properly uploaded in the WordPress repository

= 1.4 =
* **New Feature!** you can now set an external webpage as the goal of an
experiment
* **New Feature!** you can now count as a conversion an indirect navigation
to the experiment
* **Now compatible with OptimizePress**!
* **Now compatible with JetPack**!
* Some minor tweaks

= 1.3.2 =
* Bug fix: alternative pages or posts created using the "empty alternative"
option can be edited

= 1.3.1 =
* Bug fix: the definition of Goal Pages (or Posts) for theme experiments
works properly

* **New Feature!** You can now set more than one page or post as the goal
= 1.3 =
* **New Feature!** You can now set more than one page or post as the goal
of an experiment!
* Improved _progress of the experiment_ page
* Major bug fix: WSOD for PHP versions < 5.3 (because of calling a static
method using a variable; error T_PAAMAYIM_NEKUDOTAYIM)
* Some minor fixes

= 1.2.1 =
* Bug fix: stopping an experiment from the progress page is now working
* Bug fix: overriding a theme alternative with another one from the progress
page of an experiment is now working

= 1.2 =
* **New Feature!** You can now test different worpdress themes!
* Improved page for listing experiments. On the one hand, each experiment has
an icon to quickly identify its type. On the other hand, statuses are now
colorized.
* Improved metadata management when applying the winning page/post alternative
* Some minor fixes

= 1.1.2 =
* Bug fix: when querying the permalink of an alternative, the original's
permalink is returned instead (important, for instance, for social sharing
plugins)

= 1.1.1 =
* Bug fix: removing all PHP warnings and notices

= 1.1.0 =
* Bug fix: no more callings to the undefined method DateTime::setTimestamp()
for PHP < 5.3

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

= 1.5.6 =
Bug fixes and improvements. Increase the quota from within the plugin.
