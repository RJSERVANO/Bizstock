<?php
declare(strict_types=1);

// Creates only a workspace-private local instance, never a Windows service or XAMPP data reset.
$root = dirname(__DIR__);
$directory = $root . '/.tools/mariadb';
$installer = 'C:/xampp/mysql/bin/mysql_install_db.exe';
if (!is_file($installer)) {
    throw new RuntimeException('XAMPP MariaDB installer not found.');
}
if (is_dir($directory . '/data')) {
    throw new RuntimeException('Instance already initialized; start it without reinitializing.');
}
if (!is_dir($directory) && !mkdir($directory, 0700, true)) {
    throw new RuntimeException('Cannot create private instance directory.');
}
$password = bin2hex(random_bytes(24));
$process = proc_open([$installer, '--datadir=' . $directory . '/data', '--port=13307', '--password=' . $password, '--silent'],
    [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $root);
if (!is_resource($process)) {
    throw new RuntimeException('Cannot initialize instance.');
}
fclose($pipes[0]);
$output = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
fclose($pipes[1]); fclose($pipes[2]);
$status = proc_close($process);
file_put_contents($directory . '/init.log', str_replace($password, '[redacted]', $output));
if ($status !== 0) {
    throw new RuntimeException('Instance initialization failed; see private init.log.');
}
file_put_contents($directory . '/credentials.json', json_encode(['root_password' => $password], JSON_THROW_ON_ERROR));
$base = str_replace('\\', '/', $directory);
file_put_contents($directory . '/server.ini', "[mysqld]\nbasedir=C:/xampp/mysql\ndatadir={$base}/data\nport=13307\nbind-address=127.0.0.1\nskip-name-resolve\ncharacter-set-server=utf8mb4\ncollation-server=utf8mb4_unicode_ci\nlog-error={$base}/server.log\npid-file={$base}/server.pid\ninnodb-buffer-pool-size=32M\ngeneral-log=0\ninnodb-use-native-aio=0\n");
file_put_contents($directory . '/admin.ini', "[client]\nhost=127.0.0.1\nport=13307\nuser=root\npassword={$password}\n");
echo "Local MariaDB initialized in ignored .tools/mariadb; no XAMPP data or Windows service changed.\n";
