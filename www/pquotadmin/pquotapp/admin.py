from django.contrib import admin
from .models import Quota, State

# Register your models here.
class CuotaAdmin(admin.ModelAdmin):
    list_display = ('client_ip', 'quota', 'used', 'last_update', 'cache_peer')
    search_fields = ('client_ip', 'quota')

admin.site.register(Quota, CuotaAdmin)
admin.site.register(State)
