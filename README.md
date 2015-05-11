Mozilla's LDAP Phonebook
========================

Built with Django-backed API and an Ember frontend, this is Mozilla's internal phonebook. It connects to Mozilla's LDAP server via the Django app, allowing you to update phonebook entries and get an up-to-date copy of the entire phonebook to use offline.

Development
-----------

You need [`python`](https://www.python.org/) and [`node`](http://nodejs.org/) installed.

_TODO:_ Provide better bootstrapping docs.

You'll need to run the Ember and Django app at the same time. Eventually we'll have something better but for now, follow these steps:

 1. Install the Django/Ember dependencies:
    `python ./bin/peep.py install -r requirements.txt`
    and
    `cd static/ember && npm install`
 2. Run the Django migrations:
    `python manage.py migrate`
 3. Run the Django server:
    `python manage.py runserver`
 4. In a separate terminal window, run the Ember app:
    `cd static/ember && ember serve`
 5. Open [localhost:8000](http://localhost:8000) and run the app!

*Note:* The app requires LDAP server access. You probably need a [Mozilla-internal VPN connection](https://mana.mozilla.org/wiki/display/SD/VPN) up and running. Without, you can't currently develop on *Phonebook*.

Deploying
---------

_TODO:_ Document a deployment method, preferably with docker + serving the built Ember app inside Django's `/static/` folder.

Contributing
------------

File bugs in Bugzilla under [Webtools :: Phonebook](https://bugzilla.mozilla.org/buglist.cgi?component=Phonebook&product=Webtools&resolution=---).

If you have a feature you'd like to see or a patch, simply open a pull request. If there is a related Bugzilla bug, please reference the bug number in the commit/pull request. Thank you!

# License

This program is free software; it is distributed under an
[MPL 2.0 License](https://github.com/mozilla/phonebook/blob/master/LICENSE).

---

Copyright (c) 2015 [Mozilla](https://mozilla.org)
([Contributors](https://github.com/mozilla/phonebook/graphs/contributors)).

