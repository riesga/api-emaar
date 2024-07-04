<?php



echo "PHP Version: " . phpversion(). "<br/>";
echo "Client Version: " . oci_client_version() . "<br/>";
$conn = oci_connect('sieweb', 'arauca', '190.85.204.244/cliesieweb');
if (!$conn) {
    $e = oci_error();
    trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
} else echo "Server Version: " . oci_server_version($conn);
