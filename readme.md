# GoPay OpenCart Integration

## Table of Contents

- [About the Project](#about-the-project)
    - [Built With](#built-with)
- [Development](#development)
    - [Prerequisites](#prerequisites)
    - [Installation](#instalation)
    - [Run project](#run-project)
    - [Project Structure](#project-structure)
    - [Migrations](#migrations)
    - [Dependencies](#dependencies)
    - [Testing](#testing)
- [Versioning](#versioning)
- [Deployment](#deployment)
- [Documentation](#documentation)
- [Other useful links](#other-useful-links)

## About The Project

GoPay payment gateway integration with the OpenCart eCommerce platform.

### Built With

- [GoPay's PHP SDK for Payments REST API](https://github.com/gopaycommunity/gopay-php-api)
- [Composer](https://getcomposer.org/)

## Development

Running project on local machine for development and testing purposes.

### Prerequisites

- [PHP](https://www.php.net)
- [OpenCart](https://www.opencart.com)
- [Docker Desktop](https://www.docker.com/get-started)
- [Docker Compose](https://docs.docker.com/compose/) _(is part of Docker Desktop)_

### Instalation

### Run project

For local project execution, first install OpenCart, then upload and configure the plugin by following the steps below:
1. Install the plugin through the OpenCart extension installer screen directly.
2. On extensions filter by payments and install OpenCart GoPay gateway.
4. Configure the plugin by providing goid, client id and secret to load the other options (follow these [steps](https://help.gopay.com/en/knowledge-base/gopay-account/gopay-business-account/signing-in-password-reset-activating-and-deactivating-the-payment-gateway/how-to-activate-the-payment-gateway) to activate the payment gateway and get goid, client id and secret).
5. Finally, choose the options you want to be available in the payment gateway (payment methods and banks must be enabled in your GoPay account).

### Project Structure

- **`admin`**
  - **`controller`**
  - **`language`**
  - **`model`**
  - **`view`**
- **`catalog`**
  - **`controller`**
  - **`language`**
  - **`model`**
  - **`view`**
- **`system`**
  - **`config`**
  - **`library`**
- **`vendor`**
- **`readme.md`**
- **`install.json`**
- **`composer.json`**

### Migrations

### Dependencies

Use Composer inside Docker container to install or upgrade dependencies.

Run docker-compose.

```sh
make run-dev
```

Run update.

```sh
make update
```

See `makefile` for more commands.

### Testing

## Versioning

This plugin uses [SemVer](http://semver.org/) for versioning scheme.

### Contribution

- `master` - contains production code. You must not make changes directly to the master!
- `staging` - contains staging code. Pre-production environment for testing.
- `development` - contains development code.

### Contribution process in details

1. Use the development branch for the implementation.
2. Update corresponding readmes after the completion of the development.
3. Create a pull request and properly revise all your changes before merging.
4. Push into the development branch.
5. Upload to staging for testing.
6. When the feature is tested and approved on staging, pull you changes to master.

## Deployment

This plugin uses [Git Updater](https://github.com/afragen/git-updater/) to manage updates.

Before deploy change Version in the `install.json`, then commit & push. Staging site uses staging branch.

## Internationalization

### Add new language

Create inside the language folder a new locale folder and a file with the same path and name where the text is located. Open the new file on any text editor and add the new translation.

### Update an existing language

The translation file can be opened on any text editor and change to the new translated phrase.

## Documentation

## Other useful links
