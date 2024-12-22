#!/usr/bin/env python
from django import forms
from .models import Quota

class AddCuota(forms.ModelForm):
    class Meta:
        model = Quota
        fields = ['client_ip', 'quota']
        labels = {
            'client_ip': 'Nombre de Usuario',
        }

    def save(self, commit=True):
        instance = super().save(commit=False)
        instance.organization = instance.client_ip  # Copiar el nombre de usuario a organization
        instance.used = 0
        if commit:
            instance.save()
        return instance