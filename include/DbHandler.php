<?php

/**
 * Class to handle all db operations
 * This class will have CRUD methods for database tables
 *
 * @author Ravi Tamada
 * @link URL Tutorial link
 */
class DbHandler {

    private $conn;

    function __construct() {
        require_once dirname(__FILE__) . '/DbConnect.php';
        // opening db connection
        $db = new DbConnect();
        $this->conn = $db->connect();
    }

    /* ------------- `users` table method ------------------ */

    /**
     * Creating new user
     * @param String $name User full name
     * @param String $email User login email id
     * @param String $password User login password
     */
    public function createUser($name, $email, $password) {
        require_once 'PassHash.php';
        $response = array();

        // First check if user already existed in db
        if (!$this->isUserExists($email)) {
            // Generating password hash
            $password_hash = PassHash::hash($password);

            // Generating API key
            $api_key = $this->generateApiKey();

            // insert query
            $stmt = $this->conn->prepare("insert into usuario values ((select ifnull(max(u.id_usuario),0)+1 from usuario u),?,?,?,?,null,null,null)");
            $stmt->bind_param("ssss", $name, $email, $password_hash, $api_key);

            $result = $stmt->execute();

            $stmt->close();

            // Check for successful insertion
            if ($result) {
                // User successfully inserted
                return USER_CREATED_SUCCESSFULLY;
            } else {
                // Failed to create user
                return USER_CREATE_FAILED;
            }
        } else {
            // User with same email already existed in the db
            return USER_ALREADY_EXISTED;
        }

        return $response;
    }
	
	public function temTransacao($id_usuario_jogo) {
		
		$sql = "select 1
				  from transacao t,
					   usuario_jogo uj
				 where t.id_estado_transacao <> 4
				   and (t.id_usuario_jogo_solicitante = uj.id_usuario_jogo or t.id_usuario_jogo_ofertante = uj.id_usuario_jogo)
				   and uj.id_usuario_jogo = $id_usuario_jogo
				   and uj.ativo = 1";
               
	   // insert query
		$stmt = $this->conn->prepare($sql);
		$stmt->execute();
		$stmt->store_result();
		$result = $stmt->num_rows>0;
		$stmt->close();
		
		return $result;		
	}
		
	public function checkUserInterest($id_interesse, $id_usuario, $id_jogo, $id_plataforma) {
		$response = array();
                               
		if($id_interesse==1){
            $aux = "1,3,4";
		}elseif($id_interesse==2){
			$aux = "2,3,4";
		}elseif($id_interesse==3){
			$aux = "1,3,2";
		}elseif($id_interesse==4){
			$aux = "1,2,4";
		}
		
		$sql = "select id_usuario_jogo
				  from usuario_jogo
				 where id_usuario = ".$id_usuario." and 
					   id_jogo = ".$id_jogo." and
					   id_plataforma = ".$id_plataforma." and
					   id_interesse in (".$aux.") and
					   ativo = 1";
               
	   // insert query
		$stmt = $this->conn->prepare($sql);
		$stmt->execute();
		$stmt->store_result();
		$result = $stmt->num_rows>0;
		$stmt->close();
		return $result;
   }
	
	public function insert_update($tab, $ids, $id_sequence, $obj) {
		
		if($ids!=null){
			$and = '';
			foreach($ids as $i => $id){
				$and .= " and $i = '$id'";
			}		
			$stmt = $this->conn->prepare("select 1 from $tab WHERE 1=1 $and");
			$stmt->execute();
			$stmt->store_result();
			$existe = $stmt->num_rows > 0;
			$stmt->close();
		}else{
			$existe = false;
		}
		
		if($existe){
			$result = $this->update($tab, $ids, $obj);
		}else{
			if(!$id_sequence){
				foreach($ids as $i => $id){
					$obj[$i] = $id;
				}
			}
			$result = $this->insert($tab, $obj);
		}
		return $result;
	}
	
	
	public function insertID($tab, $obj) {
		$response = array();
				
		$campos = array();
		$valores = array();
		
		foreach($obj as $campo => $valor){
			$campos[] = $campo;
			if($campo == 'foto'){
				$valores[] = $valor == null ? 'null' : "FROM_BASE64('".$valor."')";
			}else{
				$valores[] = $valor == null ? 'null' : "'".$valor."'";
			}
		}
		
		$sql = "insert into $tab
					(".implode(',',$campos).")
				values
					(".implode(',',$valores).")";
		
		// insert query
		$stmt = $this->conn->prepare($sql);
		$result = $stmt->execute();
		$stmt->store_result();
		$id = mysqli_insert_id($this->conn);
		$stmt->close();
		
		if($result){
			return $id;
		}
		return null;
	}
	
	
	public function insert($tab, $obj) {
        $response = array();
				
		$campos = array();
		$valores = array();
		
		foreach($obj as $campo => $valor){
			
			$campos[] = $campo;
			
			if($campo == 'foto'){
				$valores[] = $valor == null ? 'null' : "FROM_BASE64('".$valor."')";
			}else{
				$valores[] = $valor == null ? 'null' : "'".$valor."'";
			}
		}
		
		$sql = "insert into $tab
					(".implode(',',$campos).")
				values
					(".implode(',',$valores).")";
		
		// insert query
		$stmt = $this->conn->prepare($sql);
		$result = $stmt->execute();
		$stmt->close();

		// Check for successful insertion
		return $result;
    }

	public function update($tab, $ids, $obj) {
	
		$campos = array();
		
		$and = '';
		foreach($ids as $i => $id){
			$and .= " and $i = '$id'";
		}
		
		foreach($obj as $campo => $valor){
			if($campo == 'foto'){
				$campos[] = "$campo = FROM_BASE64('$valor')";
			}else{
				$campos[] = "$campo = '$valor'";
			}
			
		}
		$upd = implode(',',$campos);
		
		$sql = "update $tab
				   set $upd
				 where 1=1
				  $and";
		
		$stmt = $this->conn->prepare($sql);
		
		$result = $stmt->execute();
		$stmt->close();
		
		return $result;
	}
	
	public function delete($tab, $ids, $logica) {
		$and = '';
		foreach($ids as $i => $id){
			$and .= " and $i = '$id'";
		}
		
		if($logica){
			$sql = "update $tab
					   set ativo=0
					 where 1=1
					  $and";
		}else{
			$sql = "delete
					  from $tab
					 where 1=1
					  $and";
		}
		$stmt = $this->conn->prepare($sql);
		
		$result = $stmt->execute();
		$stmt->close();
		
		return $result;
	}
	
	public function deletaInteresses($id_transacao){
		$sql = "select id_usuario_jogo_solicitante,
					   id_usuario_jogo_ofertante
				  from transacao
				 where id_transacao = $id_transacao";
        
		$stmt = $this->conn->prepare($sql);
        if ($stmt->execute()) {
            $int = $stmt->get_result()->fetch_assoc();
            $stmt->close();
			
			$result = $this->delete('usuario_jogo', array('id_usuario_jogo' => $int["id_usuario_jogo_solicitante"]), true);
			$result = $this->delete('usuario_jogo', array('id_usuario_jogo' => $int["id_usuario_jogo_ofertante"]), true);	
		}
	}
	
	public function getFoto($id_foto){
		$sql = "select foto
				  from foto
				 where id_foto = $id_foto";
        
		$stmt = $this->conn->prepare($sql);
        if ($stmt->execute()) {
            $user = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return $user["foto"];
        } else {
            return NULL;
        }
	}
	
    /**
     * Checking user login
     * @param String $email User login email id
     * @param String $password User login password
     * @return boolean User login status success/fail
     */
    public function checkLogin($email, $password) {
        // fetching user by email
        $stmt = $this->conn->prepare("SELECT senha FROM usuario WHERE email = ?");

        $stmt->bind_param("s", $email);

        $stmt->execute();

        $stmt->bind_result($senha);

        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            // Found user with the email
            // Now verify the password

            $stmt->fetch();

            $stmt->close();

            if (PassHash::check_password($senha, $password)) {
                // User password is correct
                return TRUE;
            } else {
                // user password is incorrect
                return FALSE;
            }
        } else {
            $stmt->close();

            // user not existed with the email
            return FALSE;
        }
    }

    /**
     * Checking for duplicate user by email address
     * @param String $email email to check in db
     * @return boolean
     */
    private function isUserExists($email) {
        $stmt = $this->conn->prepare("SELECT id_usuario from usuario WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
    }

	
	
	public function getUserById($id_usuario) {
        $stmt = $this->conn->prepare("SELECT id_usuario, nome, email, chave_api, foto FROM usuario WHERE id_usuario = ?");
        $stmt->bind_param("i", $id_usuario);
        if ($stmt->execute()) {
            // $user = $stmt->get_result()->fetch_assoc();
            $stmt->bind_result($id_usuario, $name, $email, $api_key, $foto);
            $stmt->fetch();
            $user = array();
			$user["id_usuario"] = $id_usuario;
            $user["nome"] = $name;
            $user["email"] = $email;
            $user["chave_api"] = $api_key;
            $user["foto"] = base64_encode($foto);
            $stmt->close();
            return $user;
        } else {
            return NULL;
        }
    }
	
	public function getPreferencias($id_usuario){
		$stmt = $this->conn->prepare("select 
											 e.descricao desc_estado_jogo,
											 m.descricao desc_metodo_envio
										from usuario u,
											 estado_jogo e,
											 metodo_envio m
									   where u.id_estado_jogo = e.id_estado_jogo
									     and u.id_metodo_envio = m.id_metodo_envio
										 and u.id_usuario = $id_usuario");
		$stmt->execute();
		$stmt->bind_result($desc_estado_jogo, $desc_metodo_envio);
        $stmt->fetch();
		
		$prefer = array();
		$prefer["desc_estado_jogo"] = $desc_estado_jogo;
		$prefer["desc_metodo_envio"] = $desc_metodo_envio;
		$stmt->close();
		return $prefer;
	}
	
    /**
     * Fetching user by email
     * @param String $email User email id
     */
    public function getUserByEmail($email) {
        $stmt = $this->conn->prepare("SELECT id_usuario, nome, email, chave_api FROM usuario WHERE email = ?");
        $stmt->bind_param("s", $email);
		$stmt->execute();
        $stmt->store_result();
		$num_rows = $stmt->num_rows;
        if ($num_rows>0) {
            // $user = $stmt->get_result()->fetch_assoc();
            $stmt->bind_result($id_usuario, $name, $email, $api_key);
            $stmt->fetch();
            $user = array();
			$user["id_usuario"] = $id_usuario;
            $user["nome"] = $name;
            $user["email"] = $email;
            $user["chave_api"] = $api_key;
            $stmt->close();
            return $user;
        } else {
            return NULL;
        }
    }

    /**
     * Fetching user api key
     * @param String $user_id user id primary key in user table
     */
    public function getApiKeyById($user_id) {
        $stmt = $this->conn->prepare("SELECT chave_api FROM usuario WHERE id_usuario = ?");
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            // $api_key = $stmt->get_result()->fetch_assoc();
            // TODO
            $stmt->bind_result($api_key);
            $stmt->close();
            return $api_key;
        } else {
            return NULL;
        }
    }

    /**
     * Fetching user id by api key
     * @param String $api_key user api key
     */
    public function getUserId($api_key) {
        $stmt = $this->conn->prepare("SELECT id_usuario FROM usuario WHERE chave_api = ?");
        $stmt->bind_param("s", $api_key);
        if ($stmt->execute()) {
            $stmt->bind_result($user_id);
            $stmt->fetch();
            // TODO
            // $user_id = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return $user_id;
        } else {
            return NULL;
        }
    }

	
    public function getTodosEstadoJogo() {
        $stmt = $this->conn->prepare("SELECT id_estado_jogo, descricao FROM estado_jogo");
		$stmt->execute();
        $estados = $stmt->get_result();
        $stmt->close();
		
		$response = array();
		            
		while ($estado = $estados->fetch_assoc()) {
			$estado_jogo = array();
			$estado_jogo["id_estado_jogo"] = $estado["id_estado_jogo"];
			$estado_jogo["descricao"] = $estado["descricao"];
			array_push($response, $estado_jogo);
		}
		return $response;
    }
	
    public function getTodosUsuarios($filtro) {
		
		$where = '';
		if($filtro){
			$where = " where nome like '%$filtro%'";
		}
		$sql = "SELECT id_usuario, nome, foto FROM usuario $where";
        $stmt = $this->conn->prepare($sql);
		$stmt->execute();
        $usuarios = $stmt->get_result();
        $stmt->close();
		
		$response = array();
		            
		while ($u = $usuarios->fetch_assoc()) {
			$usuario = array();
			$usuario["id_usuario"] = $u["id_usuario"];
			$usuario["nome"] = $u["nome"];
			$usuario["foto"] = base64_encode($u["foto"]);
			array_push($response, $usuario);
		}
		
		return $response;
    }
	
	public function getJogosDoUsuario($id_usuario) {
		
		$sql = "SELECT distinct j.id_jogo, j.descricao, j.foto
				  FROM jogo j
				 WHERE exists (select 1
								 from usuario_jogo uj
								where uj.id_jogo = j.id_jogo
								  and uj.id_interesse in (1,2)
								  and uj.id_usuario = $id_usuario
								  and uj.ativo = 1)";
								  
        $stmt = $this->conn->prepare($sql);
		$stmt->execute();
        $usuarios = $stmt->get_result();
        $stmt->close();
		
		$response = array();
		            
		while ($u = $usuarios->fetch_assoc()) {
			$usuario = array();
			$usuario["id_jogo"] = $u["id_jogo"];
			$usuario["descricao"] = $u["descricao"];
			$usuario["foto"] = base64_encode($u["foto"]);
			array_push($response, $usuario);
		}
		return count($response)>0 ? $response : null;
    }
	
	
	
	public function getUsuarioTemJogo($id_jogo) {
		
		$sql = "SELECT u.id_usuario, u.nome, u.foto
				  FROM usuario u
				 WHERE exists (select 1
								 from usuario_jogo uj
								where uj.id_usuario = u.id_usuario
								  and uj.id_interesse in (1,2)
								  and uj.id_jogo = $id_jogo
								  and uj.ativo = 1)";
		
        $stmt = $this->conn->prepare($sql);
		$stmt->execute();
        $usuarios = $stmt->get_result();
        $stmt->close();
		
		$response = array();
		            
		while ($u = $usuarios->fetch_assoc()) {
			$usuario = array();
			$usuario["id_usuario"] = $u["id_usuario"];
			$usuario["nome"] = $u["nome"];
			$usuario["foto"] = base64_encode($u["foto"]);
			array_push($response, $usuario);
		}
		return count($response)>0 ? $response : null;
    }
	
	
	
	public function getJogo($id_jogo) {
		$sql = "SELECT id_jogo, descricao, ano, foto FROM jogo where id_jogo = $id_jogo";
        $stmt = $this->conn->prepare($sql);
		$stmt->execute();
		$jogos = $stmt->get_result();
        $stmt->close();

		$j = $jogos->fetch_assoc();

		$jogo = array();
		$jogo["id_jogo"] = $j["id_jogo"];
		$jogo["descricao"] = $j["descricao"];
		$jogo["ano"] = $j["ano"];
		$jogo["foto"] = base64_encode($j["foto"]);
		$jogo["plataformas"] = array();

		$i = -1;
		
		$sql = "SELECT p.id_plataforma,
					   p.descricao,
					   p.marca
				  FROM jogo_plataforma jp,
					   plataforma p
				 WHERE p.id_plataforma = jp.id_plataforma
				   AND jp.id_jogo = ".$jogo["id_jogo"];
		$stmt = $this->conn->prepare($sql);
		$stmt->execute();
		$plat = $stmt->get_result();
		$stmt->close();
		while ($p = $plat->fetch_assoc()) {
			$i++;
			$jogo["plataformas"][$i] = array();
			$jogo["plataformas"][$i]["id_plataforma"] = $p["id_plataforma"];
			$jogo["plataformas"][$i]["descricao"] = $p["descricao"];
			$jogo["plataformas"][$i]["marca"] = $p["marca"];
		}
		return $jogo;
	}
	
	public function getTodosJogos($filtro,$interesse,$id_usuario){
		
		$and = '';
		
		if($filtro){
			$and .= " and descricao like '%$filtro%'";
		}
		if($interesse){
			if($interesse==1){
				$and .= " and not exists (select 1 from usuario_jogo uj where uj.ativo = 1 and uj.id_interesse in (1,2) and uj.id_usuario = $id_usuario and uj.id_jogo = j.id_jogo)";
			}elseif($interesse==3){
				$and .= " and exists (select 1 from usuario_jogo uj where uj.ativo = 1 and uj.id_interesse in (1,2) and uj.id_usuario = $id_usuario and uj.id_jogo = j.id_jogo)";
			}
		}
		
		$sql = "SELECT j.id_jogo, j.descricao, j.foto FROM jogo j where 1=1 $and";
				
        $stmt = $this->conn->prepare($sql);
		$stmt->execute();
		$jogos = $stmt->get_result();
        $stmt->close();
		
		$response = array();
		            
		while ($j = $jogos->fetch_assoc()) {
			
			$jogo = array();
			$jogo["id_jogo"] = $j["id_jogo"];
			$jogo["descricao"] = $j["descricao"];
			$jogo["foto"] = base64_encode($j["foto"]);
			$jogo["plataformas"] = array();
			$i = -1;
			
			$sql = "SELECT p.id_plataforma,
						   p.descricao
					  FROM jogo_plataforma jp,
						   plataforma p
					 WHERE p.id_plataforma = jp.id_plataforma
					   AND jp.id_jogo = ".$jogo["id_jogo"];
			$stmt = $this->conn->prepare($sql);
			$stmt->execute();
			$plat = $stmt->get_result();
			$stmt->close();
			while ($p = $plat->fetch_assoc()) {
				$i++;
				$jogo["plataformas"][$i] = array();
				$jogo["plataformas"][$i]["id_plataforma"] = $p["id_plataforma"];
				$jogo["plataformas"][$i]["descricao"] = $p["descricao"];
			}
			
			array_push($response, $jogo);
		}
		
		return $response;
    }
	
	public function getTodosCadastros(){
		
		$sql = "SELECT 'estado_jogo' as 'tabela', 'id_estado_jogo' as 'campo_id', ej.id_estado_jogo as 'valor_id', 'descricao' as 'campo_des', ej.descricao as 'valor_des' FROM estado_jogo ej
				union
				SELECT 'estado_transacao' as 'tabela', 'id_estado_transacao' as 'campo_id', et.id_estado_transacao as 'valor_id', 'descricao' as 'campo_des', et.descricao as 'valor_des' FROM estado_transacao et
				union
				SELECT 'metodo_envio' as 'tabela', 'id_metodo_envio' as 'campo_id', me.id_metodo_envio as 'valor_id', 'descricao' as 'campo_des', me.descricao as 'valor_des' FROM metodo_envio me";
				
		$stmt = $this->conn->prepare($sql);
		$stmt->execute();
		$cad = $stmt->get_result();
        $stmt->close();
				
		$response = array();
		
		while ($c = $cad->fetch_assoc()) {
			array_push($response, $c);
		}
		return count($response)>0?$response:null;
	}
	
	public function getEndereco($id_usuario) {
		
		$sql = "select e.* from endereco e where e.id_usuario = $id_usuario";
				   
		$stmt = $this->conn->prepare($sql);
		$stmt->execute();
		$res = $stmt->get_result();
        $stmt->close();
		
		$endereco = $res->fetch_assoc();
		
		return $endereco;
	}
	
	
	public function getContatoTransacao($id_usuario){
		$sql = "select u.id_usuario,
					   u.nome,
					   u.email,
					   u.foto,
					   e.*,
					   t.*
				  from usuario u
				  left outer join endereco e on u.id_usuario = e.id_usuario
				  left outer join telefone t on u.id_usuario = t.id_usuario
				 where u.id_usuario = $id_usuario";
		
		$stmt = $this->conn->prepare($sql);
		$stmt->execute();
		$res = $stmt->get_result();
        $stmt->close();
				
		$contato = $res->fetch_assoc();
				
		if(!$contato){
			return false;
		}
		
		$response = array();
		
		$response['id_usuario']	= $contato['id_usuario'];
		$response['nome']		= $contato['nome'];
		$response['email']		= $contato['email'];
		$response['foto']		= base64_encode($contato['foto']);
		
		$response['logradouro']	= $contato['logradouro'];
		$response['cep']		= $contato['cep'];
		$response['bairro']		= $contato['bairro'];
		$response['cidade']		= $contato['cidade'];
		$response['uf']			= $contato['uf'];
		$response['numero']		= $contato['numero'];
		$response['complemento']= $contato['complemento'];
		$response['ddd']		= $contato['ddd'];
		$response['telefone']	= $contato['telefone'];
		
		return $response;
	}
	
	
	public function getContatos($id_usuario) {
		
		$sql = "select u.id_usuario,
					   e.logradouro,
					   e.cep,
					   e.bairro,
					   e.cidade,
					   e.uf,
					   e.numero,
					   e.complemento,
					   t.ddd,
					   t.telefone
				  
				  from usuario u
				  
				  left outer join endereco e on u.id_usuario = e.id_usuario
				  left outer join telefone t on u.id_usuario = t.id_usuario
				  
				 where u.id_usuario = $id_usuario";
				   
		$stmt = $this->conn->prepare($sql);
		$stmt->execute();
		$res = $stmt->get_result();
        $stmt->close();
		
		$response = array();
		
		$contatos = $res->fetch_assoc();
		
		if(!$contatos){
			return false;
		}
		
		if($contatos['logradouro'])  $response['logradouro']  = $contatos['logradouro'];
		if($contatos['cep']) 		 $response['cep']		  = $contatos['cep'];
		if($contatos['bairro']) 	 $response['bairro']	  = $contatos['bairro'];
		if($contatos['cidade']) 	 $response['cidade']	  = $contatos['cidade'];
		if($contatos['uf']) 		 $response['uf']		  = $contatos['uf'];
		if($contatos['numero'])		 $response['numero']	  = $contatos['numero'];
		if($contatos['complemento']) $response['complemento'] = $contatos['complemento'];
		if($contatos['ddd']) 		 $response['ddd']		  = $contatos['ddd'];
		if($contatos['telefone']) 	 $response['telefone']	  = $contatos['telefone'];
		
		return $response;
	}
	
	public function getFotos($id_usuario_jogo){
		$sql = "SELECT id_foto, foto FROM foto WHERE id_usuario_jogo = $id_usuario_jogo";
				   
		$stmt = $this->conn->prepare($sql);
		$stmt->execute();
		$foto = $stmt->get_result();
        $stmt->close();
		
		$response = array();
		
		while ($f = $foto->fetch_assoc()) {
			$ft = array();
			$ft["id_foto"] = $f["id_foto"];
            $ft["foto"]    = base64_encode($f["foto"]);
			array_push($response, $ft);
        }
		return count($response)>0?$response:null;
	}	
	
	
	public function getAvaliacoes($id_usuario){
		$sql = "select a.*,
					   u.nome,
					   u.foto
				  from avaliacao_transacao a,
					   usuario u
				 where a.id_usuario_avaliado = $id_usuario
				   and a.id_usuario_avaliador = u.id_usuario";
				   
		$stmt = $this->conn->prepare($sql);
		$stmt->execute();
		$aval = $stmt->get_result();
        $stmt->close();
		
		$response = array();
		
		while ($a = $aval->fetch_assoc()) {
			$av = array();
			$av["id_avaliacao_transacao"] 	= $a["id_avaliacao_transacao"];
			$av["id_transacao"] 			= $a["id_transacao"];
			$av["id_usuario_avaliador"] 	= $a["id_usuario_avaliador"];
			$av["id_usuario_avaliado"] 		= $a["id_usuario_avaliado"];
			$av["avaliacao"] 				= $a["avaliacao"];
			$av["observacao"] 				= $a["observacao"];
            $av["nome"] 					= $a["nome"];
            $av["foto"] 					= base64_encode($a["foto"]);
			array_push($response, $av);
        }
		return count($response)>0?$response:null;
	}
	
	public function getDadosAvaliacao($id_transacao, $id_usuario_avaliador){
		$sql = "select *
				  from avaliacao_transacao
				 where id_transacao = $id_transacao
				   and id_usuario_avaliador = $id_usuario_avaliador";
		
		$stmt = $this->conn->prepare($sql);
		$stmt->execute();
		$aval = $stmt->get_result();
        $stmt->close();
		
		return $aval->fetch_assoc();
	}
	
	
	public function getDadosTransacao($id_transacao){
		
		$sql = "select t.id_transacao,
						t.id_estado_transacao,
						t.envio_solicitante,
						t.envio_ofertante,
						
						ujs.id_interesse as id_interesse,		
						us.id_usuario,
						(select mes.descricao from metodo_envio mes where mes.id_metodo_envio = ifnull(t.id_metodo_envio_solicitante, us.id_metodo_envio)) as metodo_envio,
						us.nome,
						jt.descricao as descricao_jogo,
						pt.descricao as plataforma_jogo,
						(select ejs.descricao from estado_jogo ejs where ejs.id_estado_jogo = ujs.id_estado_jogo) as estado_jogo,
						jt.foto as foto_jogo,
						ujs.id_usuario_jogo,
						(select count(0) from foto fs where fs.id_usuario_jogo = ujs.id_usuario_jogo) qtd_foto,
						
						ujo.id_interesse as id_interesse_ofert,
						uo.id_usuario as id_usuario_ofert,
						(select meo.descricao from metodo_envio meo where meo.id_metodo_envio = ifnull(t.id_metodo_envio_ofertante, uo.id_metodo_envio)) as metodo_envio_ofert,
						uo.nome as nome_ofert,
						js.id_jogo as id_jogo_ofert,
						js.descricao as descricao_jogo_ofert,
						ps.descricao as plataforma_jogo_ofert,
						(select ejo.descricao from estado_jogo ejo where ejo.id_estado_jogo = ujo.id_estado_jogo) as estado_jogo_ofert,
						js.foto as foto_jogo_ofert,
						ujo.preco as preco_jogo_ofert,
						ujo.id_usuario_jogo as id_usuario_jogo_ofert,
						(select count(0) from foto fo where fo.id_usuario_jogo = ujo.id_usuario_jogo) qtd_foto_ofert
					from 
						usuario_jogo ujo,
						usuario us,
						usuario uo,						
						jogo js,
						plataforma ps,
						transacao t,
						usuario_jogo ujs  left outer join jogo jt on ujs.id_jogo_troca = jt.id_jogo
										  left outer join plataforma pt on ujs.id_plataforma_troca = pt.id_plataforma
					where 
						ujs.id_usuario_jogo = t.id_usuario_jogo_solicitante and 
						ujs.id_usuario = us.id_usuario and
						ujs.id_jogo = js.id_jogo and
						ujs.id_plataforma = ps.id_plataforma and
						ujo.id_usuario_jogo = t.id_usuario_jogo_ofertante and
						ujo.id_usuario = uo.id_usuario and 
						t.id_transacao = ".$id_transacao;
						
		$stmt = $this->conn->prepare($sql);
		$stmt->execute();
		$trans = $stmt->get_result();
        $stmt->close();
		
		$t = $trans->fetch_assoc();
		
		$tr = array();
		$tr["id_transacao"] 				= $t["id_transacao"];
		$tr["id_estado_transacao"] 			= $t["id_estado_transacao"];
		$tr["envio_solicitante"] 			= $t["envio_solicitante"];
		$tr["envio_ofertante"] 				= $t["envio_ofertante"];
		$tr["id_interesse"] 				= $t["id_interesse"];
		$tr["id_usuario"] 					= $t["id_usuario"];
		$tr["metodo_envio"]					= $t["metodo_envio"];
		$tr["nome"] 						= $t["nome"];
		$tr["descricao_jogo"] 				= $t["descricao_jogo"];
		$tr["plataforma_jogo"] 				= $t["plataforma_jogo"];
		$tr["estado_jogo"] 					= $t["estado_jogo"];
		//$tr["foto_jogo"] 					= base64_encode($t["foto_jogo"]);
		$tr["id_usuario_jogo"] 				= $t["id_usuario_jogo"];
		$tr["qtd_foto"] 					= $t["qtd_foto"];
		$tr["id_interesse_ofert"] 			= $t["id_interesse_ofert"];
		$tr["id_usuario_ofert"] 			= $t["id_usuario_ofert"];
		$tr["metodo_envio_ofert"]			= $t["metodo_envio_ofert"];
		$tr["nome_ofert"] 					= $t["nome_ofert"];
		$tr["id_jogo_ofert"] 				= $t["id_jogo_ofert"];
		$tr["descricao_jogo_ofert"] 		= $t["descricao_jogo_ofert"];
		$tr["plataforma_jogo_ofert"]		= $t["plataforma_jogo_ofert"];
		$tr["estado_jogo_ofert"]			= $t["estado_jogo_ofert"];
		//$tr["foto_jogo_ofert"] 			= base64_encode($t["foto_jogo_ofert"]);
		$tr["preco_jogo_ofert"] 			= $t["preco_jogo_ofert"];
		$tr["id_usuario_jogo_ofert"]		= $t["id_usuario_jogo_ofert"];
		$tr["qtd_foto_ofert"] 				= $t["qtd_foto_ofert"];
		
		return $tr;
	}
	
	public function getTransacoes($id_usuario, $status){
		
		$sql = "select  t.id_transacao,
						
						ujs.id_interesse as id_interesse,		
						us.id_usuario,
						(select mes.descricao from metodo_envio mes where mes.id_metodo_envio = us.id_metodo_envio) as metodo_envio,
						us.nome,
						js.descricao as descricao_jogo,
						ps.descricao as plataforma_jogo,
						(select ejs.descricao from estado_jogo ejs where ejs.id_estado_jogo = ujs.id_estado_jogo) as estado_jogo,
						js.foto as foto_jogo,
						ujs.id_usuario_jogo,
						
						ujo.id_interesse as id_interesse_ofert,
						uo.id_usuario as id_usuario_ofert,
						(select meo.descricao from metodo_envio meo where meo.id_metodo_envio = uo.id_metodo_envio) as metodo_envio_ofert,
						uo.nome as nome_ofert,
						jo.id_jogo as id_jogo_ofert,
						jo.descricao as descricao_jogo_ofert,
						po.descricao as plataforma_jogo_ofert,
						(select ejo.descricao from estado_jogo ejo where ejo.id_estado_jogo = ujo.id_estado_jogo) as estado_jogo_ofert,
						jo.foto as foto_jogo_ofert,
						ujo.preco as preco_jogo_ofert,
						ujo.id_usuario_jogo as id_usuario_jogo_ofert
					from 
						usuario_jogo ujs,
						usuario_jogo ujo,
						usuario us,
						usuario uo,						
						jogo js,
						jogo jo,
						plataforma ps,
						plataforma po,
						transacao t
					where 
						ujs.id_usuario_jogo = t.id_usuario_jogo_solicitante and 
						ujs.id_usuario = us.id_usuario and
						ujs.id_jogo = js.id_jogo and
						ujs.id_plataforma = ps.id_plataforma and
						ujo.id_usuario_jogo = t.id_usuario_jogo_ofertante and
						ujo.id_usuario = uo.id_usuario and 
						ujo.id_jogo = jo.id_jogo and
						ujo.id_plataforma = po.id_plataforma and 
						t.id_estado_transacao = ".$status." and
						(us.id_usuario = ".$id_usuario." or uo.id_usuario = ".$id_usuario.")";
		
		$stmt = $this->conn->prepare($sql);
		$stmt->execute();
		$trans = $stmt->get_result();
        $stmt->close();
		
		$response = array();
		
		while ($t = $trans->fetch_assoc()) {
			$tr = array();
			$tr["id_transacao"] 		= $t["id_transacao"];
			$tr["id_interesse"] 		= $t["id_interesse"];
			$tr["descricao_jogo"] 		= $t["descricao_jogo"];
			$tr["plataforma_jogo"] 		= $t["plataforma_jogo"];
			$tr["foto_jogo"] 			= base64_encode($t["foto_jogo"]);
			$tr["id_usuario_jogo"] 		= $t["id_usuario_jogo"];
			$tr["id_usuario_ofert"] 	= $t["id_usuario_ofert"];
            $tr["nome_ofert"] 			= $t["nome_ofert"];
            $tr["id_jogo_ofert"] 		= $t["id_jogo_ofert"];
			$tr["descricao_jogo_ofert"] = $t["descricao_jogo_ofert"];
			$tr["plataforma_jogo_ofert"]= $t["plataforma_jogo_ofert"];
            $tr["foto_jogo_ofert"] 		= base64_encode($t["foto_jogo_ofert"]);
			$tr["preco_jogo_ofert"] 	= $t["preco_jogo_ofert"];
			$tr["id_usuario_jogo_ofert"]= $t["id_usuario_jogo_ofert"];
			array_push($response, $tr);
        }
		
		return count($response)>0?$response:null;
	}
	
	public function getInteresses($id_usuario, $id_interesse){
		
		$sql = "select  uj.id_usuario_jogo,
						uj.id_interesse,		
						u.id_usuario,
						u.nome,
						j.descricao as descricao_jogo,
						p.descricao as plataforma_jogo,
						j.foto as foto_jogo,
						jt.descricao as descricao_jogo_troca,
						pt.descricao as plataforma_jogo_troca,
						jt.foto as foto_jogo_troca,
						uj.preco,
						uj.preco_inicial,
						uj.preco_final
				   
				   from usuario_jogo uj
						inner join usuario u on uj.id_usuario = u.id_usuario
						inner join jogo j on uj.id_jogo = j.id_jogo
						inner join plataforma p on uj.id_plataforma = p.id_plataforma 
						left outer join jogo jt on uj.id_jogo_troca = jt.id_jogo
						left outer join plataforma pt on uj.id_plataforma_troca = pt.id_plataforma
				  
				  where uj.id_interesse = ".$id_interesse." and
						u.id_usuario = ".$id_usuario." and
						uj.ativo = 1";
		
		$stmt = $this->conn->prepare($sql);
		$stmt->execute();
		$trans = $stmt->get_result();
        $stmt->close();
		
		$response = array();
		
		while ($t = $trans->fetch_assoc()) {
			$t["foto_jogo"] = base64_encode($t["foto_jogo"]);
			$t["foto_jogo_troca"] = base64_encode($t["foto_jogo_troca"]);
			array_push($response, $t);
        }
		
		return count($response)>0?$response:null;
	}
	
	
	public function getDadosOportunidade($id_usuario_jogo_solic, $id_usuario_jogo_ofert) {
		
		$sql = "select ujs.id_interesse as id_interesse,		
						us.id_usuario,
						(select mes.descricao from metodo_envio mes where mes.id_metodo_envio = us.id_metodo_envio) as metodo_envio,
						us.nome,
						jt.descricao as descricao_jogo,
						pt.descricao as plataforma_jogo,
						(select ejs.descricao from estado_jogo ejs where ejs.id_estado_jogo = ujs.id_estado_jogo) as estado_jogo,
						jt.foto as foto_jogo,
						ujs.id_usuario_jogo,
						(select count(0) from foto fs where fs.id_usuario_jogo = ujs.id_usuario_jogo) qtd_foto,
						
						ujo.id_interesse as id_interesse_ofert,
						uo.id_usuario as id_usuario_ofert,
						(select meo.descricao from metodo_envio meo where meo.id_metodo_envio = uo.id_metodo_envio) as metodo_envio_ofert,
						uo.nome as nome_ofert,
						js.id_jogo as id_jogo_ofert,
						js.descricao as descricao_jogo_ofert,
						ps.descricao as plataforma_jogo_ofert,
						(select ejo.descricao from estado_jogo ejo where ejo.id_estado_jogo = ujo.id_estado_jogo) as estado_jogo_ofert,
						js.foto as foto_jogo_ofert,
						ujo.preco as preco_jogo_ofert,
						ujo.id_usuario_jogo as id_usuario_jogo_ofert,
						(select count(0) from foto fo where fo.id_usuario_jogo = ujo.id_usuario_jogo) qtd_foto_ofert
					from 
						usuario_jogo ujo,
						usuario us,
						usuario uo,						
						jogo js,
						plataforma ps,
						usuario_jogo ujs left outer join jogo jt on ujs.id_jogo_troca = jt.id_jogo
										 left outer join plataforma pt on ujs.id_plataforma_troca = pt.id_plataforma
					where 
						ujs.id_usuario_jogo = ".$id_usuario_jogo_solic." and 
						ujs.id_usuario = us.id_usuario and
						
						ujo.id_usuario_jogo = ".$id_usuario_jogo_ofert." and
						ujo.id_usuario = uo.id_usuario and 
						
						ujs.ativo = 1 and
						ujo.ativo = 1 and
						
						ujs.id_jogo = js.id_jogo and
						ujs.id_plataforma = ps.id_plataforma";
						
		$stmt = $this->conn->prepare($sql);
		$stmt->execute();
		$oport = $stmt->get_result();
        $stmt->close();
		
		$o = $oport->fetch_assoc();
		
		$op = array();
		$op["id_interesse"] 				= $o["id_interesse"];
		$op["id_usuario"] 					= $o["id_usuario"];
		$op["metodo_envio"]					= $o["metodo_envio"];
		$op["nome"] 						= $o["nome"];
		$op["descricao_jogo"] 				= $o["descricao_jogo"];
		$op["plataforma_jogo"] 				= $o["plataforma_jogo"];
		$op["estado_jogo"] 					= $o["estado_jogo"];
		//$op["foto_jogo"] 					= base64_encode($o["foto_jogo"]);
		$op["id_usuario_jogo"] 				= $o["id_usuario_jogo"];
		$op["qtd_foto"] 					= $o["qtd_foto"];
		$op["id_interesse_ofert"] 			= $o["id_interesse_ofert"];
		$op["id_usuario_ofert"] 			= $o["id_usuario_ofert"];
		$op["metodo_envio_ofert"]			= $o["metodo_envio_ofert"];
		$op["nome_ofert"] 					= $o["nome_ofert"];
		$op["id_jogo_ofert"] 				= $o["id_jogo_ofert"];
		$op["descricao_jogo_ofert"] 		= $o["descricao_jogo_ofert"];
		$op["plataforma_jogo_ofert"]		= $o["plataforma_jogo_ofert"];
		$op["estado_jogo_ofert"]			= $o["estado_jogo_ofert"];
		//$op["foto_jogo_ofert"] 			= base64_encode($o["foto_jogo_ofert"]);
		$op["preco_jogo_ofert"] 			= $o["preco_jogo_ofert"];
		$op["id_usuario_jogo_ofert"]		= $o["id_usuario_jogo_ofert"];
		$op["qtd_foto_ofert"] 				= $o["qtd_foto_ofert"];
		
		return $op;
	}
	
	
	public function getOportunidades($id_usuario) {
		
        $sql = "select  prioridade,
						id_interesse,
						descricao_jogo,
						plataforma_jogo,
						foto_jogo,
						id_usuario_jogo,
						id_usuario_ofert,
						nome_ofert,
						id_jogo_ofert,
						descricao_jogo_ofert,
						plataforma_jogo_ofert,
						foto_jogo_ofert,
						preco_jogo_ofert,
						id_usuario_jogo_ofert,	   
						ifnull((select 1 from transacao t where (t.id_usuario_jogo_solicitante = info.id_usuario_jogo and t.id_usuario_jogo_ofertante = info.id_usuario_jogo_ofert)),0) as existe_transacao

				  from  (
						
						(select 1 as prioridade,

								ujs.id_interesse,
								js.descricao as descricao_jogo,
								ps.descricao as plataforma_jogo,
								js.foto as foto_jogo,
								ujs.id_usuario_jogo,
								uo.id_usuario as id_usuario_ofert,
								uo.nome as nome_ofert,
								jo.id_jogo as id_jogo_ofert,
								jo.descricao as descricao_jogo_ofert,
								po.descricao as plataforma_jogo_ofert,
								jo.foto as foto_jogo_ofert,
								null as preco_jogo_ofert,
								ujo.id_usuario_jogo as id_usuario_jogo_ofert
						  
						  from 	usuario_jogo ujs,
								usuario_jogo ujo,
								usuario us,
								usuario uo,
								jogo js,
								jogo jo,
								plataforma ps,
								plataforma po
								
						  where ujs.id_usuario = us.id_usuario
							and ujo.id_usuario = uo.id_usuario
							and ujs.id_jogo_troca = js.id_jogo
							and ujo.id_jogo = jo.id_jogo
							and ujs.id_plataforma_troca = ps.id_plataforma
							and ujo.id_plataforma = po.id_plataforma
						  
							and ujs.id_interesse = 3
							and ujo.id_interesse = 1
							
							and ujs.id_jogo = ujo.id_jogo
							and ujs.id_plataforma = ujo.id_plataforma	
							
							and ujs.id_jogo_troca = ujo.id_jogo_troca
							and ujs.id_plataforma_troca = ujo.id_plataforma_troca
							
							and (ifnull(ujs.id_estado_jogo, us.id_estado_jogo) = ujo.id_estado_jogo
							or	(ujs.id_estado_jogo is null
							and us.id_estado_jogo is null
							and ujo.id_estado_jogo is not null) 
							or (ujs.id_estado_jogo is null
							and ujo.id_estado_jogo is null))
							
							and ujs.id_usuario = $id_usuario
							and ujs.ativo = 1
							and ujo.ativo = 1
							
						  order by ujo.id_estado_jogo desc)
							
						union

						(select 3 as prioridade,

								ujs.id_interesse,
								null as descricao_jogo,
								null as plataforma_jogo,
								null as foto_jogo,
								ujs.id_usuario_jogo,
								uo.id_usuario as id_usuario_ofert,
								uo.nome as nome_ofert,
								jo.id_jogo as id_jogo_ofert,
								jo.descricao as descricao_jogo_ofert,
								po.descricao as plataforma_jogo_ofert,
								jo.foto as foto_jogo_ofert,
								null as preco_jogo_ofert,
								ujo.id_usuario_jogo as id_usuario_jogo_ofert
						  
						  from 	usuario_jogo ujs,
								usuario_jogo ujo,
								usuario us,
								usuario uo,
								jogo jo,
								plataforma po
								
						  where ujs.id_usuario = us.id_usuario
							and ujo.id_usuario = uo.id_usuario
							and ujo.id_jogo = jo.id_jogo
							and ujo.id_plataforma = po.id_plataforma
						  
							and ujs.id_interesse = 3
							and ujo.id_interesse = 1
							
							and ujs.id_jogo = ujo.id_jogo
							and ujs.id_plataforma = ujo.id_plataforma	
							
							and ujs.id_jogo_troca is null
							and ujo.id_jogo_troca is null
							
							and (ifnull(ujs.id_estado_jogo, us.id_estado_jogo) = ujo.id_estado_jogo
							or	(ujs.id_estado_jogo is null
							and us.id_estado_jogo is null
							and ujo.id_estado_jogo is not null) 
							or (ujs.id_estado_jogo is null
							and ujo.id_estado_jogo is null))
							
							and ujs.id_usuario = $id_usuario
							and ujs.ativo = 1
							and ujo.ativo = 1
							
						  order by ujo.id_estado_jogo desc)
						  
						union

						(select 5 as prioridade,

								ujs.id_interesse,
								null as descricao_jogo,
								null as plataforma_jogo,
								null as foto_jogo,
								ujs.id_usuario_jogo,
								uo.id_usuario as id_usuario_ofert,
								uo.nome as nome_ofert,
								jo.id_jogo as id_jogo_ofert,
								jo.descricao as descricao_jogo_ofert,
								po.descricao as plataforma_jogo_ofert,
								jo.foto as foto_jogo_ofert,
								null as preco_jogo_ofert,
								ujo.id_usuario_jogo as id_usuario_jogo_ofert
						  
						  from 	usuario_jogo ujs,
								usuario_jogo ujo,
								usuario us,
								usuario uo,
								jogo js,
								jogo jo,
								plataforma ps,
								plataforma po
								
						  where ujs.id_usuario = us.id_usuario
							and ujo.id_usuario = uo.id_usuario
							and ujo.id_jogo = jo.id_jogo
							and ujo.id_plataforma = po.id_plataforma
						  
							and ujs.id_interesse = 3
							and ujo.id_interesse = 1
							
							and ujs.id_jogo = ujo.id_jogo
							and ujs.id_plataforma = ujo.id_plataforma	
							
							and ujs.id_jogo_troca is null
							and ujo.id_jogo_troca is not null
							
							and (ifnull(ujs.id_estado_jogo, us.id_estado_jogo) = ujo.id_estado_jogo
							or	(ujs.id_estado_jogo is null
							and us.id_estado_jogo is null
							and ujo.id_estado_jogo is not null) 
							or (ujs.id_estado_jogo is null
							and ujo.id_estado_jogo is null))
							
							and ujs.id_usuario = $id_usuario
							and ujs.ativo = 1
							and ujo.ativo = 1
							
						  order by ujo.id_estado_jogo desc) 
						
						union

						(select 7 as prioridade,

								ujs.id_interesse,
								js.descricao as descricao_jogo,
								ps.descricao as plataforma_jogo,
								js.foto as foto_jogo,
								ujs.id_usuario_jogo,
								uo.id_usuario as id_usuario_ofert,
								uo.nome as nome_ofert,
								jo.id_jogo as id_jogo_ofert,
								jo.descricao as descricao_jogo_ofert,
								po.descricao as plataforma_jogo_ofert,
								jo.foto as foto_jogo_ofert,
								null as preco_jogo_ofert,
								ujo.id_usuario_jogo as id_usuario_jogo_ofert
						  
						  from 	usuario_jogo ujs,
								usuario_jogo ujo,
								usuario us,
								usuario uo,
								jogo js,
								jogo jo,
								plataforma ps,
								plataforma po
								
						  where ujs.id_usuario = us.id_usuario
							and ujo.id_usuario = uo.id_usuario
							and ujs.id_jogo_troca = js.id_jogo
							and ujo.id_jogo = jo.id_jogo
							and ujs.id_plataforma_troca = ps.id_plataforma
							and ujo.id_plataforma = po.id_plataforma
						  
							and ujs.id_interesse = 3
							and ujo.id_interesse = 1
							
							and ujs.id_jogo = ujo.id_jogo
							and ujs.id_plataforma = ujo.id_plataforma	
							
							and ujs.id_jogo_troca is not null
							and ujo.id_jogo_troca is null
							
							and (ifnull(ujs.id_estado_jogo, us.id_estado_jogo) = ujo.id_estado_jogo
							or	(ujs.id_estado_jogo is null
							and us.id_estado_jogo is null
							and ujo.id_estado_jogo is not null) 
							or (ujs.id_estado_jogo is null
							and ujo.id_estado_jogo is null))
							
							and ujs.id_usuario = $id_usuario
							and ujs.ativo = 1
							and ujo.ativo = 1
							
						  order by ujo.id_estado_jogo desc)
				  
						union  
				  
						(select 2 as prioridade,

								ujs.id_interesse,
								null as descricao_jogo,
								null as plataforma_jogo,
								null as foto_jogo,
								ujs.id_usuario_jogo,
								uo.id_usuario as id_usuario_ofert,
								uo.nome as nome_ofert,
								jo.id_jogo as id_jogo_ofert,
								jo.descricao as descricao_jogo_ofert,
								po.descricao as plataforma_jogo_ofert,
								jo.foto as foto_jogo_ofert,
								ujo.preco as preco_jogo_ofert,
								ujo.id_usuario_jogo as id_usuario_jogo_ofert
						  
						  from 	usuario_jogo ujs,
								usuario_jogo ujo,
								usuario us,
								usuario uo,
								jogo js,
								jogo jo,
								plataforma ps,
								plataforma po
								
						  where ujs.id_usuario = us.id_usuario
							and ujo.id_usuario = uo.id_usuario
							and ujs.id_jogo = js.id_jogo
							and ujo.id_jogo = jo.id_jogo
							and ujs.id_plataforma = ps.id_plataforma
							and ujo.id_plataforma = po.id_plataforma
						  
							and ujs.id_interesse = 4
							and ujo.id_interesse = 2
							
							and ujs.id_jogo = ujo.id_jogo
							and ujs.id_plataforma = ujo.id_plataforma	
							and ujo.preco between ujs.preco_inicial and ujs.preco_final
							
							and ifnull(ujs.id_estado_jogo, us.id_estado_jogo) = ujo.id_estado_jogo
							
							and ujs.id_usuario = $id_usuario
							and ujs.ativo = 1
							and ujo.ativo = 1
							
						  order by ujo.id_estado_jogo desc, ujo.preco)
							
						union

						(select 4 as prioridade,

								ujs.id_interesse,
								null as descricao_jogo,
								null as plataforma_jogo,
								null as foto_jogo,
								ujs.id_usuario_jogo,
								uo.id_usuario as id_usuario_ofert,
								uo.nome as nome_ofert,
								jo.id_jogo as id_jogo_ofert,
								jo.descricao as descricao_jogo_ofert,
								po.descricao as plataforma_jogo_ofert,
								jo.foto as foto_jogo_ofert,
								ujo.preco as preco_jogo_ofert,
								ujo.id_usuario_jogo as id_usuario_jogo_ofert
						  
						  from 	usuario_jogo ujs,
								usuario_jogo ujo,
								usuario us,
								usuario uo,
								jogo js,
								jogo jo,
								plataforma ps,
								plataforma po
								
						  where ujs.id_usuario = us.id_usuario
							and ujo.id_usuario = uo.id_usuario
							and ujs.id_jogo = js.id_jogo
							and ujo.id_jogo = jo.id_jogo
							and ujs.id_plataforma = ps.id_plataforma
							and ujo.id_plataforma = po.id_plataforma
							
							and ujs.id_interesse = 4
							and ujo.id_interesse = 2
							
							and ujs.id_jogo = ujo.id_jogo
							and ujs.id_plataforma = ujo.id_plataforma	
							and ujo.preco between ujs.preco_inicial and ujs.preco_final
							
							and ujs.id_estado_jogo is null
							and us.id_estado_jogo is null
							and ujo.id_estado_jogo is not null
							
							and ujs.id_usuario = $id_usuario
							and ujs.ativo = 1
							and ujo.ativo = 1
							
						  order by ujo.id_estado_jogo desc, ujo.preco)
						  
						union

						(select 6 as prioridade,

								ujs.id_interesse,
								null as descricao_jogo,
								null as plataforma_jogo,
								null as foto_jogo,
								ujs.id_usuario_jogo,
								uo.id_usuario as id_usuario_ofert,
								uo.nome as nome_ofert,
								jo.id_jogo as id_jogo_ofert,
								jo.descricao as descricao_jogo_ofert,
								po.descricao as plataforma_jogo_ofert,
								jo.foto as foto_jogo_ofert,
								ujo.preco as preco_jogo_ofert,
								ujo.id_usuario_jogo as id_usuario_jogo_ofert
						  
						  from 	usuario_jogo ujs,
								usuario_jogo ujo,
								usuario us,
								usuario uo,
								jogo js,
								jogo jo,
								plataforma ps,
								plataforma po
								
						  where ujs.id_usuario = us.id_usuario
							and ujo.id_usuario = uo.id_usuario
							and ujs.id_jogo = js.id_jogo
							and ujo.id_jogo = jo.id_jogo
							and ujs.id_plataforma = ps.id_plataforma
							and ujo.id_plataforma = po.id_plataforma
						  
							and ujs.id_interesse = 4
							and ujo.id_interesse = 2
							
							and ujs.id_jogo = ujo.id_jogo
							and ujs.id_plataforma = ujo.id_plataforma	
							and ujo.preco between ujs.preco_inicial and ujs.preco_final
							
							and ujs.id_estado_jogo is null
							and ujo.id_estado_jogo is null
							
							and ujs.id_usuario = $id_usuario
							and ujs.ativo = 1
							and ujo.ativo = 1
							
						  order by ujo.preco)
						
						) as info
						  
				  where not exists (select 1
									  from transacao t
									 where (t.id_usuario_jogo_solicitante = info.id_usuario_jogo and t.id_usuario_jogo_ofertante = info.id_usuario_jogo_ofert)
									   and t.id_estado_transacao <> 1)
				  order by prioridade";
		
		$stmt = $this->conn->prepare($sql);
		$stmt->execute();
		$oport = $stmt->get_result();
        $stmt->close();
		
		$response = array();
		            
		while ($o = $oport->fetch_assoc()) {
		
			$op = array();
			$op["existe_transacao"] 	= $o["existe_transacao"];
			$op["id_interesse"] 		= $o["id_interesse"];
			$op["descricao_jogo"] 		= $o["descricao_jogo"];
			$op["plataforma_jogo"] 		= $o["plataforma_jogo"];
			$op["foto_jogo"] 			= base64_encode($o["foto_jogo"]);
			$op["id_usuario_jogo"] 		= $o["id_usuario_jogo"];
			$op["id_usuario_ofert"] 	= $o["id_usuario_ofert"];
            $op["nome_ofert"] 			= $o["nome_ofert"];
            $op["id_jogo_ofert"] 		= $o["id_jogo_ofert"];
			$op["descricao_jogo_ofert"] = $o["descricao_jogo_ofert"];
			$op["plataforma_jogo_ofert"]= $o["plataforma_jogo_ofert"];
            $op["foto_jogo_ofert"] 		= base64_encode($o["foto_jogo_ofert"]);
			$op["preco_jogo_ofert"] 	= $o["preco_jogo_ofert"];
			$op["id_usuario_jogo_ofert"]= $o["id_usuario_jogo_ofert"];
			array_push($response, $op);
        }
		
		return count($response)>0?$response:null;
    }	
	
	
	public function getTransacaoByUser($id_usuario) {
        $stmt = $this->conn->prepare("SELECT count(id_transacao) as qtd FROM transacao WHERE id_usuario_jogo_ofertante in (SELECT id_usuario_jogo FROM usuario_jogo WHERE id_usuario = ? ) AND id_estado_transacao=1");
        $stmt->bind_param("i", $id_usuario);
        if ($stmt->execute()) {
            $user = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return $user["qtd"]>0;
        } else {
            return NULL;
        }
    }
	
    /**
     * Validating user api key
     * If the api key is there in db, it is a valid key
     * @param String $api_key user api key
     * @return boolean
     */
    public function isValidApiKey($api_key) {
        $stmt = $this->conn->prepare("SELECT id_usuario from usuario WHERE chave_api = ?");
        $stmt->bind_param("s", $api_key);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
    }

    /**
     * Generating random Unique MD5 String for user Api key
     */
    private function generateApiKey() {
        return md5(uniqid(rand(), true));
    }
}

?>
