import ldap
from functools import wraps

from django.http import HttpResponse

from phonebook import settings


def http_basic_auth(func):
    @wraps(func)
    def _decorator(view, *args, **kwargs):
        user = http_basic_auth_credentials(view)

        if user:
            connection = ldap.initialize(settings.AUTH_LDAP_SERVER_URI)
            result = connection.simple_bind_s(user['username'],
                                              user['password'])

            print(result)

            if result:
                return func(view, *args, **kwargs)

        response = HttpResponse("Auth Required", status = 401)
        response['WWW-Authenticate'] = 'Basic realm="Mozilla LDAP Crendentials"'
        return response
    return _decorator


def http_basic_auth_credentials(view):
    request = view.request
    if request.META.has_key('HTTP_AUTHORIZATION'):
        authmethod, auth = request.META['HTTP_AUTHORIZATION'].split(' ', 1)
        if authmethod.lower() == 'basic':
            auth = auth.strip().decode('base64')
            username, password = auth.split(':', 1)
            username = 'mail=' + username + ',o=com,dc=mozilla'

            if username and password:
                return {"username": username, "password": password}
            else:
                return None

    return None
