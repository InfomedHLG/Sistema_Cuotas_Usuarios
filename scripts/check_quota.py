#!/usr/bin/python3 -u

import sys
import os
import pymysql
from configparser import ConfigParser

CONFDIR = '/etc/pquot'

cfg = ConfigParser()
cfg.read(os.path.join(CONFDIR, 'pquot.ini'))

dbhost = cfg.get('db', 'host')
dbuser = cfg.get('db', 'user')
dbpasswd = cfg.get('db', 'password')
dbname = cfg.get('db', 'dbname')
dbport = cfg.getint('db', 'port')

try:
    conn = pymysql.connect(host=dbhost, port=dbport, user=dbuser, passwd=dbpasswd, db=dbname )
    cur = conn.cursor()
except pymysql.Error as e:
    print(f'Error connecting to MySQL: {e}')
    sys.exit(2)

SELECT_CLIENTIP_QUERY = "SELECT client_ip FROM state WHERE client_ip = '%s'"	
	
req = sys.stdin.readline().strip()
while req:
    cur.execute(SELECT_CLIENTIP_QUERY % (req.split()[0]))
    if cur.fetchall():
        print('ERR')
    else:
        print('OK')
    req = sys.stdin.readline().strip()
