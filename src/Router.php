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
        // Converte a URI com wildcards como {id} para um padrão regex
        $uri = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<\1>[^/]+)', $uri);
        $uri = '#^' . $uri . '$#';
        $this->routes['GET'][$uri] = $controller;
    }

    public function post($uri, $controller)
    {
        // Converte a URI com wildcards como {id} para um padrão regex
        $uri = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<\1>[^/]+)', $uri);
        $uri = '#^' . $uri . '$#';
        $this->routes['POST'][$uri] = $controller;
    }

    public function direct($uri, $method)
    {
        // Ajuste para subpasta: Remove o BASE_URL da URI se estiver presente
        $requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        if (defined('BASE_URL') && strpos($requestPath, BASE_URL) === 0) {
            $requestPath = substr($requestPath, strlen(BASE_URL));
        }
        $uri = trim($requestPath, '/');

        // Itera sobre todas as rotas registradas para o método atual
        foreach ($this->routes[$method] as $route => $controller) {
            // Verifica se a URI atual corresponde ao padrão da rota
            if (preg_match($route, $uri, $matches)) {
                // Extrai os parâmetros nomeados (ex: id) da URL
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                
                [$controllerClass, $actionMethod] = explode('@', $controller);

                return $this->callAction($controllerClass, $actionMethod, $params);
            }
        }

        http_response_code(404);
        return view('404'); // Carrega uma view de página não encontrada
    }

    protected function callAction($controller, $action, $params = [])
    {
        // 1. Verifica se o arquivo do Controller existe
        $controllerFile = __DIR__ . "/Controllers/{$controller}.php";
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

        // 5. Se tudo estiver certo, chama a ação, passando os parâmetros da URL
        return $controllerInstance->$action(...array_values($params));
    }
}