<?php
/**
 * Controlador Base
 *
 * Este archivo contiene la clase base Controller que proporciona
 * la funcionalidad común de todos los controladores del sistema.
 * Implementa el patrón MVC gestionando la comunicación entre
 * los modelos y las vistas.
 *
 * @package App\Core
 * @author Sistema de Gestión Clínica
 * @version 1.0
 */

namespace App\Core;

/**
 * Clase base para todos los controladores de la aplicación
 *
 * Esta clase abstracta proporciona métodos comunes para renderizar vistas,
 * enviar datos a las vistas, gestionar redirecciones y mensajes de alerta.
 * Todos los controladores específicos deben extender esta clase.
 */
abstract class Controller
{
    /**
     * @var array Datos para pasar a las vistas
     */
    protected $viewData = [];
    
    /**
     * @var string Layout por defecto para las vistas
     */
    protected $defaultLayout = 'default';
    
    /**
     * @var string Título por defecto para las páginas
     */
    protected $pageTitle = '';
    
    /**
     * Agrega datos para pasar a la vista
     *
     * @param string $key Nombre de la variable en la vista
     * @param mixed $value Valor a asignar
     * @return void
     */
    protected function setViewData(string $key, $value): void
    {
        $this->viewData[$key] = $value;
    }
    
    /**
     * Agrega múltiples datos para pasar a la vista
     *
     * @param array $data Array asociativo de datos [nombre => valor]
     * @return void
     */
    protected function setViewDataBulk(array $data): void
    {
        foreach ($data as $key => $value) {
            $this->viewData[$key] = $value;
        }
    }
    
    /**
     * Establece el título de la página
     *
     * @param string $title Título de la página
     * @return void
     */
    protected function setPageTitle(string $title): void
    {
        $this->pageTitle = $title;
        $this->setViewData('titulo_pagina', $title);
    }
    
    /**
     * Renderiza una vista con el layout especificado
     *
     * Este método carga la vista especificada dentro del layout indicado,
     * pasando los datos configurados previamente.
     *
     * @param string $view Ruta relativa a la vista (sin extensión .php)
     * @param string|null $layout Layout a utilizar (null para no usar layout)
     * @return void
     */
    protected function render(string $view, ?string $layout = null): void
    {
        // Extraer variables para la vista
        extract($this->viewData);
        
        // Establecer layout a utilizar
        $layout = $layout ?? $this->defaultLayout;
        
        // Añadir datos comunes para todas las vistas si no existen
        if (!isset($titulo_pagina) && !empty($this->pageTitle)) {
            $titulo_pagina = $this->pageTitle;
        }
        
        // Capturar contenido de la vista
        ob_start();
        $viewPath = ROOT_PATH . "/app/views/pages/{$view}.php";
        
        if (!file_exists($viewPath)) {
            // Si la vista no existe, mostrar error
            ob_end_clean();
            throw new \Exception("Vista no encontrada: {$viewPath}");
        }
        
        include $viewPath;
        $content = ob_get_clean();
        
        if ($layout === null) {
            // Sin layout, mostrar solo el contenido de la vista
            echo $content;
            return;
        }
        
        // Renderizar con layout
        $layoutPath = ROOT_PATH . "/app/views/layouts/{$layout}.php";
        
        if (!file_exists($layoutPath)) {
            // Si el layout no existe, mostrar error
            throw new \Exception("Layout no encontrado: {$layoutPath}");
        }
        
        include $layoutPath;
    }
    
    /**
     * Redirige a la URL especificada
     *
     * @param string $url URL a la que redirigir
     * @param int $statusCode Código de estado HTTP (por defecto 302)
     * @return void
     */
    protected function redirect(string $url, int $statusCode = 302): void
    {
        header("Location: {$url}", true, $statusCode);
        exit;
    }
    
    /**
     * Establece un mensaje de alerta en la sesión
     *
     * Guarda un mensaje que se mostrará en la próxima carga de página.
     * Típicamente usado en combinación con redirecciones.
     *
     * @param string $type Tipo de alerta ('success', 'danger', 'warning', 'info')
     * @param string $message Mensaje a mostrar
     * @return void
     */
    protected function setAlert(string $type, string $message): void
    {
        $_SESSION['alert_type'] = $type;
        $_SESSION['alert_message'] = $message;
    }
    
    /**
     * Verifica si hay errores de validación y los prepara para la vista
     *
     * @param array $errors Array asociativo de errores [campo => mensaje]
     * @return bool True si hay errores, False si no
     */
    protected function hasValidationErrors(array $errors): bool
    {
        if (empty($errors)) {
            return false;
        }
        
        $this->setViewData('validation_errors', $errors);
        
        // Extraer y preparar mensajes para mostrar como alerta
        $errorMessages = [];
        foreach ($errors as $field => $message) {
            $errorMessages[] = $message;
        }
        
        $this->setAlert('danger', implode('<br>', $errorMessages));
        
        return true;
    }
    
    /**
     * Recupera parámetros GET de forma segura
     *
     * @param string $param Nombre del parámetro a recuperar
     * @param mixed $default Valor por defecto si no existe el parámetro
     * @return mixed Valor del parámetro o valor por defecto
     */
    protected function getParam(string $param, $default = null)
    {
        return $_GET[$param] ?? $default;
    }
    
    /**
     * Recupera parámetros POST de forma segura
     *
     * @param string $param Nombre del parámetro a recuperar
     * @param mixed $default Valor por defecto si no existe el parámetro
     * @return mixed Valor del parámetro o valor por defecto
     */
    protected function postParam(string $param, $default = null)
    {
        return $_POST[$param] ?? $default;
    }
    
    /**
     * Verifica si la solicitud actual es POST
     *
     * @return bool True si es POST, False si no
     */
    protected function isPost(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }
    
    /**
     * Verifica si el usuario está autenticado
     *
     * @return bool True si está autenticado, False si no
     */
    protected function isAuthenticated(): bool
    {
        return isset($_SESSION['usuario_id']) && !empty($_SESSION['usuario_id']);
    }
    
    /**
     * Requiere autenticación para acceder al controlador
     * Redirige al login si no está autenticado
     *
     * @return void
     */
    protected function requireAuthentication(): void
    {
        if (!$this->isAuthenticated()) {
            $this->setAlert('warning', 'Debe iniciar sesión para acceder a esta sección.');
            $this->redirect(BASE_URL . '/login.php');
        }
    }
    
    /**
     * Devuelve respuesta en formato JSON
     *
     * @param mixed $data Datos a convertir a JSON
     * @param int $statusCode Código de estado HTTP
     * @return void
     */
    protected function jsonResponse($data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
} 