=== Nelio AB Testing ===
Contributors: nelio
Tags: ab testing, ab test, a/b testing, a/b test, a b testing, a b test, split testing, conversion optimization, optimization, conversion, heatmap
Requires at least: 3.3
Tested up to: 4.0
Stable tag: 3.1.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A/B Testing, conversion rate optimization, and beautiful Heatmaps specifically
designed for WordPress.


== Description ==

[Nelio A/B Testing](http://wp-abtesting.com) is a conversion optimization
service for your WordPress site. It helps you define, manage, and keep track of
**A/B-testing experiments**, combined with powerful and beautiful **Heatmaps**.
Get everything you need from within your WordPress dashboard, where you'll
benefit from a lovely, integrated, and familiar user experience. Since we
designed the tool specifically for WordPress, you will have complete control on
what you test: pages, posts, themes, titles... and many more that will be
available in future releases!

**Version 3.0** is the first big update of Nelio A/B Testing since we first
launched it. It uses a new user interface that is faster and easier to use.  It
also includes many efficiency and stability improvements.

**Note** Please keep in mind that [you will need an
account](http://wp-abtesting.com/subscription-plans/) to use our plugin (the
service includes a 14-day free trial period).

[youtube https://www.youtube.com/watch?v=ZDgTkqI4SNk]


= Features =

* **There's no need to learn an external tool!** The definition, management,
and tracking of experiments is beautifully integrated in WordPress. Nelio A/B
Testing provides a lovely user interface that simplifies the process of
creating alternatives and applying the winning ones.
* **Nelio A/B Testing is a powerful A/B Testing tool.** Test alternatives for
your pages and posts or modify the look and feel of your website testing
different themes or tweaking the CSS files.
* **Heatmaps as the perfect companion for A/B Testing** Understand more about
your website and your customers using our Heatmaps feature. Use Heatmaps to
know the spots in which your users pay more attention or to discover what they
are ignoring!
* **Understand what's going on with your site.** Get fresh information about
the evolution of the experiment every day, with nice graphics about visitors
and conversions. Detailed statistical information is also available if you want
it. Otherwise, the plugin summarizes the key information for you.
* **Don't worry about your server performance.** Gathered information about
experiments and statistical calculus are stored and performed in Nelio's
backend servers.
* **A plan for everybody.** Our subscription plans are suited for everybody and
can be adapted to tailor your needs.


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


**I successfully installed the plugin. What should I do now?**

If you have successfully installed our plugin using one of the aforementioned
methods, now it is just time to use it! Take a look at our [Getting Started
Guide](http://wp-abtesting.com/getting-started-guide/) to cover the basics.


== Frequently Asked Questions ==

= Why should I use an A/B Testing Solution specifically designed for WordPress? =

* **Smooth learning curve.** There is no need to learn yet another tool.
Everything (from the creation of tests to the visualization of the results) is
done from the WordPress dashboard you are familiar with, resulting in a
well-known, perfectly integrated user interface.
* **Evolve your WordPress site easily.** A native WordPress solution automates
and simplifies the evolution of your site. Consider, for example, applying the
winner alternative in your site as soon as the results are statistically
significant. With a native solution, this is as easy as clicking one single
button. With a generic tool, on the other hand, you would have to manually
modify the page so that it integrates the changes you created in the (winning)
alternative.
* **Fine-grained testing.** The "testing unit" in a generic tool is the web
page, since the HTML of the page is the only thing those tools have access to.
A native WordPress solution, however, permits you to test any WordPress
specific component, such as posts, pages, CSS, themes, child themes, and many
more coming!


= More questions? Take a look at our site! =

We keep an [up-to-date FAQs page in our site](http://wp-abtesting.com/faqs/).


== Screenshots ==

1. **Relevant info with a quick glance.** Running experiments are visible in
Nelio's Dashboard. There, you'll find a summary of all the relevant information
you need.
2. **Progress of your Experiment.** Whilst an experiment is running (or once
it is finished), you can see how it is performing in the Results of the
Experiment page.
3. **Data made easy.** Additional graphics help you understand what is going
on.
4. **Experiment Management.** Manage your experiments without leaving
WordPress: creation, deletion, and monitoring!
5. **Experiment Creation.** Creating new experiments has never been so easy!
Just set a name, a description, and the page you want to test!
6. **Alternatives are Regular Pages.** For each page to test, you can create
alternatives quickly and easily, from scratch or from already-existing pages!
7. **Account details.** View your account information and access your directly
from the plugin.
8. **Use our plugin in more than one site.** You can use our plugin and service
on more than one site. Very useful for staging and production environments!
9. **Different types of experiments.** Our product let's you test different
aspects of your WordPress site; just select what you want to test and create
the experiment!
10. **Heatmaps.** Our service let's you analyse the hottest areas of any page
on your site so that you can understand your customers better.


== Changelog ==

= 3.1.3 =
* Bug fix: if the user is upgrading from a previous version of the plugin, the
	cache of running experiments is properly updated.

= 3.1.2 =
* Bug fix: when running a global (CSS or Theme) experiment, the user does no
longer see the latest post only when accessing the latest posts page.

= 3.1.1 =
* The management of registered sites has been improved.
* Bug fix: removing several notices (on strict PHP environments).

= 3.1.0 =
* **New Feature**. You can now use form submissions as conversion actions. In
particular, we now support Contact Forms 7 and Gravity Forms plugins.
* Improved page and post selectors during experiment creation/edition. You're
no longer limited to the latest posts/pages, but you can now search and select
any page or post from your site.
* Improved CSS editor, with syntax highlighting and warning/error
notifications.
* Bug fix: in the progress of the experiment page, under the summary section,
the conversion rate timeline does no longer show conversion rates greater than
100 per cent.
* Bug fix: the order in which conversion goals and conversion actions are
defined is now preserved.

= 3.0.11 =
* Bug fix: when editing a theme experiment (after its creation), the behavior
of the overall process generated a few problems. Users got stuck, alternatives
where not properly selected, and so on. It should be fixed, now.

= 3.0.10 =
* Improvement: when the user has been deactivated, _User Deactivated Page_
error page is shown. If he access the _My Account_ page to check his
subscription details and everything is OK, the error page is removed and he can
use the plugin again.

= 3.0.9 =
* Bug fix: viewing the details of a title experiment throwed (sometimes) an
exception.

= 3.0.8 =
* Bug fix: alternatives with single quote chars can now be created.
* Compatibility with Member Access plugin.

= 3.0.7 =
* Improvement: enable and disable from the Settings page a Must Use Plugin to
improve performance.
* Bug fix: heatmaps on Latest Posts page are now working.

= 3.0.6 =
* Bug fix: you can now set up the Shop Page in a WordPress installation as a
conversion goal action.

= 3.0.5 =
* Improved Efficiency. Prevent sending information to Nelio backend servers
when no quota is available.

= 3.0.4 =
* Quick Fix with Dashboard Cards. Some of you had CSS problems with the cards.
They should be fixed, now.
* Compatibility with Custom Permalinks plugin.

= 3.0.3 =
* **New Feature**. So far, external goals were only tracked if the user clicked
a link whose href was the same as the external goal's URL. Now, this is also
extended to form submission (assuming that form's action attribute is the same
as the external goal's URL).
* Quick fix. In the progress of the experiment page, goals created with
previous version of the plugin have useful names (instead of "Undefined").

= 3.0.2 =
* Bug fix: Fatal error when no results available.

= 3.0.1 =
* Improvement. Making sure that body remains insivible during experiment load.

= 3.0.0 =
* **New User Interface** The User Interface in the Dashboard has been
redesigned. Experiment creation and edition is easier and faster.
* **Advanced Goal Management** You now have full control when it comes to
define conversions. All the information you need, when you need it.
* **New Dashboard** First version of an A/B Testing dashboard. You now have
all relevant information just one click away!
* **Efficiency Improvements** We updated the plugin to make it faster and
more reliable.

= 2.1.7 =
* Bug fix: pages created with OptimizePress can now be properly duplicated.

= 2.1.6 =
* Bug fix: deprecated use of function 'split' is now fixed.

= 2.1.5 =
* Bug fix related to PHP Strict Warnings.
* Bug fix with the function is_page_template(x). It now returns the proper
value.

= 2.1.3 =
* Bug fix: compatibility with meta options defined by Lotus theme.
* Bug fix related to PHP Strict Warnings.
* New feature: you can now select whether external goals should take GET params
into account when tracking conversions.

= 2.1.2 =
* Bug fix: AJAX error on windows installations does no longer appear.
* Improvement: tracking conversions to external goals is now faster and more
reliable.
* Some minor changes and bug fixes for specific installations.

= 2.1.1 =
* Bug fix in experiment creation: if you have many published posts, you can
now select among the most recent ones (instead of the alphabetically-ordered
first ones).

= 2.1.0 =
* **Greedy Algorithm.** Do you want to exploit the winning alternative? Now
you can! Use a greedy algorithm to increase the chances of your visitors to
see the winning alternative of your experiment
* **New Settings Page**. Our plugin includes a new Settings page where you
can tune a few parameters of the plugin. This are the first steps towards a
more customizable plugin!
* Graphical improvements
* Minor bug fixes

= 2.0.14 =
* Bug fix: title tag does no longer show a _notice_ on certain installations
* Bug fix: heatmap tracking script now works for those elements that include
the attribute "class", but for which no classes are specified

= 2.0.13 =
* Tested with version 3.9
* Added "Preview" buttons for the original and goal pages when creating/editing
an experiment.
* Added a "Back" button when viewing Heatmaps, so that the user can now go
back and forth between the progress of a experiment and the heatmaps of its
alternatives
* Improved user interface in "the Progress of the experiment" page, making the
possible actions visible at all times
* Improved Heatmap tracking scripts
* Bug fix: compatibility issues with older versions of IE

= 2.0.12 =
* Bug fix: improvements on the admin interface (SSL support)
* Bug fix: compatibility issues with JavaScript and IE8
* Some minor improvements

= 2.0.11 =
* Some minor improvements and bug fixes

= 2.0.10 =
* **New Features for Basic Subscriptors!** Use professional features from a
basic account
* Improved management of account status

= 2.0.9 =
* **New Feature!** Title experiments have been improved. Originally, title
experiments were a shortcut of page/post experiments, where only titles were
changed. Now, however, you test which title gets more visitors into the tested
post; i.e. the goal page is the post itself!
* Some minor improvements

= 2.0.6 =
* Bug fix: Heatmaps work properly with latest Chrome version
* Some minor improvements

= 2.0.5 =
* Bug fix: you can now see the Heatmaps of the alternatives of a page/post
split testing experiment

= 2.0.4 =
* Bug fix: CSS experiments in the "Latest Posts" page are now working
* Improved Heatmap tracking algorithm

= 2.0.3 =
* Bug fix: Heatmap tracking of "Latest Posts" page is now working

= 2.0.2 =
* Bug fix: AJAX-related error when starting a Heatmap experiment

= 2.0.1 =
* Some minor tweaks

= 2.0 =
* **New Feature!** Heatmaps and Clickmaps of your customers
* **New Feature!** A/B Test CSS modifications
* Improved Settings page (account and registered sites management)
* Translated to Spanish
* Some minor tweaks

= 1.5.9 =
* Bug fix: when creating theme experiments, the widgets of each theme are
properly loaded

= 1.5.8 =
* Bug fix: you can now create theme experiments with child themes

= 1.5.7 =
* Bug fix: fatal error on Windows machines

= 1.5.6 =
* UI Improvement: list of experiments is now sortable
* Bug fix: when starting an experiment with multiple goals, the error "Too
few parameters" does no longer appear

= 1.5.5 =
* Quota Management: you can now increase the available quota from within
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

= 3.1.3 =
A couple of bug fixes. First, when running a global (CSS or Theme) experiment,
the user does no longer see the latest post only when accessing the latest
posts page. Second, if the user is upgrading from a previous version of the
plugin, the cache of running experiments is properly updated.

