# BiTrade

Buy & Sell BitCoin on Coinbase with BiTrade Bot.
BiTrade is a Bot with Web Interface for Buy/Sell BitCoin on http://CoinBase.com. You Buy or Sell directly or Create an Order. An order waits for the price of a BitCoin to be reached to Sell or Buy.

## Getting Started

These instructions will get you a copy of the project up and running on your local machine for production and development purposes.

### Prerequisites

* [CoinBase Account](http://coinbase.com) - BitCoin Market
* [Composer](https://getcomposer.org/) - Dependency Manager for PHP

### Installing

A step by step series of examples that tell you have to get a production env running

Download & Installation


```
git clone https://github.com/Mediashare/BiTrade-V.2
cd BiTrade/
composer install
```
Import Db sql [Db_BiTrade](web/Db_BiTrade)
```
Rename example.config.inc to config.inc.php
```
Insert your data in config.inc.php & web/Db.php
```
// The currency you're going to pay with when buying new coins
// This can also be a crypto currency you have on Coinbase
// EUR or USD or even ETH or BTC
define('CURRENCY','EUR');

// The crypto currency the bot is going to trade.
// BTC or ETH only the moment
define('CRYPTO','BTC');

// The local timezone of this machine
// must be a string according to http://php.net/manual/en/timezones.php
define('TIMEZONE','Europe/Paris'); 

//how long between price checks in the watchdog?
define('SLEEPTIME',9);

// Coinbase 
// Visit 'https://www.coinbase.com/settings/api'
define('COINBASE_KEY','Key Api');
define('COINBASE_SECRET','Secret Key Api');


 // DB Connection        
 define('HOST', '127.0.0.1');    
 define('USERNAME', 'root');    
 define('PASSWORD', 'root');    
 define('DBNAME', 'coinbase');

```
Start Bot Process
```
php Process.php Start
```
Start your Server and go to : http://localhost/BiTrade/web/

## Contact

* [Irc]
  * Host : irc.slote.me
  * Port : 6667

* [Mail]
  * admin@slote.me
  * Mediashare.supp@gmail.com
  * Marquand.Thibault@gmail.com

## Contributing

Please read [CONTRIBUTING.md](CONTRIBUTING.md) for details on our code of conduct, and the process for submitting pull requests to us.

### Donate
My BitCoin Address : 1NFoAx12XazvYZH7G8vZfo8ibyFoJiQc3v

## Versioning

We use [SemVer](http://semver.org/) for versioning. For the versions available, see the [tags on this repository](https://github.com/your/project/tags). 

## Authors

* **Thibault Marquand** - *Initial work* - [Mediashare](https://github.com/Mediashare)

See also the list of [contributors](https://github.com/Mediashare/BiTrade/graphs/contributors) who participated in this project.

## License

This project is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details
