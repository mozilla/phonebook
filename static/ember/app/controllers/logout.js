import Ember from 'ember';

export default Ember.Controller.extend({
  init: function() {
    localforage.removeItem('phonebook').then(function() {
      return localforage.removeItem('lastUpdated');
    }).then(function() {
      window.location.reload();
    });
  }
});
