import ldap

from django.http import JsonResponse
from django.shortcuts import render
from django.views.generic import View

from phonebook import settings

from .auth import http_basic_auth, http_basic_auth_credentials
from .models import User


class Directory(View):
    def get(self):
        # <view logic>
        return render({}, 'directory/index.html')


class UsersJSON(View):
    def search(self, query=None):
        FIELDS = ('cn', 'title', 'telephoneNumber', 'mobile', 'description',
                  'manager', 'other', 'im', 'mail', 'emailAlias',
                  'physicalDeliveryOfficeName', 'workdaylocation',
                  'workdaycostcenter', 'deptname', 'employeeNumber',
                  'employeeType', 'description', 'isManager', 'bugzillaEmail',
                  'shirtSize', 'isManager', 'b2gNumber', 'roomNumber',)

        user = http_basic_auth_credentials(self)

        ldap_connection = ldap.initialize(settings.AUTH_LDAP_SERVER_URI)
        ldap_connection.simple_bind_s(user['username'], user['password'])

        if not query:
            search = 'objectClass=mozComPerson'
        else:
            search = '(&(|(cn=*' + query + '*)(bugzillaEmail=*' + query + '*)(mail=*' + query + '*)(emailAlias=*' + query + '*)(im=*' + query + '*)(physicalDeliveryOfficeName=*' + query + '*)(description=*' + query + '*)(telephoneNumber=*' + query + '*)(mobile=*' + query + '*)(b2gNumber=*' + query + '*))(objectClass=mozComPerson))'
        search_result = ldap_connection.search_s('o=com,dc=mozilla',
            ldap.SCOPE_SUBTREE, search, FIELDS)

        return search_result
        return None

    @http_basic_auth
    def get(self, *args):
        query = self.request.GET.get('query')
        search_result = self.search(query)
        return JsonResponse(search_result, safe=False)
