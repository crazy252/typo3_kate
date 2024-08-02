# TYPO3 Kate

Introducing the new open-source TYPO3 extension that integrates seamlessly with the RoadRunner server to supercharge your website's performance. RoadRunner, a high-performance PHP application server, is designed to handle high traffic with minimal resource consumption. By leveraging RoadRunner's robust architecture, this extension optimizes TYPO3's backend operations, significantly reducing load times and improving scalability. Whether you're managing a small site or a large-scale application, our extension provides a powerful solution to ensure your TYPO3 installation runs at peak efficiency. Dive into the future of web performance with our cutting-edge integration, and experience the full potential of TYPO3 with RoadRunner.

> [!WARNING]
> This package is currently in development and is not production ready!

## Setup

Installation via composer

```shell
composer require crazy252/typo3_kate
```

Install the RoadRunner Server within your project with the following command:

```shell
./vendor/bin/typo3 kate:install
```

This will copy an example configuration into your root directory and also download the RoadRunner binary. After that you are ready to rock.

The RoadRunner Server will bind to `0.0.0.0:8080` by default. You can change that in the configuration.

## Usage

To start the RoadRunner Server you need to run the following command:

```shell
./vendor/bin/typo3 kate:start
```

If you changed something in the vendor directory or your composer autoload you need to reload the Server with the following command:

```shell
./vendor/bin/typo3 kate:reload
```

To stop the Server execute the following command:

```shell
./vendor/bin/typo3 kate:stop
```

## Development

Currently it is not recommended to use it for development because there is no file watcher for changes to reload the Server automatically. It is planned to add a file watcher for this.

## Future

- Add FrankenPHP as server type besides RoadRunner
- Add file watcher to automatically reload the server in development mode
- Add better documentation
- Add make it producation ready with version 1.0
