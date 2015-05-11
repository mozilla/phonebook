import ldap

from django.core.cache import cache
from django.http import Http404, HttpResponse
from django.shortcuts import render
from django.views.generic import View

from phonebook.directory.decorators import auth


# Create your views here.
class UserPhoto(View):
    @auth
    def get(self, *args, **kwargs):
        email = kwargs['email']
        photo = cache.get('photo_' + email)

        if photo:
            photo = photo.decode('base64')
        else:
            self.ldap.simple_bind_s(self.http_user['username'],
                                    self.http_user['password'])

            results = [r[1] for r in self.ldap.search_s('o=com,dc=mozilla',
                ldap.SCOPE_SUBTREE, '(mail=' + email + ')', ['jpegPhoto'])]

            if not len(results):
                raise Http404('User does not exist')

            photo = results[0]['jpegPhoto'][0]

            cache.set('photo_' + email, photo.encode('base64'))

        return HttpResponse(photo, content_type='image/jpg')
