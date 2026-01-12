<?php
/**
 * emails.php
 *
 * - Reads the FIRST email from emails.txt
 * - Removes it immediately (destructive pop)
 * - Returns the email as plain text
 * - Uses file locking to avoid race conditions
 */

$EMAIL_FILE = __DIR__ . '/emails.txt';

header('Content-Type: text/plain');

// ---- Basic existence check ----
if (!file_exists($EMAIL_FILE)) {
    http_response_code(500);
    echo "ERROR: emails.txt not found";
    exit;
}

// ---- Open file with read/write ----
$fp = fopen($EMAIL_FILE, 'c+');
if (!$fp) {
    http_response_code(500);
    echo "ERROR: Unable to open emails.txt";
    exit;
}

// ---- Acquire exclusive lock ----
if (!flock($fp, LOCK_EX)) {
    fclose($fp);
    http_response_code(500);
    echo "ERROR: Unable to lock email file";
    exit;
}

// ---- Read all emails ----
$contents = stream_get_contents($fp);
$lines = array_values(array_filter(
    array_map('trim', explode("\n", $contents))
));

// ---- No emails left ----
if (count($lines) === 0) {
    flock($fp, LOCK_UN);
    fclose($fp);
    http_response_code(204); // No Content
    echo "";
    exit;
}

// ---- Pop first email ----
$email = array_shift($lines);

// ---- Rewrite file WITHOUT the popped email ----
ftruncate($fp, 0);
rewind($fp);
fwrite($fp, implode("\n", $lines) . (count($lines) ? "\n" : ""));

// ---- Release lock ----
flock($fp, LOCK_UN);
fclose($fp);

// ---- Return the email ----
echo $email;
exit;
