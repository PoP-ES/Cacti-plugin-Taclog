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
#    Version ........ 1.0
#
#*/

import difflib
import psycopg2
import shutil
import logging
import sys

#parameters
#

db="tacacs"
user="tacswatch"
host="127.0.0.1"
pswd="tacacs"

###
###
#open tacacs+ log file
fp1 = open('/var/log/tac_plus.acct', 'r')
lines1 = fp1.readlines()
fp1.close()

#open parser cache
fp2 = open('/var/log/tac_plus_cache', 'rw')
lines2 = fp2.readlines()
fp2.close()

#obtain diffs between then
result_list = list(difflib.Differ().compare(lines1, lines2))

lines_diff1 = []
lines_diff2 = []
for l in result_list:
	if l[0] == '-':
		lines_diff1.append(l)

#logging configurations
logger = logging.getLogger('TaclogParser')
hdlr = logging.FileHandler('/var/log/taclogparser.log')
formatter = logging.Formatter('%(asctime)s %(levelname)s %(message)s')
hdlr.setFormatter(formatter)
logger.addHandler(hdlr)
logger.setLevel(logging.INFO)

#for each different line, add on database
for i in range(0, len(lines_diff1)):
	args = lines_diff1[i].split('\t');
	for i in range(0, len(args)):
		#remove - sign from date
		if i == 0:
			aux = args[0].split('- ');
			args[0] = aux[1];

	#obtain which fields needs to be filled in
	sql = "INSERT INTO dados(datehour, ip, login, loginconsole, sync, type"
        for i in range(4, len(args)):
        	if (args[i].startswith('task_id')):
                        sql = sql + ", taskid"
                if (args[i].startswith('service')):
                        sql = sql + ", service"
                if (args[i].startswith('timezone')):
                        sql = sql + ", timezone"
                if (args[i].startswith('process*')):
                        sql = sql + ", event"
                if (args[i].startswith('priv-lvl')):
                        sql = sql + ", privlvl"
                if (args[i].startswith('cmd')):
                        sql = sql + ", command"
                if (args[i].startswith('elapsed_time')):
                        sql = sql + ", elapsedtime"
                if (args[i].startswith('start_time')):
                        sql = sql + ", starttime"
                if (args[i].startswith('disc-cause=')):
                        sql = sql + ", disccause"
                if (args[i].startswith('pre-session-time')):
                        sql = sql + ", presessiontime"
                if (args[i].startswith('disc-cause-ext')):
                        sql = sql + ", disccauseext"
                if (args[i].startswith('event')):
                        sql = sql + ", event"
                if (args[i].startswith('reason')):
                        sql = sql + ", reason"
        sql = sql + ") VALUES("
        sql = sql + "\'" + args[0] + "\'"


	for i in range(0, len(args)):
		#remove equal sign
		aux2 = args[i].split('=');
		if len(aux2) > 1:
			args[i] = aux2[1];
		#remove line break from last parameter
		aux2 = args[i].split('\n');
		if len(aux2) > 1:
			args[i] = aux2[0];

	#complete sql query with values
	for i in range(1, len(args)):
                        sql = sql + ", \'" + args[i] + "\'"

        sql = sql + ")"

	con = None

	#insert on database
	try:
                con = psycopg2.connect(database=db, user=user, host=host, password=pswd)

                cur = con.cursor()

                cur.execute(sql)

                con.commit()

                logger.info('New logs recorded successfully');
		 
		shutil.copy("/var/log/tac_plus.acct", "/var/log/tac_plus_cache");

        except psycopg2.DatabaseError, e:

                if con:
                        con.rollback()

		logger.error('%s' %e);
                sys.exit(1)


        finally:

	    if con:
            	con.close()
