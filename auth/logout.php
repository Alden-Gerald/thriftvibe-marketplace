<?php


require_once __DIR__ . '/../config/session.php';

destroySession();
header('Location: /');
exit;
