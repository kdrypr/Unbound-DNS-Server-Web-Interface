<?php
require_once('config/db.php');
$mysqli = mysqli_connect($dbhost, $dbuser, $dbpass, $dbname);

if ($mysqli->connect_errno) {
    echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
}
if (!$mysqli->set_charset("utf8")) {
    printf("Error loading character set utf8: %s\n", $con->error);
}


function login($username)
{
    global $mysqli;
    $stmt = $mysqli->prepare("SELECT * FROM users WHERE username=?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
    $row = $result->fetch_array(MYSQLI_NUM);
    return $row;
}

function getDNSList()
{
    echo '<tbody>';

    $handle = fopen("/etc/unbound/host_entries.conf", "r");
    if ($handle) {
        $i = 0;
        while (($line = fgets($handle)) !== false) {
            $arr = preg_split('/["| ]/', $line);
            if ($arr[0] !== "local-data-ptr:") {
                $i++;
                $arr = preg_split('/["| ]/', $line);

                echo '<tr>';
                echo '<th class="col-md-2" scope="row">' . $i . '</th>';
                echo '<td class="col-md-4">' . $arr[2] . '</td>';
                if ($arr[4] == 10) {
                    echo '<td class="col-md-2">' . $arr[5] . '</td>';
                } else {
                    echo '<td class="col-md-2">' . $arr[4] . '</td>';
                }
                echo '<td class="col-md-4">' . $arr[3] . '</td>';
                echo '

                <td>
                <div class="dropdown">
                <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown"><i class="bx bx-dots-vertical-rounded"></i></button>
                <div class="dropdown-menu">
                <a class="dropdown-item" href="javascript:fillEditDNSRecord(' . $i . ');"><i class="bx bx-edit-alt me-1"></i> Edit</a>
                <a class="dropdown-item" href="javascript:deleteRecord(' . $i . ');"><i class="bx bx-trash me-1"></i> Delete</a>
                </div>
                </div>
                </td>
                ';
                echo '</tr>';
            }
        }

        fclose($handle);
    }
    echo '</tbody>';
}

function getHostEntriesHash()
{
    return hash_file('sha256', '/etc/unbound/host_entries.conf');
}

function readHashFromFile()
{
    $line = '';
    $file = 'filehash.txt';
    if ($f = fopen($file, 'r')) {
        $line = fgets($f); // read until first newline
        fclose($f);
    }
    return $line;
}

function checkHostEntriesHash()
{
    $hashFromFile = readHashFromFile();
    $hostEntriesHash = getHostEntriesHash();
    if (strcmp($hostEntriesHash, $hashFromFile) !== 0) {
        echo "";
    } else {
        echo "disabled";
    }
}

function restartDNSService()
{
    //shell_exec('sudo service unbound reload');
    $lastHash = getHostEntriesHash();
    $oldHash = file_get_contents('filehash.txt');
    str_replace('filehash.txt', '', $oldHash);
    file_put_contents('filehash.txt', $lastHash);

}

function addNewDNSRecord($fqdn, $ipAddr, $recordType)
{
    if ($recordType == "MX") {
        $newRecordString = 'local-data: "' . $fqdn . '. ' . $recordType . ' 10 ' . $ipAddr . '"';
    } else {
        $newRecordString = 'local-data: "' . $fqdn . '. ' . $recordType . ' ' . $ipAddr . '"';
    }

    file_put_contents('/etc/unbound/host_entries.conf', $newRecordString . PHP_EOL, FILE_APPEND | LOCK_EX);
}

function editDNSRecord($fqdn, $ipAddr, $recordType, $oldFQDN, $oldIPAddr, $oldRecordType)
{
    $file = '/etc/unbound/host_entries.conf';
    if ($oldRecordType == "MX") {
        $oldRecord = '' . $oldFQDN . ' ' . $oldRecordType . ' 10 ' . $oldIPAddr . '';
    } else {
        $oldRecord = '' . $oldFQDN . ' ' . $oldRecordType . ' ' . $oldIPAddr . '';
    }
    if ($recordType == "MX") {
        $newRecordString = '' . $fqdn . ' ' . $recordType . ' 10 ' . $ipAddr . '';
    } else {
        $newRecordString = '' . $fqdn . ' ' . $recordType . ' ' . $ipAddr . '';
    }

    shell_exec('sudo sed -i \'s/' . $oldRecord . '/' . $newRecordString . '/g\' ' . $file . '');
}

function deleteRecord($delFQDN, $delIPAddr, $delRecordType)
{
    $file = '/etc/unbound/host_entries.conf';

    if ($delRecordType == "MX") {
        $delRecordString = '' . $delFQDN . ' ' . $delRecordType . ' 10 ' . $delIPAddr . '';
    } else {
        $delRecordString = '' . $delFQDN . ' ' . $delRecordType . ' ' . $delIPAddr . '';
    }
    shell_exec('sudo sed -i \'/' . $delRecordString . '/d\' ' . $file . '');
}

function test()
{
    $file = '/etc/unbound/host_entries.conf';
    $oldFQDN = 'merhaba.dns.com.';
    $oldRecordType = 'A';
    $oldIPAddr = '173.123.44.33';

    $fqdn = 'merhaba.test.com.';
    $recordType = 'A';
    $ipAddr = '10.10.20.21';

    $oldRecord = '' . $oldFQDN . ' ' . $oldRecordType . ' ' . $oldIPAddr . '';
    $newRecordString = '' . $fqdn . ' ' . $recordType . ' ' . $ipAddr . '';

    echo 'sudo sed -i \'s/' . $oldRecord . '/' . $newRecordString . '/g\' ' . $file . '';
}

test();
?>