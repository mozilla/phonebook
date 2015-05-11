from django.conf.urls import url

from .views import *


urlpatterns = [
    url(r'^(?P<email>.*)/photo\.jpg$', UserPhoto.as_view()),
]
