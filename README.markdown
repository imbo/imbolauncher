Imbo launcher
=============
This application can be used to launch one or more Imbo servers hosted by PHP's built in web server. This can come in handy for testing purposes, and can be used on Travis-CI for instance when integrating clients.

Requirements
------------
This application requires PHP-5.4 or greater.

Usage
-----
First, install the package using Composer:

    curl https://getcomposer.org/installer | php
    php composer.phar create-project -n imbo/imbolauncher imbolauncher dev-develop

then, execute the binary for more information:

    imbolauncher/imbolauncher

You can also install the package by simply cloning it:

    git clone https://github.com/imbo/imbolauncher.git
    cd imbolauncher
    curl https://getcomposer.org/installer | php
    php composer.phar install

and then executing the binary:

    ./imbolauncher

