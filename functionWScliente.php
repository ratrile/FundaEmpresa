<?php
// Firebase
define('URL_Firebase', 'https://firestore.googleapis.com/v1beta1/projects/petoapp/databases/(default)/documents/solicitud/');
//ruta cargar imagenes cliente
define('RUTAcliente','https://demo.reggat.com/assets/cliente/');
// define('RUTANoticias','https://demo.reggat.com/assets/noticias/');
// define('RUTA_IMGcategoria','https://demo.reggat.com/assets/noticias/');
// define('RUTA_IMGproveedor','https://demo.reggat.com/assets/noticias/');
// define('RUTA_IMGproducto','https://demo.reggat.com/assets/productos/');


// define('RUTANoticias','http://localhost:8888/proyectosGIT/reggat-app/src/assets/noticias/');
// define('RUTA_IMGproducto','http://localhost:8888/proyectosGIT/reggat-app/src/assets/productos/');

//DEMO TEMPORAL
define('RUTANoticias','https://demoweb.reggat.com/assets/noticias/');
define('RUTA_IMGproducto','https://demoweb.reggat.com/assets/productos/');
define('RUTA_IMGcategoria','https://demoweb.reggat.com/assets/categoria/');
define('RUTA_IMGproveedor','https://demoweb.reggat.com/assets/proveedor/');



// RE01CLI - POST - INSERTAR USUARIO
$app->post('/postRegistrarCliente', function ($request, $response) {

  try{
    $data = $request->getParsedBody();
    $vacio = '';
    if(!existeCorreoCliente($data['correo'])){
        $db = getConnection();
        $sql = "INSERT INTO cliente
        (nombre, correo, password,
        telefono, recuperarPass,estado,
        tipoRegistro, idCiudad,ci)

        VALUES (:nombre, :correo, :password,  :telefono,
          0, 1,
         :tipoRegistro, 1,:ci)";
        $stmt = $db->prepare($sql);
        $stmt ->bindParam('nombre', $data['nombre']);
        $stmt ->bindParam('correo', $data['correo']);
        $pw = hash_hmac('sha512', 'salt' . $data['password'], 92432);
        $stmt ->bindParam('password', $pw);
        $stmt ->bindParam('telefono', $data['telefono']);
        $stmt ->bindParam('tipoRegistro', $data['tipoRegistro']);
        $stmt ->bindParam('ci', $vacio);
        $stmt->execute();
        $id= $db->lastInsertId();
        $data['token']= setTokenCliente($id);
        $data['foto']=RUTAcliente."user.png";
        $data['idCliente']=$id;
        $data['nit']="";
        $data['razonSocial']="";
        $data['recuperarPass']="0";
        if ( $stmt->rowCount() > 0 ) {
            // getcorreopass($data['correo'],$data['nombre'],'0','goboxcorreobienvenido.php');
          //  $data['cuenta']= insertarcuentacliente($id);
            echo ' {
                "errorCode": 0,
                "errorMessage": "Servicio ejecutado con éxito",
                "msg": '.json_encode( $data).'

            }';
        }
        $db = null;
    }else{
        echo ' {
            "errorCode": 2,
            "errorMessage": "Correo existente.",
            "msg": 0
        }';
    }

  } catch(PDOException $e){
      $db = null;
      echo $e;
          echo ' {
              "errorCode": 3,
              "errorMessage": "Error al ejecutar el servicio web.",
              "msg": 0
          }';
  }
});

function setTokenCliente($id){
	try {
		$db = getConnection();
		$sql   = "UPDATE cliente SET token=:token WHERE idCliente=:idCliente;";
		$stmt = $db->prepare($sql);
		$stmt->bindParam("idCliente", $id);
		$fecha = date("Y-m-d H:i:s");
		$token = sha1($fecha.$id);
		$stmt->bindParam("token", $token);
		$stmt->execute();
		if ( $stmt->rowCount() > 0 ) {
			return $token;
		}else{
			return false;
		}
		$db = null;
	} catch (PDOException $e){
		return false;
	}
}

function existeCorreoCliente($corre){
  try{
      $sql = "SELECT idCliente FROM cliente where correo=:correo; ";
      $db   = getConnection();
      $stmt = $db->prepare($sql);
      $stmt->bindParam('correo',$corre);
      $stmt->execute();

      if ( $stmt->rowCount() > 0 ) {

          return true;
      }else{
          return false;
      }
  $db = null;
  } catch(PDOException $e){
  $db = null;
  echo '{
                  "errorCode": 3,
                  "errorMessage": "Error al ejecutar el servicio web.",
                  "msg": "'.$e.'"
          }';

      }
}

function getValidateCliente($id,$token){
  if($id == 0 && $token == 0) {
      return true;
  } else {
      $sql = "SELECT idCliente FROM cliente WHERE idCliente=:idCliente AND token=:token;";
      try{
          $db   = getConnection();
          $stmt = $db->prepare($sql);
          $stmt->bindParam("idCliente",$id);
          $stmt->bindParam("token",$token);
          $stmt->execute();
          if ( $stmt->rowCount() > 0 ) {
              return true;
          }else{
              return false;
          }
          $db = null;
      }catch(PDOException $e){
          return false;
      }
  }
}

function getRangoBusqueda() {
  try{
    // $validate = getValidateUsuario($idtoken,$token);
    $validate=true;
    if($validate) {
      $sql = "SELECT valor
              FROM cms_configuracion
              WHERE idConfiguracion=1";
      $db = getConnection();
      $stmt = $db->prepare($sql);
      $stmt->execute();
      $i = 0;
      if ( $stmt->rowCount() > 0 ) {
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)){
          $array = trim($data['valor']);
          $i++;
        }
        return $array;
      }else{
        return false;
    }
    $db = null;
    } else {
      echo ' {
          "errorCode": 4,
          "errorMessage": "No autenticado.",
          "msg": 0
          }';
    }
  } catch(PDOException $e){
    $db = null;
    echo $e;
    echo ' {
        "errorCode": 3,
        "errorMessage": "Error al ejecutar el servicio web.",
        "msg": 0
        }';
    }
}

// RE02CLI - POST - LOGIN
$app->post('/postLogin', function ($request, $response) {
  
  try {
    $data = $request->getParsedBody();
    $db = getConnection();
    $i=0;
    $recuperarPass=0;
        $sql   = "SELECT idCliente,nombre,correo,telefono,foto,
        recuperarPass,password
        FROM `cliente`
        WHERE correo=:correo AND password=:password;";

        $stmt = $db->prepare($sql);
        $stmt	->bindParam("correo", $data['correo']);
        $pw = hash_hmac('sha512', 'salt' . $data['password'], 92432);
        $stmt->bindParam("password", $pw);
        $stmt->execute();
        if ( $stmt->rowCount() > 0 ) {
            while ($data1  = $stmt->fetch(PDO::FETCH_ASSOC)){
                $id                         =trim($data1['idCliente']);
                $array['idCliente']   	= trim($data1['idCliente']);
                $array['nombre'] 		= trim($data1['nombre']);
                $array['correo'] 		= trim($data1['correo']);
                $array['telefono'] 		= trim($data1['telefono']);
                $token 			        = setTokenCliente($id);
                $array['token']         = trim($token);
                $foto=explode("://", $data1['foto']);
                if($foto[0]=="https"){
                    $array['foto']          = $data1['foto'];
                }else{
                    $array['foto']          = trim(RUTAcliente.$data1['foto']);

                }
                $array['recuperarPass'] = trim($data1['recuperarPass']);
                // $array['password']      = trim($data1['password']);
                // $array['cuenta']        =getcuentaclienteone($id);
                $recuperarPass          = trim($data1['recuperarPass']);
                $i++;
            }
            if($recuperarPass == 0) {
                echo  ' {
                    "errorCode": 0,
                    "errorMessage": "Login Exitoso. ",

                    "msg": '.json_encode($array).'
                    }';
            } else {
                echo  ' {
                    "errorCode": 5,
                    "errorMessage": "Bandera Recuperar password activa",
                    "msg": '.json_encode($array).'
                    }';
            }

        }else{
            echo  ' {
                "errorCode": 2,
                "errorMessage": "Error en Clave o correo.",
                "msg": ""
                }';

        }
    $db = null;

  } catch (PDOException $e){
      $db = null;
      echo  	'{
                  "errorCode": 3,
                  "errorMessage": "Error al ejecutar el servicio web.",
                  "msg": "'.$e->getMessage() .'"
              }';
  }
  
});

// RE03 CLI - POST - DIRECCION USUARIO 
$app->post('/postDireccionCliente', function ($request, $response) {
  try{

    $data = $request->getParsedBody();
    $db = getConnection();
    $sql = "INSERT INTO direccion
    (nombre, detalle, longitud, latitud,idCliente,estado)

    VALUES (:nombre, :detalle, :longitud, :latitud,:idCliente,1)";
    $stmt = $db->prepare($sql);
    $stmt ->bindParam('nombre',     $data['nombre']);
    $stmt ->bindParam('detalle',    $data['detalle']);
    $stmt ->bindParam('longitud',   $data['longitud']);
    $stmt ->bindParam('latitud',    $data['latitud']);
    $stmt ->bindParam('idCliente',  $data['idCliente']);
    $stmt->execute();
    $id=$db->lastInsertId();
    $data['idDireccion']= $id;
    if ( $stmt->rowCount() > 0 ) {

        echo ' {
            "errorCode": 0,
            "errorMessage": "Servicio ejecutado con éxito",

            "msg":'.json_encode($data).'
        }';
    }else{
        echo ' {
            "errorCode": 2,
            "errorMessage": "No hay datos.",
            "msg": 0
        }';
    }
    $db = null;
  } catch(PDOException $e){
      $db = null;
      echo $e;
          echo ' {
              "errorCode": 3,
              "errorMessage": "Error al ejecutar el servicio web.",
              "msg": 0
          }';
  }
 
});

// RE04 CLI - GET - DIRECCION cliente 
$app->get('/getDireccionCliente/{idCliente}/{token}', function ($request, $response, $data) {
  try{
    $validate 	= getValidateCliente($data['idCliente'],$data['token']);
    $validate = true;
    if($validate){
        $sql = "SELECT idDireccion, nombre,detalle,latitud,longitud,idCliente
                FROM direccion
                WHERE idCliente=:id; ";
        $db   = getConnection();
        $stmt = $db->prepare($sql);
        $stmt->bindParam("id",$data['idCliente']);
        $stmt->execute();
        $i = 0;
        if ( $stmt->rowCount() > 0 ) {
            while ($data  = $stmt->fetch(PDO::FETCH_ASSOC)){
                $array[$i]['idDireccion']   = trim($data['idDireccion']);
                $array[$i]['nombre'] 		= trim($data['nombre']);
                $array[$i]['detalle'] 		= trim($data['detalle']);
                $array[$i]['longitud'] 		= trim($data['longitud']);
                $array[$i]['latitud'] 		= trim($data['latitud']);
                $array[$i]['idCliente']     = trim($data['idCliente']);
                $i++;
            }
            echo '{
                    "errorCode": 0,
                    "errorMessage": "Direcciones cliente.",
                    "msg": '.json_encode($array).'
                  }';

        }else{
            echo '{
                    "errorCode": 2,
                    "errorMessage": "No hay datos.",
                    "msg": "0"
                  }';
        }
    } else {
        echo '{
                "errorCode": 4,
                "errorMessage": "No autenticado.",
                "msg": 0
              }';
    }
    $db = null;
  } catch(PDOException $e){
  $db = null;
  echo '{
          "errorCode": 3,
          "errorMessage": "Error al ejecutar el servicio web.",
          "msg": "'.$e.'"
        }';
  }
});

// RE05 CLI - GET - Noticias
$app->get('/getnoticias/{lat}/{lng}', function ($request, $response,$data) {
  try{
    $lat = $data['lat'];
    $lng = $data['lng'];
    $rango = getRangoBusqueda();
    $sql = "SELECT n.idNoticias,n.nombre,n.detalle,n.idTipoNoticia,n.fechaReg,n.idPro,n.idSucursal,n.estado,
            n.fechaIni,n.fechaFin,n.foto,n.idSucursal, s.idProveedor
            FROM cms_noticias n
            JOIN sucursal s ON s.idSucursal=n.idSucursal
            WHERE n.estado=1 AND
            (acos(sin(radians(s.latitud)) * sin(radians(:lat)) +
            cos(radians(s.latitud)) * cos(radians(:lat)) *
            cos(radians(s.longitud) - radians(:lng))) * 6378)<=:rango
            ORDER BY n.fechaIni DESC
            LIMIT 6";
    $db   = getConnection();
    $stmt = $db->prepare($sql);
    $stmt ->bindParam('rango', $rango);
    $stmt ->bindParam('lat', $lat);
    $stmt ->bindParam('lng', $lng);
    $stmt->execute();
    $i = 0;
    if ( $stmt->rowCount() > 0 ) {
        while ($data  = $stmt->fetch(PDO::FETCH_ASSOC)){
            $array[$i]['idNoticias']    = trim($data['idNoticias']);
            $array[$i]['nombre']        = trim($data['nombre']);
            $array[$i]['detalle']       = trim($data['detalle']);
            $array[$i]['idTipoNoticia'] = trim($data['idTipoNoticia']);
            $array[$i]['fechaReg']      = trim($data['fechaReg']);
            $array[$i]['idProducto']         = trim($data['idPro']);
            $array[$i]['idSucursal']    = trim($data['idSucursal']);
            $array[$i]['idProveedor']    = trim($data['idProveedor']);
            $array[$i]['estado']        = trim($data['estado']);
            $array[$i]['fechaIni']      = trim($data['fechaIni']);
            $array[$i]['fechaFin']      = trim($data['fechaFin']);
            $array[$i]['foto']          = trim(RUTANoticias.$data['foto']);
            // $array[$i]['horarios']      = funcionHorarioSurcursal($data['idSucursal']);
            if($data['idTipoNoticia']==1){
                // $array[$i]['detallepro']       = funcionGetproveedor($data['idPro'],$data['idSucursal']);
            }else{
                // $array[$i]['detallepro']       = funcionproductos($data['idPro'],$data['idSucursal']);
            }
            $i++;
        }
        echo  '{
                "errorCode": 0,
                "errorMessage": "Servicio Noticias ejecutado correctamente ",
                "msg": '.json_encode($array).'
               }';
    }else{
        echo '{
                "errorCode": 2,
                "errorMessage": "No hay datos.",
                "msg": "0"
              }';
    }
    $db = null;
  } catch(PDOException $e){
      $db = null;
      echo '{
              "errorCode": 3,
              "errorMessage": "Error al ejecutar el servicio web.",
              "msg": "'.$e.'"
            }';

  }

});

// RE06 CLI - GET - getCategorias
$app->get('/getCategorias', function ($request, $response,$data) {
  try{
    $sql = "SELECT idCategoria,nombre,foto
            FROM categoria 
            WHERE estado=1";
    $db   = getConnection();
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $i = 0;
    if ( $stmt->rowCount() > 0 ) {
        while ($data  = $stmt->fetch(PDO::FETCH_ASSOC)){
            $id                            = trim($data['idCategoria']);
            $array[$i]['idCategoria']   = trim($data['idCategoria']);
            $array[$i]['nombre'] 		= trim($data['nombre']);
            $array[$i]['imagen'] 		    = trim(RUTA_IMGcategoria.$data['foto']);
            // $array[$i]['tipo']          =getcategoriaTipocliente($id);
            $i++;
        }
        echo '{
                "errorCode": 0,
                "errorMessage": "Categorias. ",
                "msg": '.json_encode($array).'
              }';
    }else{
        echo '{
                "errorCode": 2,
                "errorMessage": "No hay datos.",
                "msg": "0"
              }';
    }
    $db = null;
  } catch(PDOException $e){
  $db = null;
      echo '{
              "errorCode": 3,
              "errorMessage": "Error al ejecutar el servicio web.",
              "msg": "'.$e.'"
            }';
  }

});

// RE07 CLI - POST - COMERCIOS
$app->post('/postListaComercios', function ($request, $response) {
  $data1 = $request->getParsedBody();
  try{
    $limite = (int) $data1['limite'];
    $posicion = (int) $data1['posicion'];
    $rango = getRangoBusqueda();
    $sql = "SELECT s.idProveedor, p.nombre, p.foto,
              round(min(acos(sin(radians(s.latitud)) * sin(radians(:lat)) +
            cos(radians(s.latitud)) * cos(radians(:lat)) *
            cos(radians(s.longitud) - radians(:lng))) * 6378),2) AS distancia 
            FROM sucursal s join proveedor p
            on s.idProveedor = p.idProveedor
            WHERE s.estado=1 and p.estado = 1 and  
            (acos(sin(radians(s.latitud)) * sin(radians(:lat)) +
            cos(radians(s.latitud)) * cos(radians(:lat)) *
            cos(radians(s.longitud) - radians(:lng))) * 6378)<=:rango
            GROUP BY s.idProveedor
            ORDER BY distancia ASC 
            LIMIT :limite offset :posicion";
    
    $db   = getConnection();
    $stmt = $db->prepare($sql);
    $stmt->bindParam("lat",$data1['latitud']);
    $stmt->bindParam("lng",$data1['longitud']);
    $stmt ->bindParam('rango', $rango);
    $stmt ->bindParam('limite', $limite , PDO::PARAM_INT);
    $stmt ->bindParam('posicion', $posicion , PDO::PARAM_INT);
    $stmt->execute();
    $i = 0;
    if ( $stmt->rowCount() > 0 ) {
        while ($data  = $stmt->fetch(PDO::FETCH_ASSOC)){
            $id                         =trim($data['idProveedor']);
            $array[$i]['idComercio'] = trim($data['idProveedor']);
            $array[$i]['nombreComercio'] = trim($data['nombre']);
            // $array[$i]['estado'] = trim($data['estado']);
            // $array[$i]['idSucursal'] = trim($data['idSucursal']);
            // $array[$i]['nombreSucursal'] = trim($data['nombresucursal']);
            // $array[$i]['direccion'] = trim($data['direccion']);
            // $array[$i]['latitud'] = trim($data['latitud']);
            // $array[$i]['longitud'] = trim($data['longitud']);
            $array[$i]['distancia'] = trim($data['distancia']);
            $array[$i]['foto'] = trim(RUTA_IMGproveedor.$data['foto']);
            // $array[$i]['horarios']          =funcionHorarioSurcursal($data['idSucursal']);
            $i++;
        }
        echo  '{
                "errorCode": 0,
                "errorMessage": "Distancia sucursal.",
                "msg": '.json_encode($array).'
               }';
    }else{
        echo '{
                "errorCode": 2,
                "errorMessage": "No hay datos.",
                "msg": "0"
              }';
    }

  $db = null;
  } catch(PDOException $e){
      $db = null;
      echo '{
            "errorCode": 3,
            "errorMessage": "Error al ejecutar el servicio web.",
            "msg": "'.$e.'"
          }';
    }
});

// RE08 CLI - GET - Producto MEs 
$app->get('/getProductoMes/{lat}/{lng}/{idtoken}/{token}', function ($request, $response,$data) {
  
  $validate = getValidateCliente($data['idtoken'], $data['token']);

  $lat = $data['lat'];
  $lng = $data['lng'];
  
  if($validate) {
     try{
       if($lat == 0 && $lng == 0) {
         $rango = 0;
         $comp = '>';
       } else {
        $rango = getRangoBusqueda();
        $comp = '<=';
       }
      //  $sql = 'SELECT p.idProducto,p.nombre,p.detalle,p.peso,p.fecha,s.idSucursal,
      //  p.idSubcategoria,t.nombre as subcategoria,p.descuentoPromo,p.idProveedor,pr.nombre as proveedor,sp.precio,
      //  p.foto as fotoP,
      //  (acos(sin(radians(s.latitud)) * sin(radians(:lat)) + 
      //       cos(radians(s.latitud)) * cos(radians(:lat)) * 
      //       cos(radians(s.longitud) - radians(:lng))) * 6378) AS distancia ,sp.precio
      //  FROM producto p,subcategoria t,proveedor pr,cms_productomes pm,productoSucursal sp,sucursal s, productoVariante pv
      //  WHERE p.idSubcategoria=t.idSubcategoria AND p.estado=1 and sp.estado=1 and pr.estado=1
      //  AND p.idProducto=pm.idProducto and s.estado=1
      //  AND CURDATE() BETWEEN pm.fechaInicio AND pm.fechaFin AND p.idProveedor=pr.idProveedor
      //  AND p.idProducto=sp.idProducto and pm.idSucursal=s.idSucursal
      //  AND sp.idSucursal=s.idSucursal AND s.idProveedor=p.idProveedor AND 
      //  (acos(sin(radians(s.latitud)) * sin(radians(:lat)) + 
      //   cos(radians(s.latitud)) * cos(radians(:lat)) * 
      //   cos(radians(s.longitud) - radians(:lng))) * 6378)'.$comp.':rango
      //  order by  RAND() , distancia asc  limit 8;';

       $sql = 'SELECT pm.idProductoMes,pm.idProducto, pm.idSucursal, p.nombre, p.detalle, p.descuentoPromo , p.foto , s.idProveedor,
       (acos(sin(radians(s.latitud)) * sin(radians(:lat)) + 
            cos(radians(s.latitud)) * cos(radians(:lat)) * 
            cos(radians(s.longitud) - radians(:lng))) * 6378) AS distancia 
        FROM cms_productomes pm LEFT join producto p ON pm.idProducto=p.idProducto
        JOIN sucursal s ON s.idSucursal = pm.idSucursal
        JOIN proveedor pr ON pr.idProveedor = s.idProveedor 
        WHERE s.estado=1 AND pr.estado = 1 
        AND CURDATE() BETWEEN pm.fechaInicio AND pm.fechaFin AND
        (acos(sin(radians(s.latitud)) * sin(radians(:lat)) + 
         cos(radians(s.latitud)) * cos(radians(:lat)) * 
         cos(radians(s.longitud) - radians(:lng))) * 6378)'.$comp.':rango
        order by  RAND() , distancia asc  limit 8;';
         $db = getConnection();
         $stmt = $db->prepare($sql);
         $stmt->bindParam("lat",$lat);
         $stmt->bindParam("lng",$lng);
         $stmt->bindParam("rango",$rango);
         $stmt->execute();
         $i = 0;
         if ( $stmt->rowCount() > 0 ) {
            while($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $array[$i]['idProducto'] = trim($data['idProducto']);
            $array[$i]['nombre'] = trim($data['nombre']);
            $array[$i]['detalle'] = trim($data['detalle']);
            // $array[$i]['peso'] = trim($data['peso']);
            $array[$i]['precio'] = getPrecioMinimo($data['idProducto']);
            // $array[$i]['fecha'] = trim($data['fecha']);
            $array[$i]['descuentoPromo'] = trim($data['descuentoPromo']);
            $array[$i]['idSucursal'] = trim($data['idSucursal']);
            $array[$i]['foto']  =trim(RUTA_IMGproducto.$data['idProveedor'].'/'.$data['foto']);
            $i++;
            }
             echo ' {
                 "errorCode": 0,
                 "errorMessage": "Servicio ejecutado con éxito",
                 "msg": '.json_encode($array).'
             }';
         }else{
             echo ' {
                 "errorCode": 2,
                 "errorMessage": "No hay datos.",
                 "msg": 0
             }';
         }
         $db = null;
     } catch(PDOException $e){
         $db = null;
         echo $e;
             echo ' {
                 "errorCode": 3,
                 "errorMessage": "Error al ejecutar el servicio web.",
                 "msg": 0
             }';
     }
  } else {
     echo ' {
        "errorCode": 4,
        "errorMessage": "No autenticado.",
        "msg": 0
   }';
  }

});

function getPrecioMinimo($idproducto) {
  try{
      $sql = "SELECT min(ps.precio) as precio FROM productoSucursal ps JOIN productoVariante pv on ps.idProductoVariante = ps.idProductoVariante
      WHERE pv.idProducto = $idproducto GROUP BY pv.idProducto";
      $db = getConnection();
      $stmt = $db->prepare($sql);
      $stmt->execute();
      if ( $stmt->rowCount() > 0 ) {
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)){
          $precio = trim($data['precio']);
          
        }
        return $precio;
      }else{
        return 0;
      }
    
  } catch(PDOException $e){
    $db = null;
    return -0;
  }
}

// RE09 CLI - GET - recuperarPassword
$app->get('/getRecuperarPassword/{correo}', function ($request, $response,$data) {

  try {
  
    $db = getConnection();
    $sql   = "SELECT idCliente from cliente where correo=:correo";
    $stmt = $db->prepare($sql);
    $stmt->bindParam("correo", $data['correo']);
    $stmt->execute();
    if($stmt->rowCount()>0){

        while ($data1 = $stmt->fetch(PDO::FETCH_ASSOC)){
            $id=    trim($data1['idCliente']);
            $envio = setolvidepasscliente($id,$data['correo']);

        }
        if ($envio){
          
          echo ' {
            "errorCode": 0,
            "errorMessage": "Servicio ejecutado con éxito",
            "msg": "Se envio Correo "
          }';

        }else{
          
          echo ' {
            "errorCode": 0,
            "errorMessage": "Servicio ejecutado con éxito",
            "msg": "No se envio Correo "
          }';
        }

    }else{
        echo ' {
            "errorCode": 2,
            "errorMessage": "No hay datos.",
            "msg": 0
        }';
    }
    $db = null;
  } catch (PDOException $e) {
      $db = null;
          echo $e;
              echo ' {
                  "errorCode": 3,
                  "errorMessage": "Error al ejecutar el servicio web.",
                  "msg": 0
              }';
  }
});

function setolvidepasscliente($id,$correo){
  $db = getConnection();
  $sql   = "UPDATE cliente SET password=:pass,recuperarPass=1 WHERE idCliente=:idCliente;";
  $stmt = $db->prepare($sql);
  $stmt->bindParam("idCliente", $id);
  $pass=generarCodigo(6);
  $pw = hash_hmac('sha512', 'salt' . $pass, 92432);
  $stmt->bindParam('pass',$pw);
  $stmt->execute();
  return getcorreopass($correo,$pass,'0','goboxcorreo.php');
}
function generarCodigo($longitud) {

  $permitted_chars = '123456789XYZ';
  
  return substr(str_shuffle($permitted_chars), 0, $longitud);
}

//enviarcorreo
function getcorreopass($correo, $pass, $asunto, $msj){
	
		$mensaje = file_get_contents("template/$msj");
    $mensaje = str_replace("{{nombre}}", utf8_decode($correo), $mensaje);
    $mensaje = str_replace("{{pass}}", utf8_decode($pass), $mensaje);
    
    $para = $correo;
		$asunto = 'Mensaje de Reggat  ';
		$mailPHP 	= new PHPMailer();
		$mailPHP->IsSMTP();
		$mailPHP->SMTPAuth = true;
		$mailPHP->SMTPSecure = "ssl";
		$mailPHP->Host = "mail.tuclinicapp.com";
		$mailPHP->Port = 465;
		$mailPHP->Username = "notificacion@tuclinicapp.com";
		$mailPHP->Password = 'tucl1n1c4pp123';
		$mailPHP->SetFrom('notificacion@goboxapp.com', '[Reggat] - Recuperar Clave');
		$mailPHP->Subject = ($asunto);
		$mailPHP->isHTML(true);
		$mailPHP->MsgHTML($mensaje);
		$mailPHP->AddAddress($para, $correo);
		if(!$mailPHP->Send()){
      // echo sprintf('%s', 400);
      return false;
		}else{
      return true;
			//getAvisoNuevoContactoAdmin($correo, $nombre, $asunto, $msj);
			//echo true;
		}
}

// RE10 CLI - POST - actulizarPassword
$app->post('/actualizarPassword', function ($request, $response) {
  $data = $request->getParsedBody();

  try{
    
        $db = getConnection();
        $sql = "UPDATE cliente SET recuperarPass=0, password = :password
                WHERE idCliente=:idCliente";
        $stmt = $db->prepare($sql);
        $stmt ->bindParam('idCliente', $data['idCliente']);
        $pw = hash_hmac('sha512', 'salt' . $data['password'], 92432);
        $stmt ->bindParam('password', $pw);
        
        $stmt->execute();
        if ( $stmt->rowCount() > 0 ) {
            echo ' {
                "errorCode": 0,
                "errorMessage": "Servicio ejecutado con éxito",
                "msg": "Modificado Correctamente "
            }';
        }else{
            echo ' {
                "errorCode": 2,
                "errorMessage": "No modifico ningun campo.",
                "msg": "No se realizo ninguna modificacion"
            }';
        }


  } catch(PDOException $e){
      $db = null;
      echo $e;
          echo ' {
              "errorCode": 3,
              "errorMessage": "Error al ejecutar el servicio web.",
              "msg": 0
          }';
  }

});

// RE11 CLI - GET - produtos en promocion
$app->get('/getProductoPromo/{idtoken}/{token}/{lat}/{lng}/{limite}/{pos}',function ($request, $response,$data) { 
  $validate = getValidateCliente($data['idtoken'],$data['token']);
  
  if($validate) {
       try{
          $limit2 = (int) $data['limite'];
          $pos2 = (int) $data['pos'];
           $rango = getRangoBusqueda();
          //  $sql = "SELECT p.idProducto,p.nombre,p.detalle,p.peso,p.fecha,
          //  p.foto,s.idSucursal,s.nombre as sucursal,
          //  (acos(sin(radians(s.latitud)) * sin(radians(:lat)) + 
          //       cos(radians(s.latitud)) * cos(radians(:lat)) * 
          //       cos(radians(s.longitud) - radians(:lng))) * 6378) as distancia,
          //  p.idSubcategoria,t.nombre as subcategoria,p.descuentoPromo,
          //  p.idProveedor,pr.nombre as proveedor,sp.precio, sp.precioPorMayor, sp.montoFree
          //  FROM producto p
          //  INNER JOIN subcategoria t ON t.idSubcategoria=p.idSubcategoria
          //  INNER JOIN proveedor pr ON pr.idProveedor=p.idProveedor
          //  INNER JOIN sucursal s ON s.idProveedor=p.idProveedor
          //  INNER JOIN sucursalproducto sp ON sp.idSucursal=s.idSucursal AND sp.idProducto=p.idProducto
          //  WHERE p.estado=1 AND sp.estado=1 AND pr.estado=1 AND s.estado=1 AND p.descuentoPromo>0 AND
          //  (acos(sin(radians(s.latitud)) * sin(radians(:lat)) + 
          //   cos(radians(s.latitud)) * cos(radians(:lat)) * 
          //   cos(radians(s.longitud) - radians(:lng))) * 6378)<=:rango
					//  ORDER BY distancia ASC
          //  LIMIT :limit offset :pos;";
           $sql = "SELECT p.nombre, p.detalle, p.foto, p.idProducto, p.idProveedor, ps.descuento 
                  FROM productoSucursal ps 
                  join productoVariante pv on pv.idProductoVariante = ps.idProductoVariante
                  join producto p on p.idProducto = pv.idProducto
                  JOIN sucursal s on s.idSucursal = ps.idSucursal
                  WHERE s.estado = 1 and ps.descuento = 0 AND
                  (acos(sin(radians(s.latitud)) * sin(radians(:lat)) + 
                  cos(radians(s.latitud)) * cos(radians(:lat)) * 
                  cos(radians(s.longitud) - radians(:lng))) * 6378)<=:rango
                  GROUP BY p.idProducto
                  LIMIT :limit offset :pos
           ";
           $db = getConnection();
           $stmt = $db->prepare($sql);
           $stmt->bindParam("lat",$data['lat']);
           $stmt->bindParam("lng",$data['lng']);
           $stmt->bindParam("rango",$rango);
           $stmt ->bindParam('limit', $limit2 , PDO::PARAM_INT);
           $stmt ->bindParam('pos', $pos2 , PDO::PARAM_INT);
           $stmt->execute();
           $i = 0;
           if ( $stmt->rowCount() > 0 ) {
              while($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
              $array[$i]['idProducto']    = trim($data['idProducto']);
              $array[$i]['nombre']        = trim($data['nombre']);
              $array[$i]['detalle']       = trim($data['detalle']);
              $array[$i]['precio'] = getPrecioMinimo($data['idProducto']);

              // $array[$i]['peso']          = trim($data['peso']);
              // $array[$i]['distancia']     = trim($data['distancia']);
              // $array[$i]['fecha']         = trim($data['fecha']);
              // $array[$i]['idSubcategoria'] = trim($data['idSubcategoria']);
              // $array[$i]['subcategoria']  = trim($data['subcategoria']);
              $array[$i]['descuento'] = trim($data['descuento']);
              // $array[$i]['idProveedor']   = trim($data['idProveedor']);
              // $array[$i]['proveedor']     = trim($data['proveedor']);
              // $array[$i]['idSucursal']    = trim($data['idSucursal']);
              // $array[$i]['sucursal']      = trim($data['sucursal']);
              // $array[$i]['propiedad']     = getDetalleProd($data['idProducto']);
              // $array[$i]['precio']        = trim($data['precio']);
              // $array[$i]['precioPorMayor']        = trim($data['precioPorMayor']);
              // $array[$i]['montoFree']        = trim($data['montoFree']);
              // //$array[$i]['foto'] = getFotoProducto($data['idProducto'],$data['idProveedor']);
              $array[$i]['foto']          =trim(RUTA_IMGproducto.$data['idProveedor'].'/'.$data['foto']);

              $i++;
              }
               echo ' {
                   "errorCode": 0,
                   "errorMessage": "Servicio ejecutado con éxito",
                   "msg": '.json_encode($array).'
               }';
           }else{
               echo ' {
                   "errorCode": 2,
                   "errorMessage": "No hay datos.",
                   "msg": 0
               }';
           }
           $db = null;
       } catch(PDOException $e){
           $db = null;
           echo $e;
               echo ' {
                   "errorCode": 3,
                   "errorMessage": "Error al ejecutar el servicio web.",
                   "msg": 0
               }';
       }
  } else {
       echo ' {
          "errorCode": 4,
          "errorMessage": "No autenticado.",
          "msg": 0
     }';
  }
});


// RE12 CLI - GET - produto Detalle
$app->get('/getProductoDetalle/{idtoken}/{token}/{idproducto}/{idsucursal}',function ($request, $response,$data) { 
  
  $validate = getValidateCliente($data['idtoken'],$data['token']);
  if($validate) {
    try{
      //$idSucursal = getCoordenadas($id, $latFrom, $lngFrom);
        $sql = "SELECT p.idProducto, p.nombre, p.detalle, p.descripcion, p.peso, p.fecha, p.estado, p.idSubcategoria,sp.stock,
        sp.tiempoFree,sp.montoFree,
       pr.nombre as nombreProveedor,
       pr.foto as fotoProveedor,
       pr.usuario,
       p.descuentoPromo, p.idProveedor,sp.idSucursal,s.latitud,sp.disponible,
       s.longitud,sp.precio,p.foto,s.nombre as nombresucursal,s.direccion,p.alto,p.ancho,p.largo, pc.plantilla,
       sp.precioPorMayor
       FROM productoSucursal sp
       INNER JOIN sucursal s ON s.idSucursal=sp.idSucursal
       INNER JOIN producto p ON  p.idProducto=sp.idProducto
       INNER JOIN proveedor pr ON pr.idProveedor=p.idProveedor
       LEFT JOIN plantillacorte pc ON pc.idProducto=p.idProducto
       WHERE p.idProducto=:idProducto 
       AND sp.idSucursal=:idSucursal 
       AND s.idProveedor=pr.idProveedor;";
        $db = getConnection();
        $stmt = $db->prepare($sql);
        $stmt ->bindParam('idProducto', $id );
        $stmt ->bindParam('idSucursal', $idsucursal );
        $stmt->execute();
        if ( $stmt->rowCount() > 0 ) {
           $data = $stmt->fetch(PDO::FETCH_ASSOC);
           $array['idProducto']        = trim($data['idProducto']);
           $array['nombre']            = trim($data['nombre']);
           $array['detalle']           = trim($data['detalle']);
           $array['descripcion']           = trim($data['descripcion']);
           $array['peso']              = trim($data['peso']);
           $array['alto']              = trim($data['alto']);
           $array['ancho']             = trim($data['ancho']);
           $array['largo']             = trim($data['largo']);
           $array['fecha']             = trim($data['fecha']);
           $array['estado']            = trim($data['estado']);
           $array['idSubcategoria']    = trim($data['idSubcategoria']);
           $array['descuentoPromo']    = trim($data['descuentoPromo']);
           $array['idProveedor']       = trim($data['idProveedor']);
           $array['nombreProveedor']   = trim($data['nombreProveedor']);
           $array['fotoProveedor']     = trim(RUTA_IMGproveedor.$data['idProveedor'].'/'.$data['fotoProveedor']);
           $array['propiedad']         = getDetalleProd($data['idProducto']);
           $array['idSucursal']        = trim($data['idSucursal']);
           $array['nombresucursal']    = trim($data['nombresucursal']);
           $array['direccion']         = trim($data['direccion']);
           $array['latitud']           = trim($data['latitud']);
           $array['longitud']          = trim($data['longitud']);
           $array['precio']            = trim($data['precio']);
           $array['stock']             = trim($data['stock']);
           $array['disponible']        = trim($data['disponible']);
           $array['correo']            = trim($data['usuario']);
           $array['tiempoFree']        = trim($data['tiempoFree']);
           $array['montoFree']         = trim($data['montoFree']);
           $array['precioPorMayor']         = trim($data['precioPorMayor']);
           //$array['foto'] = getFotoSubcategoria($data['idSubcategoria']);
           $array['foto']              = trim(RUTA_IMGproducto.$data['idProveedor'].'/'.$data['foto']);
           $array['horarios']          = funcionHorarioSurcursal($data['idSucursal']);
           if($data['plantilla'] == null) {
             $nu = null;
             $array['archivo']              = $nu;
           } else {
             $array['archivo']              = trim(RUTA_IMGproducto.$data['idProveedor'].'/'.$data['plantilla']);
           }
           
            echo ' {
                "errorCode": 0,
                "errorMessage": "Servicio ejecutado con éxito",
                "msg": '.json_encode($array).'
            }';
        }else{
            echo ' {
                "errorCode": 2,
                "errorMessage": "No hay datos.",
                "msg": 0
            }';
        }
        $db = null;
    } catch(PDOException $e){
        $db = null;
        echo $e;
            echo ' {
                "errorCode": 3,
                "errorMessage": "Error al ejecutar el servicio web.",
                "msg": 0
            }';
    }
  } else {
       echo ' {
          "errorCode": 4,
          "errorMessage": "No autenticado.",
          "msg": 0
     }';
  }
});


// RE-- CLI - GET - produto Detalle
// $app->get('/getProductoDetalle/{idtoken}/{token}/{idproducto}/{idsucursal}',function ($request, $response,$data) { 
  
//   $validate = getValidateCliente($data['idtoken'],$data['token']);
//   if($validate) {
//     try{
//       //$idSucursal = getCoordenadas($id, $latFrom, $lngFrom);
//         $sql = "SELECT p.*, s.nombre as nombresucursal, s.idSucursal, s.latitud, s.longitud, s.direccion, pr.nombre as nombreProveedor FROM productoSucursal ps
//         JOIN producto p ON ps.idProducto=p.idProducto 
//         JOIN sucursal s ON s.idSucursal = ps.idSucursal
//         JOIN proveedor pr ON pr.idProveedor = p.idProveedor
//         WHERE ps.idProducto = :idProducto and ps.idSucursal=:idSucursal GROUP BY p.idProducto;
//        ";
//         $db = getConnection();
//         $stmt = $db->prepare($sql);
//         $stmt ->bindParam('idProducto', $data['idproducto'] );
//         $stmt ->bindParam('idSucursal', $data['idsucursal'] );
//         $stmt->execute();
//         if ( $stmt->rowCount() > 0 ) {
//            $data = $stmt->fetch(PDO::FETCH_ASSOC);
//            $array['idProducto']        = trim($data['idProducto']);
//            $array['nombre']            = trim($data['nombre']);
//            $array['detalle']           = trim($data['detalle']);
//            $array['descripcion']           = trim($data['descripcion']);
//            $array['peso']              = trim($data['peso']);
//            $array['alto']              = trim($data['alto']);
//            $array['ancho']             = trim($data['ancho']);
//            $array['largo']             = trim($data['largo']);
//            $array['idProveedor']       = trim($data['idProveedor']);
//            $array['nombreProveedor']   = trim($data['nombreProveedor']);
//            $array['variaciones']         = getDetalleProducto($data['idProducto'],$data['idSucursal']);
//            $array['idSucursal']        = trim($data['idSucursal']);
//            $array['nombresucursal']    = trim($data['nombresucursal']);
//            $array['direccion']         = trim($data['direccion']);
//            $array['latitud']           = trim($data['latitud']);
//            $array['longitud']          = trim($data['longitud']);
//           //  $array['precio']            = trim($data['precio']);
//           //  $array['descuentoPromo']    = trim($data['descuentoPromo']);
//           //  $array['stock']             = trim($data['stock']);
//           //  $array['disponible']        = trim($data['disponible']);
//           //  $array['tiempoFree']        = trim($data['tiempoFree']);
//           //  $array['montoFree']         = trim($data['montoFree']);
//           //  $array['precioPorMayor']         = trim($data['precioPorMayor']);
//            //$array['foto'] = getFotoSubcategoria($data['idSubcategoria']);
//            $array['foto']              = trim(RUTA_IMGproducto.$data['idProveedor'].'/'.$data['foto']);
//           //  $array['horarios']          = funcionHorarioSurcursal($data['idSucursal']);
           
           
//             echo ' {
//                 "errorCode": 0,
//                 "errorMessage": "Servicio ejecutado con éxito",
//                 "msg": '.json_encode($array).'
//             }';
//         }else{
//             echo ' {
//                 "errorCode": 2,
//                 "errorMessage": "No hay datos.",
//                 "msg": 0
//             }';
//         }
//         $db = null;
//     } catch(PDOException $e){
//         $db = null;
//         echo $e;
//             echo ' {
//                 "errorCode": 3,
//                 "errorMessage": "Error al ejecutar el servicio web.",
//                 "msg": 0
//             }';
//     }
//   } else {
//        echo ' {
//           "errorCode": 4,
//           "errorMessage": "No autenticado.",
//           "msg": 0
//      }';
//   }
// });


function getDetalleProducto($idproducto, $idsucursal) {
  try{
      $sql = "SELECT min(ps.precio) as precio FROM productoSucursal ps JOIN productoVariante pv on ps.idProductoVariante = ps.idProductoVariante
      WHERE pv.idProducto = $idproducto GROUP BY pv.idProducto";
      $db = getConnection();
      $stmt = $db->prepare($sql);
      $stmt->execute();
      if ( $stmt->rowCount() > 0 ) {
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)){
          $precio = trim($data['precio']);
          
        }
        return $precio;
      }else{
        return 0;
      }
    
  } catch(PDOException $e){
    $db = null;
    return -0;
  }
}
////////////////////////// base servicio
// RE0 CLI - POST - 
// $app->post('/', function ($request, $response) {
//   $data = $request->getParsedBody();


// });

// RE0 CLI - GET - 
// $app->get('/', function ($request, $response,$data) {


// });
//////////////////////////









































// PE02CLI - POST - INSERTAR COMERCIO
$app->post('/postRegistrarComercio', function ($request, $response) {
  $input = $request->getParsedBody();
  $sql = "INSERT INTO comercio(nombre, propietario, telefono, direccion, lat, lng, usuario, correo, password, estado, precioBase, precioAdicional)
          VALUES (:nombre, :propietario, :telefono, :direccion, :lat, :lng, :usuario, :correo, :password, 1, 0, 0)";
  try {
    $validate = true;
    if($validate) {
      if (getExisteComercio($input['usuario'], $input['correo']) == false) {
        $db = getConnection();
        $stmt = $db->prepare($sql);
        $stmt->bindParam("nombre", $input['nombre']);
        /*if(array_key_exists('base64CI', $input)) {
          $imagen5 = subirImagen($input['base64CI'], 5);
          $stmt->bindParam("ci",  $imagen5);
        } else {
          $imagen5 = 'undefined.jpg';
          $stmt->bindParam("ci",  $imagen5);
        }*/
        $stmt->bindParam("propietario", $input['propietario']);
        $stmt->bindParam("telefono", $input['telefono']);
        $stmt->bindParam("direccion", $input['direccion']);
        $stmt->bindParam("lat", $input['lat']);
        $stmt->bindParam("lng", $input['lng']);
        $stmt->bindParam("usuario", $input['usuario']);
        $stmt->bindParam("correo", $input['correo']);
        $pw = hash_hmac('sha512', 'salt' . $input['password'], 92432);
        $stmt->bindParam("password", $pw);
        $stmt->execute();
        $id = $db->lastInsertID();
        $input['idComercio'] = $id;
        $token 					= setTokenComercio($id);
        $input['token']= trim($token);
        echo '{
                "errorCode": 0,
                "errorMessage": "Servicio ejecutado con éxito",
                "msg": "Registrado Correctamente",
                "obj": '.json_encode($input).'
              }';
        $db = null;
      } else {
        echo '{
                "errorCode": 5,
                "errorMessage": "El usuario ya está registrado",
                "msg": 0
              }';
      }
    } else {
      echo ' {
              "errorCode": 4,
              "errorMessage": "No autenticado.",
              "msg": 0
            }';
    }
  } catch(PDOException $e) {
    $db = null;
		echo  '{
						"errorCode": 3,
						"errorMessage": "Error al ejecutar el servicio web.",
						"msg": '. $e->getMessage() .'
				  }';
  }
});

// PE03CLI - POST - INSERTAR USUARIO
$app->post('/postRegistrarUsuario', function ($request, $response) {
  $input = $request->getParsedBody();
  $sql = "INSERT INTO usuario(nombre, ci, telefono, direccion, lat, lng, usuario, correo, password, estado)
          VALUES (:nombre, :ci, :telefono, :direccion, :lat, :lng, :usuario, :correo, :password, 1)";
  try {
    $validate = true;
    if($validate) {
      if (getExisteUsuario($input['usuario'], $input['correo']) == false) {
        $db = getConnection();
        $stmt = $db->prepare($sql);
        if($input['rs'] == 0) {
          $stmt->bindParam("nombre", $input['nombre']);
          $stmt->bindParam("ci", $input['ci']);
          $stmt->bindParam("telefono", $input['telefono']);
          $stmt->bindParam("direccion", $input['direccion']);
          $stmt->bindParam("lat", $input['lat']);
          $stmt->bindParam("lng", $input['lng']);
          $stmt->bindParam("usuario", $input['usuario']);
          $stmt->bindParam("correo", $input['correo']);
          $pw = hash_hmac('sha512', 'salt' . $input['password'], 92432);
          $stmt->bindParam("password", $pw);
        } else if($input['rs'] == 1) {
          $sql = "INSERT INTO usuario(nombre, ci, telefono, direccion, lat, lng, usuario, correo, password, estado)
                  VALUES (:nombre, :ci, :telefono, :direccion, :lat, :lng, :usuario, :correo, :password, 1)";
          $v = ' ';
          $stmt = $db->prepare($sql);
          $stmt->bindParam('nombre', $input['nombre']);
          $stmt->bindParam('ci', $v);
          $stmt->bindParam('telefono', $v);
          $stmt->bindParam('direccion', $v);
          $stmt->bindParam('lat', $v);
          $stmt->bindParam('lng', $v);
          $stmt->bindParam('usuario', $input['correo']);
          $stmt->bindParam('correo', $input['correo']);
          $pw = hash_hmac('sha512', 'salt' . $v, 92432);
          $stmt->bindParam('password', $pw);
        }
        $stmt->execute();
        $id = $db->lastInsertID();
        $input['idUsuario'] = $id;
        $token 					= setTokenUsuario($id);
        $input['token']= trim($token);
        $input['tipoUsuario'] 	= trim('2');
        echo '{
                "errorCode": 0,
                "errorMessage": "Servicio ejecutado con éxito",
                "msg": "Registrado Correctamente",
                "obj": '.json_encode($input).'
              }';
        $db = null;
      } else {
        if($input['rs'] == 0) {
          echo '{
                  "errorCode": 5,
                  "errorMessage": "El usuario ya existe",
                  "msg": 0
                }';
        } else {
          getLoginClienteRS($input['correo']);
        }
      }
    } else {
      echo ' {
              "errorCode": 4,
              "errorMessage": "No autenticado.",
              "msg": 0
            }';
    }
  } catch(PDOException $e) {
    $db = null;
		echo  '{
						"errorCode": 3,
						"errorMessage": "Error al ejecutar el servicio web.",
						"msg": '. $e->getMessage() .'
				  }';
  }
});

// PE04CLI - POST - INSERTAR SOLICITUD
$app->post('/postInsertarSolicitud', function ($request, $response) {
  $input = $request->getParsedBody();
  $sql = "INSERT INTO solicitud (nombreRecibe, telefonoRecibe, detalle, direccion, lat, lng, costoEnvio, horaEntregaConductor, idTipoEnvio,
          idEnvia, estado, pago, distancia, fechaSolicitud)
          VALUES (:nombreRecibe, :telefonoRecibe, :detalle, :direccion, :lat, :lng, :costoEnvio, :horaEntregaConductor, :idTipoEnvio,
          :idEnvia, 1, :pago, :distancia, :fechaSolicitud)";
  try {
    $validate = getValidate($input['idtoken'],$input['token']);
    if($validate) {
      $db = getConnection();
      $stmt = $db->prepare($sql);
      $stmt->bindParam("nombreRecibe", $input['nombreRecibe']);
      $stmt->bindParam("telefonoRecibe", $input['telefonoRecibe']);
      $stmt->bindParam("detalle", $input['detalle']);
      $stmt->bindParam("direccion", $input['direccion']);
      $stmt->bindParam("lat", $input['lat']);
      $stmt->bindParam("lng", $input['lng']);
      $stmt->bindParam("costoEnvio", $input['costoEnvio']);
      $stmt->bindParam("horaEntregaConductor", $input['horaEntregaConductor']);
      $stmt->bindParam("idTipoEnvio", $input['idTipoEnvio']);
      $stmt->bindParam("idEnvia", $input['idtoken']);
      $stmt->bindParam("pago", $input['pago']);
      $stmt->bindParam("distancia", $input['distancia']);
      if(array_key_exists('fechaSolicitud', $input)) {
        $stmt->bindParam("fechaSolicitud", $input['fechaSolicitud']);
      } else {
          $fechaSolicitud = date("Y-m-d");
          $stmt->bindParam("fechaSolicitud", $fechaSolicitud);
      }
      $stmt->execute();
      $id = $db->lastInsertID();
      if($input['idTipoEnvio'] == 1) {
        $sql = "INSERT INTO solicitudcomercio(nroPedido, factura, razonSocial, nit, montoPedido, pagaComercio, idTipoPago, idSolicitud,
                direccionOrigen, latOrigen, lngOrigen)
                VALUES (:nroPedido, :factura, :razonSocial, :nit, :montoPedido, :pagaComercio, :idTipoPago, :idSolicitud,
                :direccionOrigen, :latOrigen, :lngOrigen)";
        $stmt = $db->prepare($sql);
        $stmt->bindParam("nroPedido", $input['nroPedido']);
        $stmt->bindParam("factura", $input['factura']);
        $stmt->bindParam("razonSocial", $input['razonSocial']);
        $stmt->bindParam("nit", $input['nit']);
        $stmt->bindParam("montoPedido", $input['montoPedido']);
        $stmt->bindParam("pagaComercio", $input['pagaComercio']);
        $stmt->bindParam("idTipoPago", $input['idTipoPago']);
        $stmt->bindParam("direccionOrigen", $input['direccionOrigen']);
        $stmt->bindParam("latOrigen", $input['latOrigen']);
        $stmt->bindParam("lngOrigen", $input['lngOrigen']);
        $stmt ->bindParam('idSolicitud', $id);
        $stmt->execute();
      } else {
        $sql = "INSERT INTO solicitudusuario(direccionOrigen, latOrigen, lngOrigen, pagaUsuario, idSolicitud)
                VALUES (:direccionOrigen, :latOrigen, :lngOrigen, :pagaUsuario, :idSolicitud)";
        $stmt = $db->prepare($sql);
        $stmt->bindParam("direccionOrigen", $input['direccionOrigen']);
        $stmt->bindParam("latOrigen", $input['latOrigen']);
        $stmt->bindParam("lngOrigen", $input['lngOrigen']);
        $stmt->bindParam("pagaUsuario", $input['pagaUsuario']);
        $stmt ->bindParam('idSolicitud', $id);
        $stmt->execute();
      }
      $input['idSolicitud'] = $id;
      insertarSolicitudTransporteFirebase($input);
      echo '{
              "errorCode": 0,
              "errorMessage": "Servicio ejecutado con éxito",
              "msg": "Registrado Correctamente",
              "idSolicitud": "'.$id.'"
            }';
      $db = null;
    } else {
      echo ' {
              "errorCode": 4,
              "errorMessage": "No autenticado.",
              "msg": 0
            }';
    }
  } catch(PDOException $e) {
    $db = null;
		echo  '{
						"errorCode": 3,
						"errorMessage": "Error al ejecutar el servicio web.",
						"msg": '. $e->getMessage() .'
				  }';
  }
});

// PE05CLI - GET - OBTENER LISTA DE SOLICITUDES
$app->get('/getListaSolicitudes/{idtoken}/{token}/{idTipoEnvio}/{limite}/{posicion}', function ($request, $response, $args) {
  $sql = "SELECT s.idSolicitud, s.nombreRecibe, s.telefonoRecibe, s.direccion, s.lat, s.lng, s.costoEnvio, s.horaEntregaConductor, s.idTipoEnvio,
          s.idEnvia, s.estado, s.fechaReg, s.idConductor, s.firma, e.nombre as estadoSolicitud, c.nombre as conductor,  c.ci as ciConductor,
          t.nombre as tipoEnvio, s.fechaSolicitud
          FROM solicitud s
          LEFT JOIN estadosolicitud e ON e.idEstadoSolicitud=s.estado
          LEFT JOIN conductor c ON c.idConductor=s.idConductor
          LEFT JOIN tipoenvio t ON t.idTipoEnvio=s.idTipoEnvio
          WHERE s.idEnvia=:idEnvia AND s.idTipoEnvio=:idTipoEnvio
          LIMIT :posicion, :limite";
  try {
    $validate = getValidate($args['idtoken'],$args['token']);
    if($validate) {
      $posicion = (int)$args['posicion'];
      $limite = (int)$args['limite'];
      $db = getConnection();
      $stmt = $db->prepare($sql);
      $stmt ->bindParam('idEnvia', $args['idtoken']);
      $stmt ->bindParam('idTipoEnvio', $args['idTipoEnvio']);
      $stmt ->bindParam('posicion', $posicion, PDO::PARAM_INT);
      $stmt ->bindParam('limite', $limite, PDO::PARAM_INT);
      $stmt->execute();
      if ( $stmt->rowCount() > 0 ) {
        $i = 0;
        while ($response  = $stmt->fetch(PDO::FETCH_ASSOC)) {
          $array[$i]['idSolicitud']   = trim($response['idSolicitud']);
          $array[$i]['nombreRecibe'] 	= trim($response['nombreRecibe']);
          $array[$i]['telefonoRecibe'] 	= trim($response['telefonoRecibe']);
          $array[$i]['direccion'] 	= trim($response['direccion']);
          $array[$i]['lat'] 	= trim($response['lat']);
          $array[$i]['lng'] 	= trim($response['lng']);
          $array[$i]['costoEnvio'] 	= trim($response['costoEnvio']);
          $array[$i]['horaEntregaConductor'] 	= trim($response['horaEntregaConductor']);
          $array[$i]['idTipoEnvio'] 	= trim($response['idTipoEnvio']);
          $array[$i]['idEnvia'] 	= trim($response['idEnvia']);
          $array[$i]['estado'] 	= trim($response['estado']);
          $array[$i]['fechaReg'] 	= trim($response['fechaReg']);
          $array[$i]['idConductor'] 	= trim($response['idConductor']);
          $array[$i]['idTipoEnvio'] 	= trim($response['idTipoEnvio']);
          $array[$i]['firma'] 	= trim($response['firma']);
          $array[$i]['estadoSolicitud'] 	= trim($response['estadoSolicitud']);
          if ($response['idConductor'] == 0) {
            $array[$i]['conductor'] 	= trim("No asignado");
            $array[$i]['ciConductor'] 	= trim("0");
          } else {
            $array[$i]['conductor'] 	= trim($response['conductor']);
            $array[$i]['ciConductor'] 	= trim($response['ciConductor']);
          }
          $array[$i]['tipoEnvio'] 	= trim($response['tipoEnvio']);
          $array[$i]['fechaSolicitud'] 	= trim($response['fechaSolicitud']);
          $array[$i]['nombre'] 	= getNombreSolicitudIncidencia($response['idSolicitud'], $response['idTipoEnvio']);
          $i++;
        }
        echo  '{
                "errorCode": 0,
                "errorMessage": "Servicio ejecutado con exito.",
                "msg": '.json_encode($array).'
              }';
      }else{
        echo '{
                "errorCode": 2,
                "errorMessage": "No hay datos.",
                "msg": ""
              }';
      }
      $db = null;
    } else {
      echo ' {
              "errorCode": 4,
              "errorMessage": "No autenticado.",
              "msg": 0
            }';
    }
  } catch(PDOException $e) {
    $db = null;
		echo  '{
						"errorCode": 3,
						"errorMessage": "Error al ejecutar el servicio web.",
						"msg": '. $e->getMessage() .'
				  }';
  }
});

// PE06CLI - GET - OBTENER DETALLE DE SOLICITUD
$app->get('/getDetalleSolicitud/{idtoken}/{token}/{idSolicitud}/{idTipoEnvio}', function ($request, $response, $args) {
  try {
    $sql = "SELECT s.idSolicitud, s.direccion, s.lat, s.lng, s.fechaReg, e.nombre as estadoSolicitud, s.idTipoEnvio, s.costoEnvio, s.pago, s.estado, s.idEnvia,
            s.telefonoRecibe, s.distancia, s.detalle, s.horaEntregaConductor, s.nombreRecibe, s.fechaSolicitud
            FROM solicitud s
            LEFT JOIN estadosolicitud e ON e.idEstadoSolicitud=s.estado
            WHERE s.idSolicitud=:idSolicitud";
    $validate = getValidate($args['idtoken'],$args['token']);
    if($validate) {
      $db = getConnection();
      $stmt = $db->prepare($sql);
      $stmt ->bindParam('idSolicitud', $args['idSolicitud']);
      $stmt->execute();
      if ( $stmt->rowCount() > 0 ) {
        while ($response  = $stmt->fetch(PDO::FETCH_ASSOC)) {
          $array['idSolicitud']   = trim($response['idSolicitud']);
          $array['direccion'] 	= trim($response['direccion']);
          $array['lat'] 	= trim($response['lat']);
          $array['lng'] 	= trim($response['lng']);
          $array['fechaReg'] 	= trim($response['fechaReg']);
          $array['estado'] 	= trim($response['estado']);
          $array['estadoSolicitud'] 	= trim($response['estadoSolicitud']);
          $array['costoEnvio'] 	= trim($response['costoEnvio']);
          $array['pago'] 	= trim($response['pago']);
          $array['nombreRecibe'] 	= trim($response['nombreRecibe']);
          $array['telefonoRecibe'] 	= trim($response['telefonoRecibe']);
          $array['distancia'] 	= trim($response['distancia']);
          $array['detalle'] 	= trim($response['detalle']);
          $array['horaEntregaConductor'] 	= trim($response['horaEntregaConductor']);
          $array['fechaSolicitud'] 	= trim($response['fechaSolicitud']);
          $array['idIncidencia'] 	= getIdIncidencia($args['idSolicitud']);
          if($response['idTipoEnvio'] == 1) {
            $res = getDetalleSolicitudComercio($response['idSolicitud']);
            $array['montoPedido'] 	= $res['montoPedido'];
            $array['direccionOrigen'] 	= $res['direccionOrigen'];
            $array['latOrigen'] 	= $res['latOrigen'];
            $array['lngOrigen'] 	= $res['lngOrigen'];
            $array['tipoPago'] 	= $res['tipoPago'];
            $array['QuienPagaTransporte'] = $res['pagaComercio'];
            $array['pagaComercio'] = $res['pagaComercio'];
            $array['nombreSolicitante'] = getNombreComercio($response['idEnvia']);
          } else {
            $res = getDetalleSolicitudCliente($response['idSolicitud']);
            $array['direccionOrigen'] 	= $res['direccionOrigen'];
            $array['latOrigen'] 	= $res['latOrigen'];
            $array['lngOrigen'] 	= $res['lngOrigen'];
            $array['QuienPagaTransporte'] = $res['pagaUsuario'];
            $array['pagaUsuario'] = $res['pagaUsuario'];
            $array['nombreSolicitante'] = getNombreUsuario($response['idEnvia']);
          }
        }
        echo  '{
                "errorCode": 0,
                "errorMessage": "Servicio ejecutado con exito.",
                "msg": '.json_encode($array).'
              }';
      }else{
        echo '{
                "errorCode": 2,
                "errorMessage": "No hay datos.",
                "msg": ""
              }';
      }
      $db = null;
    } else {
      echo ' {
              "errorCode": 4,
              "errorMessage": "No autenticado.",
              "msg": 0
            }';
    }
  } catch(PDOException $e) {
    $db = null;
		echo  '{
						"errorCode": 3,
						"errorMessage": "Error al ejecutar el servicio web.",
						"msg": '. $e->getMessage() .'
				  }';
  }
});

// PE07CLI - GET - OBTENER LISTA DE ESTADOS DE SOLICITUD
$app->get('/getListaEstadosSolicitud/{idtoken}/{token}', function ($request, $response, $args) {
  $sql = "SELECT idEstadoSolicitud, nombre
          FROM estadosolicitud";
  try {
    $validate = getValidate($args['idtoken'],$args['token']);
    if($validate) {
      $db = getConnection();
      $stmt = $db->prepare($sql);
      $stmt->execute();
      if ( $stmt->rowCount() > 0 ) {
        $i = 0;
        while ($response  = $stmt->fetch(PDO::FETCH_ASSOC)) {
          $array[$i]['idEstadoSolicitud']   = trim($response['idEstadoSolicitud']);
          $array[$i]['nombre'] 	= trim($response['nombre']);
          $i++;
        }
        echo  '{
                "errorCode": 0,
                "errorMessage": "Servicio ejecutado con exito.",
                "msg": '.json_encode($array).'
              }';
      }else{
        echo '{
                "errorCode": 2,
                "errorMessage": "No hay datos.",
                "msg": ""
              }';
      }
      $db = null;
    } else {
      echo ' {
              "errorCode": 4,
              "errorMessage": "No autenticado.",
              "msg": 0
            }';
    }
  } catch(PDOException $e) {
    $db = null;
		echo  '{
						"errorCode": 3,
						"errorMessage": "Error al ejecutar el servicio web.",
						"msg": '. $e->getMessage() .'
				  }';
  }
});

// PE08CLI - POST - INSERTAR INCIDENCIA
$app->post('/postInsertarIncidencia', function ($request, $response) {
  $input = $request->getParsedBody();
  $sql = "INSERT INTO incidencia(detalle, idEstadoIncidencia, idSolicitud)
          VALUES (:detalle, 1, :idSolicitud)";
  try {
    $validate = getValidate($input['idtoken'],$input['token']);
    if($validate) {
      if (getExisteComercio($input['usuario'], $input['correo']) == true || getExisteUsuario($input['usuario'], $input['correo']) == true) {
        $db = getConnection();
        $stmt = $db->prepare($sql);
        $stmt->bindParam("detalle", $input['detalle']);
        $stmt->bindParam("idSolicitud", $input['idSolicitud']);
        $stmt->execute();
        $id = $db->lastInsertID();
        echo '{
                "errorCode": 0,
                "errorMessage": "Servicio ejecutado con éxito",
                "msg": "Registrado Correctamente",
                "idAdministrador": "'.$id.'"
              }';
        $db = null;
      } else {
        echo '{
                "errorCode": 5,
                "errorMessage": "No es un usuario registrado",
                "msg": 0
              }';
      }
    } else {
      echo ' {
              "errorCode": 4,
              "errorMessage": "No autenticado.",
              "msg": 0
            }';
    }
  } catch(PDOException $e) {
    $db = null;
		echo  '{
						"errorCode": 3,
						"errorMessage": "Error al ejecutar el servicio web.",
						"msg": '. $e->getMessage() .'
				  }';
  }
});

// PE09CLI - POST - INSERTAR RESPUESTA INCIDENCIA
$app->post('/postInsertarRespuestaIncidencia', function ($request, $response) {
  $input = $request->getParsedBody();
  $sql = "INSERT INTO respuestaincidencia(detalle, tipoUsuario, id, idIncidencia)
          VALUES (:detalle, 2, :id, :idIncidencia)";
  try {
    $validate = getValidate($input['idtoken'],$input['token']);
    if($validate) {
      $db = getConnection();
      $stmt = $db->prepare($sql);
      $stmt->bindParam("detalle", $input['detalle']);
      $stmt ->bindParam('id', $input['idtoken']);
      $stmt ->bindParam('idIncidencia', $input['idIncidencia']);
      $stmt->execute();
      $sql = "UPDATE incidencia
              SET idEstadoIncidencia=1
              WHERE idIncidencia=:idIncidencia";
      $stmt = $db->prepare($sql);
      $stmt ->bindParam('idIncidencia', $input['idIncidencia']);
      $stmt->execute();
      echo '{
              "errorCode": 0,
              "errorMessage": "Servicio ejecutado con éxito",
              "msg": "Respondido Correctamente"
            }';
      $db = null;
    } else {
      echo ' {
              "errorCode": 4,
              "errorMessage": "No autenticado.",
              "msg": 0
            }';
    }
  } catch(PDOException $e) {
    $db = null;
		echo  '{
						"errorCode": 3,
						"errorMessage": "Error al ejecutar el servicio web.",
						"msg": '. $e->getMessage() .'
				  }';
  }
});

// PE10CLI - POST - CERRAR INCIDENCIA
$app->post('/postCerrarIncidencia', function ($request, $response) {
  $input = $request->getParsedBody();
  $sql = "UPDATE incidencia
          SET idEstadoIncidencia=3
          WHERE idIncidencia=:idIncidencia";
  try {
    $validate = getValidate($input['idtoken'],$input['token']);
    if($validate) {
      $db = getConnection();
      $stmt = $db->prepare($sql);
      $stmt ->bindParam('idIncidencia', $input['idIncidencia']);
      $stmt->execute();
      echo '{
              "errorCode": 0,
              "errorMessage": "Servicio ejecutado con éxito",
              "msg": "Respondido Correctamente"
            }';
      $db = null;
    } else {
      echo ' {
              "errorCode": 4,
              "errorMessage": "No autenticado.",
              "msg": 0
            }';
    }
  } catch(PDOException $e) {
    $db = null;
		echo  '{
						"errorCode": 3,
						"errorMessage": "Error al ejecutar el servicio web.",
						"msg": '. $e->getMessage() .'
				  }';
  }
});

// PE11CLI - GET - OBTENER RESPUESTAS DE INCIDENCIAS
$app->get('/getRespuestasIncidencias/{idtoken}/{token}/{idIncidencia}', function ($request, $response, $args) {
  $sql = "SELECT i.idRespuestaIncidencia, i.detalle, i.fechaReg, i.tipoUsuario, i.id
          FROM respuestaincidencia i
          WHERE i.idIncidencia=:idIncidencia";
  try {
    $validate = getValidate($args['idtoken'],$args['token']);
    if($validate) {
      $db = getConnection();
      $stmt = $db->prepare($sql);
      $stmt ->bindParam('idIncidencia', $args['idIncidencia']);
      $stmt->execute();
      if ( $stmt->rowCount() > 0 ) {
        $i = 0;
        while ($response  = $stmt->fetch(PDO::FETCH_ASSOC)) {
          $array[$i]['idRespuestaIncidencia']   = trim($response['idRespuestaIncidencia']);
          $array[$i]['detalle'] 	= trim($response['detalle']);
          $array[$i]['fechaReg'] 	= trim($response['fechaReg']);
          $array[$i]['tipoUsuario'] 	= trim($response['tipoUsuario']);
          $array[$i]['id'] 	= trim($response['id']);
          $array[$i]['nombre'] 	= getNombreRespuestaIncidencia($response['tipoUsuario'], $response['id']);
          $array[$i]['avatar'] 	= trim($array[$i]['nombre']);
          $array[$i]['type'] 	= trim("text");
          if($response['tipoUsuario'] == 1) {
            $array[$i]['reply'] = trim("true");
          } else {
            $array[$i]['reply'] = trim("false");
          }
          $i++;
        }
        echo  '{
                "errorCode": 0,
                "errorMessage": "Servicio ejecutado con exito.",
                "msg": '.json_encode($array).'
              }';
      }else{
        echo '{
                "errorCode": 2,
                "errorMessage": "No hay datos.",
                "msg": ""
              }';
      }
      $db = null;
    } else {
      echo ' {
              "errorCode": 4,
              "errorMessage": "No autenticado.",
              "msg": 0
            }';
    }
  } catch(PDOException $e) {
    $db = null;
		echo  '{
						"errorCode": 3,
						"errorMessage": "Error al ejecutar el servicio web.",
						"msg": '. $e->getMessage() .'
				  }';
  }
});

// PE12CLI - GET - OBTENER HISTORIAL DE SOLICITUDES
$app->get('/getHistorialSolicitudes/{idtoken}/{token}/{fechaInicio}/{fechaFin}/{estado}/{idTipoEnvio}/{limite}/{posicion}', function ($request, $response, $args) {
  if ($args['estado'] == 0) {
    $sql = "SELECT s.idSolicitud, s.nombreRecibe, s.telefonoRecibe, s.direccion, s.lat, s.lng, s.costoEnvio, s.horaEntregaConductor, s.idTipoEnvio,
          s.idEnvia, s.estado, s.fechaReg, s.idConductor, s.firma, e.nombre as estadoSolicitud, c.nombre as conductor,  c.ci as ciConductor,
          t.nombre as tipoEnvio, s.pago, s.distancia, s.fechaSolicitud
          FROM solicitud s
          LEFT JOIN estadosolicitud e ON e.idEstadoSolicitud=s.estado
          LEFT JOIN conductor c ON c.idConductor=s.idConductor
          LEFT JOIN tipoenvio t ON t.idTipoEnvio=s.idTipoEnvio
          WHERE s.idEnvia=:idEnvia AND s.idTipoEnvio=:idTipoEnvio AND s.fechaReg BETWEEN :fechaInicio AND DATE_ADD(:fechaFin, INTERVAL 1 DAY)
          ORDER BY s.fechaReg DESC
          LIMIT :posicion, :limite";
  } else {
    $sql = "SELECT s.idSolicitud, s.nombreRecibe, s.telefonoRecibe, s.direccion, s.lat, s.lng, s.costoEnvio, s.horaEntregaConductor, s.idTipoEnvio,
          s.idEnvia, s.estado, s.fechaReg, s.idConductor, s.firma, e.nombre as estadoSolicitud, c.nombre as conductor,  c.ci as ciConductor,
          t.nombre as tipoEnvio, s.pago, s.distancia, s.fechaSolicitud
          FROM solicitud s
          LEFT JOIN estadosolicitud e ON e.idEstadoSolicitud=s.estado
          LEFT JOIN conductor c ON c.idConductor=s.idConductor
          LEFT JOIN tipoenvio t ON t.idTipoEnvio=s.idTipoEnvio
          WHERE s.idEnvia=:idEnvia AND s.idTipoEnvio=:idTipoEnvio AND s.fechaReg BETWEEN :fechaInicio AND DATE_ADD(:fechaFin, INTERVAL 1 DAY)
          AND s.estado = :estado
          ORDER BY s.fechaReg DESC
          LIMIT :posicion, :limite";
  }
  try {
    $validate = getValidate($args['idtoken'],$args['token']);
    if($validate) {
      $posicion = (int)$args['posicion'];
      $limite = (int)$args['limite'];
      $db = getConnection();
      $stmt = $db->prepare($sql);
      $stmt ->bindParam('idEnvia', $args['idtoken']);
      $stmt ->bindParam('idTipoEnvio', $args['idTipoEnvio']);
      $stmt ->bindParam('fechaInicio', $args['fechaInicio']);
      $stmt ->bindParam('fechaFin', $args['fechaFin']);
      if ($args['estado'] > 0) {
        $stmt ->bindParam('estado', $args['estado']);
      }
      $stmt ->bindParam('posicion', $posicion, PDO::PARAM_INT);
      $stmt ->bindParam('limite', $limite, PDO::PARAM_INT);
      $stmt->execute();
      if ( $stmt->rowCount() > 0 ) {
        $i = 0;
        while ($response  = $stmt->fetch(PDO::FETCH_ASSOC)) {
          $array[$i]['idSolicitud']   = trim($response['idSolicitud']);
          $array[$i]['nombreRecibe'] 	= trim($response['nombreRecibe']);
          $array[$i]['telefonoRecibe'] 	= trim($response['telefonoRecibe']);
          $array[$i]['direccion'] 	= trim($response['direccion']);
          $array[$i]['lat'] 	= trim($response['lat']);
          $array[$i]['lng'] 	= trim($response['lng']);
          $array[$i]['costoEnvio'] 	= trim($response['costoEnvio']);
          $array[$i]['horaEntregaConductor'] 	= trim($response['horaEntregaConductor']);
          $array[$i]['idTipoEnvio'] 	= trim($response['idTipoEnvio']);
          $array[$i]['idEnvia'] 	= trim($response['idEnvia']);
          $array[$i]['estado'] 	= trim($response['estado']);
          $array[$i]['fechaReg'] 	= trim($response['fechaReg']);
          $array[$i]['idConductor'] 	= trim($response['idConductor']);
          $array[$i]['idTipoEnvio'] 	= trim($response['idTipoEnvio']);
          $array[$i]['firma'] 	= trim($response['firma']);
          $array[$i]['estadoSolicitud'] 	= trim($response['estadoSolicitud']);
          $array[$i]['fechaSolicitud'] 	= trim($response['fechaSolicitud']);
          if ($response['idConductor'] == 0) {
            $array[$i]['conductor'] 	= trim("No asignado");
            $array[$i]['ciConductor'] 	= trim("0");
          } else {
            $array[$i]['conductor'] 	= trim($response['conductor']);
            $array[$i]['ciConductor'] 	= trim($response['ciConductor']);
          }
          $array[$i]['tipoEnvio'] 	= trim($response['tipoEnvio']);
          $array[$i]['nombre'] 	= getNombreSolicitudIncidencia($response['idSolicitud'], $response['idTipoEnvio']);
          $i++;
        }
        echo  '{
                "errorCode": 0,
                "errorMessage": "Servicio ejecutado con exito.",
                "msg": '.json_encode($array).'
              }';
      }else{
        echo '{
                "errorCode": 2,
                "errorMessage": "No hay datos.",
                "msg": ""
              }';
      }
      $db = null;
    } else {
      echo ' {
              "errorCode": 4,
              "errorMessage": "No autenticado.",
              "msg": 0
            }';
    }
  } catch(PDOException $e) {
    $db = null;
		echo  '{
						"errorCode": 3,
						"errorMessage": "Error al ejecutar el servicio web.",
						"msg": '. $e->getMessage() .'
				  }';
  }
});

// PE13CLI - GET - OBTENER PRECIO DE TRANSPORTE
$app->get('/getCostoTransporte/{latOri}/{lngOri}/{latDes}/{lngDes}/{tipoSolicitud}/{idEnvia}', function ($request, $response, $args) {
  $sql = "SELECT precioBase, precioAdicional, distanciaBase
          FROM configuracion";
  try {
    // $validate = getValidateAdministrador($args['idtoken'],$args['token']);
    $validate = true;
    if($validate) {
      $distanciaRecorrida = distanceCalculation($args['latOri'], $args['lngOri'], $args['latDes'], $args['lngDes']);
      $db = getConnection();
      $stmt = $db->prepare($sql);
      $stmt->execute();
      if ( $stmt->rowCount() > 0 ) {
        $response  = $stmt->fetch(PDO::FETCH_ASSOC);
        $precioBase   = trim($response['precioBase']);
        $precioAdicional 	= trim($response['precioAdicional']);
        if($args['tipoSolicitud'] == 1) {
          $precios = getPreciosComercio($args['idEnvia']);
          if($precios['precioBase'] > 0 && $precios['precioAdicional'] > 0 ) {
            $precioBase   = trim($precios['precioBase']);
            $precioAdicional 	= trim($precios['precioAdicional']);
          } else {
            $precioBase   = trim($response['precioBase']);
            $precioAdicional 	= trim($response['precioAdicional']);
          }
        } else {
          $precioBase   = trim($response['precioBase']);
          $precioAdicional 	= trim($response['precioAdicional']);
        }
        $distanciaBase   = trim($response['distanciaBase']);
        if($distanciaRecorrida <= $distanciaBase) {
          $precio = $precioBase;
        } else {
          $distanciaAdicional = $distanciaRecorrida - $distanciaBase;
          $km_adicional = round($distanciaAdicional, 0, PHP_ROUND_HALF_UP);
          // $km_adicional = ceil($distanciaAdicional); // Redondeo siempre al superior
          $precio = $precioBase + ( $km_adicional * $precioAdicional);
        }
        // $array['precioBase']   = trim($response['precioBase']);
        echo '{
                "errorCode": 0,
                "errorMessage": "Precio calculado correctamente",
                "msg": '.$precio.',
                "distancia": '.$distanciaRecorrida.'
              }';
      }else{
        echo '{
                "errorCode": 2,
                "errorMessage": "No hay datos.",
                "msg": ""
              }';
      }
      $db = null;
    } else {
      echo ' {
              "errorCode": 4,
              "errorMessage": "No autenticado.",
              "msg": 0
            }';
    }
  } catch(PDOException $e) {
    $db = null;
		echo  '{
						"errorCode": 3,
						"errorMessage": "Error al ejecutar el servicio web.",
						"msg": '. $e->getMessage() .'
				  }';
  }
});

// PE14CLI - GET - OBTENER ESTADO DE SOLICITUD
$app->get('/getEstadoSolicitud/{idtoken}/{token}/{idSolicitud}', function ($request, $response, $args) {
  $sql = "SELECT s.estado, es.nombre
          FROM solicitud s
          INNER JOIN estadosolicitud es ON es.idEstadoSolicitud=s.estado
          WHERE s.idSolicitud=:idSolicitud";
  try {
    $validate = getValidate($args['idtoken'],$args['token']);
    if($validate) {
      $db = getConnection();
      $stmt = $db->prepare($sql);
      $stmt ->bindParam('idSolicitud', $args['idSolicitud']);
      $stmt->execute();
      if ( $stmt->rowCount() > 0 ) {
        while ($response  = $stmt->fetch(PDO::FETCH_ASSOC)) {
          $array['estado']   = trim($response['estado']);
          $array['estadoSolicitud'] 	= trim($response['nombre']);
        }
        echo  '{
                "errorCode": 0,
                "errorMessage": "Servicio ejecutado con exito.",
                "msg": '.json_encode($array).'
              }';
      }else{
        echo '{
                "errorCode": 2,
                "errorMessage": "No hay datos.",
                "msg": ""
              }';
      }
      $db = null;
    } else {
      echo ' {
              "errorCode": 4,
              "errorMessage": "No autenticado.",
              "msg": 0
            }';
    }
  } catch(PDOException $e) {
    $db = null;
		echo  '{
						"errorCode": 3,
						"errorMessage": "Error al ejecutar el servicio web.",
						"msg": '. $e->getMessage() .'
				  }';
  }
});

// PE15CLI - GET - OBTENER TÉRMINOS Y CONDICIONES
$app->get('/getTerminosCondiciones', function ($request, $response, $args) {
  $sql = "SELECT terminosCondicionesCli
          FROM configuracion";
  try {
    // $validate = getValidate($args['idtoken'],$args['token']);
    $validate= true;
    if($validate) {
      $db = getConnection();
      $stmt = $db->prepare($sql);
      $stmt->execute();
      if ( $stmt->rowCount() > 0 ) {
        $i = 0;
        while ($response  = $stmt->fetch(PDO::FETCH_ASSOC)) {
          $array['terminosCondicionesCli']   = trim($response['terminosCondicionesCli']);
        }
        echo  '{
                "errorCode": 0,
                "errorMessage": "Servicio ejecutado con exito.",
                "msg": '.json_encode($array).'
              }';
      }else{
        echo '{
                "errorCode": 2,
                "errorMessage": "No hay datos.",
                "msg": ""
              }';
      }
      $db = null;
    } else {
      echo '{
              "errorCode": 4,
              "errorMessage": "No autenticado.",
              "msg": 0
            }';
    }
  } catch(PDOException $e) {
    $db = null;
		echo  '{
						"errorCode": 3,
						"errorMessage": "Error al ejecutar el servicio web.",
						"msg": '. $e->getMessage() .'
				  }';
  }
});

// PE16CLI - POST - CANCELAR SOLICITUD
$app->post('/postCancelarSolicitud', function ($request, $response) {
  $input = $request->getParsedBody();
  $sql = "UPDATE solicitud
          SET estado=5
          WHERE idSolicitud=:idSolicitud";
  try {
    $validate = getValidate($input['idtoken'],$input['token']);
    if($validate) {
      $db = getConnection();
      $db->beginTransaction();
      $sql2 = "SELECT estado, idConductor
              FROM solicitud
              WHERE idSolicitud=:idSolicitud";
       $stmt2 = $db->prepare($sql2);
       $stmt2->bindParam("idSolicitud", $input['idSolicitud']);
       $stmt2->execute();
       if ( $stmt2->rowCount() > 0 ) {
        $response2  = $stmt2->fetch(PDO::FETCH_ASSOC);
          $estado   = trim($response2['estado']);
          $idConductor   = trim($response2['idConductor']);
       }
        if(($estado == 2 || $estado == 1) && $idConductor == 0) {
          $stmt = $db->prepare($sql);
          $stmt->bindParam("idSolicitud", $input['idSolicitud']);
          $stmt->execute();
          if ( $stmt->rowCount() > 0 ) {
            deleteSolicitudFirebase($input['idSolicitud']);
            $db->commit();
            echo '{
                    "errorCode": 0,
                    "errorMessage": "Servicio ejecutado con éxito",
                    "msg": "Modificado Correctamente "
                  }';
          }else{
            $db->rollback();
            echo '{
                    "errorCode": 2,
                    "errorMessage": "No hay datos",
                    "msg": 0
                  }';
        }
        $db = null;
        } else {
          $db->rollback();
          echo '{
                  "errorCode": 5,
                  "errorMessage": "La solicitud ya fue aceptada por un conductor",
                  "msg": 0
                }';
        }
    } else {
      echo ' {
              "errorCode": 4,
              "errorMessage": "No autenticado.",
              "msg": 0
            }';
    }
  } catch(PDOException $e) {
    $db = null;
		echo  	'{
						  "errorCode": 3,
						  "errorMessage": "Error al ejecutar el servicio web.",
					  	"msg": '. $e->getMessage() .'
				    }';
  }
});

// PE17CLI - GET - OBTENER LISTA DE SOLICITUDES EN CURSO
$app->get('/getListaSolicitudesCurso/{idtoken}/{token}/{idTipoEnvio}/{limite}/{posicion}', function ($request, $response, $args) {
  $sql = "SELECT s.idSolicitud, s.nombreRecibe, s.direccion, s.costoEnvio, s.estado, e.nombre as estadoSolicitud, s.fechaReg, s.fechaSolicitud
          FROM solicitud s
          LEFT JOIN estadosolicitud e ON e.idEstadoSolicitud=s.estado
          WHERE s.idEnvia=:idEnvia AND s.idTipoEnvio=:idTipoEnvio AND s.estado IN (1, 2, 3)
          ORDER BY s.fechaReg DESC
          LIMIT :posicion, :limite";
  try {
    $validate = getValidate($args['idtoken'],$args['token']);
    if($validate) {
      $posicion = (int)$args['posicion'];
      $limite = (int)$args['limite'];
      $db = getConnection();
      $stmt = $db->prepare($sql);
      $stmt ->bindParam('idEnvia', $args['idtoken']);
      $stmt ->bindParam('idTipoEnvio', $args['idTipoEnvio']);
      $stmt ->bindParam('posicion', $posicion, PDO::PARAM_INT);
      $stmt ->bindParam('limite', $limite, PDO::PARAM_INT);
      $stmt->execute();
      if ( $stmt->rowCount() > 0 ) {
        $i = 0;
        while ($response  = $stmt->fetch(PDO::FETCH_ASSOC)) {
          $array[$i]['idSolicitud']   = trim($response['idSolicitud']);
          $array[$i]['nombreRecibe'] 	= trim($response['nombreRecibe']);
          $array[$i]['direccion'] 	= trim($response['direccion']);
          $array[$i]['costoEnvio'] 	= trim($response['costoEnvio']);
          // $array[$i]['idTipoEnvio'] 	= trim($response['idTipoEnvio']);
          // $array[$i]['idEnvia'] 	= trim($response['idEnvia']);
          $array[$i]['estado'] 	= trim($response['estado']);
          $array[$i]['estadoSolicitud'] 	= trim($response['estadoSolicitud']);
          $array[$i]['fechaSolicitud'] 	= trim($response['fechaSolicitud']);
          $array[$i]['fechaReg'] 	= trim($response['fechaReg']);
          // $array[$i]['tipoEnvio'] 	= trim($response['tipoEnvio']);
          if ($args['idTipoEnvio'] == 1) {
            $array[$i]['direccionOrigen'] 	= getDireccionSolicitudEmpresa($response['idSolicitud']);
          } else {
            $array[$i]['direccionOrigen'] 	= getDireccionSolicitudUsuario($response['idSolicitud']);
          }
          $i++;
        }
        echo  '{
                "errorCode": 0,
                "errorMessage": "Servicio ejecutado con exito.",
                "msg": '.json_encode($array).'
              }';
      }else{
        echo '{
                "errorCode": 2,
                "errorMessage": "No hay datos.",
                "msg": ""
              }';
      }
      $db = null;
    } else {
      echo ' {
              "errorCode": 4,
              "errorMessage": "No autenticado.",
              "msg": 0
            }';
    }
  } catch(PDOException $e) {
    $db = null;
		echo  '{
						"errorCode": 3,
						"errorMessage": "Error al ejecutar el servicio web.",
						"msg": '. $e->getMessage() .'
				  }';
  }
});

// PE18CLI - GET - RECUPERAR PASSWORD DE CLIENTE O EMPRESA
$app->post('/postRecuperarPassword', function ($request, $response) {
  $input = $request->getParsedBody();
  $sql   = "SELECT idComercio
            FROM comercio
            WHERE correo=:correo";
  try {
    $db = getConnection();
    $stmt = $db->prepare($sql);
    $stmt->bindParam("correo", $input['correo']);
    $stmt->execute();
    if ($stmt->rowCount() > 0) {
      while ($response = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $asunto = 'Contraseña provisional';
        $body = 'Su contraseña provisional es: ';
        $id =    trim($response['idComercio']);
        setNewPassComercio($id, $input['correo'], $body, $asunto);
      }
      echo '{
              "errorCode": 0,
              "errorMessage": "Servicio ejecutado con éxito",
              "msg": "Modificado Correctamente "
            }';
    }else{
      $sql = "SELECT idUsuario
              FROM usuario
              WHERE correo=:correo";
      $db = getConnection();
      $stmt = $db->prepare($sql);
      $stmt->bindParam("correo", $input['correo']);
      $stmt->execute();
      if ($stmt->rowCount() > 0) {
        while ($response = $stmt->fetch(PDO::FETCH_ASSOC)) {
          $asunto = 'Contraseña provisional';
          $body = 'Su contraseña provisional es: ';
          $id =    trim($response['idUsuario']);
          setNewPassUsuario($id, $input['correo'], $body, $asunto);
        }
        echo '{
                "errorCode": 0,
                "errorMessage": "Servicio ejecutado con éxito",
                "msg": "Modificado Correctamente "
              }';
      } else {
        echo '{
          "errorCode": 2,
          "errorMessage": "No hay datos",
          "msg": 0
        }';
      }
    }
    $db = null;
  } catch(PDOException $e) {
    $db = null;
		echo  	'{
						  "errorCode": 3,
						  "errorMessage": "Error al ejecutar el servicio web.",
					  	"msg": '. $e->getMessage() .'
				    }';
  }
});

// PE19CLI - GET - ACTUALIZAR PASSWORD DE USUARIO O COMERCIO
$app->post('/postActualizarPassword', function ($request, $response) {
  $input = $request->getParsedBody();
  $idTipoUsuario = $input['tipoUsuario'];
  if ($idTipoUsuario == 1) {
    $sql = "UPDATE comercio
            SET password=:password, recuperarPass=0
            WHERE idComercio=:idtoken";
  } else {
    $sql = "UPDATE usuario
            SET password=:password, recuperarPass=0
            WHERE idUsuario=:idtoken";
  }
  try {
    $db = getConnection();
    $stmt = $db->prepare($sql);
    $stmt->bindParam("idtoken", $input['idtoken']);
    $pw = hash_hmac('sha512', 'salt' . $input['password'], 92432);
    $stmt->bindParam('password', $pw);
    $stmt->execute();
    if ($stmt->rowCount() > 0) {
      echo '{
              "errorCode": 0,
              "errorMessage": "Servicio ejecutado con éxito",
              "msg": "Modificado Correctamente "
            }';
    }else{
      echo '{
              "errorCode": 2,
              "errorMessage": "No hay datos",
              "msg": 0
            }';
    }
    $db = null;
  } catch(PDOException $e) {
    $db = null;
		echo  	'{
						  "errorCode": 3,
						  "errorMessage": "Error al ejecutar el servicio web.",
					  	"msg": '. $e->getMessage() .'
				    }';
  }
});

// PE20CLI - GET - EDITAR PERFIL DE USUARIO O COMERCIO
$app->post('/postActualizarPerfil', function ($request, $response) {
  $input = $request->getParsedBody();
  try {
    $idTipoUsuario = $input['tipoUsuario'];
    if ($idTipoUsuario == 1) {
      $sql = "UPDATE comercio
              SET nombre=:nombre, propietario=:propietario, telefono=:telefono, direccion=:direccion, lat=:lat, lng=:lng
              WHERE idComercio=:idtoken";
      $db = getConnection();
      $stmt = $db->prepare($sql);
      $stmt->bindParam("idtoken", $input['idtoken']);
      $stmt->bindParam("nombre", $input['nombre']);
      $stmt->bindParam("propietario", $input['propietario']);
      $stmt->bindParam("telefono", $input['telefono']);
      $stmt->bindParam("direccion", $input['direccion']);
      $stmt->bindParam("lat", $input['lat']);
      $stmt->bindParam("lng", $input['lng']);
      $stmt->execute();
    } else {
      $sql = "UPDATE usuario
              SET nombre=:nombre, ci=:ci, telefono=:telefono, direccion=:direccion, lat=:lat, lng=:lng
              WHERE idUsuario=:idtoken";
      $db = getConnection();
      $stmt = $db->prepare($sql);
      $stmt->bindParam("idtoken", $input['idtoken']);
      $stmt->bindParam("nombre", $input['nombre']);
      $stmt->bindParam("ci", $input['ci']);
      $stmt->bindParam("telefono", $input['telefono']);
      $stmt->bindParam("direccion", $input['direccion']);
      $stmt->bindParam("lat", $input['lat']);
      $stmt->bindParam("lng", $input['lng']);
      $stmt->execute();
    }
    if ($stmt->rowCount() > 0) {
      echo '{
              "errorCode": 0,
              "errorMessage": "Servicio ejecutado con éxito",
              "msg": "Modificado Correctamente "
            }';
    }else{
      echo '{
              "errorCode": 2,
              "errorMessage": "No hay datos",
              "msg": 0
            }';
    }
    $db = null;
  } catch(PDOException $e) {
    $db = null;
		echo  	'{
						  "errorCode": 3,
						  "errorMessage": "Error al ejecutar el servicio web.",
					  	"msg": '. $e->getMessage() .'
				    }';
  }
});

// PE21CON - POST - CONTACTANOS
$app->post('/postContactanos', function ($request, $response) {
  $input = $request->getParsedBody();
  try {
    // $validate = getValidateCliente($input['idtoken'],$input['token']);
    $validate = true;
    if($validate) {
      $bool = sendEmailCli($input['correo'], $input['mensaje'], $input['asunto']);
      if($bool) {
        echo  '{
          "errorCode": 0,
          "errorMessage": "Servicio ejecutado con exito.",
          "msg": ""
        }';
      } else {
        echo '{
          "errorCode": 2,
          "errorMessage": "No hay datos.",
          "msg": ""
        }';
      }
    } else {
      echo ' {
              "errorCode": 4,
              "errorMessage": "No autenticado.",
              "msg": 0
            }';
    }
  } catch(PDOException $e) {
    $db = null;
		echo  	'{
						  "errorCode": 3,
						  "errorMessage": "Error al ejecutar el servicio web.",
					  	"msg": '. $e->getMessage() .'
				    }';
  }
});

// PE22CLI - GET - OBTENER INCIDENCIA
$app->get('/getIncidencia/{idtoken}/{token}/{idIncidencia}', function ($request, $response, $args) {
  $sql = "SELECT i.idIncidencia, i.detalle, i.fechaReg, i.idEstadoIncidencia, i.idSolicitud, e.nombre as estadoIncidencia, s.idTipoEnvio,
          t.nombre as tipoEnvio
          FROM incidencia i
          INNER JOIN estadoincidencia e ON e.idEstadoIncidencia=i.idEstadoIncidencia
          INNER JOIN solicitud s ON s.idSolicitud=i.idSolicitud
          INNER JOIN tipoenvio t ON t.idTipoEnvio=s.idTipoEnvio
          WHERE i.idIncidencia=:idIncidencia";
  try {
    $validate = getValidate($args['idtoken'],$args['token']);
    if($validate) {
      $db = getConnection();
      $stmt = $db->prepare($sql);
      $stmt->bindParam("idIncidencia", $args['idIncidencia']);
      $stmt->execute();
      if ( $stmt->rowCount() > 0 ) {
        $i = 0;
        while ($response  = $stmt->fetch(PDO::FETCH_ASSOC)) {
          $array['idIncidencia']   = trim($response['idIncidencia']);
          $array['detalle'] 	= trim($response['detalle']);
          $array['fechaReg'] 	= trim($response['fechaReg']);
          $array['idEstadoIncidencia'] 	= trim($response['idEstadoIncidencia']);
          $array['idSolicitud'] 	= trim($response['idSolicitud']);
          $array['estadoIncidencia'] 	= trim($response['estadoIncidencia']);
          $array['idTipoEnvio'] 	= trim($response['idTipoEnvio']);
          $array['tipoEnvio'] 	= trim($response['tipoEnvio']);
          $array['nombre'] 	= getNombreSolicitudIncidencia($response['idSolicitud'], $response['idTipoEnvio']);
          $i++;
        }
        echo  '{
                "errorCode": 0,
                "errorMessage": "Servicio ejecutado con exito.",
                "msg": '.json_encode($array).'
              }';
      }else{
        echo '{
                "errorCode": 2,
                "errorMessage": "No hay datos.",
                "msg": ""
              }';
      }
      $db = null;
    } else {
      echo ' {
              "errorCode": 4,
              "errorMessage": "No autenticado.",
              "msg": 0
            }';
    }
  } catch(PDOException $e) {
    $db = null;
		echo  '{
						"errorCode": 3,
						"errorMessage": "Error al ejecutar el servicio web.",
						"msg": '. $e->getMessage() .'
				  }';
  }
});

//PE23CLI - GET - OBTENER CANTIDAD DE SOLICITUDES REALIZADAS
$app->get('/getTotalSolicitudes/{idtoken}/{token}/{tipoUsuario}', function ($request, $response, $args) {
  $sql = "SELECT COUNT(idSolicitud) as cantidad
          FROM `solicitud`
          WHERE idEnvia=:idtoken AND idTipoEnvio=:tipoUsuario AND estado=4";
  try {
    $validate = getValidate($args['idtoken'],$args['token']);
    if($validate) {
      $db = getConnection();
      $stmt = $db->prepare($sql);
      $stmt ->bindParam('idtoken', $args['idtoken']);
      $stmt ->bindParam('tipoUsuario', $args['tipoUsuario']);
      $stmt->execute();
      if ( $stmt->rowCount() > 0 ) {
        while ($response  = $stmt->fetch(PDO::FETCH_ASSOC)) {
          $array['cantidad']   = trim($response['cantidad']);
        }
        echo  '{
                "errorCode": 0,
                "errorMessage": "Servicio ejecutado con exito.",
                "msg": '.json_encode($array).'
              }';
      }else{
        echo '{
                "errorCode": 2,
                "errorMessage": "No hay datos.",
                "msg": ""
              }';
      }
      $db = null;
    } else {
      echo ' {
              "errorCode": 4,
              "errorMessage": "No autenticado.",
              "msg": 0
            }';
    }
  } catch(PDOException $e) {
    $db = null;
		echo  '{
						"errorCode": 3,
						"errorMessage": "Error al ejecutar el servicio web.",
						"msg": '. $e->getMessage() .'
				  }';
  }
});

//PE24CLI - GET - OBTENER CANTIDAD DE SOLICITUDES POR RANGO DE FECHAS
$app->get('/getEntregasEntreFecha/{idtoken}/{token}/{fechaInicio}/{fechaFin}/{tipoUsuario}', function ($request, $response, $args) {
  $sql = "SELECT COUNT(idSolicitud) as cantidad
          FROM `solicitud`
          WHERE idEnvia=:idtoken AND idTipoEnvio=:tipoUsuario AND estado=4
          AND fechaReg BETWEEN :fechaInicio AND DATE_ADD(:fechaFin, INTERVAL 1 DAY)";
  try {
    $validate = getValidate($args['idtoken'],$args['token']);
    if($validate) {
      $db = getConnection();
      $stmt = $db->prepare($sql);
      $stmt ->bindParam('idtoken', $args['idtoken']);
      $stmt ->bindParam('tipoUsuario', $args['tipoUsuario']);
      $stmt ->bindParam('fechaInicio', $args['fechaInicio']);
      $stmt ->bindParam('fechaFin', $args['fechaFin']);
      $stmt->execute();
      if ( $stmt->rowCount() > 0 ) {
        while ($response  = $stmt->fetch(PDO::FETCH_ASSOC)) {
          $array['cantidad']   = trim($response['cantidad']);
        }
        echo  '{
                "errorCode": 0,
                "errorMessage": "Servicio ejecutado con exito.",
                "msg": '.json_encode($array).'
              }';
      }else{
        echo '{
                "errorCode": 2,
                "errorMessage": "No hay datos.",
                "msg": ""
              }';
      }
      $db = null;
    } else {
      echo ' {
              "errorCode": 4,
              "errorMessage": "No autenticado.",
              "msg": 0
            }';
    }
  } catch(PDOException $e) {
    $db = null;
		echo  '{
						"errorCode": 3,
						"errorMessage": "Error al ejecutar el servicio web.",
						"msg": '. $e->getMessage() .'
				  }';
  }
});

// PE25CLI - POST - MODIFICAR TOKEN PUSH DE USUARIO
$app->post('/postUpdatetokenpush', function ($request, $response) {
  $input = $request->getParsedBody();
  if ($input['tipoUsuario'] == 1) {
    $sql = "UPDATE comercio
            SET  tokenPush=:tokenPush
            WHERE idComercio=:idtoken";
  } else {
    $sql = "UPDATE usuario
            SET  tokenPush=:tokenPush
            WHERE idUsuario=:idtoken";
  }
  try {
    // $validate = getValidateConductor($input['idtoken'],$input['token']);
    $validate = true;
    if($validate) {
      $db = getConnection();
      $stmt = $db->prepare($sql);
      $stmt->bindParam("idtoken", $input['idtoken']);
      $stmt->bindParam("tokenPush", $input['tokenPush']);
      $stmt->execute();
      if ( $stmt->rowCount() > 0 ) {
        echo '{
                "errorCode": 0,
                "errorMessage": "Servicio ejecutado con éxito",
                "msg": "tokenPush Modificado con éxito "
              }';
      }else{
        echo '{
                "errorCode": 2,
                "errorMessage": "No modifico ningun campo.",
                "msg": "No se realizo ninguna modificacion"
              }';
      }
      $db = null;
    } else {
      echo ' {
              "errorCode": 4,
              "errorMessage": "No autenticado.",
              "msg": 0
            }';
    }
  } catch(PDOException $e) {
    $db = null;
		echo  	'{
						  "errorCode": 3,
						  "errorMessage": "Error al ejecutar el servicio web.",
					  	"msg": '. $e->getMessage() .'
				    }';
  }
});

// PE15CLI - GET - OBTENER VERSIÓN DE APP
$app->get('/getVersion', function ($request, $response, $args) {
  $sql = "SELECT version
          FROM configuracion";
  try {
    // $validate = getValidate($args['idtoken'],$args['token']);
    $validate= true;
    if($validate) {
      $db = getConnection();
      $stmt = $db->prepare($sql);
      $stmt->execute();
      if ( $stmt->rowCount() > 0 ) {
        $i = 0;
        while ($response  = $stmt->fetch(PDO::FETCH_ASSOC)) {
          $array['version']   = trim($response['version']);
        }
        echo  '{
                "errorCode": 0,
                "errorMessage": "Servicio ejecutado con exito.",
                "msg": '.json_encode($array).'
              }';
      }else{
        echo '{
                "errorCode": 2,
                "errorMessage": "No hay datos.",
                "msg": ""
              }';
      }
      $db = null;
    } else {
      echo '{
              "errorCode": 4,
              "errorMessage": "No autenticado.",
              "msg": 0
            }';
    }
  } catch(PDOException $e) {
    $db = null;
		echo  '{
						"errorCode": 3,
						"errorMessage": "Error al ejecutar el servicio web.",
						"msg": '. $e->getMessage() .'
				  }';
  }
});

// PE16CLI - POST - LOGIN APPLE
$app->post('/postLoginClienteApple', function ($request, $response) {
  $input = $request->getParsedBody();
  // $sql = "SELECT idUsuario, nombre, correo, telefono
  //         FROM usuario
  //         WHERE password=:user";
  try {
    $bool = existeUsuarioApple($input['user']);
    if ($bool) {
      $sql = "SELECT idUsuario, nombre, ci, telefono, direccion, lat, lng, usuario, correo
          FROM usuario
          WHERE (password=:user AND estado=1)";
  
      $db = getConnection();
      $stmt = $db->prepare($sql);
      $stmt->bindParam("user", $input['user']);
      $stmt->execute();
      if ( $stmt->rowCount() > 0 ) {
        $response = $stmt->fetch(PDO::FETCH_ASSOC);

        $id			  			= trim($response['idUsuario']);
        $arrUser['idUsuario']   = trim($response['idUsuario']);
        $arrUser['nombre'] 	= trim($response['nombre']);
        $arrUser['ci'] 	= trim($response['ci']);
        $arrUser['telefono'] 	= trim($response['telefono']);
        $arrUser['direccion'] 	= trim($response['direccion']);
        $arrUser['lat'] 	= trim($response['lat']);
        $arrUser['lng'] 	= trim($response['lng']);
        $arrUser['usuario'] 	= trim($response['usuario']);
        $arrUser['correo'] 	= trim($response['correo']);
        $arrUser['tipoUsuario'] 	= trim('2');
        $token 					= setTokenUsuario($id);
        $arrUser['token']= trim($token);
        echo '{
                "errorCode": 0,
                "errorMessage": "Servicio ejecutado con exito.",
                "msg": '.json_encode($arrUser).'
              }';
      }else{
        echo '{
                "errorCode": 3,
                "errorMessage": "No hay datos.",
                "msg": ""
              }';
      }
      $db = null;
    } else {
      $sql = "INSERT INTO usuario (nombre, correo, telefono, password, estado)
              VALUES (:nombre, :correo, :telefono, :password, 1)";
      try {
        $db = getConnection();
        $stmt = $db->prepare($sql);
        $vacio = ' ';
        $stmt->bindParam("nombre", $input['nombre']);
        $stmt->bindParam("correo", $input['correo']);
        $stmt->bindParam("telefono", $vacio);
        $stmt->bindParam("password", $input['user']);
        $stmt->execute();
        $id = $db->lastInsertID();
        $token = setTokenUsuario($id);
        $arrUser['idUsuario'] = trim($id);
        $arrUser['token']= trim($token);
        $arrUser['nombre']= $input['nombre'];
        $arrUser['correo']= $input['correo'];
        echo '{
                "errorCode": 0,
                "errorMessage": "Servicio ejecutado con éxito",
                "msg": "Registrado Correctamente",
                "obj": '.json_encode($arrUser).'
              }';
        $db = null;
      } catch(PDOException $e) {
        $db = null;
        echo '{
                "errorCode": 2,
                "errorMessage": "Error al ejecutar el servicio web.",
                "msg": '. $e->getMessage() .'
              }';
      }
    }
  } catch(PDOException $e) {
    $db = null;
    echo '{
            "errorCode": 2,
            "errorMessage": "Error al ejecutar el servicio web.",
            "msg": '. $e->getMessage() .'
          }';
  }
});

// RGT-85 CLI - GET - Comercio detalle
$app->get('/getComercioDetalle/{idtoken}/{token}/{idProveedor}',function ($request, $response,$data) {

    $idProveedor= $data['idProveedor'];
    $validate = getValidateCliente($data['idtoken'],$data['token']);
    if($validate) {
        try{
            $sql = "SELECT P.idProveedor,P.nombre,P.estado,P.usuario,P.password,P.token,P.foto
       FROM proveedor as P
       WHERE P.idProveedor=:idProveedor;";

            $db = getConnection();
            $stmt = $db->prepare($sql);
            $stmt ->bindParam('idProveedor', $idProveedor);
            $stmt->execute();
            if ( $stmt->rowCount() > 0 ) {
                $data = $stmt->fetch(PDO::FETCH_ASSOC);
                $array['idProveedor']        = trim($data['idProveedor']);
                $array['nombre']            = trim($data['nombre']);
                $array['estado']           = trim($data['estado']);
                $array['usuario']           = trim($data['usuario']);
                $array['password']              = trim($data['password']);
                $array['token']              = trim($data['token']);
                $array['foto']             = RUTA_IMGproveedor.trim($data['foto']);
                $array['sucursales']             = getSucursales($data['idProveedor']);

                echo ' {
                "errorCode": 0,
                "errorMessage": "Servicio ejecutado con éxito",
                "msg": '.json_encode($array).'
            }';
            }else{
                echo ' {
                "errorCode": 2,
                "errorMessage": "No hay datos.",
                "msg": 0
            }';
            }
            $db = null;
        } catch(PDOException $e){
            $db = null;
            echo $e;
            echo ' {
                "errorCode": 3,
                "errorMessage": "Error al ejecutar el servicio web.",
                "msg": 0
            }';
        }
    } else {
        echo ' {
          "errorCode": 4,
          "errorMessage": "No autenticado.",
          "msg": 0
     }';
    }
});

function getSucursales($idProveedor) {
  try{
      $sql = "SELECT *
      FROM sucursal 
      WHERE idProveedor = $idProveedor ;";
      $db = getConnection();
      $stmt = $db->prepare($sql);
      $stmt->execute();
      $i = 0;
      if ( $stmt->rowCount() > 0 ) {
          while ($data = $stmt->fetch(PDO::FETCH_ASSOC)){
           $array[$i]['idSucursal'] = trim($data['idSucursal']);
           $array[$i]['nombre'] = trim($data['nombre']);
           $array[$i]['direccion'] = trim($data['direccion']);
           $array[$i]['telefono'] = trim($data['telefono']);
           $i++;
          }
          return $array;
      }else{
          return 0;
      }
      $db = null;
  } catch(PDOException $e){
      $db = null;
      echo $e;
          echo ' {
              "errorCode": 3,
              "errorMessage": "Error al ejecutar el servicio web.",
              "msg": 0
          }';
  }
}

// RGT-87 CLI - GET - Productos por palabra clave
$app->get('/getProductosPorPalabra/{idtoken}/{token}/{comercio}/{texto}/{inicio}/{cantidad}/{latitud}/{longitud}',function ($request, $response,$data) {

    $texto = '%'.$data['texto'].'%';
    $rango = getRangoBusqueda();

    $validate = getValidateCliente($data['idtoken'],$data['token']);
    if($validate) {
        try {
            if ($data['comercio'] != 0) {
                    $sql = "SELECT P.idProducto,P.nombre,P.detalle,P.peso,P.fecha,P.estado,P.descuentoPromo,P.foto,P.codigoInterno,
                              P.alto,P.ancho,P.largo,P.descripcion,P.etiqueta,P.idProveedor,
                              ProV.Nombre as nombreProductoVariante,
                              ProV.idProductoVariante, ProS.idSucursal
                            FROM producto P INNER JOIN proveedor Pro  ON  Pro.idProveedor = P.idProveedor
                                 INNER JOIN  sucursal s ON P.idProveedor = s.idProveedor
                                 INNER JOIN productoVariante ProV ON ProV.idProducto = P.idProducto
                                  INNER JOIN productoSucursal ProS ON ProS.idProductoVariante = ProV.idProductoVariante
                            WHERE ProS.idSucursal= s.idSucursal and Pro.idProveedor=:comercio  and  
                        (acos(sin(radians(s.latitud)) * sin(radians(:lat)) +
                        cos(radians(s.latitud)) * cos(radians(:lat)) *
                        cos(radians(s.longitud) - radians(:lng))) * 6378)<=:rango 
                        and ( P.detalle LIKE :texto OR P.etiqueta LIKE :texto OR Pro.nombre LIKE :texto)
                        LIMIT :inicio, :cantidad ;";

                    $db = getConnection();
                    $stmt = $db->prepare($sql);
                    $stmt->bindParam('comercio', $data['comercio']);
                    $stmt->bindParam('texto', $texto);
                    $stmt->bindParam("lat", $data['latitud']);
                    $stmt->bindParam("lng", $data['longitud']);
                    $stmt->bindParam('rango', $rango);
                    $stmt->bindParam('inicio', $data['inicio'], PDO::PARAM_INT);
                    $stmt->bindParam('cantidad', $data['cantidad'], PDO::PARAM_INT);
                    $stmt->execute();
            } else {
                $sql = "SELECT P.idProducto,P.nombre,P.detalle,P.peso,P.fecha,P.estado,P.descuentoPromo,P.foto,P.codigoInterno,
                              P.alto,P.ancho,P.largo,P.descripcion,P.etiqueta,P.idProveedor,
                              ProV.Nombre as nombreProductoVariante,
                              ProV.idProductoVariante, ProS.idSucursal
                            FROM producto P INNER JOIN proveedor Pro  ON  Pro.idProveedor = P.idProveedor
                                 INNER JOIN  sucursal s ON P.idProveedor = s.idProveedor
                                 INNER JOIN productoVariante ProV ON ProV.idProducto = P.idProducto
                                  INNER JOIN productoSucursal ProS ON ProS.idProductoVariante = ProV.idProductoVariante
                            WHERE ProS.idSucursal= s.idSucursal and  
                        (acos(sin(radians(s.latitud)) * sin(radians(:lat)) +
                        cos(radians(s.latitud)) * cos(radians(:lat)) *
                        cos(radians(s.longitud) - radians(:lng))) * 6378)<=:rango 
                        and ( P.detalle LIKE :texto OR P.etiqueta LIKE :texto OR Pro.nombre LIKE :texto)
                        LIMIT :inicio, :cantidad ;";
                $db = getConnection();
                $stmt = $db->prepare($sql);
                $stmt->bindParam('texto', $texto);
                $stmt->bindParam("lat", $data['latitud']);
                $stmt->bindParam("lng", $data['longitud']);
                $stmt->bindParam('rango', $rango);
                $stmt->bindParam('inicio', $data['inicio'], PDO::PARAM_INT);
                $stmt->bindParam('cantidad', $data['cantidad'], PDO::PARAM_INT);
                $stmt->execute();
            }
            if ($stmt->rowCount() > 0) {
                $i = 0;
                while ($response = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $array[$i]['idProducto'] = trim($response['idProducto']);
                    $array[$i]['nombre'] = trim($response['nombre']);
                    $array[$i]['detalle'] = trim($response['detalle']);
                    $array[$i]['peso'] = trim($response['peso']);
                    $array[$i]['fecha'] = trim($response['fecha']);
                    $array[$i]['estado'] = trim($response['estado']);
                    $array[$i]['descuentoPromo'] = trim($response['descuentoPromo']);
                    if (trim($response['foto']) != null) {
                        $imagen = RUTA_IMGproducto . $response['idProveedor'] . '/' . trim($response['foto']);
                    } else {
                        $imagen = 'https://demoweb.reggat.com/assets/productos/default.png';
                    }
                    $array[$i]['foto'] = $imagen;
                    $array[$i]['codigoInterno'] = trim($response['codigoInterno']);
                    $array[$i]['alto'] = trim($response['alto']);
                    $array[$i]['ancho'] = trim($response['ancho']);
                    $array[$i]['largo'] = trim($response['largo']);
                    $array[$i]['descripcion'] = trim($response['descripcion']);
                    $array[$i]['etiqueta'] = trim($response['etiqueta']);
                    $array[$i]['idProveedor'] = trim($response['idProveedor']);
                    $array[$i]['idSucursal'] = trim($response['idSucursal']);
                    $array[$i]["variantes"] = getDetallesProducto($response['idProductoVariante']);
                    $i++;
                }
                $arraySub['productos'] = $array;
                echo ' {
                "errorCode": 0,
                "errorMessage": "Servicio ejecutado con éxito",
                "msg": ' . json_encode($arraySub) . '
            }';
            }else{
                echo ' {
                "errorCode": 2,
                "errorMessage": "No hay datos.",
                "msg": 0
            }';
            }
            $db = null;
        } catch(PDOException $e){
            $db = null;
            echo $e;
            echo ' {
                "errorCode": 3,
                "errorMessage": "Error al ejecutar el servicio web.",
                "msg": 0
            }';
        }
    } else {
        echo ' {
          "errorCode": 4,
          "errorMessage": "No autenticado.",
          "msg": 0
     }';
    }
});

// RGT-87-A CLI - GET - Productos por palabra clave info Basica
$app->get('/getProductosPorPalabraBasico/{idtoken}/{token}/{comercio}/{texto}/{inicio}/{cantidad}/{latitud}/{longitud}',function ($request, $response,$data) {

    $texto = '%'.$data['texto'].'%';
    $rango = getRangoBusqueda();

    $validate = getValidateCliente($data['idtoken'],$data['token']);
    if($validate) {
        try {
            if ($data['comercio'] != 0) {
                $sql = "SELECT P.nombre,Pro.nombre as nombreProveedor
                            FROM producto P INNER JOIN proveedor Pro  ON  Pro.idProveedor = P.idProveedor
                                 INNER JOIN  sucursal s ON P.idProveedor = s.idProveedor
                                 INNER JOIN productoVariante ProV ON ProV.idProducto = P.idProducto
                                  INNER JOIN productoSucursal ProS ON ProS.idProductoVariante = ProV.idProductoVariante
                            WHERE ProS.idSucursal= s.idSucursal and Pro.idProveedor=:comercio  and  
                        (acos(sin(radians(s.latitud)) * sin(radians(:lat)) +
                        cos(radians(s.latitud)) * cos(radians(:lat)) *
                        cos(radians(s.longitud) - radians(:lng))) * 6378)<=:rango 
                        and ( P.detalle LIKE :texto OR P.etiqueta LIKE :texto OR Pro.nombre LIKE :texto)
                        LIMIT :inicio, :cantidad ;";

                $db = getConnection();
                $stmt = $db->prepare($sql);
                $stmt->bindParam('comercio', $data['comercio']);
                $stmt->bindParam('texto', $texto);
                $stmt->bindParam("lat", $data['latitud']);
                $stmt->bindParam("lng", $data['longitud']);
                $stmt->bindParam('rango', $rango);
                $stmt->bindParam('inicio', $data['inicio'], PDO::PARAM_INT);
                $stmt->bindParam('cantidad', $data['cantidad'], PDO::PARAM_INT);
                $stmt->execute();
            } else {
                $sql = "SELECT P.nombre,Pro.nombre as nombreProveedor
                         FROM producto P INNER JOIN proveedor Pro  ON  Pro.idProveedor = P.idProveedor
                                 INNER JOIN  sucursal s ON P.idProveedor = s.idProveedor
                                 INNER JOIN productoVariante ProV ON ProV.idProducto = P.idProducto
                                  INNER JOIN productoSucursal ProS ON ProS.idProductoVariante = ProV.idProductoVariante
                        WHERE ProS.idSucursal= s.idSucursal and (acos(sin(radians(s.latitud)) * sin(radians(:lat)) +
                        cos(radians(s.latitud)) * cos(radians(:lat)) *
                        cos(radians(s.longitud) - radians(:lng))) * 6378)<=:rango 
                        and( P.detalle LIKE :texto OR 
                        P.etiqueta LIKE :texto or Pro.nombre LIKE :texto)
                        LIMIT :inicio, :cantidad ;";
                $db = getConnection();
                $stmt = $db->prepare($sql);
                $stmt->bindParam('texto', $texto);
                $stmt->bindParam("lat", $data['latitud']);
                $stmt->bindParam("lng", $data['longitud']);
                $stmt->bindParam('rango', $rango);
                $stmt->bindParam('inicio', $data['inicio'], PDO::PARAM_INT);
                $stmt->bindParam('cantidad', $data['cantidad'], PDO::PARAM_INT);
                $stmt->execute();
            }
            if ($stmt->rowCount() > 0) {
                $i = 0;
                while ($response = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $array[$i]['nombre'] = trim($response['nombre']);
                    $array[$i]['nombreProveedor'] = trim($response['nombreProveedor']);
                    $i++;
                }
                $arraySub['productos'] = $array;
                echo ' {
                "errorCode": 0,
                "errorMessage": "Servicio ejecutado con éxito",
                "msg": ' . json_encode($arraySub) . '
            }';
            }else{
                echo ' {
                "errorCode": 2,
                "errorMessage": "No hay datos.",
                "msg": 0
            }';
            }
            $db = null;
        } catch(PDOException $e){
            $db = null;
            echo $e;
            echo ' {
                "errorCode": 3,
                "errorMessage": "Error al ejecutar el servicio web.",
                "msg": 0
            }';
        }
    } else {
        echo ' {
          "errorCode": 4,
          "errorMessage": "No autenticado.",
          "msg": 0
     }';
    }
});

// RGT-86 CLI - GET - Productos por comercio
$app->get('/getProductosPorComercio/{idtoken}/{token}/{idcomercio}/{inicio}/{cantidad}',function ($request, $response,$data) {

  $inicio = $data['inicio'];
  $cantidad = $data['cantidad'];
  $validate = getValidateCliente($data['idtoken'],$data['token']);
  if($validate) {
      try{
          $sql = "SELECT P.idProducto,P.nombre,P.detalle,P.peso,P.fecha,P.estado,P.descuentoPromo,P.foto,P.codigoInterno,
                         P.alto,P.ancho,P.largo,P.descripcion,P.etiqueta,P.idProveedor, 
                         ProV.Nombre as nombreProductoVariante,
                         ProV.idProductoVariante, ProSu.idSucursal
                      FROM producto as P, proveedor as Pro, productoVariante as ProV, productoSucursal as ProSu   
                      WHERE P.idProveedor = Pro.idProveedor and ProV.idProducto = P.idProducto
                      and ProV.idProductoVariante = ProSu.idProductoVariante 
                      and P.idProveedor = :idProveedor          
                  ORDER BY Pro.nombre ASC 
                  LIMIT :inicio, :cantidad ; ";



          $db = getConnection();
          $stmt = $db->prepare($sql);
          $stmt ->bindParam('inicio', $inicio,PDO::PARAM_INT);
          $stmt ->bindParam('cantidad', $cantidad, PDO::PARAM_INT);
          $stmt ->bindParam('idProveedor', $data['idcomercio'], PDO::PARAM_INT);
          $stmt->execute();
          if ( $stmt->rowCount() > 0 ) {
              $i = 0;
              while ($response  = $stmt->fetch(PDO::FETCH_ASSOC)) {
                  $array[$i]["producto"]['idProducto']   = trim($response['idProducto']);
                  $array[$i]["producto"]['nombre'] 	= trim($response['nombre']);
                  $array[$i]["producto"]['detalle'] 	= trim($response['detalle']);
                  $array[$i]["producto"]['peso'] 	= trim($response['peso']);
                  $array[$i]["producto"]['fecha'] 	= trim($response['fecha']);
                  $array[$i]["producto"]['estado'] 	= trim($response['estado']);
                  $array[$i]["producto"]['descuentoPromo'] 	= trim($response['descuentoPromo']);
                  if(trim($response['foto'])!= null){
                      $imagen = RUTA_IMGproducto.trim($response['foto']);
                  }else{
                      $imagen = RUTA_IMGproducto.'default.png';
                  }
                  $array[$i]["producto"]['foto'] 	= $imagen;
                  $array[$i]["producto"]['codigoInterno'] 	= trim($response['codigoInterno']);
                  $array[$i]["producto"]['alto'] 	= trim($response['alto']);
                  $array[$i]["producto"]['ancho'] 	= trim($response['ancho']);
                  $array[$i]["producto"]['largo'] 	= trim($response['largo']);
                  $array[$i]["producto"]['descripcion'] 	= trim($response['descripcion']);
                  $array[$i]["producto"]['etiqueta'] 	= trim($response['etiqueta']);
                  $array[$i]["producto"]['idProveedor'] 	= trim($response['idProveedor']);
                  $array[$i]["producto"]['idSucursal'] 	= trim($response['idSucursal']);
                  $array[$i]["variantes"] = getDetallesProducto($response['idProductoVariante']);
                  $i++;
              }
              echo ' {
              "errorCode": 0,
              "errorMessage": "Servicio ejecutado con éxito",
              "msg": '.json_encode($array).'
          }';
          }else{
              echo ' {
              "errorCode": 2,
              "errorMessage": "No hay datos.",
              "msg": 0
          }';
          }
          $db = null;
      } catch(PDOException $e){
          $db = null;
          echo $e;
          echo ' {
              "errorCode": 3,
              "errorMessage": "Error al ejecutar el servicio web.",
              "msg": 0
          }';
      }
  } else {
      echo ' {
        "errorCode": 4,
        "errorMessage": "No autenticado.",
        "msg": 0
   }';
  }
});

// RGT-86 CLI - GET - Productos por comercio
$app->get('/getProductosPorSucursalComercio/{idtoken}/{token}/{idSucursal}/{inicio}/{cantidad}',function ($request, $response,$data) {

    $inicio = $data['inicio'];
    $cantidad = $data['cantidad'];
    $validate = getValidateCliente($data['idtoken'],$data['token']);
    if($validate) {
        try{
            $sql = "SELECT P.idProducto,P.nombre,P.detalle,P.peso,P.fecha,P.estado,P.descuentoPromo,P.foto,P.codigoInterno,
                           P.alto,P.ancho,P.largo,P.descripcion,P.etiqueta,P.idProveedor, 
                           ProV.Nombre as nombreProductoVariante,
                           ProV.idProductoVariante, ProSu.idSucursal
                        FROM producto as P, proveedor as Pro, productoVariante as ProV, productoSucursal as ProSu   
                        WHERE P.idProveedor = Pro.idProveedor and ProV.idProducto = P.idProducto
                        and ProV.idProductoVariante = ProSu.idProductoVariante 
                        and ProSu.idSucursal = :idSucursal         
                    ORDER BY Pro.nombre ASC 
                    LIMIT :inicio, :cantidad ; ";



            $db = getConnection();
            $stmt = $db->prepare($sql);
            $stmt ->bindParam('inicio', $inicio,PDO::PARAM_INT);
            $stmt ->bindParam('cantidad', $cantidad, PDO::PARAM_INT);
            $stmt ->bindParam('idSucursal', $data['idSucursal'], PDO::PARAM_INT);
            $stmt->execute();
            if ( $stmt->rowCount() > 0 ) {
                $i = 0;
                while ($response  = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $array[$i]['idProducto']   = trim($response['idProducto']);
                    $array[$i]['nombre'] 	= trim($response['nombre']);
                    $array[$i]['detalle'] 	= trim($response['detalle']);
                    $array[$i]['peso'] 	= trim($response['peso']);
                    $array[$i]['fecha'] 	= trim($response['fecha']);
                    $array[$i]['estado'] 	= trim($response['estado']);
                    $array[$i]['precio'] 	= getPrecioMinimo($response['idProducto']);
                    $array[$i]['descuentoPromo'] 	= trim($response['descuentoPromo']);
                    if(trim($response['foto'])!= null){
                        $imagen = RUTA_IMGproducto.$response['idProveedor'].'/'.trim($response['foto']);
                    }else{
                        $imagen = RUTA_IMGproducto.'default.png';
                    }
                    $array[$i]['foto'] 	= $imagen;
                    $array[$i]['codigoInterno'] 	= trim($response['codigoInterno']);
                    $array[$i]['alto'] 	= trim($response['alto']);
                    $array[$i]['ancho'] 	= trim($response['ancho']);
                    $array[$i]['largo'] 	= trim($response['largo']);
                    $array[$i]['descripcion'] 	= trim($response['descripcion']);
                    $array[$i]['etiqueta'] 	= trim($response['etiqueta']);
                    $array[$i]['idProveedor'] 	= trim($response['idProveedor']);
                    $array[$i]['idSucursal'] 	= trim($response['idSucursal']);
                    $array[$i]["variantes"] = getDetallesProducto($response['idProductoVariante']);
                    $i++;
                }
                echo ' {
                "errorCode": 0,
                "errorMessage": "Servicio ejecutado con éxito",
                "msg": '.json_encode($array).'
            }';
            }else{
                echo ' {
                "errorCode": 2,
                "errorMessage": "No hay datos.",
                "msg": 0
            }';
            }
            $db = null;
        } catch(PDOException $e){
            $db = null;
            echo $e;
            echo ' {
                "errorCode": 3,
                "errorMessage": "Error al ejecutar el servicio web.",
                "msg": 0
            }';
        }
    } else {
        echo ' {
          "errorCode": 4,
          "errorMessage": "No autenticado.",
          "msg": 0
     }';
    }
});

// RGT-88 CLI - POST - Insertar Productos Favoritos
$app->post('/postProductoFavoritoAgregar/{idtoken}/{token}/{idCliente}/{idProductoVariante}',function ($request, $response,$data) {

    $idCliente = $data['idCliente'];
    $idProductoVariante = $data['idProductoVariante'];
    $fechaRegistro = date("Y-m-d H:i:s");
    $validate = getValidateCliente($data['idtoken'],$data['token']);
    if($validate) {
        try{
            if(getExisteProductoFavorito($idCliente,$idProductoVariante) &&
                getExisteProductoVariante($idProductoVariante) &&
                getCliente($idCliente)){
                $sql = "INSERT INTO productoFavorito(idProductoVariante, idCliente, estado, fechaRegistro)
                        VALUES (:idProductoVariante,:idCliente, 1, :fechaRegistro)";

                $db = getConnection();
                $stmt = $db->prepare($sql);
                $stmt ->bindParam('idCliente', $idCliente);
                $stmt ->bindParam('idProductoVariante', $idProductoVariante);
                $stmt ->bindParam('fechaRegistro', $fechaRegistro);
                $stmt->execute();
                echo ' {
                "errorCode": 0,
                "errorMessage": "Servicio ejecutado con éxito",
                "msg": "Producto Registrado Correctamente en favoritos"
            }';

                $db = null;
            }else{
                echo ' {
                "errorCode": 5,
                "errorMessage": "El producto ya esta registrado en favorito o los parametros de entrada son incorectos",
                "msg": "0"
            }';
            }

        } catch(PDOException $e){
            $db = null;
            echo $e;
            echo ' {
                "errorCode": 3,
                "errorMessage": "Error al ejecutar el servicio web.",
                "msg": 0
            }';
        }
    } else {
        echo ' {
          "errorCode": 4,
          "errorMessage": "No autenticado.",
          "msg": 0
     }';
    }
});

function getExisteProductoFavorito($idCliente, $idProductoVariante){
    try{
        $sql = "SELECT * 
			        FROM productoFavorito
			        WHERE idCliente = :idCliente and idProductoVariante = :idProductoVariante ";
        $db   = getConnection();
        $stmt = $db->prepare($sql);
        $stmt->bindParam("idCliente",$idCliente);
        $stmt->bindParam("idProductoVariante",$idProductoVariante);
        $stmt->execute();
        if ( $stmt->rowCount() > 0 ) {
            return false;
        } else {
            return true;
        }
        $db = null;
    } catch(PDOException $e){
        $db = null;
        echo '{
						"errorCode": 3,
						"errorMessage": "Error al ejecutar el servicio web.",
						"msg": "0"
				  }';
    }
};
function getCliente($idCliente){
    try{
        $sql = "SELECT * 
			        FROM cliente
			        WHERE idCliente = :idCliente ; ";
        $db   = getConnection();
        $stmt = $db->prepare($sql);
        $stmt->bindParam("idCliente",$idCliente);
        $stmt->execute();
        if ( $stmt->rowCount() > 0 ) {
            return true;
        } else {
            return false;
        }
        $db = null;
    } catch(PDOException $e){
        $db = null;
        echo '{
						"errorCode": 3,
						"errorMessage": "Error al ejecutar el servicio web.",
						"msg": "0"
				  }';
    }
};

// RGT-89 CLI - DElETE - Elimina Producto Favorito
$app->delete('/postProductoFavoritoQuitar/{idtoken}/{token}/{idCliente}/{idProductoVariante}',function ($request, $response,$data) {

    $idCliente = $data['idCliente'];
    $idProductoVariante = $data['idProductoVariante'];
    $validate = getValidateCliente($data['idtoken'],$data['token']);
    if($validate) {
        try{
            if(!getExisteProductoFavorito($idCliente,$idProductoVariante)){
                $sql = "DELETE FROM productoFavorito 
                        WHERE productoFavorito.idProductoVariante=:idProductoVariante and productoFavorito.idCliente=:idCliente ";

                $db = getConnection();
                $stmt = $db->prepare($sql);
                $stmt ->bindParam('idCliente', $idCliente);
                $stmt ->bindParam('idProductoVariante', $idProductoVariante);
                $stmt->execute();
                echo ' {
                "errorCode": 0,
                "errorMessage": "Servicio ejecutado con éxito",
                "msg": "Producto Eliminado Correctamente de favoritos"
            }';

                $db = null;
            }else{
                echo ' {
                "errorCode": 5,
                "errorMessage": "El producto no esta registrado en favorito",
                "msg": "0"
            }';
            }

        } catch(PDOException $e){
            $db = null;
            echo $e;
            echo ' {
                "errorCode": 3,
                "errorMessage": "Error al ejecutar el servicio web.",
                "msg": 0
            }';
        }
    } else {
        echo ' {
          "errorCode": 4,
          "errorMessage": "No autenticado.",
          "msg": 0
     }';
    }
});

// RGT-90 CLI - GET - Lista de Favoritos
$app->get('/getListaFavorito/{idtoken}/{token}/{idCliente}',function ($request, $response,$data) {

    $idCliente = $data['idCliente'];
    $validate = getValidateCliente($data['idtoken'],$data['token']);
    if($validate) {
        try{
            $sql = "SELECT P.idProducto,P.nombre,P.detalle,P.peso,P.fecha,P.estado,P.descuentoPromo,P.foto,P.codigoInterno,
                           P.alto,P.ancho,P.largo,P.descripcion,P.etiqueta,P.idProveedor, 
                           ProV.Nombre as nombreProductoVariante,
                           ProV.idProductoVariante, ProSu.idSucursal
                        FROM producto as P, proveedor as Pro, productoVariante as ProV, productoSucursal as ProSu,
                             cliente as c, productoFavorito as proF
                        WHERE P.idProveedor = Pro.idProveedor and ProV.idProducto = P.idProducto
                        and ProV.idProductoVariante = ProSu.idProductoVariante and c.idCliente= proF.idCliente and 
                        ProV.idProductoVariante = proF.idProductoVariante
                        and c.idCliente=:idCliente ;";

                $db = getConnection();
                $stmt = $db->prepare($sql);
                $stmt ->bindParam('idCliente', $idCliente);
                $stmt->execute();
            if ($stmt->rowCount() > 0 ) {
                $i = 0;
                while ($response  = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $array[$i]["producto"]['idProducto']   = trim($response['idProducto']);
                    $array[$i]["producto"]['nombre'] 	= trim($response['nombre']);
                    $array[$i]["producto"]['detalle'] 	= trim($response['detalle']);
                    $array[$i]["producto"]['peso'] 	= trim($response['peso']);
                    $array[$i]["producto"]['fecha'] 	= trim($response['fecha']);
                    $array[$i]["producto"]['estado'] 	= trim($response['estado']);
                    $array[$i]["producto"]['descuentoPromo'] 	= trim($response['descuentoPromo']);
                    if(trim($response['foto'])!= null){
                        $imagen ='https://demoweb.reggat.com/assets/productos/'.trim($response['foto']);
                    }else{
                        $imagen = 'https://demoweb.reggat.com/assets/productos/default.png';
                    }
                    $array[$i]["producto"]['foto'] 	= $imagen;
                    $array[$i]["producto"]['codigoInterno'] 	= trim($response['codigoInterno']);
                    $array[$i]["producto"]['alto'] 	= trim($response['alto']);
                    $array[$i]["producto"]['ancho'] 	= trim($response['ancho']);
                    $array[$i]["producto"]['largo'] 	= trim($response['largo']);
                    $array[$i]["producto"]['descripcion'] 	= trim($response['descripcion']);
                    $array[$i]["producto"]['etiqueta'] 	= trim($response['etiqueta']);
                    $array[$i]["producto"]['idProveedor'] 	= trim($response['idProveedor']);
                    $array[$i]["producto"]['idSucursal'] 	= trim($response['idSucursal']);
                    $array[$i]["variantes"] = getDetallesProducto($response['idProductoVariante']);
                    $i++;
                }
                echo ' {
                "errorCode": 0,
                "errorMessage": "Servicio ejecutado con éxito",
                "msg": '.json_encode($array).'
            }';
            }else{
                echo ' {
                "errorCode": 2,
                "errorMessage": "No hay datos.",
                "msg": 0
            }';
            }
            $db = null;
        } catch(PDOException $e){
            $db = null;
            echo $e;
            echo ' {
                "errorCode": 3,
                "errorMessage": "Error al ejecutar el servicio web.",
                "msg": 0
            }';
        }
    } else {
        echo ' {
          "errorCode": 4,
          "errorMessage": "No autenticado.",
          "msg": 0
     }';
    }
});

// RGT-91 CLI - GET - Historial de Compra
$app->get('/getHistorialCompra/{idtoken}/{token}/{inicio}/{cantidad}',function ($request, $response,$data) {

    $inicio = $data['inicio'];
    $cantidad = $data['cantidad'];
    $validate = getValidateCliente($data['idtoken'],$data['token']);
    if($validate) {
        try{
            $sql = "SELECT * FROM pedido AS p ORDER BY idPedido DESC
              LIMIT :inicio, :cantidad ;";

            $db = getConnection();
            $stmt = $db->prepare($sql);
            $stmt ->bindParam('inicio', $inicio,PDO::PARAM_INT);
            $stmt ->bindParam('cantidad', $cantidad, PDO::PARAM_INT);
            $stmt->execute();
            if ( $stmt->rowCount() > 0 ) {
                $i = 0;
                while ($response  = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $array[$i]['idPedido']   = trim($response['idPedido']);
                    $array[$i]['idCliente'] 	= trim($response['idCliente']);
                    $array[$i]['idConductor'] 	= trim($response['idConductor']);
                    $array[$i]['idDireccion'] 	= trim($response['idDireccion']);
                    $array[$i]['idFormaPago'] 	= trim($response['idFormaPago']);
                    $array[$i]['idEstadoPedido'] 	= trim($response['idEstadoPedido']);
                    $array[$i]['idTipoVehiculo'] 	= trim($response['idTipoVehiculo']);
                    $array[$i]['latitud'] 	= trim($response['latitud']);
                    $array[$i]['longitud'] 	= trim($response['longitud']);
                    $array[$i]['detalle'] 	= trim($response['detalle']);
                    $array[$i]['msjTransportista'] 	= trim($response['msjTransportista']);
                    $array[$i]['msjProveedor'] 	= trim($response['msjProveedor']);
                    $array[$i]['total'] 	= trim($response['total']);
                    $array[$i]['precioTrasporte'] 	= trim($response['precioTrasporte']);
                    $array[$i]['usobono'] 	= trim($response['usobono']);
                    $array[$i]['detalleBono'] 	= trim($response['detalleBono']);
                    $array[$i]['firmaDigital'] 	= trim($response['firmaDigital']);
                    $array[$i]['fechaEstimada'] 	= trim($response['fechaEstimada']);
                    $array[$i]['horarioEstimado'] 	= trim($response['horarioEstimado']);
                    $array[$i]['fechaEnvio'] 	= trim($response['fechaEnvio']);
                    $array[$i]['horarioEnvio'] 	= trim($response['horarioEnvio']);
                    $array[$i]['fechaEntrega'] 	= trim($response['fechaEntrega']);
                    $array[$i]['horarioEntrega'] 	= trim($response['horarioEntrega']);
                    $array[$i]['fechaReg'] 	= trim($response['fechaReg']);
                    $array[$i]['fechaRegconductor'] 	= trim($response['fechaRegconductor']);
                    $array[$i]['checkPedido'] 	= trim($response['checkPedido']);
                    $array[$i]['fechaProgramada'] 	= trim($response['fechaProgramada']);
                    $array[$i]['horarioProgramado'] 	= trim($response['horarioProgramado']);
                    $array[$i]['cobrado'] 	= trim($response['cobrado']);
                    $array[$i]['nit'] 	= trim($response['nit']);
                    $array[$i]['razonSocial'] 	= trim($response['razonSocial']);
                    $array[$i]['pagoBanco'] 	= trim($response['pagoBanco']);
                    $array[$i]['estadoFinanciero'] 	= trim($response['estadoFinanciero']);
                    $array[$i]['idTransaccion'] 	= trim($response['idTransaccion']);
                    $array[$i]['auxilio'] 	= trim($response['auxilio']);
                    $array[$i]['delivery'] 	= trim($response['delivery']);
                    $i++;
                }
                echo ' {
                "errorCode": 0,
                "errorMessage": "Servicio ejecutado con éxito",
                "msg": '.json_encode($array).'
            }';
            }else{
                echo ' {
                "errorCode": 2,
                "errorMessage": "No hay datos.",
                "msg": 0
            }';
            }
            $db = null;
        } catch(PDOException $e){
            $db = null;
            echo $e;
            echo ' {
                "errorCode": 3,
                "errorMessage": "Error al ejecutar el servicio web.",
                "msg": 0
            }';
        }
    } else {
        echo ' {
          "errorCode": 4,
          "errorMessage": "No autenticado.",
          "msg": 0
     }';
    }
});

// RGT-92 CLI - GET - Productos Randon
$app->get('/getProductosRandom/{idtoken}/{token}/{numrandon}/{inicio}/{cantidad}',function ($request, $response,$data) {

    $inicio = $data['inicio'];
    $cantidad = $data['cantidad'];
    $numRandon = $data['numrandon'];
    $validate = getValidateCliente($data['idtoken'],$data['token']);
    if($validate) {
        try{
            $sql = "SELECT Ca.idCategoria, Ca.estado, Ca.comision, Ca.nombre, Ca.foto
                        FROM categoria as Ca
                        ORDER BY RAND(:nro)
                        LIMIT :inicio, :cantidad ; ";

            $db = getConnection();
            $stmt = $db->prepare($sql);
            $stmt ->bindParam('nro', $numRandon,PDO::PARAM_INT);
            $stmt ->bindParam('inicio', $inicio,PDO::PARAM_INT);
            $stmt ->bindParam('cantidad', $cantidad, PDO::PARAM_INT);
            $stmt->execute();
            if ( $stmt->rowCount() > 0 ) {
                $i = 0;
                while ($response  = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $array[$i]['categoria']   = trim($response['nombre']);
                    $array[$i]['idCategoria']   = trim($response['idCategoria']);
                    $array[$i]['estado'] 	= trim($response['estado']);
                    $array[$i]['comision'] 	= trim($response['comision']);
                    if(trim($response['foto'])!= null){
                        $imagen = RUTA_IMGcategoria.trim($response['foto']);
                    }else{
                        $imagen = RUTA_IMGcategoria.'default.png';
                    }
                    $array[$i]['imagenCategoria'] 	= $imagen;
                    $array[$i]['productos'] = getProductosPorCategoria($response['idCategoria']);
                    $i++;
                }
//                $arraySub['productos'] = $array ;
                echo ' {
                "errorCode": 0,
                "errorMessage": "Servicio ejecutado con éxito",
                "msg": '.json_encode($array).'
            }';
            }else{
                echo ' {
                "errorCode": 2,
                "errorMessage": "No hay datos.",
                "msg": 0
            }';
            }
            $db = null;
        } catch(PDOException $e){
            $db = null;
            echo $e;
            echo ' {
                "errorCode": 3,
                "errorMessage": "Error al ejecutar el servicio web.",
                "msg": 0
            }';
        }
    } else {
        echo ' {
          "errorCode": 4,
          "errorMessage": "No autenticado.",
          "msg": 0
     }';
    }
});

function getDetallesProducto($idProductoVariante){
    try{
        $array = [];
        $sql = "SELECT ProDe.idProductoDetalle, ProDe.idAtributoDetalle,
                       AtrDe.nombre as nombreAtributoDetalle, AtrDe.estado  as estadoAtributoDetalle,
                       Atri.nombre as nombreAtributo, Atri.estado as estadoAtributo, Atri.idAtributo,
                       ProSu.idSucursal, ProSu.stock, ProSu.tiempoFree, ProSu.montoFree, ProSu.precio, ProSu.descuento,
                       ProSu.comision, ProSu.estado as estadoProductoSucursal, ProSu.disponible, ProSu.precioPorMayor
			        FROM productoDetalle as ProDe, atributoDetalle as AtrDe, atributo as Atri, productoSucursal as ProSu,
			        productoVariante as ProV
			        WHERE ProDe.idAtributoDetalle = AtrDe.idAtributoDetalle and AtrDe.idAtributo = Atri.idAtributo 
			        and ProSu.idProductoVariante = ProV.idProductoVariante and ProV.idProductoVariante=ProDe.idProductoVariante
			        and ProV.idProductoVariante =:idProductoVariante ; ";
        $db   = getConnection();
        $stmt = $db->prepare($sql);
        $stmt->bindParam("idProductoVariante",$idProductoVariante);
        $stmt->execute();
        if ( $stmt->rowCount() > 0 ) {
            $i = 0;
            while ($response  = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $array[$i]["idProductoVariante"] = $idProductoVariante;
                $array[$i]["atributosDetalle"]['nombreAtributoDetalle'] 	= trim($response['nombreAtributoDetalle']);
                $array[$i]["atributosDetalle"]['estadoAtributoDetalle'] 	= trim($response['estadoAtributoDetalle']);
                $array[$i]["atributo"]['nombreAtributo'] 	= trim($response['nombreAtributo']);
                $array[$i]["atributo"]['idAtributo'] 	= trim($response['idAtributo']);
                $array[$i]['stock'] = trim($response['stock']);
                $array[$i]['tiempoFree']	= trim($response['tiempoFree']);
                $array[$i]['montoFree']	= trim($response['montoFree']);
                $array[$i]['precio']	= trim($response['precio']);
                $array[$i]['descuento']	= trim($response['descuento']);
                $array[$i]['tiempoFree']	= trim($response['tiempoFree']);
                $array[$i]['comision']	= trim($response['comision']);
                $array[$i]['estadoProductoSucursal']	= trim($response['estadoProductoSucursal']);
                $array[$i]['disponible']	= trim($response['disponible']);
                $array[$i]['precioPorMayor']	= trim($response['precioPorMayor']);
                $i++;
            }
            return $array;
        } else {
            return $array;
        }
        $db = null;
    } catch(PDOException $e){
        $db = null;
        echo '{
						"errorCode": 3,
						"errorMessage": "Error al ejecutar el servicio web.",
						"msg": "0"
				  }';
    }
};

function getProductosPorCategoria($idCategoria){
    try{
        $array = [];
        $sql = "SELECT P.idProducto,P.nombre,P.detalle,P.peso,P.fecha,P.estado,P.descuentoPromo,P.foto,P.codigoInterno,
                           P.alto,P.ancho,P.largo,P.descripcion,P.etiqueta,P.idProveedor, 
                           ProV.Nombre as nombreProductoVariante,
                           ProV.idProductoVariante, ProSu.idSucursal
                        FROM producto as P, proveedor as Pro, productoVariante as ProV, productoSucursal as ProSu,
                           categoria as Ca
                        WHERE P.idProveedor = Pro.idProveedor and ProV.idProducto = P.idProducto
                        and ProV.idProductoVariante = ProSu.idProductoVariante and Ca.idCategoria = P.idCategoria and
                        P.idCategoria =:idCategoria; ";

        $db = getConnection();
        $stmt = $db->prepare($sql);
        $stmt ->bindParam('idCategoria', $idCategoria);
        $stmt->execute();
        if ( $stmt->rowCount() > 0 ) {
            $i = 0;
            while ($response  = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $array[$i]['idProducto']   = trim($response['idProducto']);
                $array[$i]['nombre'] 	= trim($response['nombre']);
                $array[$i]['detalle'] 	= trim($response['detalle']);
                $array[$i]['peso'] 	= trim($response['peso']);
                $array[$i]['fecha'] 	= trim($response['fecha']);
                $array[$i]['estado'] 	= trim($response['estado']);
                $array[$i]['descuentoPromo'] 	= trim($response['descuentoPromo']);
                if(trim($response['foto'])!= null){
                    $imagen = RUTA_IMGproducto.$response['idProveedor'].'/'.trim($response['foto']);
                }else{
                    $imagen = 'https://demoweb.reggat.com/assets/productos/default.png';
                }
                $array[$i]['foto'] 	= $imagen;
                $array[$i]['codigoInterno'] 	= trim($response['codigoInterno']);
                $array[$i]['alto'] 	= trim($response['alto']);
                $array[$i]['ancho'] 	= trim($response['ancho']);
                $array[$i]['largo'] 	= trim($response['largo']);
                $array[$i]['descripcion'] 	= trim($response['descripcion']);
                $array[$i]['etiqueta'] 	= trim($response['etiqueta']);
                $array[$i]['idProveedor'] 	= trim($response['idProveedor']);
                $array[$i]['idSucursal'] 	= trim($response['idSucursal']);
                $array[$i]["variantes"] = getDetallesProducto($response['idProductoVariante']);
                $i++;
            }
            return $array;
        } else {
            return $array;
        }
        $db = null;
    }catch(PDOException $e){
        $db = null;
        echo '{
						"errorCode": 3,
						"errorMessage": "Error al ejecutar el servicio web.",
						"msg": "0"
				  }';
    }
};
// RGT-93 CLI - GET - Detalle de pedido
$app->get('/getDetallePedido/{idtoken}/{token}/{idpedido}',function ($request, $response,$data) {

    $idpedido = $data['idpedido'];
    $validate = getValidateCliente($data['idtoken'],$data['token']);
    if($validate) {
        try{
            $sql = "SELECT P.idPedido, P.latitud, P.longitud, P.detalle, P.msjTransportista, P.msjProveedor, P.total, P.precioTransporte,
             P.usobono, P.detalleBono, P.firmaDigital, P.fechaEstimada, P.horarioEstimado, P.fechaEnvio,P.horarioEnvio, P.fechaEntrega,
             P.horarioEntrega,P.fechaReg, P.fechaRegconductor, P.checkPedido, P.fechaProgramada, P.horarioProgramado, P.cobrado, P.nit as nitPedido,
             P.razonSocial as razonSocialPedido, P.pagoBanco, P.estadoFinanciero, P.idTransaccion, P.auxilio, P.delivery, 
             Cali.idCalificacionTransporte, Cali.idCliente, Cali.idConductor, Cali.detalle, Cali.calificacion, Cali.idPedido as idPedidoCalificacion, Cali.estado,  
             Cli.idCliente as idClienteCliente, Cli.nombre as nombreCliente, Cli.apellido as apellidoCliente, Cli.correo as correoCliente, Cli.ci as ciCliente, Cli.nit as nitCliente,Cli.razonSocial as razonsocialCliente,
             Cli.telefono as telefonoCliente, Cli.foto as fotoCliente, Cli.imei, Cli.estado as estadoCliente, Cli.fechaRegistro,
             Con.idConductor,Con.nombre as nombreConductor, Con.apellido as apellidoConductor,Con.ci as ciConductor, Con.correo as correoConductor, Con.foto as fotoConductor,Con.telefono as telefonoConductor,
             Con.estado as estadoConductor,Con.fechaRegistro as fechaRegistroConductor, Con.disponible,Con.recibo
             FROM pedido as P, calificaciontransporte as Cali, cliente as Cli, conductor as Con
             WHERE P.idPedido = Cali.idPedido and Cali.idCliente = Cli.idCliente and Cali.idConductor = Con.idConductor
             and P.idPedido=:idpedido ;";

            $db = getConnection();
            $stmt = $db->prepare($sql);
            $stmt ->bindParam('idpedido', $idpedido);
            $stmt->execute();
            if ( $stmt->rowCount() > 0 ) {
                $data = $stmt->fetch(PDO::FETCH_ASSOC);
                $array['pedido']['idPedido']        = trim($data['idPedido']);
                $array['pedido']['latitud']        = trim($data['latitud']);
                $array['pedido']['longitud']        = trim($data['longitud']);
                $array['pedido']['detalle']        = trim($data['detalle']);
                $array['pedido']['msjTransportista']        = trim($data['msjTransportista']);
                $array['pedido']['msjProveedor']        = trim($data['msjProveedor']);
                $array['pedido']['total']        = trim($data['total']);
                $array['pedido']['precioTransporte']        = trim($data['precioTransporte']);
                $array['pedido']['usobono']        = trim($data['usobono']);
                $array['pedido']['detalleBono']        = trim($data['detalleBono']);
                $array['pedido']['firmaDigital']        = trim($data['firmaDigital']);
                $array['pedido']['fechaEstimada']        = trim($data['fechaEstimada']);
                $array['pedido']['horarioEstimado']        = trim($data['horarioEstimado']);
                $array['pedido']['fechaEnvio']        = trim($data['fechaEnvio']);
                $array['pedido']['horarioEnvio']        = trim($data['horarioEnvio']);
                $array['pedido']['fechaEntrega']        = trim($data['fechaEntrega']);
                $array['pedido']['horarioEntrega']        = trim($data['horarioEntrega']);
                $array['pedido']['fechaReg']        = trim($data['fechaReg']);
                $array['pedido']['fechaRegconductor']        = trim($data['fechaRegconductor']);
                $array['pedido']['checkPedido']        = trim($data['checkPedido']);
                $array['pedido']['fechaProgramada']        = trim($data['fechaProgramada']);
                $array['pedido']['horarioProgramado']        = trim($data['horarioProgramado']);
                $array['pedido']['cobrado']        = trim($data['cobrado']);
                $array['pedido']['nitPedido']        = trim($data['nitPedido']);
                $array['pedido']['razonSocialPedido']        = trim($data['razonSocialPedido']);
                $array['pedido']['pagoBanco']        = trim($data['pagoBanco']);
                $array['pedido']['estadoFinanciero']        = trim($data['estadoFinanciero']);
                $array['pedido']['idTransaccion']        = trim($data['idTransaccion']);
                $array['pedido']['auxilio']        = trim($data['auxilio']);
                $array['pedido']['delivery']        = trim($data['delivery']);
                $array['calificaciontransporte']['idCalificacionTransportekPedido']        = trim($data['idCalificacionTransporte']);
                $array['calificaciontransporte']['idCliente']        = trim($data['idCliente']);
                $array['calificaciontransporte']['idConductor']        = trim($data['idConductor']);
                $array['calificaciontransporte']['detalle']        = trim($data['detalle']);
                $array['calificaciontransporte']['calificacion']        = trim($data['calificacion']);
                $array['calificaciontransporte']['idPedidoCalificacion']        = trim($data['idPedidoCalificacion']);
                $array['calificaciontransporte']['estado']        = trim($data['estado']);
                $array['cliente']['idClienteCliente']        = trim($data['idClienteCliente']);
                $array['cliente']['nombreCliente']        = trim($data['nombreCliente']);
                $array['cliente']['apellidoCliente']        = trim($data['apellidoCliente']);
                $array['cliente']['correoCliente']        = trim($data['correoCliente']);
                $array['cliente']['ciCliente']        = trim($data['ciCliente']);
                $array['cliente']['nitCliente']        = trim($data['nitCliente']);
                $array['cliente']['razonsocialCliente']        = trim($data['razonsocialCliente']);
                $array['cliente']['telefonoCliente']        = trim($data['telefonoCliente']);
                $array['cliente']['fotoCliente']        = trim($data['fotoCliente']);
                $array['cliente']['imei']        = trim($data['imei']);
                $array['cliente']['estadoCliente']        = trim($data['estadoCliente']);
                $array['cliente']['fechaRegistro']        = trim($data['fechaRegistro']);
                $array['Conductor']['idConductor']        = trim($data['idConductor']);
                $array['Conductor']['nombreConductor']        = trim($data['nombreConductor']);
                $array['Conductor']['apellidoConductor']        = trim($data['apellidoConductor']);
                $array['Conductor']['correoConductor']        = trim($data['correoConductor']);
                $array['Conductor']['fotoConductor']        = trim($data['fotoConductor']);
                $array['Conductor']['telefonoConductor']        = trim($data['telefonoConductor']);
                $array['Conductor']['estadoConductor']        = trim($data['estadoConductor']);
                $array['Conductor']['fechaRegistroConductor']        = trim($data['fechaRegistroConductor']);
                $array['Conductor']['disponible']        = trim($data['disponible']);
                $array['Conductor']['recibo']        = trim($data['recibo']);
                echo ' {
                "errorCode": 0,
                "errorMessage": "Servicio ejecutado con éxito",
                "msg": '.json_encode($array).'
            }';
            }else{
                echo ' {
                "errorCode": 2,
                "errorMessage": "No hay datos.",
                "msg": 0
            }';
            }
            $db = null;
        } catch(PDOException $e){
            $db = null;
            echo $e;
            echo ' {
                "errorCode": 3,
                "errorMessage": "Error al ejecutar el servicio web.",
                "msg": 0
            }';
        }
    } else {
        echo ' {
          "errorCode": 4,
          "errorMessage": "No autenticado.",
          "msg": 0
     }';
    }
});

// RGT-94 CLI - GET - Productos Regateables
$app->get('/getProductosRegateables/{idtoken}/{token}/{inicio}/{cantidad}/{idCliente}',function ($request, $response,$data) {

    $inicio = $data['inicio'];
    $cantidad = $data['cantidad'];
    $validate = getValidateCliente($data['idtoken'],$data['token']);
    if($validate) {
        try{
            if($data['idCliente'] != 0 ){
                $sql = "SELECT P.idProducto,P.nombre,P.detalle,P.peso,P.fecha,P.estado,P.descuentoPromo,P.foto,P.codigoInterno,
                           P.alto,P.ancho,P.largo,P.descripcion,P.etiqueta,P.idProveedor,
                           Re.idRegate, Re.monto, Re.idSucursalProducto, Re.estado,
                           ProS.idSucursal
             FROM regate as Re, productoVariante as ProVa, producto as P, productoSucursal as ProS
             WHERE Re.idSucursalProducto = ProVa.idProductoVariante and ProVa.idProducto=P.idProducto
             and ProVa.idProductoVariante=ProS.idProductoVariante and Re.idCliente =:idCliente 
             ORDER BY idRegate DESC
              LIMIT :inicio, :cantidad ;";

                $db = getConnection();
                $stmt = $db->prepare($sql);
                $stmt ->bindParam('inicio', $inicio,PDO::PARAM_INT);
                $stmt ->bindParam('cantidad', $cantidad, PDO::PARAM_INT);
                $stmt ->bindParam('idCliente', $data['idCliente']);
                $stmt->execute();
            }else{
                $sql = "SELECT P.idProducto,P.nombre,P.detalle,P.peso,P.fecha,P.estado,P.descuentoPromo,P.foto,P.codigoInterno,
                           P.alto,P.ancho,P.largo,P.descripcion,P.etiqueta,P.idProveedor,
                           Re.idRegate, Re.monto, Re.idSucursalProducto, Re.estado,
                           ProS.idSucursal
             FROM regate as Re, productoVariante as ProVa, producto as P, productoSucursal as ProS
             WHERE Re.idSucursalProducto = ProVa.idProductoVariante and ProVa.idProducto=P.idProducto
             and ProVa.idProductoVariante=ProS.idProductoVariante 
             ORDER BY idRegate DESC
              LIMIT :inicio, :cantidad ;";

                $db = getConnection();
                $stmt = $db->prepare($sql);
                $stmt ->bindParam('inicio', $inicio,PDO::PARAM_INT);
                $stmt ->bindParam('cantidad', $cantidad, PDO::PARAM_INT);
                $stmt->execute();
            }

            if ( $stmt->rowCount() > 0 ) {
                $i = 0;
                while ($response  = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $array[$i]['idProducto']   = trim($response['idProducto']);
                    $array[$i]['nombre'] 	= trim($response['nombre']);
                    $array[$i]['detalle'] 	= trim($response['detalle']);
                    $array[$i]['peso'] 	= trim($response['peso']);
                    $array[$i]['fecha'] 	= trim($response['fecha']);
                    $array[$i]['estado'] 	= trim($response['estado']);
                    $array[$i]['descuentoPromo'] 	= trim($response['descuentoPromo']);
                    if(trim($response['foto'])!= null){
                        $imagen = RUTA_IMGproducto.$response['idProveedor'].'/'.trim($response['foto']);
                    }else{
                        $imagen = 'https://demoweb.reggat.com/assets/productos/default.png';
                    }
                    $array[$i]['foto'] 	= $imagen;
                    $array[$i]['codigoInterno'] 	= trim($response['codigoInterno']);
                    $array[$i]['alto'] 	= trim($response['alto']);
                    $array[$i]['ancho'] 	= trim($response['ancho']);
                    $array[$i]['largo'] 	= trim($response['largo']);
                    $array[$i]['descripcion'] 	= trim($response['descripcion']);
                    $array[$i]['etiqueta'] 	= trim($response['etiqueta']);
                    $array[$i]['idProveedor'] 	= trim($response['idProveedor']);
                    $array[$i]['idSucursal'] 	= trim($response['idSucursal']);
                    $array[$i]['regate']['idRegate']   = trim($response['idRegate']);
                    $array[$i]['regate']['monto'] 	= trim($response['monto']);
                    $array[$i]['regate']['idSucursalProducto'] 	= trim($response['idSucursalProducto']);
                    $array[$i]['regate']['estado'] 	= trim($response['estado']);
                    $array[$i]["variantes"] = getDetallesProducto($response['idSucursalProducto']);
                    $i++;
                }
                $arraySub['productos'] = $array ;
                echo ' {
                "errorCode": 0,
                "errorMessage": "Servicio ejecutado con éxito",
                "msg": '.json_encode($arraySub).'
            }';
            }else{
                echo ' {
                "errorCode": 2,
                "errorMessage": "No hay datos.",
                "msg": 0
            }';
            }
            $db = null;
        } catch(PDOException $e){
            $db = null;
            echo $e;
            echo ' {
                "errorCode": 3,
                "errorMessage": "Error al ejecutar el servicio web.",
                "msg": 0
            }';
        }
    } else {
        echo ' {
          "errorCode": 4,
          "errorMessage": "No autenticado.",
          "msg": 0
     }';
    }
});

// RGT-95 CLI - POST -Insertar Productos Regateables
$app->post('/postRegatearProducto/{idtoken}/{token}/{monto}/{idSucursalProducto}',function ($request, $response,$data) {

    $monto = $data['monto'];
    $idSucursalProducto = $data['idSucursalProducto'];
    $validate = getValidateCliente($data['idtoken'],$data['token']);
    if($validate) {
        try{
            $sql = "INSERT INTO regate(monto, idSucursalProducto, estado)
                        VALUES (:monto,:idSucursalProducto, 1)";

            $db = getConnection();
            $stmt = $db->prepare($sql);
            $stmt ->bindParam('monto', $monto);
            $stmt ->bindParam('idSucursalProducto', $idSucursalProducto);
            $stmt->execute();
            echo ' {
                "errorCode": 0,
                "errorMessage": "Servicio ejecutado con éxito",
                "msg": "Regatear Registrado Correctamente en favoritos"
            }';
            $db = null;
        } catch(PDOException $e){
            $db = null;
            echo $e;
            echo ' {
                "errorCode": 3,
                "errorMessage": "Error al ejecutar el servicio web.",
                "msg": 0
            }';
        }
    } else {
        echo ' {
          "errorCode": 4,
          "errorMessage": "No autenticado.",
          "msg": 0
     }';
    }
});

// RGT-96 CLI - GET - Productos en susbasta
$app->get('/getProductosEnSubasta/{idtoken}/{token}/{inicio}/{cantidad}',function ($request, $response,$data) {

    $inicio = $data['inicio'];
    $cantidad = $data['cantidad'];
    $validate = getValidateCliente($data['idtoken'],$data['token']);
    if($validate) {
        try{
            $sql = "SELECT Su.idSubasta, Su.precioInicial, Su.fechaInicio, Su.horaInicio, Su.fechaFin,
             Su.horaFin, Su.idSucursalProducto
             FROM subasta as Su, productoVariante as ProVA
             WHERE Su.idSucursalProducto = ProVA.idProductoVariante 
             ORDER BY Su.idSubasta DESC
              LIMIT :inicio, :cantidad ;";

            $db = getConnection();
            $stmt = $db->prepare($sql);
            $stmt ->bindParam('inicio', $inicio,PDO::PARAM_INT);
            $stmt ->bindParam('cantidad', $cantidad, PDO::PARAM_INT);
            $stmt->execute();
            if ( $stmt->rowCount() > 0 ) {
                $i = 0;
                while ($response  = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $array[$i]['idSubasta']   = trim($response['idSubasta']);
                    $array[$i]['precioInicial'] 	= trim($response['precioInicial']);
                    $array[$i]['fechaInicio'] 	= trim($response['fechaInicio']);
                    $array[$i]['horaInicio'] 	= trim($response['horaInicio']);
                    $array[$i]['horaFin'] 	= trim($response['horaFin']);
                    $array[$i]['idSucursalProducto'] 	= trim($response['idSucursalProducto']);
                    $array[$i]['productos'] = getProductosDetalle($response['idSucursalProducto']);
                    $i++;
                }
                echo ' {
                "errorCode": 0,
                "errorMessage": "Servicio ejecutado con éxito",
                "msg": '.json_encode($array).'
            }';
            }else{
                echo ' {
                "errorCode": 2,
                "errorMessage": "No hay datos.",
                "msg": 0
            }';
            }
            $db = null;
        } catch(PDOException $e){
            $db = null;
            echo $e;
            echo ' {
                "errorCode": 3,
                "errorMessage": "Error al ejecutar el servicio web.",
                "msg": 0
            }';
        }
    } else {
        echo ' {
          "errorCode": 4,
          "errorMessage": "No autenticado.",
          "msg": 0
     }';
    }
});

function getProductosDetalle($idProductoVariante){
    try{
        $array = [];
        $sql = "SELECT P.idProducto,P.nombre,P.detalle,P.peso,P.fecha,P.estado,P.descuentoPromo,P.foto,P.codigoInterno,
                           P.alto,P.ancho,P.largo,P.descripcion,P.etiqueta,P.idProveedor, 
                           ProV.Nombre as nombreProductoVariante,
                           ProV.idProductoVariante, ProSu.idSucursal
                        FROM producto as P, proveedor as Pro, productoVariante as ProV, productoSucursal as ProSu,
                           categoria as Ca
                        WHERE P.idProveedor = Pro.idProveedor and ProV.idProducto = P.idProducto
                        and ProV.idProductoVariante = ProSu.idProductoVariante and Ca.idCategoria = P.idCategoria and
                        ProV.idProductoVariante =:idProductoVariante; ";

        $db = getConnection();
        $stmt = $db->prepare($sql);
        $stmt ->bindParam('idProductoVariante', $idProductoVariante);
        $stmt->execute();
        if ( $stmt->rowCount() > 0 ) {
            $i = 0;
            while ($response  = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $array[$i]['idProducto']   = trim($response['idProducto']);
                $array[$i]['nombre'] 	= trim($response['nombre']);
                $array[$i]['detalle'] 	= trim($response['detalle']);
                $array[$i]['peso'] 	= trim($response['peso']);
                $array[$i]['fecha'] 	= trim($response['fecha']);
                $array[$i]['estado'] 	= trim($response['estado']);
                $array[$i]['descuentoPromo'] 	= trim($response['descuentoPromo']);
                if(trim($response['foto'])!= null){
                    $imagen = RUTA_IMGproducto.$response['idProveedor'].'/'.trim($response['foto']);
                }else{
                    $imagen = 'https://demoweb.reggat.com/assets/productos/default.png';
                }
                $array[$i]['foto'] 	= $imagen;
                $array[$i]['codigoInterno'] 	= trim($response['codigoInterno']);
                $array[$i]['alto'] 	= trim($response['alto']);
                $array[$i]['ancho'] 	= trim($response['ancho']);
                $array[$i]['largo'] 	= trim($response['largo']);
                $array[$i]['descripcion'] 	= trim($response['descripcion']);
                $array[$i]['etiqueta'] 	= trim($response['etiqueta']);
                $array[$i]['idProveedor'] 	= trim($response['idProveedor']);
                $array[$i]['idSucursal'] 	= trim($response['idSucursal']);
                $array[$i]["variantes"] = getDetallesProducto($response['idProductoVariante']);
                $i++;
            }
            return $array;
        } else {
            return $array;
        }
        $db = null;
    }catch(PDOException $e){
        $db = null;
        echo '{
						"errorCode": 3,
						"errorMessage": "Error al ejecutar el servicio web.",
						"msg": "0"
				  }';
    }
};

// RGT-97 CLI - POST -Insertar Productos subasta
$app->post('/postRealizarSubastaProducto/{idtoken}/{token}',function ($request, $response,$data1) {

    $data = $request->getParsedBody();
    $validate = getValidateCliente($data1['idtoken'],$data1['token']);
    if($validate) {
        try{
            $sql = "INSERT INTO subasta(precioInicial, fechaInicio, horaInicio, fechaFin, horaFin, idSucursalProducto)
                        VALUES (:precioInicial,:fechaInicio, :horaInicio, :fechaFin, :horaFin, :idSucursalProducto)";

            $db = getConnection();
            $stmt = $db->prepare($sql);
            $stmt ->bindParam('precioInicial', $data['precioInicial']);
            $stmt ->bindParam('fechaInicio', $data['fechaInicio']);
            $stmt ->bindParam('horaInicio', $data['horaInicio']);
            $stmt ->bindParam('fechaFin', $data['fechaFin']);
            $stmt ->bindParam('horaFin', $data['horaFin']);
            $stmt ->bindParam('idSucursalProducto', $data['idSucursalProducto']);
            $stmt->execute();
            echo ' {
                "errorCode": 0,
                "errorMessage": "Servicio ejecutado con éxito",
                "msg": "Subasta Registrado Correctamente en favoritos"
            }';
            $db = null;
        } catch(PDOException $e){
            $db = null;
            echo $e;
            echo ' {
                "errorCode": 3,
                "errorMessage": "Error al ejecutar el servicio web.",
                "msg": 0
            }';
        }
    } else {
        echo ' {
          "errorCode": 4,
          "errorMessage": "No autenticado.",
          "msg": 0
     }';
    }
});

// RGT-98 CLI - POST - Realizar Compra
$app->post('/postRealizarCompra/{idtoken}/{token}',function ($request, $response,$data1) {

    $data = $request->getParsedBody();
    $validate = getValidateCliente($data1['idtoken'],$data1['token']);
    if($validate) {
        try{
            $sql = "INSERT INTO pedido(idCliente, idConductor, idDireccion, idFormaPago, idEstadoPedido, 
                    idTipoVehiculo, latitud, longitud, detalle, msjTransportista, msjProveedor, total, precioTransporte,
                    usobono, detalleBono, firmaDigital, fechaEstimada, horarioEstimado, fechaEnvio, horarioEnvio,
                    fechaEntrega, horarioEntrega, fechaReg, fechaRegconductor, checkPedido, fechaProgramada,horarioProgramado,
                    cobrado, nit, razonSocial, pagoBanco, estadoFinanciero, idTransaccion, auxilio, delivery)
                        VALUES (:idCliente, :idConductor, :idDireccion, :idFormaPago, :idEstadoPedido, 
                        :idTipoVehiculo, :latitud, :longitud, :detalle, :msjTransportista, :msjProveedor, :total, 
                        :precioTransporte, :usobono, :detalleBono, :firmaDigital, :fechaEstimada, :horarioEstimado, 
                        :fechaEnvio, :horarioEnvio, :fechaEntrega, :horarioEntrega, :fechaReg, :fechaRegconductor, 
                        :checkPedido, :fechaProgramada,:horarioProgramado, :cobrado, :nit, :razonSocial, :pagoBanco, 
                        :estadoFinanciero, :idTransaccion, :auxilio, :delivery) ; ";

            $db = getConnection();
            $stmt = $db->prepare($sql);
            $stmt ->bindParam('idCliente', $data['idCliente']);
            $stmt ->bindParam('idConductor', $data['idConductor']);
            $stmt ->bindParam('idDireccion', $data['idDireccion']);
            $stmt ->bindParam('idFormaPago', $data['idFormaPago']);
            $stmt ->bindParam('idEstadoPedido', $data['idEstadoPedido']);
            $stmt ->bindParam('idTipoVehiculo', $data['idTipoVehiculo']);
            $stmt ->bindParam('latitud', $data['latitud']);
            $stmt ->bindParam('longitud', $data['longitud']);
            $stmt ->bindParam('detalle', $data['detalle']);
            $stmt ->bindParam('msjTransportista', $data['msjTransportista']);
            $stmt ->bindParam('msjProveedor', $data['msjProveedor']);
            $stmt ->bindParam('total', $data['total']);
            $stmt ->bindParam('precioTransporte', $data['precioTransporte']);
            $stmt ->bindParam('usobono', $data['usobono']);
            $stmt ->bindParam('detalleBono', $data['detalleBono']);
            $stmt ->bindParam('firmaDigital', $data['firmaDigital']);
            $stmt ->bindParam('fechaEstimada', $data['fechaEstimada']);
            $stmt ->bindParam('horarioEstimado', $data['horarioEstimado']);
            $stmt ->bindParam('fechaEnvio', $data['fechaEnvio']);
            $stmt ->bindParam('horarioEnvio', $data['horarioEnvio']);
            $stmt ->bindParam('fechaEntrega', $data['fechaEntrega']);
            $stmt ->bindParam('horarioEntrega', $data['horarioEntrega']);
            $stmt ->bindParam('fechaReg', $data['fechaReg']);
            $stmt ->bindParam('fechaRegconductor', $data['fechaRegconductor']);
            $stmt ->bindParam('checkPedido', $data['checkPedido']);
            $stmt ->bindParam('fechaProgramada', $data['fechaProgramada']);
            $stmt ->bindParam('horarioProgramado', $data['horarioProgramado']);
            $stmt ->bindParam('cobrado', $data['cobrado']);
            $stmt ->bindParam('nit', $data['nit']);
            $stmt ->bindParam('razonSocial', $data['razonSocial']);
            $stmt ->bindParam('pagoBanco', $data['pagoBanco']);
            $stmt ->bindParam('estadoFinanciero', $data['estadoFinanciero']);
            $stmt ->bindParam('idTransaccion', $data['idTransaccion']);
            $stmt ->bindParam('auxilio', $data['auxilio'], PDO::PARAM_BOOL);
            $stmt ->bindParam('delivery', $data['delivery']);
            $stmt->execute();
            echo ' {
                "errorCode": 0,
                "errorMessage": "Servicio ejecutado con éxito",
                "msg": "Pedido Registrado Correctamente en favoritos"
            }';
            $db = null;
        } catch(PDOException $e){
            $db = null;
            echo $e;
            echo ' {
                "errorCode": 3,
                "errorMessage": "Error al ejecutar el servicio web.",
                "msg": 0
            }';
        }
    } else {
        echo ' {
          "errorCode": 4,
          "errorMessage": "No autenticado.",
          "msg": 0
     }';
    }
});

// RGT-99 CLI - GET - Detallle Producto
$app->get('/getDetalleProducto/{idtoken}/{token}/{idProductoVariante}',function ($request, $response,$data) {

    $idProductoVariante = $data['idProductoVariante'];
    $validate = getValidateCliente($data['idtoken'],$data['token']);
    if($validate) {
        try{
            $sql = "SELECT ProDe.idProductoDetalle, ProDe.idAtributoDetalle,
                       AtrDe.nombre as nombreAtributoDetalle, AtrDe.estado  as estadoAtributoDetalle,
                       Atri.nombre as nombreAtributo, Atri.estado as estadoAtributo,
                       ProSu.idSucursal, ProSu.stock, ProSu.tiempoFree, ProSu.montoFree, ProSu.precio, ProSu.descuento,
                       ProSu.comision, ProSu.estado as estadoProductoSucursal, ProSu.disponible, ProSu.precioPorMayor
			        FROM productoDetalle as ProDe, atributoDetalle as AtrDe, atributo as Atri, productoSucursal as ProSu,
			        productoVariante as ProV
			        WHERE ProDe.idAtributoDetalle = AtrDe.idAtributoDetalle and AtrDe.idAtributo = Atri.idAtributo 
			        and ProSu.idProductoVariante = ProV.idProductoVariante and ProV.idProductoVariante=ProDe.idProductoVariante
			        and ProV.idProductoVariante =:idProductoVariante ; ";
            $db   = getConnection();
            $stmt = $db->prepare($sql);
            $stmt->bindParam("idProductoVariante",$idProductoVariante);
            $stmt->execute();
            if ( $stmt->rowCount() > 0 ) {
                $i = 0;
                while ($response  = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $array[$i]["idProductoVariante"] = $idProductoVariante;
                    $array[$i]["atributosDetalle"]['nombreAtributoDetalle'] 	= trim($response['nombreAtributoDetalle']);
                    $array[$i]["atributosDetalle"]['estadoAtributoDetalle'] 	= trim($response['estadoAtributoDetalle']);
                    $array[$i]["atributo"]['nombreAtributo'] 	= trim($response['nombreAtributo']);
                    $array[$i]["atributo"]['estadoAtributo'] 	= trim($response['estadoAtributo']);
                    $array[$i]['stock'] = trim($response['stock']);
                    $array[$i]['tiempoFree']	= trim($response['tiempoFree']);
                    $array[$i]['montoFree']	= trim($response['montoFree']);
                    $array[$i]['precio']	= trim($response['precio']);
                    $array[$i]['descuento']	= trim($response['descuento']);
                    $array[$i]['tiempoFree']	= trim($response['tiempoFree']);
                    $array[$i]['comision']	= trim($response['comision']);
                    $array[$i]['estadoProductoSucursal']	= trim($response['estadoProductoSucursal']);
                    $array[$i]['disponible']	= trim($response['disponible']);
                    $array[$i]['precioPorMayor']	= trim($response['precioPorMayor']);
                    $i++;
                }
                echo ' {
                "errorCode": 0,
                "errorMessage": "Servicio ejecutado con éxito",
                "msg": '.json_encode($array).'
            }';
            }else{
                echo ' {
                "errorCode": 2,
                "errorMessage": "No hay datos.",
                "msg": 0
            }';
            }
            $db = null;
        } catch(PDOException $e){
            $db = null;
            echo $e;
            echo ' {
                "errorCode": 3,
                "errorMessage": "Error al ejecutar el servicio web.",
                "msg": 0
            }';
        }
    } else {
        echo ' {
          "errorCode": 4,
          "errorMessage": "No autenticado.",
          "msg": 0
     }';
    }
});

$app->get('/getDetalleProducto2/{idtoken}/{token}/{idsucursal}/{idproducto}',function ($request, $response,$data) {

  $idproducto = $data['idproducto'];
  $idsucursal = $data['idsucursal'];
  $validate = getValidateCliente($data['idtoken'],$data['token']);
  if($validate) {
      try{
          $sql = "SELECT P.idProducto,P.nombre,P.detalle,P.peso,P.fecha,P.estado,P.descuentoPromo,P.foto,P.codigoInterno,
                         P.alto,P.ancho,P.largo,P.descripcion,P.etiqueta,P.idProveedor, 
                         ProV.Nombre as nombreProductoVariante,
                         ProV.idProductoVariante, ProSu.idSucursal
                      FROM producto as P, proveedor as Pro, productoVariante as ProV, productoSucursal as ProSu   
                      WHERE P.idProveedor = Pro.idProveedor and ProV.idProducto = P.idProducto
                      and ProV.idProductoVariante = ProSu.idProductoVariante
                      and P.idProducto = :idProducto and ProSu.idSucursal = :idsucursal; ";

          $db = getConnection();
          $stmt = $db->prepare($sql);
          $stmt ->bindParam('idProducto', $idproducto,PDO::PARAM_INT);
          $stmt ->bindParam('idsucursal', $idsucursal,PDO::PARAM_INT);
          $stmt->execute();
          if ( $stmt->rowCount() > 0 ) {
              $i = 0;
              while ($response  = $stmt->fetch(PDO::FETCH_ASSOC)) {
                  $array['idProducto']   = trim($response['idProducto']);
                  $array['nombre'] 	= trim($response['nombre']);
                  $array['detalle'] 	= trim($response['detalle']);
                  $array['peso'] 	= trim($response['peso']);
                  $array['fecha'] 	= trim($response['fecha']);
                  $array['estado'] 	= trim($response['estado']);
                  $array['descuentoPromo'] 	= trim($response['descuentoPromo']);
                  if(trim($response['foto'])!= null){
                      $imagen = RUTA_IMGproducto.$response['idProveedor'].'/'.trim($response['foto']);
                  }else{
                      $imagen = 'https://demoweb.reggat.com/assets/productos/default.png';
                  }
                  $array['foto'] 	= $imagen;
                  $fotos[0] =  $imagen;
                  $array['fotos'] 	= getFotosProducto($response['idProducto'], $response['idProveedor'],$fotos);;
                  $array['codigoInterno'] 	= trim($response['codigoInterno']);
                  $array['alto'] 	= trim($response['alto']);
                  $array['ancho'] 	= trim($response['ancho']);
                  $array['largo'] 	= trim($response['largo']);
                  $array['descripcion'] 	= trim($response['descripcion']);
                  $array['etiqueta'] 	= trim($response['etiqueta']);
                  $array['idProveedor'] 	= trim($response['idProveedor']);
                  $array['idSucursal'] 	= trim($response['idSucursal']);
                  $array["variantes"] = getDetallesProducto($response['idProductoVariante']);
                  $i++;
              }
              echo ' {
              "errorCode": 0,
              "errorMessage": "Servicio ejecutado con éxito",
              "msg": '.json_encode($array).'
          }';
          }else{
              echo ' {
              "errorCode": 2,
              "errorMessage": "No hay datos.",
              "msg": 0
          }';
          }
          $db = null;
      } catch(PDOException $e){
          $db = null;
          echo $e;
          echo ' {
              "errorCode": 3,
              "errorMessage": "Error al ejecutar el servicio web.",
              "msg": 0
          }';
      }
  } else {
      echo ' {
        "errorCode": 4,
        "errorMessage": "No autenticado.",
        "msg": 0
   }';
  }
});


function getFotosProducto($idProducto,$idProveedor,$fotos) {
  try{
      $sql = "SELECT *
      FROM productoFoto 
      WHERE idProducto = $idProducto ;";
      $db = getConnection();
      $stmt = $db->prepare($sql);
      $stmt->execute();
      $i = 1;
      if ( $stmt->rowCount() > 0 ) {
          while ($data = $stmt->fetch(PDO::FETCH_ASSOC)){
           $fotos[$i] = RUTA_IMGproducto.$idProveedor.'/'.trim($data['foto']);
           $i++;
          }
          return $fotos;
      }else{
          return $fotos;
      }
      $db = null;
  } catch(PDOException $e){
      $db = null;
      echo $e;
          echo ' {
              "errorCode": 3,
              "errorMessage": "Error al ejecutar el servicio web.",
              "msg": 0
          }';
  }
}

// RGT-100 CLI - POST - Insertar Detalle Producto
$app->post('/postDetalleProducto/{idtoken}/{token}',function ($request, $response,$data1) {

    $data = $request->getParsedBody();
    $validate = getValidateCliente($data1['idtoken'],$data1['token']);
    if($validate) {
        try{
            if(getExisteProductoVariante($data['idProductoVariante']) && getExisteAtributoDetalle($data['idAtributoDetalle'])){
                $sql = "INSERT INTO productoDetalle(idProductoVariante, idAtributoDetalle)
                        VALUES (:idProductoVariante, :idAtributoDetalle ) ; ";

                $db = getConnection();
                $stmt = $db->prepare($sql);
                $stmt ->bindParam('idProductoVariante', $data['idProductoVariante']);
                $stmt ->bindParam('idAtributoDetalle', $data['idAtributoDetalle']);
                $stmt->execute();
                echo ' {
                "errorCode": 0,
                "errorMessage": "Servicio ejecutado con éxito",
                "msg": "Detalle Producto Registrado Correctamente en favoritos"
            }';
                $db = null;
            }else{
                echo ' {
                "errorCode": 5,
                "errorMessage": "No existe el idProductoVariante o  idAtributoDetalle",
                "msg": "0"
            }';
            }
        } catch(PDOException $e){
            $db = null;
            echo $e;
            echo ' {
                "errorCode": 3,
                "errorMessage": "Error al ejecutar el servicio web.",
                "msg": 0
            }';
        }
    } else {
        echo ' {
          "errorCode": 4,
          "errorMessage": "No autenticado.",
          "msg": 0
     }';
    }
});

function getExisteProductoVariante($idProductoVariante){
    try{
        $sql = "SELECT * 
			        FROM productoVariante
			        WHERE idProductoVariante = :idProductoVariante ; ";
        $db   = getConnection();
        $stmt = $db->prepare($sql);
        $stmt->bindParam("idProductoVariante",$idProductoVariante);
        $stmt->execute();
        if ( $stmt->rowCount() > 0 ) {
            return true;
        } else {
            return false;
        }
        $db = null;
    } catch(PDOException $e){
        $db = null;
        echo '{
						"errorCode": 3,
						"errorMessage": "Error al ejecutar el servicio web.",
						"msg": "0"
				  }';
    }
};

function getExisteAtributoDetalle($idAtributoDetalle){
    try{
        $sql = "SELECT * 
			        FROM atributoDetalle
			        WHERE idAtributoDetalle = :idAtributoDetalle ; ";
        $db   = getConnection();
        $stmt = $db->prepare($sql);
        $stmt->bindParam("idAtributoDetalle",$idAtributoDetalle);
        $stmt->execute();
        if ( $stmt->rowCount() > 0 ) {
            return true;
        } else {
            return false;
        }
        $db = null;
    } catch(PDOException $e){
        $db = null;
        echo '{
						"errorCode": 3,
						"errorMessage": "Error al ejecutar el servicio web.",
						"msg": "0"
				  }';
    }
};

// RGT-101 CLI - GET lista de productos por categoria
$app->get('/getListaProductosCategoria/{idtoken}/{token}/{idCategoria}/{inicio}/{cantidad}/{latitud}/{longitud}',function ($request, $response,$data) {

    $rango = getRangoBusqueda();
    $validate = getValidateCliente($data['idtoken'],$data['token']);
    if($validate) {
        try{
            $sql = "SELECT P.idProducto,P.nombre,P.detalle,P.peso,P.fecha,P.estado,P.descuentoPromo,P.foto,P.codigoInterno,
                              P.alto,P.ancho,P.largo,P.descripcion,P.etiqueta,P.idProveedor,
                              ProV.Nombre as nombreProductoVariante,
                              ProV.idProductoVariante, ProS.idSucursal, Ca.idCategoria
                            FROM producto P INNER JOIN proveedor Pro  ON  Pro.idProveedor = P.idProveedor
                                 INNER JOIN  sucursal s ON P.idProveedor = s.idProveedor
                                 INNER JOIN productoVariante ProV ON ProV.idProducto = P.idProducto
                                 INNER JOIN productoSucursal ProS ON ProS.idProductoVariante = ProV.idProductoVariante
                                 INNER JOIN categoria Ca ON P.idCategoria = Ca.idCategoria
                            WHERE ProS.idSucursal= s.idSucursal AND Ca.idCategoria =:idCategoria  and  
                        (acos(sin(radians(s.latitud)) * sin(radians(:lat)) +
                        cos(radians(s.latitud)) * cos(radians(:lat)) *
                        cos(radians(s.longitud) - radians(:lng))) * 6378)<=:rango 
                        LIMIT :inicio, :cantidad ;";

            $db = getConnection();
            $stmt = $db->prepare($sql);
            $stmt->bindParam('idCategoria', $data['idCategoria']);
            $stmt->bindParam("lat", $data['latitud']);
            $stmt->bindParam("lng", $data['longitud']);
            $stmt->bindParam('rango', $rango);
            $stmt->bindParam('inicio', $data['inicio'], PDO::PARAM_INT);
            $stmt->bindParam('cantidad', $data['cantidad'], PDO::PARAM_INT);
            $stmt->execute();
            if ($stmt->rowCount() > 0) {
                $i = 0;
                while ($response = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $array[$i]['idProducto'] = trim($response['idProducto']);
                    $array[$i]['nombre'] = trim($response['nombre']);
                    $array[$i]['detalle'] = trim($response['detalle']);
                    $array[$i]['peso'] = trim($response['peso']);
                    $array[$i]['fecha'] = trim($response['fecha']);
                    $array[$i]['estado'] = trim($response['estado']);
                    $array[$i]['descuentoPromo'] = trim($response['descuentoPromo']);
                    if (trim($response['foto']) != null) {
                        $imagen = RUTA_IMGproducto . $response['idProveedor'] . '/' . trim($response['foto']);
                    } else {
                        $imagen = 'https://demoweb.reggat.com/assets/productos/default.png';
                    }
                    $array[$i]['foto'] = $imagen;
                    $array[$i]['codigoInterno'] = trim($response['codigoInterno']);
                    $array[$i]['alto'] = trim($response['alto']);
                    $array[$i]['ancho'] = trim($response['ancho']);
                    $array[$i]['largo'] = trim($response['largo']);
                    $array[$i]['descripcion'] = trim($response['descripcion']);
                    $array[$i]['etiqueta'] = trim($response['etiqueta']);
                    $array[$i]['idProveedor'] = trim($response['idProveedor']);
                    $array[$i]['idSucursal'] = trim($response['idSucursal']);
                    $array[$i]['idCategoria'] = trim($response['idCategoria']);
                    $array[$i]["variantes"] = getDetallesProducto($response['idProductoVariante']);
                    $i++;
                }
                $arraySub['productos'] = $array;
                echo ' {
                "errorCode": 0,
                "errorMessage": "Servicio ejecutado con éxito",
                "msg": ' . json_encode($arraySub) . '
            }';
            }else{
                echo ' {
              "errorCode": 2,
              "errorMessage": "No hay datos.",
              "msg": 0
          }';
            }
            $db = null;
        } catch(PDOException $e){
            $db = null;
            echo $e;
            echo ' {
              "errorCode": 3,
              "errorMessage": "Error al ejecutar el servicio web.",
              "msg": 0
          }';
        }
    } else {
        echo ' {
        "errorCode": 4,
        "errorMessage": "No autenticado.",
        "msg": 0
   }';
    }
});
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


function getExisteComercio($usuario, $correo){
	try{
			$sql = "SELECT idComercio
			        FROM comercio
			        WHERE usuario LIKE :usuario OR correo LIKE :correo";
			$db   = getConnection();
			$stmt = $db->prepare($sql);
      $stmt->bindParam("usuario",$usuario);
      $stmt->bindParam("correo",$correo);
			$stmt->execute();
			if ( $stmt->rowCount() > 0 ) {
				return true;
			} else {
        return false;
      }
		$db = null;
	} catch(PDOException $e){
		$db = null;
		echo '{
						"errorCode": 3,
						"errorMessage": "Error al ejecutar el servicio web.",
						"msg": "0"
				  }';
	}
}

function getExisteUsuario($usuario, $correo){
	try{
			$sql = "SELECT idUsuario
			        FROM usuario
			        WHERE usuario LIKE :usuario OR correo LIKE :correo";
			$db   = getConnection();
			$stmt = $db->prepare($sql);
      $stmt->bindParam("usuario",$usuario);
      $stmt->bindParam("correo",$correo);
			$stmt->execute();
			if ( $stmt->rowCount() > 0 ) {
				return true;
			} else {
        return false;
      }
		$db = null;
	} catch(PDOException $e){
		$db = null;
		echo '{
						"errorCode": 3,
						"errorMessage": "Error al ejecutar el servicio web.",
						"msg": "0"
				  }';
	}
}

function setTokenComercio($id){
	try {
		$db = getConnection();
		$sql   = "UPDATE comercio SET token=:token WHERE idComercio=:idComercio;";
		$stmt = $db->prepare($sql);
		$stmt->bindParam("idComercio", $id);
		$fecha = date("Y-m-d H:i:s");
		$token = sha1($fecha.$id);
		$stmt->bindParam("token", $token);
		$stmt->execute();
		if ( $stmt->rowCount() > 0 ) {
			return $token;
		}else{
			return false;
		}
		$db = null;
	} catch (PDOException $e){
		return false;
	}
}

function existeUsuarioApple($user){
  $sql = "SELECT idUsuario FROM usuario WHERE password=:user;";
  try{
    $db = getConnection();
    $stmt = $db->prepare($sql);
    $stmt->bindParam("user",$user);
    $stmt->execute();
    if ( $stmt->rowCount() > 0 ) {
      return true;
    }else{
      return false;
    }
    $db = null;
  }catch(PDOException $e){
  return false;
  }
}

function setTokenUsuario($id){
	try {
		$db = getConnection();
		$sql   = "UPDATE usuario SET token=:token WHERE idUsuario=:idUsuario;";
		$stmt = $db->prepare($sql);
		$stmt->bindParam("idUsuario", $id);
		$fecha = date("Y-m-d H:i:s");
		$token = sha1($fecha.$id);
		$stmt->bindParam("token", $token);
		$stmt->execute();
		if ( $stmt->rowCount() > 0 ) {
			return $token;
		}else{
			return false;
		}
		$db = null;
	} catch (PDOException $e){
		return false;
	}
}

function getValidate($id,$token){
  $sql = "SELECT idComercio FROM comercio WHERE idComercio=:idComercio AND token=:token;";
  $sql2 = "SELECT idUsuario FROM usuario WHERE idUsuario=:idUsuario AND token=:token;";
  try{
    $db   = getConnection();
    $stmt = $db->prepare($sql);
    $stmt->bindParam("idComercio",$id);
    $stmt->bindParam("token",$token);
    $stmt->execute();
    if ( $stmt->rowCount() > 0 ) {
      return true;
    }else{
      $stmt = $db->prepare($sql2);
      $stmt->bindParam("idUsuario",$id);
      $stmt->bindParam("token",$token);
      $stmt->execute();
      if ( $stmt->rowCount() > 0 ) {
        return true;
      } else {
        return false;
      }
    }
    $db = null;
  }catch(PDOException $e){
    return false;
  }
}

function getNombreRespuestaIncidencia($tipoUsuario, $id) {
  try{
    if($tipoUsuario == 1) {
      $sql = "SELECT c.nombre
              FROM respuestaincidencia r
              INNER JOIN administrador c ON c.idAdministrador=r.id
              WHERE r.id=:id AND r.tipoUsuario=1";
    } else {
      $sql = "SELECT c.nombre
              FROM respuestaincidencia r
              INNER JOIN usuario c ON c.idUsuario=r.id
              WHERE r.id=:id AND r.tipoUsuario=2";
    }
    $db = getConnection();
      $stmt = $db->prepare($sql);
      $stmt->bindParam("id",$id);
      $stmt->execute();
      $i = 0;
      $array = [];
      if ( $stmt->rowCount() > 0 ) {
          while ($data = $stmt->fetch(PDO::FETCH_ASSOC)){
            $array = trim($data['nombre']);
            $i++;
          }
          return $array;
      }else{
        if($tipoUsuario == 2) {
          $sql = "SELECT c.nombre
                  FROM respuestaincidencia r
                  INNER JOIN comercio c ON c.idComercio=r.id
                  WHERE r.id=:id AND r.tipoUsuario=2";
          $stmt = $db->prepare($sql);
          $stmt->bindParam("id",$id);
          $stmt->execute();
          $i = 0;
          $array = [];
          if ( $stmt->rowCount() > 0 ) {
            while ($data = $stmt->fetch(PDO::FETCH_ASSOC)){
              $array = trim($data['nombre']);
              $i++;
            }
          }
          return $array;
        }
        return $array;
      }
      $db = null;
  } catch(PDOException $e){
      $db = null;
      echo $e;
          echo ' {
              "errorCode": 3,
              "errorMessage": "Error al ejecutar el servicio web.",
              "msg": 0
          }';
  }
}

function getNombreSolicitudIncidencia($idSolicitud, $idTipoEnvio) {
  try{
    if($idTipoEnvio == 1) {
      $sql = "SELECT c.nombre
              FROM solicitud s
              INNER JOIN comercio c ON c.idComercio=s.idEnvia
              WHERE s.idSolicitud=:idSolicitud";
    } else {
      $sql = "SELECT c.nombre
              FROM solicitud s
              INNER JOIN usuario c ON c.idUsuario=s.idEnvia
              WHERE s.idSolicitud=:idSolicitud";
    }
    $db = getConnection();
      $stmt = $db->prepare($sql);
      $stmt->bindParam("idSolicitud",$idSolicitud);
      $stmt->execute();
      $i = 0;
      $array = [];
      if ( $stmt->rowCount() > 0 ) {
          while ($data = $stmt->fetch(PDO::FETCH_ASSOC)){
            $array = trim($data['nombre']);
            $i++;
          }
          return $array;
      }else{
        return $array;
      }
      $db = null;
  } catch(PDOException $e){
      $db = null;
      echo $e;
          echo ' {
              "errorCode": 3,
              "errorMessage": "Error al ejecutar el servicio web.",
              "msg": 0
          }';
  }
}

function distanceCalculation($point1_lat, $point1_long, $point2_lat, $point2_long, $unit = 'km', $decimals = 2) {

	$url = 'https://maps.googleapis.com/maps/api/directions/json?origin='.$point1_lat.','.$point1_long.'&destination='.$point2_lat.','.$point2_long.'&key=AIzaSyCjSiwS6JfrSw4LHTkAbZIeU33aU9ekyTA&mode=driving';
  $client = curl_init();
  curl_setopt($client, CURLOPT_URL, $url);
  curl_setopt($client, CURLOPT_RETURNTRANSFER, 1);
  $response = curl_exec($client);
  $response = curl_exec($client);
  $res = json_decode(json_encode(json_decode($response)), true);
  $dist_mts = ($res['routes']['0']['legs']['0']['distance']['value']);
  $distance = $dist_mts / 1000;
  curl_close($client);
	return round($distance, $decimals);
}

/*function distanceCalculation($point1_lat, $point1_long, $point2_lat, $point2_long, $unit = 'km', $decimals = 2) {
	// Cálculo de la distancia en grados
	$degrees = rad2deg(acos((sin(deg2rad($point1_lat))*sin(deg2rad($point2_lat))) + (cos(deg2rad($point1_lat))*cos(deg2rad($point2_lat))*cos(deg2rad($point1_long-$point2_long)))));
	// Conversión de la distancia en grados a la unidad escogida (kilómetros, millas o millas naúticas)
	switch($unit) {
		case 'km':
			$distance = $degrees * 111.13384; // 1 grado = 111.13384 km, basándose en el diametro promedio de la Tierra (12.735 km)
			break;
		case 'mi':
			$distance = $degrees * 69.05482; // 1 grado = 69.05482 millas, basándose en el diametro promedio de la Tierra (7.913,1 millas)
			break;
		case 'nmi':
			$distance =  $degrees * 59.97662; // 1 grado = 59.97662 millas naúticas, basándose en el diametro promedio de la Tierra (6,876.3 millas naúticas)
	}
	return round($distance, $decimals);
}*/

function getPreciosComercio($idComercio) {
  $sql = "SELECT precioBase, precioAdicional
          FROM comercio
          WHERE idComercio=:idComercio";
try {
  $validate = true;
  if($validate) {
    $db = getConnection();
    $stmt = $db->prepare($sql);
    $stmt->bindParam("idComercio",$idComercio);
    $stmt->execute();
    if ( $stmt->rowCount() > 0 ) {
      $i = 0;
      while ($response  = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $array['precioBase']   = trim($response['precioBase']);
        $array['precioAdicional'] 	= trim($response['precioAdicional']);
      }
      return $array;
    }else{
      return [];
    }
    $db = null;
  } else {
    echo ' {
            "errorCode": 4,
            "errorMessage": "No autenticado.",
            "msg": 0
          }';
  }
} catch(PDOException $e) {
  $db = null;
  echo  '{
          "errorCode": 3,
          "errorMessage": "Error al ejecutar el servicio web.",
          "msg": '. $e->getMessage() .'
        }';
}
}

function insertarSolicitudTransporteFirebase($obj)
{
  $array = [];
  $array['idSolicitud']['integerValue'] = $obj['idSolicitud'];
  $array['direccionDestino']['stringValue'] = $obj['direccion'];
  $array['direccionOrigen']['stringValue'] = $obj['direccionOrigen'];
  $array['latOrigen']['doubleValue'] = $obj['latOrigen'];
  $array['lngOrigen']['doubleValue'] = $obj['lngOrigen'];
  $array['nombreSolicitante']['stringValue'] = $obj['nombreSolicitante'];
  $array['precio']['stringValue'] = $obj['costoEnvio'];
  $array['tipoPago']['stringValue'] = $obj['tipoPago'];

  $conductores = [];
  // $conductores[0]['mapValue']['fields']['idConductor']['integerValue'] 	= trim($obj['idConductor']);
  $array['conductores']['arrayValue']['values'] = $conductores;


  /*$array['foto']['stringValue'] = $obj['fotoSoli'];
  $array['idConductor']['integerValue'] = 0;
  $array['idEstadoPedido']['stringValue'] = '7';
  $array['idSolicitudTransporte']['integerValue'] = $obj['idSolicitudTransporte'];
  $array['idTipoVehiculo']['integerValue'] = $obj['idTipoVehiculo2'];
  $array['latitud']['stringValue'] = $obj['latEntrega'];
  $array['longitud']['stringValue'] = $obj['longEntrega'];
  $array['proveedor']['stringValue'] =  $obj['proveedor'];
  $array['pesoVolumetrico']['integerValue'] = +$obj['pesoVolumetrico'];
  $sucursal = [];
  $sucursal[0]['mapValue']['fields']['idSucursal']['stringValue'] 	= trim($obj['idSucursal2']);
  $sucursal[0]['mapValue']['fields']['latitudSucursal']['stringValue'] 	= trim($obj['latitudSucursal']);
  $sucursal[0]['mapValue']['fields']['longitudSucursal']['stringValue'] = trim($obj['longitudSucursal']);
  $sucursal[0]['mapValue']['fields']['direccionSucursal']['stringValue'] = trim($obj['direccionSucursal']);
  $array['sucursal']['arrayValue']['values'] = $sucursal;*/

  $datos=["fields"=>(object)$array];
  $json=json_encode($datos);

	//$Enter your firestore unique key: below is a sample
    $firestore_key = "AIzaSyCjSiwS6JfrSw4LHTkAbZIeU33aU9ekyTA";

  #Provide your firestore project ID Here
    $object_unique_id = $obj['idSolicitud'];

    $url = URL_Firebase.$object_unique_id;

    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_HTTPHEADER => array('Content-Type: application/json',
            'Content-Length: ' . strlen($json),
            'X-HTTP-Method-Override: PATCH'),
        CURLOPT_URL => $url . '?key='.$firestore_key,
        CURLOPT_USERAGENT => 'cURL',
        CURLOPT_POSTFIELDS => $json
    ));

    $response = curl_exec( $curl );
    // echo($response);

    curl_close( $curl );

// Show result
    //echo $response . "\n";
}



function setNewPassUsuario($id, $correo, $body, $asunto) {
  $db = getConnection();
  $sql   = "UPDATE usuario
            SET password=:pass,recuperarPass=1
            WHERE idUsuario=:idUsuario;";
  $stmt = $db->prepare($sql);
  $stmt->bindParam("idConductor", $id);
  $date     = time();
  $npassword   = sha1($date . $id);
  $pass   = substr($npassword, 0, 6);
  $pw = hash_hmac('sha512', 'salt' . $pass, 92432);
  $stmt->bindParam('pass', $pw);
  $stmt->execute();
  $body2 = $body . $pass . ' <br>' . 'por favor ingrese a la aplicación con su nueva contraseña y siga las instrucciones.';
  sendEmail($correo, $body2, $asunto); // Actualizar luego de tener el correo del servidor
}

function sendEmailCli($email, $body, $asunto)
{ //MODIFICAR CORREO, ETC
  $fecha = date("Y-m-d H:i:s");
  //ENVIAR CORREO
  $mensaje = file_get_contents("function/template/mail2.html");
  $mensaje = str_replace("[ATTACHMENT]", utf8_decode($body), $mensaje);
  $mensaje = str_replace("[CORREO]", utf8_decode($email), $mensaje);
  $mensaje = str_replace("[DATE]", $fecha, $mensaje);

  $mail = new PHPMailer();
  $mail->IsSMTP(); // habilita SMTP
  // $mail->SMTPDebug = 1; // debugging: 1 = errores y mensajes, 2 = sólo mensajes
  $mail->SMTPAuth = true; // auth habilitada
  $mail->SMTPSecure = 'ssl'; // transferencia segura REQUERIDA para Gmail
  $mail->Host = "smtp.gmail.com";
  $mail->Port = 465; // or 587
  $mail->IsHTML(true);
  $mail->Username = "peto.app.clanbolivia@gmail.com";
  $mail->Password = "peto2020app";
  $mail->SetFrom("peto.app.clanbolivia@gmail.com");
  $mail->Subject = $asunto;
  $mail->Body = $mensaje;
  $mail->AddAddress("peto.app.clanbolivia@gmail.com");
  if (!$mail->Send()) {
    return false;
  } else {
    return true;
  }
}

function sendEmail($email, $body, $asunto)
{ //MODIFICAR CORREO, ETC
  $fecha = date("Y-m-d H:i:s");
  //ENVIAR CORREO
  $mensaje = file_get_contents("function/template/mail3.html");
  $mensaje = str_replace("[ATTACHMENT]", utf8_decode($body), $mensaje);
  $mensaje = str_replace("[DATE]", $fecha, $mensaje);

  $mail = new PHPMailer();
  $mail->IsSMTP(); // habilita SMTP
  // $mail->SMTPDebug = 1; // debugging: 1 = errores y mensajes, 2 = sólo mensajes
  $mail->SMTPAuth = true; // auth habilitada
  $mail->SMTPSecure = 'ssl'; // transferencia segura REQUERIDA para Gmail
  $mail->Host = "smtp.gmail.com";
  $mail->Port = 465; // or 587
  $mail->IsHTML(true);
  $mail->Username = "peto.app.clanbolivia@gmail.com";
  $mail->Password = "peto2020app";
  $mail->SetFrom("peto.app.clanbolivia@gmail.com");
  $mail->Subject = $asunto;
  $mail->Body = $mensaje;
  $mail->AddAddress($email);
  if (!$mail->Send()) {
    return false;
  } else {
    return true;
  }
}

function deleteSolicitudFirebase($idSolicitud)
{
	$firestore_key = "AAIzaSyCjSiwS6JfrSw4LHTkAbZIeU33aU9ekyTA";
	$url = URL_Firebase.$idSolicitud;
	$curl = curl_init();
	curl_setopt_array($curl, array(
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_CUSTOMREQUEST => 'DELETE',
		CURLOPT_URL => $url . '?key='.$firestore_key,
		CURLOPT_USERAGENT => 'cURL',
	));
	$response = curl_exec( $curl );
	curl_close( $curl );
	// Show result
	// echo $response . "\n";
}

function getLoginClienteRS($correo)
{
  try {
    // $input = $request->getParsedBody();
    $sql   = "SELECT idUsuario, nombre, ci, telefono, direccion, lat, lng, usuario, correo, recuperarPass
              FROM usuario
              WHERE correo=:correo AND estado=1";
    $db = getConnection();
    $stmt = $db->prepare($sql);
    $stmt->bindParam("correo", $correo);
    $stmt->execute();
    if ($stmt->rowCount() > 0) {
      while ($response  = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $id              = trim($response['idUsuario']);
        $arrUser['idUsuario']   = trim($response['idUsuario']);
        $arrUser['nombre']   = trim($response['nombre']);
        $arrUser['ci']   = trim($response['ci']);
        $arrUser['telefono']   = trim($response['telefono']);
        $arrUser['direccion']   = trim($response['direccion']);
        $arrUser['lat']   = trim($response['lat']);
        $arrUser['lng']   = trim($response['lng']);
        $arrUser['usuario']   = trim($response['usuario']);
        $arrUser['correo']   = trim($response['correo']);
        $token           = setTokenUsuario($id);
        $arrUser['token'] = trim($token);
        $recuperarPass          = trim($response['recuperarPass']);
        $arrUser['tipoUsuario'] 	= trim('2');
        if ($recuperarPass == 0) {
          echo  ' {
                  "errorCode": 0,
                  "errorMessage": "Login Exitoso.",
                  "msg": ' . json_encode($arrUser) . '
                  }';
        } else {
          echo  ' {
                  "errorCode": 5,
                  "errorMessage": "Bandera Recuperar password activa",
                  "msg": ' . json_encode($arrUser) . '
                  }';
        }
      }
    } else {
      echo '{
						"errorCode": 2,
						"errorMessage": "No hay datos.",
						"msg": ""
					}';
    }
    $db = null;
  } catch (PDOException $e) {
    $db = null;
    echo    '{
						"errorCode": 3,
						"errorMessage": "Error al ejecutar el servicio web.",
						"msg": ' . $e . '
				}';
  }
}
