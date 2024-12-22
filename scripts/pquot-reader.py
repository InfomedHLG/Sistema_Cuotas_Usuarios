#!/usr/bin/python3
# -*- coding: utf-8 -*-

# Este script  Hace lectura del sistema y registra en los logs

import fcntl
import logging
import os
import signal
import sys
import time

WORKDIR = '/var/spool/pquot'
ACCESSLOG = '/var/log/squid/access_cuotas.log'
ACTIVELOG = os.path.join(WORKDIR, 'active.log')
PIDFILE = '/var/run/pquot-updater.pid'

def openlog(seek_end=True):
    try:
        f = open(ACCESSLOG, 'r')
    except IOError as e:
        sys.exit('Error al abrir {}: {}'.format(ACCESSLOG, e))
    if seek_end:
        f.seek(0, 2)
    return f

def readlog(f):
    line = f.readline()
    while line:
        fields = line.strip().split()
        if len(fields) > 0 and fields[-1] == '-':
            try:
                with open(ACTIVELOG, 'a') as active_log:
                    active_log.write('{} {} {} {} {}\n'.format(fields[0], fields[3], fields[4], fields[8], fields[7]))
            except IndexError as e:
                print('Índice fuera de rango: {} con línea [{}]'.format(e, line))
        line = f.readline()

def handler(signum, frame):
    logging.info('SIGHUP recibida, releyendo access.log')
    if f.closed:
        logging.error('El objeto de archivo access.log está cerrado dentro del controlador SIGHUP')
        pidfile.close()
        os.unlink(PIDFILE)
        sys.exit(1)
    readlog(f)
    f.close()
    f = openlog(seek_end=False)

def main():
    logformat = '%(asctime)s [reader:{}]: %(levelname)-8s %(message)s'.format(os.getpid())
    logging.basicConfig(level=logging.DEBUG,
                        format=logformat,
                        datefmt='%b %d %H:%M:%S',
                        filename='/var/log/pquot/reader.log',
                        filemode='a+')

    pidfile = open(PIDFILE, 'w')
    try:
        fcntl.flock(pidfile.fileno(), fcntl.LOCK_EX | fcntl.LOCK_NB)
    except IOError as e:
        logging.info('No se puede adquirir el bloqueo en {}'.format(PIDFILE))
        sys.exit(4)

    pidfile.write('{}\n'.format(os.getpid()))
    pidfile.flush()

    signal.signal(signal.SIGHUP, handler)
    f = openlog()

    logging.info('Ingresando al ciclo principal')
    while True:
        readlog(f)
        time.sleep(1)

if __name__ == '__main__':
    main()
