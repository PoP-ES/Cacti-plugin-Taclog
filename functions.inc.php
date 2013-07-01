<?php

require_once('DB.php');

chdir('../../');
include_once("./include/auth.php");
$_SESSION['custom']=false;
include_once("./include/config.php");

/* global vars */
$tableNames = array();
$PHP_SELF = $_SERVER['PHP_SELF'];
$set = array();
$keyCond = array();
$time = gettimeofday();
$timeFormat = "Y-m-d H:i";
$fields = preg_split('/[, ]/', read_config_option("taclog_fields"));
$set['sqlcond'] = array();
/* read Database configs */
$dbType = read_config_option("taclog_dbType");
$dbUser = read_config_option("taclog_dbUser");
$dbPass = read_config_option("taclog_dbPass");
$dbHost = read_config_option("taclog_dbHost");
$dbPort = read_config_option("taclog_dbPort");
$dbName = read_config_option("taclog_dbName");

/* connect to database */
$db = DB::connect("${dbType}://${dbUser}:${dbPass}@${dbHost}:${dbPort}/${dbName}");
if (DB::isError($db)) die($db->getMessage());
#$db = pg_connect("host=".$dbHost." dbname=".$dbName." user=".$dbUser." password=".$dbPass." port=".$dbPort) or die('Could not connect: ' . pg_last_error());

/* load table names in $tableNames*/
getTableNames();
if (isset($_POST['table'])){
	$set['table'] = $_POST['table'];
}else{
	if (count($tableNames) > 0)
		$set['table'] = $tableNames[count($tableNames) - 1];
	else
		$set['table'] = null;
}
getKeyNames();

/* fill up $set with values from $_POST or defaults */
$_SESSION["sess_current_date1"] = date($timeFormat,$time['sec']-3600) ;
$_SESSION["sess_current_date2"] = date($timeFormat,$time['sec']) ;
if (isset($_POST['date1'])){
	$set['start'] = $_POST['date1'];
}else{
	$set['start'] = $_SESSION["sess_current_date1"];
}
if (isset($_POST['date2'])){
	$set['end'] = $_POST['date2'];
}else{
	$set['end'] = $_SESSION["sess_current_date2"];
}
$set['page'] = ( isset($_POST['page']) ? $_POST['page'] : 1 );
if ($set['page'] < 1 ) $set['page'] = 1;
$set['rows_page'] = ( isset($_POST['rowspage']) ? $_POST['rowspage'] : 5000 );
$set['sqlcond']['login'] = ( isset($_POST['sqllogin']) ? $_POST['sqllogin'] : null);
$set['sqlcond']['ip'] = ( isset($_POST['sqlip']) ? $_POST['sqlip'] : null);
$set['sqlcond']['loginconsole'] = ( isset($_POST['lconsole']) ? $_POST['lconsole'] : null);
$set['sqlcond']['command'] = ( isset($_POST['command']) ? $_POST['command'] : null);
$set ['total'] =  False;
if (isset($_POST['total']))
	if ($_POST['total'] == 'true')
		$set['total'] = True;

/* Validate POST parameters */
#Valid date
validateInput($set['start'], "/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/");
validateInput($set['end'], "/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/");
#Check for characters, _ and numbers
validateInput($set['table'], "/^[\w\d]*$/");
validateInput($set['sqlcond']['ip'], "/^[\d:\.abcdef1234567890]*$/");
validateInput($set['sqlcond']['login'], "/^[\d:\.abcdefghijklmnopqrstuvwxyz01234567_\-89]*$/");
validateInput($set['sqlcond']['loginconsole'], "/^[\d:\.abcdefghijklmnopqrstuvwxyz0123456789]*$/");
validateInput($set['sqlcond']['command'], "/^[\d:\.abcdefghijklm nopqrstuvwxyz0123456789]*$/");
#Check numbers
input_validate_input_number($set['page']);
input_validate_input_number($set['rows_page']);

function validateInput($value, $pattern){
	if (!preg_match($pattern, $value) && ($value != ""))
		die_html_input_error();
}

function getTableNames() {
	global $db,$tableNames;
	$tableNames=array();
	#$resul = pg_query($db, "select relname from pg_stat_user_tables order by relname") or die('Query failed: ' . pg_last_error());
	#$tableNames = pg_fetch_all_columns($resul);
	$tableNames = $db->getListOf('tables');
	if (DB::isError($tableNames)) die("getTableNames(): " . $tableNames->getMessage());
}

function getKeyNames() {
	global $db,$set,$fields;
	$keyNames = array();
	#$resul = pg_query($db, "SELECT * FROM " . dq($set['table']) . " LIMIT 0") or die('Query failed: ' . pg_last_error());
	#$int_colums = pg_num_fields($resul);
	#for ( $col = 0; $col < $int_colums; $col++ ) {
	#	array_push($keyNames , pg_field_name( $resul, $col));
	#}
	$q = $db->query("SELECT * FROM " . dq($set['table']) . " LIMIT 0");
	if (DB::isError($q)) die("getKeyNames(): " . $q->getMessage());
	$info = $db->tableInfo($q);
	foreach ($info as $v) array_push($keyNames, $v['name']);
	if ($fields[0] == '*')
		$fields = $keyNames;
	else
		$fields = array_intersect($fields, $keyNames);
}

function selectedOptions($select, $values){
	foreach($values as $v) {
		echo "<option ";
		if ($v == $select) echo "selected=\"selected\"";
		echo ">$v</option>\n";
        }
}

function dq($string) {
	global $dbType;
	if ($dbType == "mysql")
		return(str_replace(" ", "_", $string));
	return("\"" . str_replace("\"", "\"\"", $string) . "\"");
}

function sq($string) {
	return("'%" . str_replace("'", "''", $string) . "%'");
}

/* Create the SQL query */
function createSQL($limit=True){
	global $set, $fields;
	$timeField = "datehour";

	$page = $set['page'];
	$per_row = $set['rows_page'];
	$sql = "SELECT ";
	$i = 0;
	# Find select fields
	$sel_fields = "";
	# Make the select fields
	foreach ($fields as $v) {
		$sel_fields .= $v;
		if ($i != (count($fields) - 1))
			$sel_fields .= ", ";
		$i++;
	}
	$sql .= $sel_fields;
	$sql .= " FROM " . dq($set['table']);
	$sql .= " WHERE ";
	$sql .= dq($timeField);
        $sql .= " BETWEEN " . sq($set['start']) . " AND " . sq($set['end']);
	# Check for sql cond fields
        if (isset($set['sqlcond'])){
		foreach($set['sqlcond'] as $i => $value){
			if ($value != null AND $value != ""){
				$sql .= " AND (" . dq($i) . " LIKE " . sq($value) . ") ";
			}
		}
	}
	// SET LIMIT
	if ($limit){
		$start = ($page-1)*$per_row;
		$sql .= " LIMIT $per_row OFFSET $start";
	}
	return $sql;
}	

function print_table() {
	global $counters,$set,$db,$fields;

	$page = $set['page'];
	$per_row = $set['rows_page'];

	// DO THE SEARCH
	$sql = createSQL();
	#pg_send_query($db, $sql);
	#$resul = pg_get_result($db);
	#$total = pg_num_rows($resul);
	$q = $db->query($sql);
	if (DB::isError($q)) die("printTable(): " . $q->getMessage());
	$total = $q->numRows();
	if ($total != 0)
		$total_rows = intval($total / $per_row) +1;
	else
		$total_rows = 1;

	// PRINT NUM ROWS BAR	
	print '<br><table align="center" width="100%" cellpadding=1 cellspacing=0 border=0 bgcolor="#00438C"><tr><td>';
	print html_nav_bar_pmacct('00438C', $page, $per_row, $total);
	print "<tr bgcolor='#6d88ad' >";

	// PRINT TABLE FIELDS
	foreach ($fields as $value) print "<td style='padding: 4px; margin: 4px;'><font color='#FFFFFF'><b>" . $value . "</b></font></td>";

	// PRINT ROWS
	$bg = "#E7E9F2";
	#while ($row=pg_fetch_row($resul)) {
	while ($q->fetchInto($row)) {
		if ($bg == '#E7E9F2')
			$bg = '#F5F5F5';
		else
			$bg = '#E7E9F2';
		print "<tr bgcolor='$bg' >";
		foreach($row as $v)
			echo '<td class="padding1">'.$v.'</td>';
		print "</tr>";
	}
	if ($total == 0)
		print "<tr bgcolor='$bg'><td style='padding: 4px; margin: 4px;' colspan=100%><center>There are no Logs to display!</center></td></tr>";

	print html_nav_bar_pmacct('00438C', $page, $per_row, $total);
}

function html_nav_bar_pmacct($background_color, $current_page, $rows_per_page, $total_rows) {
?>
<tr bgcolor='#<?php print $background_color;?>' class='noprint'>
<td colspan='<?php print "100%";?>'>
<table width='100%' cellspacing='0' cellpadding='3' border='0'>
<tr>
<td align='left' class='textHeaderDark'>
<strong>&lt;&lt; <?php if ($current_page > 1) { print "<a class='linkOverDark' href='javascript:void(1);' onClick='previousPage();'>"; } print "Previous"; if ($current_page > 1) { print "</a>"; } ?></strong>
</td>
<td align='center' class='textHeaderDark'>
	Showing Rows <?php print (($rows_per_page*($current_page-1))+1);?> to <?php print ($rows_per_page*$current_page);?>
	<?php 
		global $db,$set;
		if ($set['total']){
			$sql = createSQL(False);
			$q = $db->query($sql);
			$total = $q->numRows();
			print ' from total of '. $total;
		}
	?>

</td>
<td align='right' class='textHeaderDark'>
<strong><?php if ($rows_per_page <= $total_rows) { print '<a class="linkOverDark" href="javascript:void(1);" onClick="nextPage();">'; } print "Next"; if ($rows_per_page <= $total_rows) { print "</a>"; } ?> &gt;&gt;</strong>
</td>
</tr>
</table>
</td>
</tr>
<?php
}

?>
