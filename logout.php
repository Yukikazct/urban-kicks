<?php
require 'init.php';
unset($_SESSION['username']);
header('Location: index.php');
exit;
