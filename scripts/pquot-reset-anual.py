#!/usr/bin/python3

# Este script Resetea la cuota anual

import datetime
import configparser
import logging
import pymysql
import os

def now():
    return str(datetime.datetime.now())

CONFDIR = '/etc/pquot'
LOGDIR = '/var/log/pquot'

# Configurar el registro de eventos
logging.basicConfig(filename=os.path.join(LOGDIR, 'pquot-reset-anual.log'), level=logging.INFO, format='[ %(asctime)s ] %(message)s')
logging.info('Iniciando reinicio de cuotas anuales')

cfg = configparser.ConfigParser()
cfg.read(os.path.join(CONFDIR, 'pquot.ini'))

dbhost = cfg.get('db', 'host')
dbport = cfg.getint('db', 'port')
dbuser = cfg.get('db', 'user')
dbpasswd = cfg.get('db', 'password')
dbname = cfg.get('db', 'dbname')

try:     
    db = pymysql.connect(host=dbhost, port=dbport, user=dbuser, passwd=dbpasswd, db=dbname)
    cursor = db.cursor()

    UPDATE_QUERY = "UPDATE quota SET used_quota_anual = 0"

    update = cursor.execute(UPDATE_QUERY)
    if update:
        logging.info('Reinicio completo de todas las cuotas anuales')
    else:
        logging.info('No hay cuotas anuales para reiniciar')

except pymysql.Error as e:
    logging.error(f'Error: {str(e)}')

finally:
    if db:
        db.close()