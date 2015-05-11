import Ember from 'ember';

export default Ember.Controller.extend({
  username: null,
  password: null,

  actions: {
    saveCredentials: function() {
      if (!this.get('username') || !this.get('password')) {
        alert('Fill in the fields!');
        return;
      }

      console.log({
        username: this.get('username'),
        password: this.get('password'),
      });

      localforage.setItem('credentials', {
        username: this.get('username'),
        password: this.get('password'),
      }).then(function() {
        window.location.reload();
      });
    }
  }
});
