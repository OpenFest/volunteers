<?php
// remove session
session_unset();
session_destroy();
header('Location: /');
exit;