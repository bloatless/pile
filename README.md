<p align="center">
    <img src="https://bloatless.org/img/logo.svg" width="60px" height="80px">
</p>

<h1 align="center">Pile</h1>

<p align="center">
    Dead simple centralized logging.
</p>

## Installation

To install Pile just clone this repository to your server:

```
git clone https://github.com/bloatless/pile.git
```

* Point your virtual host document root to the `public` directory and rewrite ald requests to the `index.php` file.
* Create a new MySQL database and import the structure using the `db_stucture.sql` file.
* Copy `config/config.sample.php` to `config/config.php` and adjust database configuration, api-keys and user-account settings.

## Documentation

### Log message structure

The general structure of a log message to be send to the Pile API should be as follows:

key        | type                      | description
-----------|---------------------------|--------------------------------------------------------------------------------
source¹    | string                    | An identifier describing the source of the log message. (e.g. the project name)
message¹   | string                    | The actual log message.
level¹     | int                       | A valid log level code. (See listing below)
context    | array                     | Arbitrary data passed together with the log message.
channel    | string                    | The channel this message was logged to.
datetime   | string                    | Date and time when the message was logged. (Format: YYYY-MM-DD HH:ii:ss)
extra      | array                     | Additional data added by the processor.

¹ = field is required

#### Log levels

Valid log level codes are:

code  | level name
------|-----------
100   | debug
200   | info
250   | notice
300   | warning
400   | error
500   | critical
550   | alert
600   | emergency

### Sending logs to your Pile instance

#### Using HTTP REST API

Log messages can be sent to your Pile instance using a simple post request:

```
POST https://pile.yourdomain.com/api/v1/log
Content-Type: application/vnd.api+json
Accept: application/vnd.api+json
X-API-Key: 123123123

{
    "data": {
        "type": "log",
        "attributes": {
            "source": "MyProjectName",
            "message": "Some error occoured",
            "context": {
                "exception": {
                    "class": "My\\Fancy\\Classname",
                    "message": "Invalid value",
                    "code": "42",
                    "file": "\/framework\/src\/Foo\/Bar\/Classname.php:1337"
                }
            },
            "level": 400,
            "level_name": "ERROR",
            "channel": "dev",
            "datetime": "2019-11-05 17:44:26",
            "extra": []
        }
    }
} 
```

Be sure to include an API key within the request header.

#### Using Monolog Handler

If you are using [Monolog](http://github.com/Seldaek/monolog) in your project you can also use the
[MonoPile](https://github.com/bloatless/MonoPile) package which provides a handler and formatter for Monolog to easily
send error-logs to your Pile instance.

## License

MIT