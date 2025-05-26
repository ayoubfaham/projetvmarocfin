<?php
$hash = '$2y$10$JLgWZKhir2HbZQ/ajGQ1l.qW9W0EmdolZoxmNDlH5X5K6ygSJYkHq';
var_dump(password_verify('123', $hash)); // doit afficher bool(true)
?> 