<?php
// resident/resident_logout.php
session_start();
session_unset();
session_destroy();
header('Location: resident.php');
exit;