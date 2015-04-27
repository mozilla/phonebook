# -*- coding: utf-8 -*-
from __future__ import unicode_literals

from django.db import models, migrations


class Migration(migrations.Migration):

    dependencies = [
    ]

    operations = [
        migrations.CreateModel(
            name='ContactMethod',
            fields=[
                ('id', models.AutoField(verbose_name='ID', serialize=False, auto_created=True, primary_key=True)),
                ('info', models.CharField(max_length=200)),
            ],
        ),
        migrations.CreateModel(
            name='PhoneNumber',
            fields=[
                ('id', models.AutoField(verbose_name='ID', serialize=False, auto_created=True, primary_key=True)),
                ('number', models.CharField(max_length=40)),
            ],
        ),
        migrations.CreateModel(
            name='User',
            fields=[
                ('id', models.AutoField(verbose_name='ID', serialize=False, auto_created=True, primary_key=True)),
                ('name', models.CharField(max_length=200)),
                ('title', models.CharField(max_length=200)),
                ('phone_extension', models.CharField(max_length=200)),
                ('city', models.CharField(max_length=200)),
                ('picture', models.BinaryField()),
                ('contacts', models.ForeignKey(to='directory.ContactMethod')),
                ('manager', models.OneToOneField(to='directory.User')),
                ('phones', models.ForeignKey(to='directory.PhoneNumber')),
            ],
        ),
    ]
