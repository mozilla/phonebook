import Ember from 'ember';

export default Ember.Controller.extend({
  init: function() {
    // TODO: Check into this later, CSP makes it weird.
    // var self = this;

    // // TODO: Abstract this as a "credentials or login "
    // localforage.getItem('credentials').then(function(credentials) {
    //   if (!credentials) {
    //     return self.transitionToRoute('login');
    //   }

    //   var request = new XMLHttpRequest();
    //   request.open('GET', self.model.get('photoURL'), true,
    //                credentials.username, credentials.password);
    //   request.responseType = 'blob';

    //   request.onreadystatechange = function() {
    //     if (request.readyState !== request.DONE) {
    //       return;
    //     }

    //     console.log(request.response);

    //     Ember.$('#person-photo').attr('src',
    //       window.URL.createObjectURL(request.response));
    //   };

    //   request.send();
    // });
  }
});
