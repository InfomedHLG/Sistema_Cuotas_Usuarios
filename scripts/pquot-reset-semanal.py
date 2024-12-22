#!/usr/bin/python3

# Este script  Resetea la cuota

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
logging.basicConfig(filename=os.path.join(LOGDIR, 'pquot-reset-24horas.log'), level=logging.INFO, format='[ %(asctime)s ] %(message)s')
logging.info('Iniciando reinicio de cuotas')

cfg = configparser.ConfigParser()
cfg.read(os.path.join(CONFDIR, 'pquot.ini'))

dbhost = cfg.get('db', 'host')
dbport = cfg.getint('db', 'port')
dbuser = cfg.get('db', 'user')
dbpasswd = cfg.get('db', 'password')
dbname = cfg.get('db', 'dbname')

try:     
    db = pymysql.connect(host=dbhost, port=dbport, user=dbuser, passwd=dbpasswd, db=dbname )
    cursor = db.cursor()

    UPDATE_QUERY = "UPDATE quota SET used_quota_24h = 0"
    DELETE_QUERY = "DELETE FROM state"

    update = cursor.execute(UPDATE_QUERY)
    if update:
        logging.info('Reinicio completo de todas las cuotas')
    else:
        logging.info('No hay cuotas para reiniciar en la tabla de cuotas')

    delete = cursor.execute(DELETE_QUERY)
    if delete:
        logging.info('Reinicio completo de la tabla de estado')
    else:
        logging.info('No hay datos para reiniciar en la tabla de estado')

except pymysql.Error as e:
    logging.error(f'Error: {str(e)}')

finally:
    if db:
        db.close()
