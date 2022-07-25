# Newrelic Transactions
Drupal 8 module for naming transactions in New Relic according to route, bundle, and role.

By default, the transactions tab of New Relic is not very helpful with diagnosing performance issues with Drupal sites. Lacking information on the architecture of Drupal, New Relic catagorizes transactions according to what functions are called the most, which often results in transactions that are grouped by low-level functions such as permission checking or menu loading, rather than the specific action that the user is trying to complete.

This module names transactions based on the routing information of the page request, giving clear indication of what action the user was trying to take on the site. For routes that have to do with entities, the id placeholder is replaced with the type of entity that is being accessed. The hightest-weighted role is appended to the transaction, so transactions can be grouped by whether users are anonymous, authenticated, or have other roles.
