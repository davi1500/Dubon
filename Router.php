<?php

class Router
{
    protected $routes = [
        'GET' => [],
        'POST' => []
    ];

    public static function load($file)
    {
        $router = new static;
        require $file;
        return $router;
    }

    public function get($uri, $controller)
    {
        $this->routes['GET'][$uri] = $controller;
    }

    public function post($uri, $controller)
    {
        $this->routes['POST'][$uri] = $controller;
    }

    public function direct($uri, $method)
    {
        if (array_key_exists($uri, $this->routes[$method])) {
            // A sintaxe 'Controller@metodo' é dividida em duas partes
            return $this->callAction(
                ...explode('@', $this->routes[$method][$uri])
            );
        }

        http_response_code(404);
        return view('404'); // Carrega uma view de página não encontrada
    }

    protected function callAction($controller, $action)
    {
        // 1. Verifica se o arquivo do Controller existe
        $controllerFile = "src/Controllers/{$controller}.php";
        if (!file_exists($controllerFile)) {
            throw new Exception("Arquivo do Controller não encontrado: {$controllerFile}");
        }
        require_once $controllerFile;

        // 2. Verifica se a classe existe dentro do arquivo
        if (!class_exists($controller)) {
            throw new Exception("Classe do Controller não definida: {$controller}");
        }

        // 3. Cria uma nova instância do controller
        $controllerInstance = new $controller;

        // 4. Verifica se o método (ação) existe na classe
        if (! method_exists($controllerInstance, $action)) {
            throw new Exception("Ação {$action} não encontrada no controller {$controller}.");
        }

        // 5. Se tudo estiver certo, chama a ação
        return $controllerInstance->$action();
    }
}