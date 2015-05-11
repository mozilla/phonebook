import Ember from 'ember';

export default Ember.Route.extend({
  model: function(params) {
    return this.get('store').find('person', { email: params.person_email });
  },

  serialize: function(model) {
    return { person_email: model.get('email') };
  }
});
