=== ClassicPress Pepper Password Plugin ===
Contributors: ClassicPress Team
Tags: security, users
Requires at least: 4.9.15
Tested up to: 2.2.0
Requires PHP: 7.4
Requires CP: 2.2
Stable tag: 1.0.2
License: GNU General Public License v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

# ClassicPress Pepper Password Plugin

## Overview

The **ClassicPress Pepper Password Plugin** is designed to enhance the security of user passwords by implementing a unique "pepper" mechanism. This plugin allows administrators to generate and manage a pepper string that is used in conjunction with password hashing, adding an additional layer of protection against unauthorized access.

## Features

- **Pepper Generation**: Easily generate (or refresh) a random pepper string to enhance password security by clicking a button.
- **Settings Menu**: Access the plugin settings through the ClassicPress admin menu at **Settings > Pepper**.
- **Automatic Activation**: The plugin creates a pepper file upon activation, if it doesn't already exist, to store the random Pepper string.
- **User-Friendly Interface**: Simple navigation and clear messages for users.

## Manual Installation

1. Download the plugin .zip file.
2. Upload the `pepper-password` folder to the `/wp-content/plugins/` directory via FTP, or visiting **Plugins > Upload**.
3. Activate the plugin through the **Plugins** menu in ClassicPress.

## Installation

- Install the **ClassicPress Directory Integration** plugin and activate it on your site
- Visit **Plugins > Install CP Plugins** and search for **ClassicPress Pepper Password Plugin**
- You can install and activate the plugin by clicking the respective buttons.

## Usage

### Accessing Settings

- Navigate to **Settings > Pepper** in the ClassicPress admin dashboard to manage your pepper settings.

### Generating a Pepper

- Click on the **Enable Pepper** or **Renew Pepper** button within the settings page to generate or renew the pepper string.

### Important Notes

- Changing or deactivating this plugin will invalidate all stored password hashes, requiring users to reset their passwords.
- Ensure you have filesystem permissions set correctly for the plugin to function properly.

## Contributing

Contributions are welcome! Please fork the repository and submit a pull request for any enhancements or bug fixes.

## License

This plugin is licensed under the GPL v2 or later. Please see the LICENSE file for more details.

## Support

For support or inquiries, please open an issue in the repository or contact the plugin author directly.
