# Electronic Hydrometer Public Server

This is a public server for electronic hydrometers like [TILT Hydrometer](https://tilthydrometer.com/) or [iSpindel](https://github.com/universam1/iSpindel), that are commonly used to measure fermentation process in homebrewing.
The server allows homebrewers to keep a log of the data and visualize it via charts.

## Approach

The Hydrometers and their data are saved in a relational database.
The data is visualized using [C3.js](http://c3js.org/) charts.
The interface is built with [Bootstrap 4](https://v4-alpha.getbootstrap.com/).
Authentication is handled by OAuth2, with various supported providers.

## Installation

[Install Composer](https://getcomposer.org/doc/00-intro.md), PHPs package manager, if not available.

Create a project in DIRECTORY

```
composer create-project ckrack/hydrometer-public-server DIRECTORY --prefer-dist
```

Run spark to generate a set of numbers required for the config.

```
composer spark
```

Modify the database settings to suit your environment.
Modify optimus configuration with the above numbers.
Create credentials at an oauth2 provider (@see .env).

```
nano ./src/.env
```

When your settings are updated, generate the database tables:

```
composer setup-db
```

## TCP-Server or HTTP?

If you wish to run the TCP-Server, you need to run `php ./bin/tcp-server.php` as a process.
You can always use the HTTP API.
