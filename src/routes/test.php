<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

use Slim\Http\UploadedFile;
$app = new \Slim\App;
// Get container
$container = $app->getContainer();
$container['upload_directory'] = __DIR__ . '/files';
// Register component on container
$container['view'] = function ($container) {
    return new \Slim\Views\PhpRenderer('public/');
};

$app->options('/{routes:.+}', function ($request, $response, $args) {
    return $response;
});

$app->add(function ($req, $res, $next) {
    $response = $next($req, $res);
    return $response
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
});
// Reder form upload
$app->get('/', function ($request, $response, $args) {
    return $this->view->render($response, 'uploads.phtml');
})->setName('uploads');
// Get All test
$app->get('/api/test', function(Request $request, Response $response){
    $sql = "SELECT * FROM test";

    try{
        // Get DB Object
        $db = new db();
        // Connect
        $db = $db->connect();

        $stmt = $db->query($sql);
        $customers = $stmt->fetchAll(PDO::FETCH_OBJ);
        $db = null;
        echo json_encode($customers);
    } catch(PDOException $e){
        echo '{"error": {"text": '.$e->getMessage().'}';
    }
});

// Get Single test
$app->get('/api/test/{id}', function(Request $request, Response $response){
    $id = $request->getAttribute('id');

    $sql = "SELECT * FROM test WHERE id = $id";

    try{
        // Get DB Object
        $db = new db();
        // Connect
        $db = $db->connect();

        $stmt = $db->query($sql);
        $customer = $stmt->fetch(PDO::FETCH_OBJ);
        $db = null;
        echo json_encode($customer);
    } catch(PDOException $e){
        echo '{"error": {"text": '.$e->getMessage().'}';
    }
});

// Add test
$app->post('/api/test/add', function(Request $request, Response $response){
    $nombre = $request->getParam('first_name');
    $apellido = $request->getParam('last_name');
    $edad = $request->getParam('age');

   //INSERT INTO `test` (`id`, `nombre`, `apellido`, `edad`) VALUES (NULL, 'nombre', 'apellido ', '1')

    $sql = "INSERT INTO test (id,nombre,apellido,edad) VALUES
    (null,:nombre,:apellido,:edad)";

    try{
        // Get DB Object
        $db = new db();
        // Connect
        $db = $db->connect();

        $stmt = $db->prepare($sql);

        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':apellido',  $apellido);
        $stmt->bindParam(':edad',      $edad);

        $stmt->execute();

        echo '{"notice": {"text": "Test Added"}';

    } catch(PDOException $e){
        echo '{"error": {"text": '.$e->getMessage().'}';
    }
});

// Update test
$app->put('/api/test/update/{id}', function(Request $request, Response $response){
    $id = $request->getAttribute('id');
    $nombre = $request->getParam('first_name');
    $apellido = $request->getParam('last_name');
    $edad = $request->getParam('age');
    //UPDATE `test` SET `nombre` = 'rosa', `apellido` = 'ester', `edad` = '22' WHERE `test`.`id` = 2
    $sql = "UPDATE test SET
				nombre 	    = :nombre,
				apellido 	= :apellido,
                edad		= :edad
               
			WHERE id = $id";
    
    try{
        // Get DB Object
        $db = new db();
        // Connect
        $db = $db->connect();

        $stmt = $db->prepare($sql);

        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':apellido',  $apellido);
        $stmt->bindParam(':edad',      $edad);
       

        $stmt->execute();

        echo '{"notice": {"text": "Test Updated"}';

    } catch(PDOException $e){
        echo '{"error": {"text": '.$e->getMessage().'}';
    }
});

// Delete test
$app->delete('/api/test/delete/{id}', function(Request $request, Response $response){
    $id = $request->getAttribute('id');

    $sql = "DELETE FROM test WHERE id = $id";

    try{
        // Get DB Object
        $db = new db();
        // Connect
        $db = $db->connect();

        $stmt = $db->prepare($sql);
        $stmt->execute();
        $db = null;
        echo '{"notice": {"text": "Test Deleted"}';
    } catch(PDOException $e){
        echo '{"error": {"text": '.$e->getMessage().'}';
    }
});

//upload files
$app->post('/upload', function(Request $request, Response $response) {
    $directory = $this->get('upload_directory');

    $uploadedFiles = $request->getUploadedFiles();

    // handle single input with single file upload
    $uploadedFile = $uploadedFiles['newfile'];
    if ($uploadedFile->getError() === UPLOAD_ERR_OK) {
        $filename = moveUploadedFile($directory, $uploadedFile);
        $response->write('uploaded ' . $filename . '<br/>');
    }
   
});

/**
 * Moves the uploaded file to the upload directory and assigns it a unique name
 * to avoid overwriting an existing uploaded file.
 *
 * @param string $directory directory to which the file is moved
 * @param UploadedFile $uploaded file uploaded file to move
 * @return string filename of moved file
 */
function moveUploadedFile($directory, UploadedFile $uploadedFile)
{
    $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
    $basename = bin2hex(random_bytes(8)); // see http://php.net/manual/en/function.random-bytes.php
    $filename = sprintf('%s.%0.8s', $basename, $extension);

    $uploadedFile->moveTo($directory . DIRECTORY_SEPARATOR . $filename);

    return $filename;
}
