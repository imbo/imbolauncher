# Imbo launcher
This application can be used to launch one or more [Imbo](https://github.com/imbo/imbo) servers hosted by PHPs [built in web server](http://php.net/manual/en/features.commandline.webserver.php). This can come in handy for testing purposes, and can be used with [Travis-CI](https://travis-ci.org) when integrating clients for instance.

## Requirements
This application requires [PHP-5.6](http://php.net) or greater.

## Usage/Installation
First, install the package using [Composer](https://getcomposer.org):

    curl https://getcomposer.org/installer | php
    php composer.phar create-project -n imbo/imbolauncher imbolauncher dev-develop

then, execute the binary for more information:

    cd imbolauncher
    ./bin/imbolauncher

You can also install the package by simply cloning it:

    git clone https://github.com/imbo/imbolauncher.git
    cd imbolauncher
    curl https://getcomposer.org/installer | php
    php composer.phar install

and then executing the binary:

    ./bin/imbolauncher

## Commands
Below you will find the complete list of commands supported by ImboLauncher.

### start-servers
This command will start one or more servers based on your configuration file.

#### Options
##### --config
Path to the configuration file. For instance `bin/imbolauncher start-servers --config config.json`. The configuration file uses JSON, and must follow the [configuration file schema](config-schema.json). Below is an example of such a config file:

    {
        "servers": [
            {
                "version": "dev-develop",
                "host": "localhost",
                "port": 9010,
                "config": "imbo1.php"
            },
            {
                "version": "2.2.1",
                "host": "localhost",
                "port": 9011,
                "config": "imbo2.php"
            }
        ]
    }

##### --install-path
The Imbo servers will be installed in this directory. Each server will be installed in a separate directory matching the version. If you use the above configuration file, and for instance `/path/to/installations` as `--install-path` the servers will be installed to:

* `/path/to/installations/dev-develop`
* `/path/to/installations/2.2.1`

##### --timeout
Specify the amount of seconds each server is allowed to use when starting up. The default value is `2`.

##### --pid-file
Path to the file used to store the PIDs of the started servers. The default value is `/tmp/imbolauncher-pids`.

#### Example(s)
* `./bin/imbolauncher start-servers --config config.json`
* `./bin/imbolauncher start-servers --config config.json --timeout 1 --install-path /tmp/imbolauncher/installations --pid-file /tmp/imbolauncher/pids`

### kill-servers
This command can be used to kill the servers previously started by ImboLauncher. To do this you need to specify the path to the PID file you used when starting the servers.

#### Options
##### --pid-file
Path to the file that holds the PIDs of the servers previously started with ImboLauncher. The command tries to figure out what the different PIDs refer to and present this to the user before continuing to kill the processes. After the processes have been killed the file referred to is deleted. The default value is `/tmp/imbolauncher-pids`.

#### Example(s)
* `./bin/imbolauncher kill-servers`
* `./bin/imbolauncher kill-servers --pid-file /path/to/pid/file`
