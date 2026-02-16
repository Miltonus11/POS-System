<?php
require_once 'functions.php';

/* Test formatCurrency */
echo formatCurrency(1500);
echo "<br>";

/* Test receipt generator */
echo generateReceiptNumber();
echo "<br>";

/* Test sanitize */
$dirty = "<script>alert('hack')</script>   John   ";
echo sanitizeInput($dirty);
echo "<br>";

/* Test date formatter */
echo formatDate("2026-02-15 14:30:00");
?>