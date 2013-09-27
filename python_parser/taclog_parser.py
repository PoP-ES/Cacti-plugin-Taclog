#/*
# +-------------------------------------------------------------------------+
# | This program is free software; you can redistribute it and/or           |
# | modify it under the terms of the GNU General Public License             |
# | as published by the Free Software Foundation; either version 2          |
# | of the License, or (at your option) any later version.                  |
# |                                                                         |
# | This program is distributed in the hope that it will be useful,         |
# | but WITHOUT ANY WARRANTY; without even the implied warranty of          |
# | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the           |
# | GNU General Public License for more details.                            |
# +-------------------------------------------------------------------------+
#
#    Author ......... PoP-ES/RNP
#    Home Site ...... http://www.pop-es.rnp.br
#    Program ........ Taclog parser
#    Version ........ 1.1
#
#*/

import difflib
import psycopg2
import shutil
import logging
import sys
from mercurial import error, lock

#parameters
#

db="tacacs"
user="tacswatch"
host="127.0.0.1"
pswd="zx6kzrYEFX8"

#logging configurations
logger = logging.getLogger('TaclogParser')
hdlr = logging.FileHandler('/var/log/taclogparser.log')
formatter = logging.Formatter('%(asctime)s %(levelname)s %(message)s')
hdlr.setFormatter(formatter)
logger.addHandler(hdlr)
logger.setLevel(logging.INFO)
###
###


try:
	lkfl = lock.lock("/var/log/taclog_lock", timeout=50) # wait at most 50 seconds
	logger.info('Took the lock')
	#open tacacs+ log file
	fp1 = open('/var/log/tac_plus.acct', 'r')
	lines1 = fp1.readlines()
	fp1.close()
	logger.info('Opened tac_plus.acct file');

	#open parser cache
	fp2 = open('/var/log/tac_plus_cache', 'rw')
	lines2 = fp2.readlines()
	fp2.close()
	logger.info('Opened tac_plus_cache file');


	#obtain diffs between then
	result_list = list(difflib.Differ().compare(lines1, lines2))

	lines_diff1 = []
	lines_diff2 = []
	for l in result_list:
		if l[0] == '-':
			lines_diff1.append(l)
	
	logger.info('Diff created');
	
	
	logger.info('Changed: %d' % len(lines_diff1));

	#for each different line, add on database
	for i in range(0, len(lines_diff1)):
		args = lines_diff1[i].split('\t');
	   	if args[2] != "unknown":
		  for i in range(0, len(args)):
			#remove - sign from date
			if i == 0:
				aux = args[0].split('- ');
				args[0] = aux[1];
		  #obtain which fields needs to be filled in
		  sql = "INSERT INTO dados(datehour, ip, login, loginconsole, sync, type"
	          for j in range(4, len(args)):
        		if (args[j].startswith('task_id')):
                	        sql = sql + ", taskid"
	                if (args[j].startswith('service')):
        	                sql = sql + ", service"
                	if (args[j].startswith('timezone')):
	                        sql = sql + ", timezone"
        	        if (args[j].startswith('process*')):
                	        sql = sql + ", event"
	                if (args[j].startswith('priv-lvl')):
        	                sql = sql + ", privlvl"
                	if (args[j].startswith('cmd')):
	                        sql = sql + ", command"
        	        if (args[j].startswith('elapsed_time')):
                	        sql = sql + ", elapsedtime"
	                if (args[j].startswith('start_time')):
        	                sql = sql + ", starttime"
                	if (args[j].startswith('disc-cause=')):
	                        sql = sql + ", disccause"
        	        if (args[j].startswith('pre-session-time')):
                	        sql = sql + ", presessiontime"
	                if (args[j].startswith('disc-cause-ext')):
        	                sql = sql + ", disccauseext"
                	if (args[j].startswith('event')):
	                        sql = sql + ", event"
        	        if (args[j].startswith('reason')):
                	        sql = sql + ", reason"
	          sql = sql + ") VALUES("
        	  sql = sql + "\'" + args[0] + "\'"
	

		  for k in range(0, len(args)):
			#remove equal sign
			aux2 = args[k].split('=');
			if len(aux2) > 1:
				args[k] = aux2[1];
			#remove line break from last parameter
			aux2 = args[k].split('\n');
			if len(aux2) > 1:
				args[k] = aux2[0];

		  #complete sql query with values
		  for l in range(1, len(args)):
	                        sql = sql + ", \'" + args[l] + "\'"

	          sql = sql + ")"

		  con = None

		  #insert on database
		  try:
	                con = psycopg2.connect(database=db, user=user, host=host, password=pswd)

	                cur = con.cursor()

	                cur.execute(sql)

	                con.commit()

	                logger.info('[%d] New logs recorded successfully' % i);
		 
			shutil.copy("/var/log/tac_plus.acct", "/var/log/tac_plus_cache");

	          except psycopg2.DatabaseError, e:

	                if con:
	                        con.rollback()

			logger.error('%s' %e);
	                sys.exit(1)


	          finally:

		    if con:
	            	con.close()
except error.LockHeld:
	# couldn't take the lock
	logger.info("couldn't take the lock")
else:
	lkfl.release()
