<?php
session_start();
session_destroy();

header('Location: ../src/views/auth/login.php');
exit;
