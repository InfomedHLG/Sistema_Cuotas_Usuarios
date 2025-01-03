# Generated by Django 4.2.7 on 2023-11-25 16:10

from django.db import migrations, models


class Migration(migrations.Migration):

    initial = True

    dependencies = [
    ]

    operations = [
        migrations.CreateModel(
            name='Quota',
            fields=[
                ('client_ip', models.CharField(max_length=255, primary_key=True, serialize=False)),
                ('organization', models.CharField(blank=True, max_length=765)),
                ('quota', models.BigIntegerField()),
                ('used', models.BigIntegerField()),
                ('last_update', models.DateTimeField(blank=True, null=True)),
                ('cache_peer', models.CharField(blank=True, max_length=765)),
            ],
            options={
                'verbose_name_plural': 'Cuota',
                'db_table': 'quota',
            },
        ),
        migrations.CreateModel(
            name='State',
            fields=[
                ('client_ip', models.CharField(max_length=255, primary_key=True, serialize=False)),
                ('available', models.IntegerField()),
            ],
            options={
                'verbose_name_plural': 'Estado',
                'db_table': 'state',
            },
        ),
    ]
