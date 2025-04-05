<?php
// Archivo de redirección
require_once '../../includes/config.php';

// Redirigir a la página correcta
header("Location: " . BASE_URL . "/modules/configuracion/list.php#usuarios");
exit;
?> 