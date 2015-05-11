import DS from 'ember-data';

var attr = DS.attr;
var belongsTo = DS.belongsTo;
var hasMany = DS.hasMany;

export default DS.Model.extend({
  name: attr('string'),
  email: attr('string'),
  title: attr('string'),
  deptName: attr('string'),

  location: attr('string'),
  officeLocations: attr('object'),

  emailAliases: attr('object'),
  contactMethods: attr('object'),
  phoneNumbers: attr('object'),

  bio: attr('string'),
  worksOn: attr('string'),

  employees: hasMany('person', { inverse: 'manager' }),
  manager: belongsTo('person'),
  managerEmail: attr('string'),

  photoBlob: attr('object'),

  bugzillaEmail: attr('string'),
  employeeNumber: attr('string'),
  employeeType: attr('string'),
  shirtSize: attr('string'),
  workdayCostCenter: attr('string'),

  photoURL: function() {
    return '/people/' + this.get('email') + '/photo.jpg';
  }.property('email')
});
