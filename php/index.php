<?php

require __DIR__ . '/vendor/autoload.php';

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

$app = AppFactory::create();
$app->addBodyParsingMiddleware();

/**
 * Connessione al database
 */
function getConnection() {
    return new mysqli('my_mariadb', 'root', 'ciccio', 'scuola');
}

/**
 * GET /alunni
 */
$app->get('/alunni', function (Request $request, Response $response) {

    $conn = getConnection();
    $result = $conn->query("SELECT * FROM alunni");

    $alunni = [];

    while ($row = $result->fetch_assoc()) {
        $alunni[] = $row;
    }

    $response->getBody()->write(json_encode($alunni));
    return $response->withHeader('Content-Type', 'application/json');
});

/**
 * GET /alunni/{id}
 */
$app->get('/alunni/{id}', function (Request $request, Response $response, $args) {

    $conn = getConnection();
    $id = (int)$args['id'];

    $stmt = $conn->prepare("SELECT * FROM alunni WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    $result = $stmt->get_result();
    $alunno = $result->fetch_assoc();

    $response->getBody()->write(json_encode($alunno));
    return $response->withHeader('Content-Type', 'application/json');
});

/**
 * POST /alunni
 */
$app->post('/alunni', function (Request $request, Response $response) {

    $conn = getConnection();
    $data = $request->getParsedBody();

    $stmt = $conn->prepare("INSERT INTO alunni (nome, cognome) VALUES (?, ?)");
    $stmt->bind_param("ss", $data['nome'], $data['cognome']);
    $stmt->execute();

    $response->getBody()->write(json_encode([
        "message" => "Alunno creato",
        "id" => $stmt->insert_id
    ]));

    return $response->withHeader('Content-Type', 'application/json');
});

/**
 * PUT /alunni/{id}
 */
$app->put('/alunni/{id}', function (Request $request, Response $response, $args) {

    $conn = getConnection();
    $id = (int)$args['id'];
    $data = $request->getParsedBody();

    $stmt = $conn->prepare("UPDATE alunni SET nome = ?, cognome = ? WHERE id = ?");
    $stmt->bind_param("ssi", $data['nome'], $data['cognome'], $id);
    $stmt->execute();

    $response->getBody()->write(json_encode([
        "message" => "Alunno aggiornato"
    ]));

    return $response->withHeader('Content-Type', 'application/json');
});

/**
 * DELETE /alunni/{id}
 */
$app->delete('/alunni/{id}', function (Request $request, Response $response, $args) {

    $conn = getConnection();
    $id = (int)$args['id'];

    $stmt = $conn->prepare("DELETE FROM alunni WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    $response->getBody()->write(json_encode([
        "message" => "Alunno eliminato"
    ]));

    return $response->withHeader('Content-Type', 'application/json');
});

/**
 * GET certificazioni di un alunno
 */
$app->get('/alunni/{id}/certificazioni', function (Request $request, Response $response, $args) {

    $conn = getConnection();
    $id = (int)$args['id'];

    $stmt = $conn->prepare("
        SELECT *
        FROM certificazioni
        WHERE alunno_id = ?
    ");

    $stmt->bind_param("i", $id);
    $stmt->execute();

    $result = $stmt->get_result();
    $certificazioni = [];

    while ($row = $result->fetch_assoc()) {
        $certificazioni[] = $row;
    }

    $response->getBody()->write(json_encode($certificazioni));
    return $response->withHeader('Content-Type', 'application/json');
});

/**
 * GET singola certificazione
 */
$app->get('/alunni/{id}/certificazioni/{id_cert}', function (Request $request, Response $response, $args) {

    $conn = getConnection();

    $id = (int)$args['id'];
    $id_cert = (int)$args['id_cert'];

    $stmt = $conn->prepare("
        SELECT *
        FROM certificazioni
        WHERE alunno_id = ? AND id = ?
    ");

    $stmt->bind_param("ii", $id, $id_cert);
    $stmt->execute();

    $result = $stmt->get_result();
    $certificazione = $result->fetch_assoc();

    $response->getBody()->write(json_encode($certificazione));
    return $response->withHeader('Content-Type', 'application/json');
});

/**
 * POST certificazione
 */
$app->post('/alunni/{id}/certificazioni', function (Request $request, Response $response, $args) {

    $conn = getConnection();

    $id = (int)$args['id'];
    $data = $request->getParsedBody();

    $stmt = $conn->prepare("
        INSERT INTO certificazioni (titolo, votazione, ente, alunno_id)
        VALUES (?, ?, ?, ?)
    ");

    $stmt->bind_param("sisi", $data['titolo'], $data['votazione'], $data['ente'], $id);
    $stmt->execute();

    $response->getBody()->write(json_encode([
        "message" => "Certificazione aggiunta",
        "id" => $stmt->insert_id
    ]));

    return $response->withHeader('Content-Type', 'application/json');
});

$app->run();