<?
function find_text($text, $delimit_start = '`', $delimit_end = '`') {
	$start = strpos($text, $delimit_start);
	if ($start === false) return false;

	$end = strpos( substr($text, $start + 1), $delimit_end);
	if ($end === false) return false;

	return substr( $text, $start + 1, $end);
}


class Scaffold {

	public $table = array();
	public $download = false;

	function Scaffold($table) {
		$this->table = $table;
	}

	function listtable() {
		$column_array = array();
		$return_string = "<?\n";

		if ($this->table['include'] != '')
			$return_string .= "include('{$this->table['include']}');\n";

		$return_string .= "\nprint_header('{$this->table['name']}');\n\necho '<table>\n";
		$return_string .= "  <tr>\n";
		foreach($this->table AS $key => $value) {
			if (is_array($value)) {
				$column = $key;
				$column_array[] = array( 'tipo' => $value, 'nombre' => $key );
				$return_string .= "    <th>". $this->title($column) ."</th>\n";
			}
		}
		$return_string .= "  </tr>';

\$r = mysql_query(\"SELECT * FROM `{$this->table['name']}`\") or trigger_error(mysql_error());
while(\$row = mysql_fetch_array(\$r)) {\n";
		$return_string .= "	echo '  <tr>\n";
		
		foreach($column_array as $value) {
			if($value[tipo][blob])
				$val = 'nl2br($row['.$value[nombre].'])';
			elseif($value[tipo][datetime])
				$val = 'humanize($row['.$value[nombre].'])';
			else
				$val = '$row['.$value[nombre].']';

			$return_string .= "    <td>' . $val . '</td>\n";
		}
		$return_string .= "    <td><a href=\"{$this->table['edit_page']}?{$this->table['id_key']}=' . \$row['{$this->table[id_key]}'] . '\">Edit</a></td>
    <td><a href=\"{$this->table['delete_page']}?{$this->table['id_key']}=' . \$row['{$this->table[id_key]}'] . '\" onclick=\"return confirm(\'Are you sure?\')\">Delete</a></td>
  </tr>';\n";
		$return_string .= "}\n\n";
		$return_string .= "echo '</table>

<p><a href=\"{$this->table['new_page']}\">New entry</a></p>';

print_footer();
?>";

		return $return_string;
	}

	function newrow() {
		$return_string = "<?\n";
		if ($this->table['include'] != '')
			$return_string .= "include('{$this->table['include']}');\n";

		$column_array = array();
		$text = "<ul>\n";
		foreach($this->table as $key => $value) {
			if (is_array($value)) {
				$column = $key;
				if($column != $this->table['id_key'] ) {
					$column_array[] = array( 'tipo' => $value, 'nombre' => $key );
					if($value['blob']) {
						$text .= $this->html_chars('  <li>' . $this->title($column) . ': <textarea name="'.$column.'" cols="40" rows="10"></textarea></li>' . "\n");
					} elseif($value['datetime']) {
						$text .= $this->html_chars('  <li>' . $this->title($column) . ": <?=input_datetime('".strtolower($this->title($column))."', NULL)?>\n");
					} else {
						$text .= '  <li>' . $this->title($column) . ': <input type="text" name="'.$column.'" /></li>' . "\n";
					}
				}
			}
		}
		$text .= '</ul>';

		$return_string .= "
print_header('{$this->table['name']}');\n
if (isset(\$_POST['submitted'])) {
	foreach(\$_POST AS \$key => \$value) { \$_POST[\$key] = mysql_real_escape_string(\$value); }\n";
		$insert = "INSERT INTO `{$this->table['name']}` (";
		$counter = 0;
		foreach($column_array as $value) {
			$insert .= "`$value[nombre]`" ;
			if ($counter < count($column_array) - 1)
				$insert .= ", ";
			$counter++;
		}
		$insert .= ') VALUES (';

		$counter = 0;
		foreach($column_array as $value) {
			$val = parse($value[nombre], $value[tipo]);
			$insert .= "'$val'" ;
			if ($counter < count($column_array) - 1 )
				$insert .= ", ";
			$counter++;
		}
		$insert .= ")";

		$return_string .= "	\$sql = \"$insert\";
	mysql_query(\$sql) or die(mysql_error());
	echo '<p>Added row.</p>';
}
?>\n\n";
		$return_string .= '<form action="" method="post">' . "
$text
" . '<p><input type="hidden" value="1" name="submitted" />
<input type="submit" value="Create" /></p>
</form>
<?
print_footer();
?>';

		return $return_string;
	}


	function editrow() {
		$return_string = "<?\n";
		if ($this->table['include'] != '')
			$return_string .= "include('{$this->table['include']}');\n\n";

		$return_string .= "print_header('{$this->table['name']}');

if (isset(\$_GET['{$this->table['id_key']}']) ) {
	\${$this->table['id_key']} = \$_GET['{$this->table['id_key']}'];\n\n";

		$column_array = array();
		$text = "<ul>\n";
		foreach($this->table as $key => $value) {
			if (is_array($value)) {
				$column = $key;
				if($column != $this->table['id_key'] ) {
					$column_array[] = array( 'tipo' => $value, 'nombre' => $key );
					if($value['blob']) {
						$text .= $this->html_chars('  <li>' . $this->title($column) . ": <textarea name=\"$column\" cols=\"40\" rows=\"10\"><?= stripslashes(\$row[$column]) ?></textarea></li>\n");
					} elseif($value['datetime']) {
						$text .= $this->html_chars('  <li>' . $this->title($column) . ": <?=input_datetime('".strtolower($this->title($column))."', \$row[$column])?>\n");
					} else {
						$text .= '  <li>' . $this->title($column) . ': <input type="text" name="'.$column.'" value="<?= stripslashes($row['.$column.']) ?>" /></li>' . "\n";
					}
				}
			}
		}
		$text .= '</ul>';

		$return_string .= "if (isset(\$_POST['submitted'])) {
	foreach(\$_POST AS \$key => \$value) { \$_POST[\$key] = mysql_real_escape_string(\$value); }\n";
		$insert = "UPDATE `{$this->table['name']}` SET ";
		$counter = 0;
		foreach($column_array as $value) {
			$field = $value[nombre];
			$val = parse($field, $value[tipo]);
			$insert .= " `$field` =  '$val'" ;
			if ($counter < count($column_array) - 1 )
				$insert .= ", ";
			$counter++;
		}
		$insert .= "  WHERE `{$this->table['id_key']}` = '\${$this->table['id_key']}' ";

		$return_string .= "	\$sql = \"$insert\";
	mysql_query(\$sql) or die(mysql_error());
	echo (mysql_affected_rows()) ? \"Edited row.<br />\" : \"Nothing changed. <br />\";
}

\$row = mysql_fetch_array ( mysql_query(\"SELECT * FROM `{$this->table['name']}` WHERE `{$this->table['id_key']}` = '\${$this->table['id_key']}' \"));
?>\n\n";

$return_string .= '<form action="" method="post">
'.$text.'
<p><input type="hidden" value="1" name="submitted" />
  <input type="submit" value="Edit" /></p>
</form>

<?
}
print_footer();
?>';

		return $return_string;
	}

 

	function deleterow() {
		$return_string = "<?\n";
		if ($this->table['include'] != '')
			$return_string .= "include('{$this->table['include']}');\n";

		$return_string .= "
print_header('{$this->table['name']}');

mysql_query(\"DELETE FROM `{$this->table['name']}` WHERE `{$this->table['id_key']}` = '\$_GET[{$this->table['id_key']}]}'\");
echo (mysql_affected_rows()) ? \"<p>Row deleted.</p>\" : \"<p>Nothing deleted.</p>\";

print_footer();
?>";
		return $return_string;
	}

	function get_functions() {
		$return_string = '<?
/* General configuration */
// MySQL
$host = \'localhost\';
$user = \'mysql_user\';
$pass = \'mysql_password\';
$dbname = \'database\';

// Basic HTTP Authentication
$login = array(\'admin\' => \'pass\');


/* phpscaffold code - you may leave this untouched */
function doAuth() {
	header(\'WWW-Authenticate: Basic realm="Protected Area"\');
	header(\'HTTP/1.0 401 Unauthorized\');
	echo \'Valid username / password required.\';
	exit;
}

function checkUser() {
	global $login;
	$b = false;
	if($_SERVER[\'PHP_AUTH_USER\']!=\'\' && $_SERVER[\'PHP_AUTH_PW\']!=\'\') {
		if($login[$_SERVER[\'PHP_AUTH_USER\']] == $_SERVER[\'PHP_AUTH_PW\'])
			$b = true;
	}
	return $b;
}

if (!isset($_SERVER[\'PHP_AUTH_USER\']) or !checkUser())
	doAuth();


// DB connect
$link = mysql_connect($host, $user, $pass);
if (!$link)
	die(\'Not connected : \' . mysql_error());
if (!mysql_select_db($dbname))
	die ("Can\'t use $dbname: " . mysql_error());

function print_header($title) {
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
 "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="es">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title><?=$title?> - Admin</title>
<style type="text/css" media="screen">
body {
  font: .9em "Trebuchet MS", Trebuchet, Verdana, Sans-Serif;
}
</style>
</head>

<body>
<h1><?=$title?> - Admin</h1>
<?
}

function print_footer() {
	if (ereg(\''.$this->table['list_page'].'\', $_SERVER[\'PHP_SELF\']))
		echo \'<p><a href="'.$this->table['list_page'].'">Back to Listing</a></p>
</body>
</html>\';
}'."

function input_datetime(\$field, \$value) {
	\$seg  = \$field . '_seg';
	\$min  = \$field . '_min';
	\$hour = \$field . '_hour';
	\$day  = \$field . '_day';
	\$mth  = \$field . '_mth';
	\$year = \$field . '_year';

	\$sel_seg  = (substr(\$value,17,2) ? substr(\$value,17,2) : date(s));
	\$sel_min  = (substr(\$value,14,2) ? substr(\$value,14,2) : date(i));
	\$sel_hour = (substr(\$value,11,2) ? substr(\$value,11,2) : date(h));
	\$sel_day  = (substr(\$value,8,2) ? substr(\$value,8,2) : date(d));
	\$sel_mth  = (substr(\$value,5,2) ? substr(\$value,5,2) : date(m));
	\$sel_year = (substr(\$value,0,4) ? substr(\$value,0,4) : date(Y));

	\$ret = select_range(\$hour, \$sel_hour, 0, 23, 1) . ':';
	\$ret .= select_range(\$min, \$sel_min, 0, 59, 5) . ':';
	\$ret .= select_range(\$seg, \$sel_seg, 0, 59, 1) . ' @ ';
	\$ret .= select_range(\$day, \$sel_day, 1, 31, 1) . '/';
	\$ret .= select_range(\$mth, \$sel_mth, 1, 12, 1) . '/';
	\$ret .= select_range(\$year, \$sel_year, 2009, 2020, 1);

	return \$ret;
}

function humanize(\$mysql_datetime) {
	return date('d/m/Y @ h:i:s', strtotime(\$mysql_datetime));
}

function select_range(\$name, \$selected, \$start, \$finish, \$range) {
	\$ret = '<select name=\"'.\$name.'\">';
	for(\$i=\$start; \$i <= \$finish; \$i += \$range) {
		(\$selected == \$i) ? \$sel = ' selected=\"selected\"' : \$sel = '';
		\$ret .= \"<option\$sel>\$i</option>\\n\";
	}
	\$ret .= '</select>';
	return \$ret;
}
?>";
		return $return_string;
	}

	function title($name) {
		return ucwords(str_replace("_", " ", trim($name)));
	}

	function html_chars($var) {
		return ($this->download) ? $var : htmlspecialchars($var);
	}
}

function parse($field, $type) {
	if($type[datetime]) {
		$seg  = $field . '_seg';
		$min  = $field . '_min';
		$hour = $field . '_hour';
		$day  = $field . '_day';
		$mth  = $field . '_mth';
		$year = $field . '_year';
		$val = "\$_POST[$year]-\$_POST[$mth]-\$_POST[$day] \$_POST[$hour]:\$_POST[$min]:\$_POST[$seg]";
	} else
		$val = "\$_POST[$field]";
	return $val;
}
?>