#!/usr/bin/python3

# V4 Este script actualiza la cuota de los usuarios.

import datetime
import configparser
import logging
import pymysql
import os
import sys

CONFDIR = '/etc/pquot'
LOGDIR = '/var/log/pquot'

def ahora():
    return str(datetime.datetime.now())

def main():
    logFile = open(os.path.join(LOGDIR, 'pquot-update-users.log'), 'a')
    configure_logging(logFile)

    cfg = read_config()

    db = connect_to_db(cfg)
    cursor = db.cursor()

    SELECT_QUERY = "SELECT client_ip, quota, used FROM quota"
    SELECT_CLIENTIP_QUERY = "SELECT client_ip FROM state WHERE client_ip = %s"
    DELETE_QUERY = "DELETE FROM state WHERE client_ip = %s"
    INSERT_QUERY = "INSERT INTO state (client_ip) VALUES (%s)"

    try:
        cursor.execute(SELECT_QUERY)
        QUERY_ALL_USER = cursor.fetchall()

        if QUERY_ALL_USER:
            logFile.write('\n[ %s ] Iniciando actualización de usuarios\n' % ahora())
            logFile.write('[ %s ] Procesando %s usuarios\n' % (ahora(), cursor.rowcount))

        for resultado in QUERY_ALL_USER:
            cursor.execute(SELECT_CLIENTIP_QUERY, (resultado[0],))
            QUERY_STATE_USER = cursor.fetchall()

            if QUERY_STATE_USER:
                logFile.write('[ %s ] Usuario %s ya procesado, se ignora\n' % (ahora(), QUERY_STATE_USER[0][0]))
            else:
                if resultado[1] == 0 or resultado[2] < resultado[1]:
                    cursor.execute(DELETE_QUERY, (resultado[0],))
                else:
                    cursor.execute(INSERT_QUERY, (resultado[0],))
                    logFile.write('[ %s ] Procesando usuario %s\n' % (ahora(), resultado[0]))

        db.commit()
    except Exception as e:
        logFile.write('[ %s ] Error: %s\n' % (ahora(), str(e)))
    finally:
        cursor.close()
        db.close()
        logFile.close()

def configure_logging(logFile):
    logging.basicConfig(filename=logFile.name, level=logging.INFO,
                        format='[%(asctime)s] %(levelname)s: %(message)s', datefmt='%Y-%m-%d %H:%M:%S')

def read_config():
    cfg = configparser.ConfigParser()
    cfg.read(os.path.join(CONFDIR, 'pquot.ini'))
    return cfg

def connect_to_db(cfg):
    dbhost = cfg.get('db', 'host')
    dbport = cfg.getint('db', 'port')
    dbuser = cfg.get('db', 'user')
    dbpasswd = cfg.get('db', 'password')
    dbname = cfg.get('db', 'dbname')


    db = pymysql.connect(host=dbhost, port=dbport, user=dbuser, passwd=dbpasswd, db=dbname )
    return db

if __name__ == "__main__":
    main()
