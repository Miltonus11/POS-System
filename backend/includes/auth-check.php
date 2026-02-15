<?php
  // Redirect to login if not authenticated
  if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
  }

  // Start session
  if (session_status() == PHP_SESSION_NONE) {
    session_start();
  }

  // logout the user if inactive for 30 min
  $timeout = 1800;

  if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout) {
    session_unset();
    session_destroy();

    header("Location: /login.php?timeout=1");
    exit;
  }

  $_SESSION['last_activity'] = time();

  // Require specific role
  function requireRole($role) {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== $role) {
      header("Location: /unauthorized.php");
      exit;
    }
  }
  
  // Check if the user has one of the allowed roles
  function requireAnyRole($roles = []) {
    if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $roles)) {
      header("Location: /unauthorized.php");
      exit;
    }
  }




?>