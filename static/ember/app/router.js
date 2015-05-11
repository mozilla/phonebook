import Ember from 'ember';
import config from './config/environment';

var Router = Ember.Router.extend({
  location: config.locationType
});

export default Router.map(function() {
  this.resource('people', { path: '/' }, function() {
    // this.resource('person', { path: ':person_id' });
    //this.resource('person', { path: ':person_email' });
  });
  this.resource('person', { path: 'person/:person_email' });
  this.route('login');
  this.route('logout');
});
