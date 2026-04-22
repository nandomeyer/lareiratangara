<?php
require_once '../config.php';
session_name(ADMIN_SESSION_NAME);
session_start();
session_destroy();
header('Location: login.php?logout=1');
exit;
