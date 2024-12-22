from django.db import models
from django.apps import apps

if not apps.is_installed('pquotapp.Quota'):
    class Quota(models.Model):
        client_ip = models.CharField(max_length=255, primary_key=True)  # Cambiamos de GenericIPAddressField a CharField
        organization = models.CharField(max_length=255)
        quota = models.BigIntegerField(default=0)
        used = models.BigIntegerField(default=0)
        last_update = models.DateTimeField()  # Quitamos auto_now=True
        cache_peer = models.CharField(max_length=255, null=True, blank=True)
        used_quota_24h = models.BigIntegerField(default=0)
        used_quota_mensual = models.BigIntegerField(default=0)  # Agregamos este campo
        used_quota_anual = models.BigIntegerField(default=0)    # Agregamos este campo        

        def __unicode__(self):
            return "%s %s %s %s %s" % (self.client_ip, self.quota, self.used, self.last_update, self.cache_peer)

        class Meta:
            verbose_name_plural = 'Cuota'
            db_table = u'quota'
            app_label = 'pquotapp'

class State(models.Model):
    client_ip = models.CharField(max_length=255, primary_key=True)
    available = models.IntegerField()

    def __unicode__(self):
        return "%s" % (self.client_ip)

    class Meta:
        verbose_name_plural = 'Estado'
        db_table = u'state'