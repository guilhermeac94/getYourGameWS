<?php

require_once '../include/DbHandler.php';
require_once '../include/PassHash.php';
require '.././libs/Slim/Slim.php';

\Slim\Slim::registerAutoloader();

$app = new \Slim\Slim();

// User id from db - Global Variable
$user_id = NULL;
//
/**
 * Adding Middle Layer to authenticate every request
 * Checking if the request has valid api key in the 'Authorization' header
 */
function authenticate(\Slim\Route $route) {
    // Getting request headers
    $headers = apache_request_headers();
    $response = array();
    $app = \Slim\Slim::getInstance();

    // Verifying Authorization Header
    if (isset($headers['Authorization'])) {
        $db = new DbHandler();

        // get the api key
        $api_key = $headers['Authorization'];
        // validating api key
        if (!$db->isValidApiKey($api_key)) {
            // api key is not present in users table
            $response["error"] = true;
            $response["message"] = "Access Denied. Invalid Api key";
            echoRespnse(401, $response);
            $app->stop();
        } else {
            global $user_id;
            // get user primary key id
            $user_id = $db->getUserId($api_key);
        }
    } else {
        // api key is missing in header
        $response["error"] = true;
        $response["message"] = "Api key is misssing";
        echoRespnse(400, $response);
        $app->stop();
    }
}

/**
 * ----------- METHODS WITHOUT AUTHENTICATION ---------------------------------
 */
/**
 * User Registration
 * url - /register
 * method - POST
 * params - name, email, password
 */
 
$app->get('/teste', function() use ($app) {
	$response = array();
	$response["id"] = 1;
	$response["content"] = 'Web Service consumido com sucesso!!!';
	echoRespnse(201, $response);
});
 
$app->post('/cadastro', function() use ($app) {
            // check for required params
            verifyRequiredParams(array('nome', 'email', 'senha'));

            $response = array();

            // reading post params
            $name = $app->request->post('nome');
            $email = $app->request->post('email');
            $password = $app->request->post('senha');

            // validating email address
            validateEmail($email);

            $db = new DbHandler();
            $res = $db->createUser($name, $email, $password);

            if ($res == USER_CREATED_SUCCESSFULLY) {
				
				$user = $db->getUserByEmail($email);
                $response['id_usuario'] = $user['id_usuario'];
				$response['chave_api'] = $user['chave_api'];
                $response["error"] = false;
                $response["message"] = "Usuário registrado com sucesso!";
				$response["nome"] = $name;
            } else if ($res == USER_CREATE_FAILED) {
                $response["error"] = true;
                $response["message"] = "Um erro ocorreu durante o cadastro!";
            } else if ($res == USER_ALREADY_EXISTED) {
                $response["error"] = true;
                $response["message"] = "Usuário já existente";
            }
            // echo json response
            echoRespnse(201, $response);
        });

$app->post('/usuario_jogo', function() use ($app) {
            // check for required params
            verifyRequiredParams(array());

            $response = array();			
			/*
            // reading post params
			$id_jogo		= ($app->request->post('id_jogo')		? $app->request->post('id_jogo') 		: 'null');
			$id_usuario		= ($app->request->post('id_usuario')	? $app->request->post('id_usuario') 	: 'null');
			$id_interesse	= ($app->request->post('id_interesse')	? $app->request->post('id_interesse') 	: 'null');
			$id_nivel		= ($app->request->post('id_nivel')		? $app->request->post('id_nivel') 		: 'null');
			$distancia		= ($app->request->post('distancia')		? $app->request->post('distancia') 		: 'null');
			$id_plataforma	= ($app->request->post('id_plataforma')	? $app->request->post('id_plataforma') 	: 'null');
			$preco			= ($app->request->post('preco')			? $app->request->post('preco') 			: 'null');
			$id_jogo_troca	= ($app->request->post('id_jogo_troca')	? $app->request->post('id_jogo_troca') 	: 'null');
			$preco_inicial	= ($app->request->post('preco_inicial')	? $app->request->post('preco_inicial') 	: 'null');
			$preco_final	= ($app->request->post('preco_final')	? $app->request->post('preco_final') 	: 'null');
			*/
			
			$obj = array('id_jogo'			=> $app->request->post('id_jogo'),
						 'id_usuario'		=> $app->request->post('id_usuario'),
						 'id_interesse'		=> $app->request->post('id_interesse'),
						 'id_nivel'			=> $app->request->post('id_nivel'),
						 'distancia'		=> $app->request->post('distancia'),
						 'id_plataforma'	=> $app->request->post('id_plataforma'),
						 'preco'			=> $app->request->post('preco'),
						 'id_jogo_troca'	=> $app->request->post('id_jogo_troca'),
						 'preco_inicial'	=> $app->request->post('preco_inicial'),
						 'preco_final'		=> $app->request->post('preco_final'));
            
            $db = new DbHandler();
            //$res = $db->insertUsuarioJogo($id_jogo, $id_usuario, $id_interesse, $id_nivel, $distancia, $id_plataforma, $preco, $id_jogo_troca, $preco_inicial, $preco_final);
			$res = $db->insert('usuario_jogo', $obj);

            if ($res) {
                $response["error"] = false;
                $response["message"] = "Interesse salvo com sucesso!";
            } else {
                $response["error"] = true;
                $response["message"] = "Erro ao salvar interesse!";
            }
            // echo json response
            echoRespnse(201, $response);
        });
		
$app->put('/usuario/:id', function($id_usuario) use($app) {
            global $user_id;
			
			$obj = array();
            
			if($app->request->put('nome')!==null) 			 $obj['nome'] 			 = $app->request->put('nome');
			if($app->request->put('gps')!==null) 			 $obj['gps'] 			 = $app->request->put('gps');
			if($app->request->put('id_metodo_envio')!==null) $obj['id_metodo_envio'] = $app->request->put('id_metodo_envio');
			if($app->request->put('id_estado_jogo')!==null)  $obj['id_estado_jogo']  = $app->request->put('id_estado_jogo');
			if($app->request->put('distancia')!==null) 		 $obj['distancia']		 = $app->request->put('distancia');
			if($app->request->put('foto')!==null) 		     $obj['foto']		     = $app->request->put('foto');
				
            $db = new DbHandler();
            $response = array();
			
            $result = $db->update('usuario', $id_usuario, $obj);
            if ($result) {
                $response["error"] = false;
                $response["message"] = "Usuário atualizado com sucesso!";
            } else {
				$response["error"] = true;
                $response["message"] = "Erro ao atualizar o usuário!";
            }
            echoRespnse(200, $response);
        });
/**
 * User Login
 * url - /login
 * method - POST
 * params - email, password
 */
$app->post('/login', function() use ($app) {
			global $user_id;
			
            // check for required params
            verifyRequiredParams(array('email', 'senha'));

            // reading post params
            $email = $app->request()->post('email');
            $password = $app->request()->post('senha');
            $response = array();

            $db = new DbHandler();
            // check for correct email and password
            if ($db->checkLogin($email, $password)) {
                // get the user by email
                $user = $db->getUserByEmail($email);

                if ($user != NULL) {
					/*
                    $response['name'] = $user['name'];
                    $response['email'] = $user['email'];
                    */
					$response["error"] = false;
					$response['id_usuario'] = $user['id_usuario'];
					$response['chave_api'] = $user['chave_api'];
					
					$user_id = $user['id_usuario'];
                } else {
                    // unknown error occurred
                    $response['error'] = true;
                    $response['message'] = "An error occurred. Please try again";
                }
            } else {
                // user credentials are wrong
                $response['error'] = true;
                $response['message'] = 'Falha no Login! Credenciais inválidas.';
            }

            echoRespnse(200, $response);
        });


$app->get('/usuario_email/:email' , function($email) {
			//global $user_id;
			$response = array();
			$db = new DbHandler();
			
			// fetch task
			$result = $db->getUserByEmail($email);

			if ($result != NULL) {
				$response["error"] = false;
				$response['id_usuario'] = $result['id_usuario'];
				$response['nome'] = $result['nome'];
				$response['email'] = $result['email'];
				$response['chave_api'] = $result['chave_api'];
				echoRespnse(200, $response);
			} else {
				$response["error"] = true;
				echoRespnse(200, $response);
			}
		});
		
$app->get('/usuario/:id', 'authenticate', function($id_usuario) {
            //global $user_id;
            $response = array();
            $db = new DbHandler();

            // fetch task
            $result = $db->getUserById($id_usuario);

            if ($result != NULL) {
				/*
                $response["error"] = false;
                $response["id"] = $result["id"];
                $response["task"] = $result["task"];
                $response["status"] = $result["status"];
                $response["createdAt"] = $result["created_at"];
				*/
				$response["error"] = false;
				$response['nome'] = $result['nome'];
				$response['email'] = $result['email'];
				$response['chave_api'] = $result['chave_api'];
                echoRespnse(200, $response);
            } else {
                $response["error"] = true;
                $response["message"] = "O usuário informado não existe!";
                echoRespnse(200, $response);
            }
        });
		
		
//$app->get('/usuario/:id', 'authenticate', function() {
$app->get('/estado_jogo', function() use ($app) {
            //global $user_id;
            $response = array();
			$db = new DbHandler();

            // fetch task
            $response = $db->getTodosEstadoJogo();
			
			echoRespnse(200, $response);
		});

$app->post('/usuarios', function() use ($app) {	
            //global $user_id;
			
			$filtro = $app->request()->post('filtro');
						
			$response = array();
			$db = new DbHandler();
			
            // fetch task
            $response = $db->getTodosUsuarios($filtro);
			
			echoRespnse(200, $response);
		});

$app->post('/jogos', function() use ($app) {	
			//global $user_id;
			
			$filtro = $app->request()->post('filtro');
						
			$response = array();
			$db = new DbHandler();
			
			// fetch task
			$response = $db->getTodosJogos($filtro);
			
			echoRespnse(200, $response);
});		
		
		
$app->get('/cadastros', function() use ($app) {
            //global $user_id;
            $response = array();
			$db = new DbHandler();

            // fetch task
            $response = $db->getTodosCadastros();
			
			echoRespnse(200, $response);
		});		

		
		
/*
 * ------------------------ METHODS WITH AUTHENTICATION ------------------------
 */

/**
 * Listing all tasks of particual user
 * method GET
 * url /tasks          
 */
$app->get('/tasks', 'authenticate', function() {
            global $user_id;
            $response = array();
            $db = new DbHandler();

            // fetching all user tasks
            $result = $db->getAllUserTasks($user_id);

            $response["error"] = false;
            $response["tasks"] = array();

            // looping through result and preparing tasks array
            while ($task = $result->fetch_assoc()) {
                $tmp = array();
                $tmp["id"] = $task["id"];
                $tmp["task"] = $task["task"];
                $tmp["status"] = $task["status"];
                $tmp["createdAt"] = $task["created_at"];
                array_push($response["tasks"], $tmp);
            }

            echoRespnse(200, $response);
        });

/**
 * Listing single task of particual user
 * method GET
 * url /tasks/:id
 * Will return 404 if the task doesn't belongs to user
 */
$app->get('/tasks/:id', 'authenticate', function($task_id) {
            global $user_id;
            $response = array();
            $db = new DbHandler();

            // fetch task
            $result = $db->getTask($task_id, $user_id);

            if ($result != NULL) {
                $response["error"] = false;
                $response["id"] = $result["id"];
                $response["task"] = $result["task"];
                $response["status"] = $result["status"];
                $response["createdAt"] = $result["created_at"];
                echoRespnse(200, $response);
            } else {
                $response["error"] = true;
                $response["message"] = "The requested resource doesn't exists";
                echoRespnse(404, $response);
            }
        });

/**
 * Creating new task in db
 * method POST
 * params - name
 * url - /tasks/
 */
$app->post('/tasks', 'authenticate', function() use ($app) {
            // check for required params
            verifyRequiredParams(array('task'));

            $response = array();
            $task = $app->request->post('task');

            global $user_id;
            $db = new DbHandler();

            // creating new task
            $task_id = $db->createTask($user_id, $task);

            if ($task_id != NULL) {
                $response["error"] = false;
                $response["message"] = "Task created successfully";
                $response["task_id"] = $task_id;
                echoRespnse(201, $response);
            } else {
                $response["error"] = true;
                $response["message"] = "Failed to create task. Please try again";
                echoRespnse(200, $response);
            }            
        });

/**
 * Updating existing task
 * method PUT
 * params task, status
 * url - /tasks/:id
 */
$app->put('/tasks/:id', 'authenticate', function($task_id) use($app) {
            // check for required params
            verifyRequiredParams(array('task', 'status'));

            global $user_id;            
            $task = $app->request->put('task');
            $status = $app->request->put('status');

            $db = new DbHandler();
            $response = array();

            // updating task
            $result = $db->updateTask($user_id, $task_id, $task, $status);
            if ($result) {
                // task updated successfully
                $response["error"] = false;
                $response["message"] = "Task updated successfully";
            } else {
                // task failed to update
                $response["error"] = true;
                $response["message"] = "Task failed to update. Please try again!";
            }
            echoRespnse(200, $response);
        });

/**
 * Deleting task. Users can delete only their tasks
 * method DELETE
 * url /tasks
 */
$app->delete('/tasks/:id', 'authenticate', function($task_id) use($app) {
            global $user_id;

            $db = new DbHandler();
            $response = array();
            $result = $db->deleteTask($user_id, $task_id);
            if ($result) {
                // task deleted successfully
                $response["error"] = false;
                $response["message"] = "Task deleted succesfully";
            } else {
                // task failed to delete
                $response["error"] = true;
                $response["message"] = "Task failed to delete. Please try again!";
            }
            echoRespnse(200, $response);
        });

/**
 * Verifying required params posted or not
 */
function verifyRequiredParams($required_fields) {
    $error = false;
    $error_fields = "";
    $request_params = array();
    $request_params = $_REQUEST;
    // Handling PUT request params
    if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
        $app = \Slim\Slim::getInstance();
        parse_str($app->request()->getBody(), $request_params);
    }
    foreach ($required_fields as $field) {
        if (!isset($request_params[$field]) || strlen(trim($request_params[$field])) <= 0) {
            $error = true;
            $error_fields .= $field . ', ';
        }
    }

    if ($error) {
        // Required field(s) are missing or empty
        // echo error json and stop the app
        $response = array();
        $app = \Slim\Slim::getInstance();
        $response["error"] = true;
        $response["message"] = 'Campos necessários: (' . substr($error_fields, 0, -2) . ') não informado(s).';
        echoRespnse(200, $response);
        $app->stop();
    }
}

/**
 * Validating email address
 */
function validateEmail($email) {
    $app = \Slim\Slim::getInstance();
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response["error"] = true;
        $response["message"] = 'Endereço de e-mail inválido!';
        echoRespnse(200, $response);
        $app->stop();
    }
}

/**
 * Echoing json response to client
 * @param String $status_code Http response code
 * @param Int $response Json response
 */
function echoRespnse($status_code, $response) {
    $app = \Slim\Slim::getInstance();
    // Http response code
    $app->status($status_code);

    // setting response content type to json
    $app->contentType('application/json');

    echo json_encode($response);
}

$app->run();
?>