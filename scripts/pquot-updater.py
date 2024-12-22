#!/usr/bin/python3
# -*- coding: utf-8 -*-

# Este script actualiza la base de datos

from datetime import datetime
import configparser
from socket import gethostname
import logging
import pymysql
import os
import re
import sys

CONFDIR = '/etc/pquot'
WORKDIR = '/var/spool/pquot'
ACTIVELOG = os.path.join(WORKDIR, 'active.log')
PROCESSLOG = os.path.join(WORKDIR, 'process.log')
LINESBLOCK = 2000

ACCOUNTABLE_STATUS = (
    'TCP_CLIENT_REFRESH_MISS',
    'TCP_MISS',
    'TCP_MISS_ABORTED',
    'TCP_TUNNEL',
    'TCP_TUNNEL_ABORTED',
    'TCP_REFRESH_UNMODIFIED',
    'TCP_NEGATIVE_HIT',
    'TCP_REFRESH_MISS',
    'TCP_SWAPFAIL_MISS',
)

UNACCOUNTABLE_ORIGINS = ()
UNACCOUNTABLE_DESTS = ()

UPDATE_QUERY = """UPDATE quota SET 
    used = used + %s, 
    used_quota_24h = used_quota_24h + %s,
    used_quota_mensual = used_quota_mensual + %s,
    used_quota_anual = used_quota_anual + %s,
    last_update = NOW(), 
    cache_peer = '%s' 
WHERE client_ip = '%s'"""

INSERT_QUERY = """INSERT INTO quota 
    (client_ip, quota, used, used_quota_24h, used_quota_mensual, used_quota_anual, last_update, cache_peer) 
VALUES ('%s', %s, %s, %s, %s, %s, NOW(), '%s')"""

ipre = re.compile(r'\d+\.\d+\.\d+\.\d+')

def accountable(fields):
    status, code = fields['result_code'].split('/')
    hier_code, dest = fields['hierarchy_code'].split('/')
    origin = fields['client_address']
    code = int(code)
    #return code >= 200 and \
    return code <= 307 and \
           status in ACCOUNTABLE_STATUS and \
           hier_code != 'NONE'           

def write_dict(cursor, buffer_dict, hostname):
    temp_update, temp_insert = 0, 0
    for key in buffer_dict:
        bytes_used = str(buffer_dict[key][2])
        # Actualizar incluyendo las nuevas columnas
        cursor.execute(UPDATE_QUERY % (
            bytes_used, bytes_used, bytes_used, bytes_used,
            hostname, key))
        temp_update += 1
        
        if cursor.rowcount == 0:
            try:
                # Insertar incluyendo las nuevas columnas
                cursor.execute(INSERT_QUERY % (
                    key, 102400 * 1024, 
                    bytes_used, bytes_used, bytes_used, bytes_used,
                    hostname))
                temp_insert += 1
            except pymysql.IntegrityError as e:
                logging.warning('IntegrityError al insertar: %s' % line)
                print('IntegrityError al insertar: %s' % line)

    return temp_update, temp_insert













def main():
    logformat = '%(asctime)s ' + ('updater[%s]' % (os.getpid())) + ': %(levelname)-8s %(message)s'
    logging.basicConfig(level=logging.DEBUG,
                        format=logformat,
                        datefmt='%b %d %H:%M:%S',
                        filename='/var/log/pquot/updater.log',
                        filemode='a+')

    logging.info('Begin run')

    cfg = configparser.ConfigParser()
    cfg.read(os.path.join(CONFDIR, 'pquot.ini'))

    dbhost = cfg.get('db', 'host')
    dbport = cfg.getint('db', 'port')
    dbuser = cfg.get('db', 'user')
    dbpasswd = cfg.get('db', 'password')
    dbname = cfg.get('db', 'dbname')

    hostname = gethostname()

    if not os.access(ACTIVELOG, os.F_OK):
        logging.info('reader seems to be down, starting it')
        os.system("ps aux | grep -e 'reader.py' | grep -v grep | awk '{print $2}' | xargs -i kill {}")
        os.system('pquot-reader.py&')
        sys.exit(3)




    try:

        conn = pymysql.connect(host=dbhost, port=dbport, user=dbuser, passwd=dbpasswd, db=dbname )
    except pymysql.Error as e:
        logging.error('Error connecting to MySQL: %s' % e)
        print('Error connecting to MySQL: %s' % e)
        sys.exit(2)

    try:
        os.rename(ACTIVELOG, PROCESSLOG)
    except IOError as e:
        logging.error('Error renaming %s: %s' % (ACTIVELOG, e))
        print('Error renaming %s: %s' % (ACTIVELOG, e))
        sys.exit(1)

    plogbak = open('%s.%s' % (PROCESSLOG, datetime.now().strftime('%Y%m%d')), 'a+')

    cursor = conn.cursor()
    lines, queries_update, queries_insert, temp_update, temp_insert = 0, 0, 0, 0, 0
    buffer_dict = {}
    count = 0
    for line in open(PROCESSLOG):
        lines += 1
        line = line.strip()
        _fields = line.split()
        fields = dict()
        for key in ['time', 'result_code', 'bytes', 'hierarchy_code', 'client_address']:
            fields[key] = _fields.pop(0)
        if int(fields['bytes']) > 0 and accountable(fields):
            if fields['client_address'] in buffer_dict:
                buffer_dict[fields['client_address']][2] += int(fields['bytes'])
            else:
                buffer_dict[fields['client_address']] = [fields['time'], fields['result_code'], int(fields['bytes']),
                                                         fields['hierarchy_code']]
            count += 1
        if count >= LINESBLOCK:
            temp_update, temp_insert = write_dict(cursor, buffer_dict, hostname)
            queries_update += temp_update
            queries_insert += temp_insert
            temp_update = 0
            temp_insert = 0
            count = 0
            buffer_dict = {}
        plogbak.write('%s\n' % line)
    temp_update, temp_inser = write_dict(cursor, buffer_dict, hostname)
    queries_update += temp_update
    queries_insert += temp_insert
    temp_update = 0
    temp_insert = 0
    conn.close()
    os.unlink(PROCESSLOG)
    plogbak.close()
    logging.info('End run: lines=%s, queries_update=%s, queries_insert=%s (%d%%)' %
                 (lines, queries_update, queries_insert, float(queries_update + queries_insert) / lines * 100))


if __name__ == '__main__':
    main()
