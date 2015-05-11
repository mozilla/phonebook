import ldap

from django.core.cache import cache
from django.http import JsonResponse
from django.shortcuts import render
from django.views.generic import View

from phonebook import settings

from .decorators import auth
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
                  'employeeType', 'isManager', 'bugzillaEmail',
                  'shirtSize', 'isManager', 'b2gNumber', 'roomNumber',)

        if not query:
            search = 'objectClass=mozComPerson'
        else:
            search = '(&(|(cn=*' + query + '*)(bugzillaEmail=*' + query + '*)(mail=*' + query + '*)(emailAlias=*' + query + '*)(im=*' + query + '*)(physicalDeliveryOfficeName=*' + query + '*)(description=*' + query + '*)(telephoneNumber=*' + query + '*)(mobile=*' + query + '*)(b2gNumber=*' + query + '*))(objectClass=mozComPerson))'
        search_result = self.ldap.search_s('o=com,dc=mozilla',
            ldap.SCOPE_SUBTREE, search, FIELDS)

        return [r[1] for r in search_result]

    @auth
    def get(self, *args, **kwargs):
        query = self.request.GET.get('query') or ''

        if cache.get(self.http_user['username'] + query):
            search_result = cache.get(self.http_user['username'] + query)
        else:
            search_result = self.search(query)
            cache.set(self.http_user['username'] + query, search_result, 5)

        response = JsonResponse(search_result, safe=False)
        response['Access-Control-Allow-Origin'] = '*'
        return response
