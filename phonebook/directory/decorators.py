import ldap
from functools import wraps

from django.http import HttpResponse

from phonebook import settings


def auth(func):
    @wraps(func)
    def _decorator(view, *args, **kwargs):
        view.http_user = _http_basic_auth_credentials(view)

        if view.http_user:
            view.ldap = ldap.initialize(settings.AUTH_LDAP_SERVER_URI)
            result = view.ldap.simple_bind_s(view.http_user['username'],
                                             view.http_user['password'])

            if result:
                return func(view, *args, **kwargs)

        response = HttpResponse("Auth Required", status = 401)
        response['WWW-Authenticate'] = 'Basic realm="Mozilla LDAP Crendentials"'
        response['Access-Control-Allow-Origin'] = '*'
        return response
    return _decorator


def _http_basic_auth_credentials(view):
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
