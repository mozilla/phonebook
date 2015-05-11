import LFAdapter from 'ember-localforage-adapter/adapters/localforage';

localforage.setDriver(localforage.LOCALSTORAGE);

export default LFAdapter.extend({
  namespace: 'phonebook'
});
