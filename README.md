# Woocommerce Multi Signup

This project is to be used along with the following plugins in Wordpress:

* WooCommerce
* LifterLMS
* LifterLMS WooCommerce (3.1.0 or newer)
* LifterLMS Groups (3.0.0 or newer)

This plugin allows customers sign up multiple students or a different student for a class in a single checkout.

## How it works

* On the checkout page the customer enters a name and email address for every student (or ticks "Sign up a different student" when buying a single seat).
* When the order reaches the LifterLMS WooCommerce enrollment status (completed by default), the plugin uses the `llms_wc_do_default_enrollment` filter to stop LifterLMS WooCommerce from enrolling the buyer in those courses.
* On the `llms_wc_order_item_fulfill` action the plugin creates a LifterLMS Group for the purchased course, makes the buyer its primary administrator and adds every listed student as a member. Group membership enrolls the students in the course, and the buyer can add, remove or move students from the group's page.
* The buyer manages the group without being a member of it: the group has exactly as many seats as were purchased, and the buyer holds no seat and gets no course access unless they list themselves as one of the students. Because they are not a member, their groups appear in the dashboard's "Groups I Manage" section (below) rather than in the Groups add-on's "My Groups" tab.
* A buyer only ever gets one group per course: when they already own a group for the course (from an earlier order, or an earlier item in the same order) the purchased seats and the new students are added to that group instead of creating another one.
* If the access plan is already a LifterLMS Groups "group enrolment" plan, the group that LifterLMS Groups creates for the order is reused and the students are added to it.
* The WooCommerce My Account dashboard and the LifterLMS student dashboard home both get a "Groups I Manage" section listing every group the user administers or leads, with the course, role, seat usage and a "Manage students" link to the group's Members tab. Users who manage no groups don't see it. Its "View All My Groups" link opens the My Groups tab on the WooCommerce My Account page (falling back to the LifterLMS dashboard page if that tab isn't shown there).
* The dashboard's "Order History" tab links to WooCommerce's order list (My Account, Orders) instead of the always-empty LifterLMS order history, and the old LifterLMS orders endpoint redirects there.
* Students that do not have an account yet get one, plus an email with a link to set their password.
* Because the buyer's account owns the group, checkout asks guests to log in or create an account when they register other students.

## Prerequisites

* You must have [Docker](https://www.docker.com/get-started) and [Docker compose](https://docs.docker.com/compose/install/) installed
* You need [VS Code](https://code.visualstudio.com/download) installed and the [Remote Containers Extension](https://marketplace.visualstudio.com/items?itemName=ms-vscode-remote.remote-containers) installed in it

## Start

1. Clone this repo into onto your computer

1. Open the folder in VS Code

1. Hit `Ctrl+Shift+P` or `F1` to show the command palette

1. Type `Remote-Containers: Reopen in Container`

1. Write your plugin in the `my-new-plugin` folder

1. To deploy the plugin, hit `Ctrl+Shift+P` to show the command palette and type `Tasks: RunTask`

1. Click enter (`Deploy Plugin` should be selected)

1. Open http://localhost:8080/wp-admin/plugins.php

1.  You should see the plugin deployed. You will ned to hit activate for it to have any effect.

## Tests

Run the PHP unit tests from the `woocommerce-multi-signup` folder with `vendor/bin/phpunit`.

## Notes

* The first time you open your wordpress instance you will need to go through the installation process. You only need to do this once unless you clear your my sql db volume.
