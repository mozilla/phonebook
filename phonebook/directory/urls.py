from django.conf.urls import url

from .views import Directory, UsersJSON

urlpatterns = [
    url(r'^$', Directory.as_view()),
    url(r'^users\.json$', UsersJSON.as_view()),
]
