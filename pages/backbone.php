<?php
//check if user is loogged in and has admin rights

if (!isset($_SESSION['user']) || !$_SESSION['user']->isAdmin()) {
	header('Location: /login');
	exit;
}

