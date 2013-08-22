# Imbo launcher
This application can be used to launch one or more Imbo servers hosted by PHP's built in web server. This can come in handy for testing purposes, and can be used on Travis-CI for instance when integrating clients.

## Requirements
This application requires PHP-5.4 or greater.

## Usage/Installation
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

## Commands
Below you will find the complete list of commands supported by ImboLauncher.

### start-servers
This command will start one or more servers based on your configuration file.

#### Options
The following options can be used with the `start-servers` command:

##### --config
Path to the configuration file. For instance `imbolauncher/imbolauncher --config config.json start-servers`. The configuration file uses JSON, and must follow the [configuration file schema](config-schema.json). Below is an example of such a config file:

    {
        "servers": [
            {
                "version": "dev-develop",
                "host": "localhost",
                "port": 9010,
                "config": "imbo1.php"
            },
            {
                "version": "0.3.2",
                "host": "localhost",
                "port": 9011,
                "config": "imbo2.php"
            }
        ]
    }

##### --install-path
The Imbo servers will be installed in this directory. Each server will be installed in separate directories matching the versions. If you use the above configuration file, and for instance `/path/to/installations` as `--install-path` the servers will be installed to:

* `/path/to/installations/dev-develop`
* `/path/to/installations/0.3.2`
