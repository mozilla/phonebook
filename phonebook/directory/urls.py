from django.conf.urls import url

from .views import *


urlpatterns = [
    url(r'^$', Directory.as_view()),
    url(r'^users\.json$', UsersJSON.as_view()),
]
