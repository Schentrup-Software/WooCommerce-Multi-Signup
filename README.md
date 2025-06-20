# Woocommerce Multi Signup

This project is to be used along with the following 3 plugins in Wordpress:

* WooCommerce
* LifterLMS
* LifterLMS WooCommerce

This plugin allows customers sign up multiple students or a different student for a class in a single checkout.

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

## Notes

* The first time you open your wordpress instance you will need to go through the installation process. You only need to do this once unless you clear your my sql db volume.
