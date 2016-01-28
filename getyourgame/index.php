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
	
	verifyRequiredParams(array());
	$response = array();
	
	$fotos = array();
	
	$i = 0;
	while(true){
		if($app->request->post('foto'.$i)!==null){
			$fotos[] = $app->request->post('foto'.$i);
		}else{
			break;
		}		
		$i++;
	}
	
	$obj = array('id_jogo'				=> $app->request->post('id_jogo'),
				'id_usuario'			=> $app->request->post('id_usuario'),
				'id_interesse'			=> $app->request->post('id_interesse'),
				'id_estado_jogo'		=> $app->request->post('id_estado_jogo'),
				'id_plataforma'			=> $app->request->post('id_plataforma'),
				'preco'					=> $app->request->post('preco'),
				'id_jogo_troca'			=> $app->request->post('id_jogo_troca'),
				'id_plataforma_troca'	=> $app->request->post('id_plataforma_troca'),
				'preco_inicial'			=> $app->request->post('preco_inicial'),
				'preco_final'			=> $app->request->post('preco_final'),
				'ativo'					=> 1);
							
   $db = new DbHandler();
   
   $result = $db->checkUserInterest($app->request->post('id_interesse'),
									$app->request->post('id_usuario'),
									$app->request->post('id_jogo'),
									$app->request->post('id_plataforma'));
   
   if(!$result){

		$id = $db->insertID('usuario_jogo', $obj);

		if($id!==null){
			foreach($fotos as $f){
				$res = $db->insert('foto', array('id_usuario_jogo' => $id, 'foto' => $f));
			}
			$response["error"] = false;
			$response["message"] = "Interesse salvo com sucesso!";
		} else {
			$response["error"] = true;
			$response["message"] = "Erro ao salvar interesse!";
		}
	}else{
		$response["error"] = true;
		$response["message"] = "Usuário já possui interesse conflitante!";
	}

	echoRespnse(201, $response);
});
		
$app->put('/usuario/:id', function($id_usuario) use($app) {
            global $user_id;
			
			$obj = array();
            
			if($app->request->put('nome')!==null) 			 $obj['nome'] 			 = $app->request->put('nome');
			if($app->request->put('id_metodo_envio')!==null) $obj['id_metodo_envio'] = $app->request->put('id_metodo_envio');
			if($app->request->put('id_estado_jogo')!==null)  $obj['id_estado_jogo']  = $app->request->put('id_estado_jogo');
			if($app->request->put('foto')!==null) 		     $obj['foto']		     = $app->request->put('foto');
				
            $db = new DbHandler();
            $response = array();
			
			
			$ids = array('id_usuario' => $id_usuario);
			
            $result = $db->update('usuario', $ids, $obj);
            if ($result) {
                $response["error"] = false;
                $response["message"] = "Usuário atualizado com sucesso!";
            } else {
				$response["error"] = true;
                $response["message"] = "Erro ao atualizar o usuário!";
            }
            echoRespnse(200, $response);
        });

		
$app->get('/endereco/:id', function($id_usuario) use($app) {
	
	$response = array();
	$db = new DbHandler();

	$response = array();
	$aux = array();
   
	$result = $db->getEndereco($id_usuario);
   
	if($result){
		$aux = $result;
		$response["id_usuario"]  = $result["id_usuario"];
		$response["logradouro"]  = $result["logradouro"];
		$response["cep"]         = $result["cep"];
		$response["bairro"]      = $result["bairro"];
		$response["cidade"]      = $result["cidade"];
		$response["uf"]          = $result["uf"];
		$response["numero"]      = $result["numero"];
		$response["complemento"] = $result["complemento"];
		$response["error"] 		 = false;
		$response["message"] 	 = "";
   }else{
		$response["error"] = true;
		$response["message"] = "Nenhum endereço encontrado!";
   }
			   
	echoRespnse(200, $response);
});


$app->get('/contato_transacao/:id', function($id_usuario) use($app) {
	
	$response = array();
	$db = new DbHandler();
	
	$result = $db->getContatoTransacao($id_usuario);
	
	if($result){
		$response = $result;		
	}else{
		$response = null;
	}		
	
	echoRespnse(200, $response);
});


$app->get('/contato/:id', function($id_usuario) use($app) {
	//global $user_id;
	$response = array();
	$db = new DbHandler();
	
	$result = $db->getContatos($id_usuario);
	
	if($result){
		$response = $result;
	}else{
		$response['error'] = true;
		$response['message'] = 'Nenhum contato encontrado!';
	}		
	
	echoRespnse(200, $response);
});
		
		
$app->put('/contato/:id', function($id_usuario) use($app) {
            global $user_id;
			
            $endereco = false;
			$telefone = false;
			
			if( $app->request->put('logradouro') !==null ||
				$app->request->put('cep')		 !==null ||
				$app->request->put('bairro')	 !==null ||
				$app->request->put('cidade')	 !==null ||
				$app->request->put('uf')		 !==null ||
				$app->request->put('numero')	 !==null ||
				$app->request->put('complemento')!==null){
				
				verifyRequiredParams(array('logradouro','cep','bairro','cidade','uf','numero'));
				$endereco = true;
			}
			
			if( $app->request->put('ddd')	   !==null ||
				$app->request->put('telefone') !==null){
				
				verifyRequiredParams(array('ddd','telefone'));
				$telefone = true;
			}
			
			$db = new DbHandler();
			$ids = array('id_usuario' => $id_usuario);
            
			if($endereco){
				$obj = array();
				if($app->request->put('logradouro')	!==null) $obj['logradouro']  = $app->request->put('logradouro');
				if($app->request->put('cep')		!==null) $obj['cep'] 		 = $app->request->put('cep');
				if($app->request->put('bairro')		!==null) $obj['bairro'] 	 = $app->request->put('bairro');
				if($app->request->put('cidade')		!==null) $obj['cidade']  	 = $app->request->put('cidade');
				if($app->request->put('uf')			!==null) $obj['uf']		  	 = $app->request->put('uf');
				if($app->request->put('numero')		!==null) $obj['numero']	  	 = $app->request->put('numero');
				if($app->request->put('complemento')!==null) $obj['complemento'] = $app->request->put('complemento');
				
				$result_endereco = $db->insert_update('endereco', $ids, false, $obj);
			}else{
				$result_endereco = true;
			}
			
			if($telefone){
				$obj = array();
				if($app->request->put('ddd')	 !==null) $obj['ddd']  	   = $app->request->put('ddd');
				if($app->request->put('telefone')!==null) $obj['telefone'] = $app->request->put('telefone');
				
				$result_telefone = $db->insert_update('telefone', $ids, false, $obj);
			}else{
				$result_telefone = true;
			}
			
            $response = array();
			
			if($endereco || $telefone){
				$result = $result_endereco && $result_telefone;
				if ($result) {
					$response["error"] = false;
					$response["message"] = "Contato salvo com sucesso!";
				} else {
					$response["error"] = true;
					$response["message"] = "Erro ao salvar o contato!";
				}
			}else{
				$response["error"] = false;
				$response["message"] = "Nenhum dado gravado!";
			}
            echoRespnse(200, $response);
        });

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
					$response['tem_transacao'] = $db->getTransacaoByUser($user['id_usuario']);
					
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
				
				$response['tem_transacao'] = $db->getTransacaoByUser($result['id_usuario']);
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
				$response['id_usuario'] = $result['id_usuario'];
				$response['nome'] = $result['nome'];
				$response['email'] = $result['email'];
				$response['chave_api'] = $result['chave_api'];
				$response['foto'] = $result['foto'];
                echoRespnse(200, $response);
            } else {
                $response["error"] = true;
                $response["message"] = "O usuário informado não existe!";
                echoRespnse(200, $response);
            }
        });
		
$app->get('/preferencias/:id', function($id_usuario) {
            //global $user_id;
            $response = array();
            $db = new DbHandler();

            // fetch task
            $response = $db->getPreferencias($id_usuario);
			echoRespnse(200, $response);
        });


$app->get('/avaliacoes_usuario/:id_usuario', function($id_usuario){
	$response = array();
	$db = new DbHandler();

	// fetch task
	$response = $db->getAvaliacoes($id_usuario);
	echoRespnse(200, $response);
});
		
$app->get('/avaliacao_transacao/:id_transacao/:id_usuario_avaliador', function($id_transacao, $id_usuario_avaliador) {
	//global $user_id;
	$db = new DbHandler();

	// fetch task
	$a = $db->getDadosAvaliacao($id_transacao, $id_usuario_avaliador);
	
	if($a){
		$response = array();
		$response["id_avaliacao_transacao"]	= $a["id_avaliacao_transacao"];
		$response["id_transacao"] 			= $a["id_transacao"];
		$response["id_usuario_avaliado"] 	= $a["id_usuario_avaliado"];
		$response["id_usuario_avaliador"] 	= $a["id_usuario_avaliador"];
		$response["avaliacao"] 				= $a["avaliacao"];
		$response["observacao"] 			= $a["observacao"];
	}else{
		$response = null;
	}
		
	echoRespnse(200, $response);	
});
		
		
$app->post('/avaliacao_transacao', function() use($app) {
	$response = array();
	$db = new DbHandler();
	$ids = null;
	
	$id_avaliacao_transacao = null;
	
	if($app->request->put('id_avaliacao_transacao')!==null){
		$ids = array('id_avaliacao_transacao' => $app->request->put('id_avaliacao_transacao'));
	}
	
	$id_transacao			= $app->request()->post('id_transacao');
	$id_usuario_avaliador	= $app->request()->post('id_usuario_avaliador');
	$id_usuario_avaliado	= $app->request()->post('id_usuario_avaliado');
	$avaliacao				= $app->request()->post('avaliacao');
	$observacao				= $app->request()->post('observacao');
	
	$obj = array('id_transacao'			=> $id_transacao,
				 'id_usuario_avaliador'	=> $id_usuario_avaliador,
				 'id_usuario_avaliado'	=> $id_usuario_avaliado,
				 'avaliacao'			=> $avaliacao,
				 'observacao'			=> $observacao);
		
	$result = $db->insert_update('avaliacao_transacao', $ids, true, $obj);
		
	if($result){
		$response["error"] = false;
		$response["message"] = "Avaliação salva com sucesso!";
	}else{
		$response["error"] = true;
		$response["message"] = "Ocorreu um erro ao salvar a avaliação!";
	}
	
	echoRespnse(200, $response);
});
		
$app->get('/dados_transacao/:id', function($id_transacao) {
	//global $user_id;
	$response = array();
	$db = new DbHandler();
	
	$response = $db->getDadosTransacao($id_transacao);
	echoRespnse(200, $response);
});


$app->put('/transacao/:id', function($id_transacao) use($app) {
	$obj = array();
	
	$tipo = null;
	
	if($app->request->put('tipo_atualizacao')!==null){
		$tipo = $app->request->put('tipo_atualizacao');
	}
	
	if($app->request->put('id_estado_transacao')!==null) 			$obj['id_estado_transacao']			= $app->request->put('id_estado_transacao');
	if($app->request->put('id_metodo_envio_solicitante')!==null)	$obj['id_metodo_envio_solicitante']	= $app->request->put('id_metodo_envio_solicitante');
	if($app->request->put('id_metodo_envio_ofertante')!==null)		$obj['id_metodo_envio_ofertante']	= $app->request->put('id_metodo_envio_ofertante');
	if($app->request->put('envio_solicitante')!==null)  			$obj['envio_solicitante']  			= $app->request->put('envio_solicitante');
	if($app->request->put('envio_ofertante')!==null) 		 		$obj['envio_ofertante']		 		= $app->request->put('envio_ofertante');
		
		
	$db = new DbHandler();
	$response = array();
	
	$result = $db->update('transacao', array('id_transacao' => $id_transacao), $obj);
	
	if($app->request->put('id_estado_transacao')!==null){
		if($obj['id_estado_transacao'] == 3){
			$db->deletaInteresses($id_transacao);
		}
	}
	
	if ($result) {
		
		$response["error"] = false;
		switch($tipo){
			case 'iniciar':
				$response["message"] = "Transação iniciada! Acompanhe seu andamento através da lista de Transações.";
				break;
			case 'enviar':
				$response["message"] = "Produto/pagamento enviado! A Transação será encerrada assim que ambos os usuários enviarem.";
				break;
			case 'concluir':
				$response["message"] = "Transação concluída!";
				break;
			case 'cancelar':
				$response["message"] = "Transação cancelada!";
				break;			
			default:
				$response["message"] = "Transação atualizada com sucesso!";
		}		
	} else {
		$response["error"] = true;
		$response["message"] = "Erro ao atualizar a transação!";
	}
	echoRespnse(200, $response);
});


$app->delete('/transacao/:id_transacao', function($id_transacao) {
	$response = array();
	$db = new DbHandler();
		
	$result = $db->delete('transacao', array('id_transacao' => $id_transacao), false);
	
	if ($result) {		
		$response["error"] = false;
		$response["message"] = "Transação removida.";
	}else{
		$response["error"] = true;
		$response["message"] = "Erro ao remover a Transação!";
	}
	
	echoRespnse(200, $response);
});


$app->get('/transacao/:id_usuario/:status', function($id_usuario, $status) {
	
	//global $user_id;
	$response = array();
	$db = new DbHandler();
		
	$response = $db->getTransacoes($id_usuario, $status);
	echoRespnse(200, $response);
});
		
$app->post('/transacao', function() use ($app) {
	//global $user_id;
	$response = array();
	$db = new DbHandler();
	
	$id_usuario_jogo_solic = $app->request()->post('id_usuario_jogo_solic');
	$id_usuario_jogo_ofert = $app->request()->post('id_usuario_jogo_ofert');
	$id_metodo_envio_solicitante = $app->request()->post('id_metodo_envio_solicitante');
	
	$obj = array('id_usuario_jogo_solicitante'	=> $id_usuario_jogo_solic,
				 'id_usuario_jogo_ofertante'	=> $id_usuario_jogo_ofert,
				 'id_estado_transacao'			=> '1',
				 'id_metodo_envio_solicitante'	=> $id_metodo_envio_solicitante,
				 'id_metodo_envio_ofertante'	=> '',
				 'envio_solicitante'			=> '0',
				 'envio_ofertante'				=> '0');
	
	$response = $db->insert('transacao', $obj);
	
	echoRespnse(200, $response);
});


$app->delete('/interesse/:id_usuario_jogo', function($id_usuario_jogo) {
	$response = array();
	$db = new DbHandler();
	
	if($db->temTransacao($id_usuario_jogo)){
		$response["error"] = true;
		$response["message"] = "O interesse não pode ser excluído pois possui transações em curso!";
	}else{
				
		$result = $db->delete('usuario_jogo', array('id_usuario_jogo' => $id_usuario_jogo), true);
		
		if ($result) {
			$response["error"] = false;
			$response["message"] = "Interesse removido!";
		}else{
			$response["error"] = true;
			$response["message"] = "Erro ao remover o interesse!";
		}
	}
	echoRespnse(200, $response);
});
	
		
$app->get('/interesse/:id_usuario/:id_interesse', function($id_usuario, $id_interesse) {
	
	//global $user_id;
	$response = array();
	$db = new DbHandler();
		
	$response = $db->getInteresses($id_usuario, $id_interesse);
	echoRespnse(200, $response);
});
		
$app->get('/dados_oportunidade/:id_usuario_jogo_solic/:id_usuario_jogo_ofert', function($id_usuario_jogo_solic, $id_usuario_jogo_ofert) {
	//global $user_id;
	$response = array();
	$db = new DbHandler();

	$response = $db->getDadosOportunidade($id_usuario_jogo_solic, $id_usuario_jogo_ofert);
	echoRespnse(200, $response);
});
		
$app->get('/oportunidades/:id', function($id_usuario) {
	//global $user_id;
	$response = array();
	$db = new DbHandler();

	$response = $db->getOportunidades($id_usuario);
	echoRespnse(200, $response);
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

$app->post('/jogo', function() use ($app) {	
			//global $user_id;
			
			$filtro = null;
			$interesse = null;
			$id_usuario = null;
			
			if($app->request()->post('filtro')){
				$filtro = $app->request()->post('filtro');
			}
			
			if($app->request()->post('interesse')){
				$interesse = $app->request()->post('interesse');
				$id_usuario = $app->request()->post('id_usuario');
			}
						
			$response = array();
			$db = new DbHandler();
			
			// fetch task
			$response = $db->getTodosJogos($filtro,$interesse,$id_usuario);
			
			echoRespnse(200, $response);
});	

$app->get('/jogos_do_usuario/:id', function($id_usuario) {
	$response = array();
	$db = new DbHandler();

	// fetch task
	$response = $db->getJogosDoUsuario($id_usuario);

	echoRespnse(200, $response);
});

$app->get('/usuario_tem_jogo/:id', function($id_jogo) {	
	$response = array();
	$db = new DbHandler();

	// fetch task
	$response = $db->getUsuarioTemJogo($id_jogo);

	echoRespnse(200, $response);
});

$app->get('/jogo/:id', function($id_jogo) {	
			$response = array();
			$db = new DbHandler();
			
			// fetch task
			$response = $db->getJogo($id_jogo);
			
			echoRespnse(200, $response);
});
		
$app->get('/cadastros', function() use ($app) {
	$response = array();
	$db = new DbHandler();

	$response = $db->getTodosCadastros();
	
	echoRespnse(200, $response);
});		

$app->get('/fotos/:id', function($id_usuario_jogo){
	$response = array();
	$db = new DbHandler();

	$response = $db->getFotos($id_usuario_jogo);
	
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