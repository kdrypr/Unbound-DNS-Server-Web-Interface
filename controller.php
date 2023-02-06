<?php
include_once("core/functions.php");

//DNS Service Restart Controller
if (isset($_POST['restart_dns_service'])) {
    if ($_POST['restart_dns_service'] == 'yes') {
        restartDNSService();
        http_response_code(200);
    } else {
        http_response_code(400);
    }
}

//New DNS Record Add Controller
if (isset($_POST['fqdn'], $_POST['ipAddr'], $_POST['recordType'], $_POST['newRecord'])) {
    if ($_POST['newRecord'] == 'yes') {
        $fqdn = $_POST['fqdn'];
        $ipAddr = $_POST['ipAddr'];
        $recordType = $_POST['recordType'];
        addNewDNSRecord($fqdn, $ipAddr, $recordType);
        http_response_code(200);
    } else {
        http_response_code(400);
    }
}

//Existing DNS Record Edit Controller
if (isset($_POST['editFQDN'], $_POST['editIPAddr'], $_POST['editRecordType'], $_POST['oldFQDN'], $_POST['oldIPAddr'], $_POST['oldRecordType'], $_POST['editRecord'])) {
    if ($_POST['editRecord'] == 'yes') {
        $fqdn = $_POST['editFQDN'];
        $ipAddr = $_POST['editIPAddr'];
        $recordType = $_POST['editRecordType'];
        $oldFQDN = $_POST['oldFQDN'];
        $oldIPAddr = $_POST['oldIPAddr'];
        $oldRecordType = $_POST['oldRecordType'];
        editDNSRecord($fqdn, $ipAddr, $recordType, $oldFQDN, $oldIPAddr, $oldRecordType);
        http_response_code(200);
    } else {
        http_response_code(400);
    }
}

//Delete DNS Record Controller
if (isset($_POST['delFQDN'], $_POST['delIPAddr'], $_POST['delRecordType'], $_POST['deleteRecord'])) {
    if ($_POST['deleteRecord'] == 'yes') {
        $delFQDN = $_POST['delFQDN'];
        $delIPAddr = $_POST['delIPAddr'];
        $delRecordType = $_POST['delRecordType'];
        deleteRecord($delFQDN, $delIPAddr, $delRecordType);
        http_response_code(200);
    } else {
        http_response_code(400);
    }
}

//Change Password
if (isset($_POST['oldPassword'], $_POST['newPassword'], $_POST['changePassword'])) {
    if ($_POST['changePassword'] == 'yes') {
        echo changePassword($_POST['oldPassword'], $_POST['newPassword']);
        http_response_code(200);
    } else {
        http_response_code(400);
    }
}


?>