<?php
//Se a rota não existir, retorne um erro 404;

$routes = [];

function route($method, $url, $callback)
{
  global $routes;

  if ($_SERVER['REQUEST_METHOD'] == $method && $url == parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) {
    $routes[$url] = $callback;
  }
}

function resolve()
{
  global $routes;

  $request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

  if (array_key_exists($request_uri, $routes)) {
    return $routes[$request_uri]();
  } else {
    http_response_code(404);
    echo '404 - Not Found';
  }
}

route('GET', '/api', function () {
  echo 'API';
});

route('GET', '/home', function () {
  echo 'Home';
});

route('POST', '/user/create', function () use ($conn) {

  // Captura o corpo da requisição
  $input = file_get_contents('php://input');

  // Decodifica o JSON para um array associativo
  $data = json_decode($input, true);

  // Verifica se todos os parâmetros estão presentes
  if (!isset($data['name']) || !isset($data['email']) || !isset($data['password'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Parâmetros obrigatórios ausentes'], JSON_UNESCAPED_UNICODE);
    return;
  }

  // Atribui os valores aos parâmetros
  $name = $data['name'];
  $email = $data['email'];
  $password = $data['password'];

  // Query de inserção
  $query = "INSERT INTO users (name, email, password) VALUES (?,?,?)";

  // Prepara a consulta
  $stmt = $conn->prepare($query);

  if ($stmt === false) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Erro ao preparar a consulta: ' . $conn->error], JSON_UNESCAPED_UNICODE);
    return;
  }

  // Vincula os parâmetros
  $stmt->bind_param("sss", $name, $email, $password);

  // Executa a consulta
  if ($stmt->execute()) {
    http_response_code(200);
    echo json_encode(['status' => 'success', 'message' => 'Usuário criado com sucesso!'], JSON_UNESCAPED_UNICODE);
  } else {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Erro ao criar usuário!'], JSON_UNESCAPED_UNICODE);
  }

  // Fecha a consulta
  $stmt->close();
});

