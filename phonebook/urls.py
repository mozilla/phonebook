from django.conf.urls import patterns, include, url
from django.contrib import admin

from httpproxy.views import HttpProxy


urlpatterns = patterns(
    '',
    # Examples:
    # url(r'^$', 'phonebook.base.views.home', name='home'),
    # url(r'^blog/', include('blog.urls')),

    url(r'^api/', include('phonebook.directory.urls')),
    url(r'^people/', include('phonebook.photos.urls')),
    url(r'^admin/', include(admin.site.urls)),
    (r'^(?P<url>.*)$',
        HttpProxy.as_view(base_url='http://localhost:4200/'))
)
