# GoPay OpenCart Integration

## Table of Contents

- [About the Project](#about-the-project)
    - [Built With](#built-with)
- [Changelog](#changelog)
- [Development](#development)
    - [Prerequisites](#prerequisites)
    - [Installation](#instalation)
    - [Run project](#run-project)
    - [Project Structure](#project-structure)
    - [Testing](#testing)
- [Versioning](#versioning)
    - [Contribution](#contribution)
    - [Contribution process in details](#contribution-process-in-details)
- [Deployment](#deployment)
- [Internationalization](#internationalization)
    - [Add new language](#add-new-language)
    - [Update an existing language](#update-an-existing-language)
- [Documentation](#documentation)
- [Other useful links](#other-useful-links)

## About The Project

GoPay payment gateway integration with the OpenCart eCommerce platform.

### Built With

- [GoPay's PHP SDK for Payments REST API](https://github.com/gopaycommunity/gopay-php-api)
- [Composer](https://getcomposer.org/)

## Changelog
### 1.0.0
- OpenCart and GoPay gateway integration.

### 1.0.1
- Update GoPay extension to support latest OpenCart from v4.0.2.0 to v4.0.2.3

## Development

Running project on local machine for development and testing purposes.

### Prerequisites

- [PHP](https://www.php.net)
- [OpenCart](https://www.opencart.com)

### Instalation
1. Download and rename the extension:
- Visit the GitHub repository of the GoPay extension.
- Download the latest version of the extension to your device.
- Extract the source file and rename it to `opencart_gopay.ocmod`.
2. Plugin Compression:
- Create an archive of the extension using compression.
- Archive the entire folder as a .zip file.
- The whole installation file is named as `opencart_gopay.ocmod.zip`.
3. Uploading the extension to OpenCart:
- Log in to the OpenCart administrative interface.
- Navigate to Extensions -> Installer.
- Upload the compressed extension (.zip file) to the system.
4. Extension Installation:
- After successful upload, go to Extensions -> Payments -> Payment Method.
- The extension type has been configured as Payments, and locate GoPay within the list.
- Click the Install button next to the GoPay extension.
5. Activating the extension:
- After installation, go to Extensions -> Payments.
- Activate the GoPay extension by clicking the Edit button next to it and selecting Enabled.
6. Configuring the extension:
- Navigate to Extensions -> Payments.
- Click Edit next to the GoPay extension.
- Fill in the required information such as GoID, ClientID, Secret, etc.
- Save the changes.
7. Summary:
- Verify that the GoPay extension is now active and correctly configured.
- Test the payment functionality and ensure that all details were entered correctly.

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

### Testing
1. Perform test transactions: Execute a variety of test transactions using different scenarios. Access the URL provided for all product [requirements](https://argo22.atlassian.net/wiki/spaces/GPY020/pages/2932703233/Product+requirements). Verify that the plugin accurately handles each scenario and delivers the correct behavior to the end-user.

2. Debug log: Use monitoring tools within the OpenCart environment. Access the Error Log in the right panel under System > Maintenance > Error Logs. Alternatively, you can find it at `/system/storage/logs/error.log`. This tool is valuable for debugging and logging purposes.

3. Check order processing: After completing test transactions, verify that orders are processed correctly within OpenCart. Ensure that order details, payment statuses, and transaction logs are accurately recorded and reflected in the OpenCart admin panel.

4. Inspect and review the log: Inspect the log file which often contains valuable information regarding errors, warnings, and other debug messages generated during the testing process. Pay close attention to any entries related to the functionality being tested.

5. Review transaction logs: In the OpenCart admin panel, navigate to the payment gateway's log section for comprehensive transaction insights. This dedicated log section provides detailed records of all transactions processed through the payment gateway, offering valuable insights into payment statuses, transaction IDs, timestamps, and any potential errors encountered during the payment process.

6. Test compatibility: Ensuring compatibility with OpenCart is our primary objective during the development and testing phases of the plugin. However, due to the diverse ecosystem of OpenCart extensions and the unique configurations that users may employ, we cannot guarantee seamless compatibility with every extension or user setting.

7. Review error handling: Test the plugin's error handling capabilities by deliberately triggering errors, such as invalid payment credentials or network timeouts. Verify that error messages are clear, expected, and guide users toward resolution steps.

8. Note test results: Record your test findings, noting any issues like unexpected behaviors, warnings, errors, deprecated functions, and identified bugs along with their solutions. Keep detailed test notes for future reference and troubleshooting.

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
