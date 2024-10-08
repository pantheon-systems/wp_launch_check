![WPLC Logo](docs/wplc.png)

# WP Launch Check

WP Launch Check is an extension for WP-CLI designed for Pantheon.io WordPress customers. While designed initially for the Pantheon dashboard it is intended to be fully usable outside of Pantheon.

[![Tests](https://github.com/pantheon-systems/wp_launch_check/actions/workflows/validate.yml/badge.svg)](https://github.com/pantheon-systems/wp_launch_check/actions/workflows/validate.yml)
[![Build Status](https://github.com/pantheon-systems/wp_launch_check/actions/workflows/release.yml/badge.svg)](https://github.com/pantheon-systems/wp_launch_check/actions/workflows/release.yml)
[![Actively Maintained](https://img.shields.io/badge/Pantheon-Actively_Maintained-yellow?logo=pantheon&color=FFDC28)](https://pantheon.io/docs/oss-support-levels#actively-maintained-support)


To use WP Launch Check simply run the ```wp launchcheck <subcommand>``` command like you would any other WP-CLI command.

For more information about WP-CLI you can visit [their github page](https://github.com/wp-cli/wp-cli).

## Installing

Installing this package requires WP-CLI v0.23.0 or greater. Update to the latest stable release with `wp cli update`.

Once you've done so, you can install this package with `wp package install pantheon-systems/wp_launch_check`.

## Available commands

Below is a summary of the available commands. *Full technical description of each check run by each command can be found in the [CHECKS.md](CHECKS.md)*

  * **wp launchcheck cron** : Checks whether cron is enabled and what jobs are scheduled
  * **wp launchcheck general**: General checks for data and best practice, i.e. are you running the debug-bar plugin or have WP_DEBUG defined? This will tell you.
  * **wp launchcheck database**: Checks related to the databases.
  * **wp launchcheck object_cache**: Checks whether object caching is enabled and if on Pantheon whether redis is enabled.
  * **wp launchcheck sessions**: Checks for plugins referring to the php session_start() function or the superglobal ```$SESSION``` variable. In either case, if you are on a cloud/distributed platform you will need additional configuration achieve the expected functionality
  * **wp launchcheck plugins**: Checks plugins for updates
  * **wp launchcheck themes**: Checks themes for updates




