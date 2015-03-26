# clue/mdns-react [![Build Status](https://travis-ci.org/clue/php-mdns-react.svg?branch=master)](https://travis-ci.org/clue/php-mdns-react)

Simple, async multicast DNS (mDNS) resolver library, built on top of [React PHP](http://reactphp.org/).

[Multicast DNS](http://www.multicastdns.org/) name resolution is commonly used
as part of [zeroconf networking](http://en.wikipedia.org/wiki/Zero-configuration_networking)
ala Bonjour/Avahi.
It is defined in [RFC 6762](http://tools.ietf.org/html/rfc6762), in particular
this specification also highlights the
[differences to normal DNS operation](http://tools.ietf.org/html/rfc6762#section-19). 

The mDNS protocol is related to, but independent of, DNS-Based Service Discovery (DNS-SD)
as defined in [RFC 6763](http://tools.ietf.org/html/rfc6763).

> Note: This project is in early alpha stage! Feel free to report any issues you encounter.

## Quickstart example

Once [installed](#install), you can use the following code to look up the address of a local domain name:

```php
$loop = React\EventLoop\Factory::create();
$factory = new Factory($loop);
$resolver = $factory->createResolver();

$resolver->lookup('hostname.local')->then(function ($ip) {
   echo 'Found: ' . $ip . PHP_EOL;
});

$loop->run();
```

See also the [examples](examples).

## Install

The recommended way to install this library is [through composer](http://getcomposer.org). [New to composer?](http://getcomposer.org/doc/00-intro.md)

```JSON
{
    "require": {
        "clue/mdns-react": "dev-master"
    }
}
```

## License

MIT
