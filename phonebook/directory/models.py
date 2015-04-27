from django.db import models

# from phonenumber_field.modelfields import PhoneNumberField


# Create your models here.
class User(models.Model):
    name = models.CharField(max_length=200)
    title = models.CharField(max_length=200)
    phone_extension = models.CharField(max_length=200)
    city = models.CharField(max_length=200)
    picture = models.BinaryField()

    # Multiple values
    phones = models.ForeignKey('directory.PhoneNumber')
    contacts = models.ForeignKey('directory.ContactMethod')

    manager = models.OneToOneField('directory.User')

class ContactMethod(models.Model):
    # user = models.ForeignKey('directory.User', unique=True)
    info = models.CharField(max_length=200)

class PhoneNumber(models.Model):
    # user = models.ForeignKey('directory.User', unique=True)
    number = models.CharField(max_length=40)
