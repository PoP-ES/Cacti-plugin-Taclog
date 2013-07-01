/*
 +----------------------------------------------------------------+
 | This program is free software; you can redistribute it and/or  |
 | modify it under the terms of the GNU General Public License    |
 | as published by the Free Software Foundation; either version 2 |
 | of the License, or (at your option) any later version.         |
 |                                                                |
 | This program is distributed in the hope that it will be useful,|
 | but WITHOUT ANY WARRANTY; without even the implied warranty of |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the  |
 | GNU General Public License for more details.                   |
 +----------------------------------------------------------------+
 | Cacti: The Complete RRDTool-based Graphing Solution            |
 +----------------------------------------------------------------+
 | http://www.cacti.net/                                          |
 +----------------------------------------------------------------+
*/
/******************************************************************

    Author ......... PoP-ES/RNP
    Home Site ...... http://www.pop-es.rnp.br
    Program ........ taclog "Taclog plugin"
    Version ........ 1.0
    Purpose ........ Visualize Tacacs+ logs
******************************************************************/

This plugin allows easy visualization of TACACS+ logs database.
To put TACACS+ logs into the database, see README_PARSER file for more information.

##############################
####### INSTALATION ########################################################

Copy all files in taclog.tar.gz to "plugins" cacti's directory.
On cacti web interface, click on "configuration"'s menu option "Plugin Management".
You can find the plugin settings under the "tacmon" tab.
Fill in the settings according. You can use mysql or pgsql as database.

