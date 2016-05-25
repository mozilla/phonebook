# Phonebook
Mozilla's internal phonebook app.

## Installation
* Clone, host on a local, PHP-enabled server, run.
* Optional: `cp config-local.php-dist config-local.php` and tweak as needed

*Note:* The app requires LDAP server access. You probably need a [Mozilla-internal VPN connection](https://mana.mozilla.org/wiki/display/SD/VPN) up and running. Without, you can't currently develop on *Phonebook*. You can download your VPN certificate at [login.mozilla.com](https://login.mozilla.com).

## Apache, Authentication, and LDAP

Previous versions of the app authenticated twice - once to Apache (`mod_authnz_ldap`), and then again to PHP itself. This is not compatible with more complex auth methods (`mod_auth_mellon`), which set `REMOTE_USER` without providing a password for LDAP use.

The app now trusts that Apache is configured with `require valid-user`. If `REMOTE_USER` is not set, the app assumes something has gone wrong and refuses to proceed. The app admin is responsible for configuring Apache with a valid auth handler.

This should continue to work correctly under *both* the classic `mod_authnz_ldap` *and* the modern `mod_auth_mellon` methods, but please pay especial attention to this in future testing.

-- 2016-Apr (atoll)

## Contributing

To contribute, file bugs in Bugzilla and use GitHub for code review:

* Bugs in Bugzilla: [Webtools :: Phonebook](https://bugzilla.mozilla.org/buglist.cgi?component=Phonebook&product=Webtools&resolution=---)
* Code via github pull request
