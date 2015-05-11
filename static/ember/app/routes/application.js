import Ember from 'ember';

export default Ember.Route.extend({
  setupController: function(controller) {
    var self = this;
    // localforage.getItem('accessToken').then(function(accessToken) {
    //   if (!accessToken) {
    //     controller.set('currentUser', null);
    //   } else {
    //     self.store.find('user', {
    //       accessToken: accessToken
    //     }).then(function(user) {
    //       controller.set('currentUser', user);
    //     }).catch(function(err) {
    //       console.error(err);
    //     });
    //   }
    // });

    controller.updateLocalCache();
  }
});
