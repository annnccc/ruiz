<?php
/**
 * Configuración del módulo de exportación
 * Define constantes y parámetros para los diferentes formatos de exportación
 */

// Formatos de exportación disponibles
define('EXPORT_FORMAT_PDF', 'pdf');
define('EXPORT_FORMAT_EXCEL', 'excel');

// Tipos de reportes
define('REPORT_PATIENTS', 'patients');
define('REPORT_APPOINTMENTS', 'appointments');
define('REPORT_CUSTOM', 'custom');

// Configuración de documentos
$export_config = [
    // Configuración PDF
    'pdf' => [
        'author' => 'Clínica Ruiz',
        'creator' => 'Sistema de Gestión',
        'title' => 'Reporte generado',
        'subject' => 'Datos exportados',
        'keywords' => 'clínica, pacientes, citas',
        'headerLogo' => BASE_URL . 'assets/img/logo.png',
        'headerTitle' => 'Clínica Ruiz',
        'footerText' => 'Documento generado el ' . date('d/m/Y H:i'),
        'orientation' => 'P', // P=Portrait, L=Landscape
        'unit' => 'mm',
        'format' => 'A4',
        'fontSize' => 10,
        'margin' => [
            'top' => 15,
            'right' => 10,
            'bottom' => 15,
            'left' => 10
        ]
    ],
    
    // Configuración Excel
    'excel' => [
        'creator' => 'Clínica Ruiz',
        'title' => 'Datos exportados',
        'subject' => 'Reporte',
        'description' => 'Documento generado automáticamente',
        'keywords' => 'clínica, datos, exportación',
        'category' => 'Reportes'
    ],
    
    // Reportes predefinidos
    'reports' => [
        'patients_list' => [
            'name' => 'Listado de pacientes',
            'description' => 'Lista completa de pacientes con datos básicos',
            'type' => REPORT_PATIENTS,
            'fields' => ['id', 'nombre', 'apellidos', 'email', 'telefono', 'fecha_nacimiento', 'genero'],
            'filters' => ['genero', 'ciudad']
        ],
        'appointments_list' => [
            'name' => 'Listado de citas',
            'description' => 'Lista de citas con información del paciente',
            'type' => REPORT_APPOINTMENTS,
            'fields' => ['id', 'fecha', 'hora_inicio', 'hora_fin', 'paciente_id', 'motivo', 'estado'],
            'filters' => ['fecha_desde', 'fecha_hasta', 'estado']
        ],
        'appointments_day' => [
            'name' => 'Citas del día',
            'description' => 'Lista de citas programadas para el día actual',
            'type' => REPORT_APPOINTMENTS,
            'fields' => ['id', 'hora_inicio', 'hora_fin', 'paciente_id', 'motivo', 'estado', 'notas'],
            'filters' => ['fecha', 'estado']
        ],
        'patients_appointments' => [
            'name' => 'Historial de citas por paciente',
            'description' => 'Historial completo de citas de un paciente específico',
            'type' => REPORT_CUSTOM,
            'fields' => ['id', 'fecha', 'hora_inicio', 'hora_fin', 'motivo', 'estado', 'notas'],
            'filters' => ['paciente_id']
        ]
    ]
]; 