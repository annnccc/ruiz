<?php
require_once '../../includes/config.php';

// Este archivo simplemente redirige a edit.php sin ID para crear un nuevo servicio
header("Location: " . BASE_URL . "/modules/servicios/edit.php");
exit;
?> 