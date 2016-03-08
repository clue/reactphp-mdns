# clue/mdns-react [![Build Status](https://travis-ci.org/clue/php-mdns-react.svg?branch=master)](https://travis-ci.org/clue/php-mdns-react)

Simple, async multicast DNS (mDNS) resolver library, built on top of [React PHP](http://reactphp.org/).

[Multicast DNS](http://www.multicastdns.org/) name resolution is commonly used
as part of [zeroconf networking](http://en.wikipedia.org/wiki/Zero-configuration_networking).
It is used by Mac OS X (Bonjour), many Linux distributions (Avahi) and quite a few other networking devices such as printers, camers etc. to resolve hostnames of your local LAN clients to IP addresses.

This library implements the mDNS protocol as defined in [RFC 6762](http://tools.ietf.org/html/rfc6762).
Note that this protocol is related to, but independent of, DNS-Based Service Discovery (DNS-SD)
as defined in [RFC 6763](http://tools.ietf.org/html/rfc6763).

**Table of Contents**

* [Quickstart example](#quickstart-example)
* [Usage](#usage)
  * [Factory](#factory)
    * [createResolver()](#createresolver)
  * [Resolver](#resolver)
    * [Promises](#promises)
    * [Blocking](#blocking)
* [Install](#install)
* [License](#license)
* [More](#more)

> Note: This project is in beta stage! Feel free to report any issues you encounter.

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

## Usage

### Factory

The `Factory` is responsible for creating your [`Resolver`](#resolver) instance.
It also registers everything with the main [`EventLoop`](https://github.com/reactphp/event-loop#usage).

```php
$loop = React\EventLoop\Factory::create();
$factory = new Factory($loop);
```

#### createResolver()

The `createResolver()` method can be used to create a mDNS resolver instance that sends multicast DNS queries and waits for incoming unicast DNS responses. It returns a [`Resolver`](#resolver) instance.

```php
$resolver = $factory->createResolver();
```

### Resolver

The [`Factory`](#factory) creates instances of the `React\Dns\Resolver\Resolver` class from the [react/dns](https://github.com/reactphp/dns) package.

While React's *normal* DNS resolver uses unicast UDP messages (and TCP streams) to query a given nameserver,
this resolver instance uses multicast UDP messages to query all reachable hosts in your network.

#### Promises

Sending queries is async (non-blocking), so you can actually send multiple DNS queries in parallel.
The mDNS hosts will respond to each DNS query message with a DNS response message. The order is not guaranteed.
Sending queries uses a [Promise](https://github.com/reactphp/promise)-based interface that makes it easy to react to when a query is *fulfilled*
(i.e. either successfully resolved or rejected with an error):

```php
$resolver->lookup($hostname)->then(
    function ($ip) {
        // IP successfully resolved
    },
    function (Exception $e) {
        // an error occured while looking up the given hostname
    }
});
```

Please refer to the [DNS documentation](https://github.com/reactphp/dns#readme) for more details.

#### Blocking

As stated above, this library provides you a powerful, async API by default.

If, however, you want to integrate this into your traditional, blocking environment,
you should look into also using [clue/block-react](https://github.com/clue/php-block-react).

The resulting blocking code could look something like this:

```php
use Clue\React\Block;

$loop = React\EventLoop\Factory::create();
$factory = new Factory($loop);
$resolver = $factory->createResolver();

$promise = $resolver->lookup('me.local');

try {
    $ip = Block\await($promise, $loop);
    // IP successfully resolved
} catch (Exception $e) {
    // an error occured while performing the request
}
```

Similarly, you can also process multiple lookups concurrently and await an array of results:

```php
$promises = array(
    $resolver->lookup('first.local'),
    $resolver->lookup('second.local'),
);

$ips = Block\awaitAll($promises, $loop);
```

Please refer to [clue/block-react](https://github.com/clue/php-block-react#readme) for more details.

## Install

The recommended way to install this library is [through Composer](http://getcomposer.org).
[New to Composer?](http://getcomposer.org/doc/00-intro.md)

```bash
$ composer require clue/mdns-react:~0.2.0
```

See also the [CHANGELOG](CHANGELOG.md) for details about version upgrades.

## License

MIT

## More

* Multicast DNS is defined in [RFC 6762](http://tools.ietf.org/html/rfc6762), in particular
  this specification also highlights the
  [differences to normal DNS operation](http://tools.ietf.org/html/rfc6762#section-19). 
* Please refer to the [react/dns component](https://github.com/reactphp/dns#readme) for more details
  about normal DNS operation.
