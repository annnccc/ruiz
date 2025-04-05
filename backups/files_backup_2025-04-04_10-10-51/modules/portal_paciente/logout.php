<?php
require_once '../../includes/config.php';

// Eliminar variables de sesión específicas del paciente
unset($_SESSION['paciente_id']);
unset($_SESSION['paciente_nombre']);
unset($_SESSION['paciente_apellidos']);
unset($_SESSION['paciente_email']);

// Redirigir a la página de login
header('Location: ' . BASE_URL . '/modules/portal_paciente/login.php');
exit; 