<?php
include_once "includes/config.php";
session_unset();
session_destroy();
setcookie(session_name(), "", time()-3600, "/");
redirect_location();
exit;
