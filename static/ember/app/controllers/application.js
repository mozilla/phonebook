import Ember from 'ember';

export default Ember.Controller.extend({
  people: undefined,

  updateLocalCache: function() {
    var self = this;
    this.set('people', self.store.find('person'));

    localforage.getItem('credentials').then(function(credentials) {
      if (!credentials) {
        return self.transitionToRoute('login');
      }

      localforage.getItem('lastUpdated').then(function(lastUpdated) {
        if (lastUpdated &&
            moment(lastUpdated).add(1, 'h').isAfter(moment())) {
          return;
        }

        console.info('Fetching JSON');

        Ember.$.ajax('/api/users.json', {
          username: credentials.username,
          password: credentials.password
        }).then(function(results) {
          console.info('Fetched JSON');
          var peopleToSavePromises = [];
          var people = {};

          results.forEach(function(person) {
            var p = {
              name: person.cn[0],
              email: person.mail[0]
            };

            // Field mappings...
            if (person.workdayLocation) {
              p.location = person.workdayLocation[0];
            }
            if (person.physicalDeliveryOfficeName) {
              p.officeLocations = person.physicalDeliveryOfficeName;
            }

            if (person.title) {
              p.title = person.title[0];
            }
            if (person.deptName) {
              p.deptName = person.deptName[0];
            }
            if (person.manager && person.manager[0]) {
              p.managerEmail = person.manager[0].match(/mail=(.*),o/)[1];
            }

            if (person.other) {
              p.bio = person.other[0];
            }
            // This is the "I work on..." field.
            if (person.description) {
              p.worksOn = person.description[0];
            }

            var newRecord = self.store.createRecord('person', p);
            people[p.email] = newRecord;

            peopleToSavePromises.push(newRecord.save());
          });

          Ember.RSVP.allSettled(peopleToSavePromises).then(function() {
            var managerPromises = [];

            for (var email in people) {
              var personToSetManager = people[email];
              personToSetManager.set('manager',
                people[personToSetManager.get('managerEmail')]);
              managerPromises.push(personToSetManager.save());
            }

            return Ember.RSVP.allSettled(managerPromises);
          }).then(function() {
            return localforage.setItem('lastUpdated', moment());
          }).then(function() {
            console.info('Phonebook updated');
            self.set('people', self.store.find('person'));
          });
        });
      });
    });
  }
});
