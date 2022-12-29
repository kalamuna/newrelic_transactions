# New Relic Transactions

Drupal module for naming transactions in New Relic according to route, bundle, and role.

## Why

By default, the transactions tab of New Relic is not very helpful with diagnosing performance issues with Drupal sites. Lacking information on the architecture of Drupal, New Relic catagorizes transactions according to what functions are called the most, which often results in transactions that are grouped by low-level functions such as permission checking or menu loading, rather than the specific action that the user is trying to complete.

This module names transactions based on the routing information of the page request, giving clear indication of what action the user was trying to take on the site. For routes that have to do with entities, the id placeholder is replaced with the type of entity that is being accessed. The hightest-weighted role is appended to the transaction, so transactions can be grouped by whether users are anonymous, authenticated, or have other roles.

## Requirements

This module requires the following PHP extensions:

- [New Relic](https://docs.newrelic.com/docs/apm/agents/php-agent/getting-started/introduction-new-relic-php/)

## Installation

Install as you would normally install a contributed Drupal module. For further information, see [Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules).

## Configuration

1. Enable the module at Administration > Extend.
1. Configure the module at `admin/config/development/newrelic-transactions`
1. See the Status report page to ensure it's functioning correct at `admin/reports/status`

## Maintainers

- Mike McCaffrey (mikemccaffrey) - https://www.drupal.org/u/mikemccaffrey
- Kelly Jacobs (kellymjacobs) - https://www.drupal.org/u/kellymjacobs

## License

[GPL-2.0](LICENSE)
