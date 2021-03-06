<?php

/**
 *
 */
class loginController extends BaseController
{

  function verPagina()
  {
    if(Request::method() == 'POST')
        {

          /*Guardamos en la variable $Form todos los valores obtenidos*/
          $Form = Input::get('data'); //datos
          /*Creamos la reglas de validacion para esos valores*/
          $Reglas = array(
            'username' => 'required',
            'password' => 'required'
          );
          $mensajes = array(
            'required' => 'Ingresar Contraseña'
          );
          /*Creamos la validación diciendo que datos
          * y como se validarán
          */
          if($Form){
              $Validar  = Validator::make($Form, $Reglas, $mensajes);
              /* Verificamos si los datos fueron validados*/
              if($Validar->fails())
              {
                  /*Retornamos los errores de validación encontrados*/
                 return $Validar->messages();
              }
              else
              {
                /*Creamos el array para enviar los datos
                * a la autentifiacion del usuario*/
                $validarAuth = array(
                    'username'  =>  Input::get('data.username'),
                    'password'  =>  Input::get('data.password'),
                    'active'    =>  1
                );
                  /*Vemos si las credenciales son correctas*/
                  if(Auth::attempt($validarAuth)){
                    //Ingresamos un id de session
                    $idSession = $this->generaidSession();
                    User::where('id','=',Auth::user()->id)->update(array('id_session'=>$idSession));
                    Session::put('sessionId',$idSession);
                    return Response::json(array(0=>'success'));
                  }
                  else{
                    return Response::json(array(0=>'error'));;
                  }
              }
          }
          else{

              //---Este else responderá a Android
              $validarAuth = array(
                    'username'  =>  Input::get('username'),
                    'password'  =>  Input::get('password'),
                    'active'    =>  1
                );
               /*Vemos si las credenciales son correctas*/
                  if(Auth::attempt($validarAuth)){
                    //Ingresamos un id de session
                    if(Auth::user()->hasRole('padre')){
                        try{
                            $idSession = $this->generaidSession();
                            User::where('id','=',Auth::user()->id)->update(array('id_session'=>$idSession));
                            Session::put('sessionId',$idSession);
                            $persona = Auth::user()->persona()->first();
                            $idpadre =Auth::user()->persona()->first()->padre()->first()->id;
                            $email = Auth::User()->persona()->first()->padre()->pluck('email') == null ? "Sin email" : Auth::User()->persona()->first()->padre()->pluck('email');
                            $nombre_completo = $persona->nombre." ".$persona->apellido_paterno." ".$persona->apellido_materno;
                            return Response::json(array("estado"=>"200",
                                                        "message"=>"success",
                                                        "email"=>  $email,
                                                        "username"=>Auth::user()->username,
                                                        "nombre_completo"=>$nombre_completo,
                                                        "padre_id"=>$idpadre

                                                ));
                        }catch(Exception $e){return $e;}
                    }else return Response::json(array("estado"=>"404","message"=>"No eres padre"));

                  }
                  else{
                    return Response::json(array("estado"=>"404","message"=>"No se encontro el usuario"));
                  }
          }
        }
      else{
        // return User::where('users.id', '=', 9)
        // ->join('perfiles', 'users.id', '=', 'perfiles.users_id')
        // ->select('perfiles.foto_perfil')->get();
         // $skin = Skin::where('skin', '=', 'skin-blue')->pluck('id');
         // $usuario = new User();
         // $usuario->username = 'contenido';
         // $usuario->password = Hash::make('12345');
         // $usuario->active = 1;
         // $usuario->token = Hash::make('contenido');
         // $usuario->skin_id = $skin;
         // $usuario->save();
         // $myRole = DB::table('roles')->where('name', '=', 'content manager')->pluck('id');
         // $usuario->attachRole($myRole);
         // $perfil = new Perfil();
         // $perfil->foto_perfil = 'perfil-default.jpg';
         // $perfil->users_id = $usuario->id;
         // $perfil->save();
         // return "se registro al usuario: ".$usuario->username;
         return View::make('vista_login');
      }
    }

    public function salir(){
        Auth::logout();
        return Redirect::to('/');
    }

    public function verificarUsuario(){
      $reglas = array(
        'username' => 'required'
      );
      $Validar  = Validator::make(Input::get('data'), $reglas);
      /* Verificamos si los datos fueron validados*/

      // if($Validar->fails())
      // {
      //   /*Retornamos los errores de validación encontrados*/
      //    return $Validar->messages();
      // }
      // else{
        $Usuario = User::where('username', '=',Input::get('data'))
        ->where('active', '=', 1)
        ->first();
        if($Usuario == ''){
          return Response::json(array(0=>'null'));
        }
        else{
          return $Usuario->perfil()->get();
        }
      // }
    }
    public function loginFB(){
        $validarAuth = array(
            'username' => Input::get('first_name').' '.Input::get('last_name'),
            'password' => Input::get('id')
        );
        /*Vemos si las credenciales son correctas*/
                  if(Auth::attempt($validarAuth)){
                        $idSession = $this->generaidSession();
                        User::where('id','=',Auth::user()->id)->update(array('id_session'=>$idSession));
                        Session::put('sessionId',$idSession);
                        return Response::json(array(0=>'success'));
                  }
                  else{
                     $user = new User();
                        $user->username=Input::get('first_name').' '.Input::get('last_name');
                        $user->password=Hash::make(Input::get('id'));
                        $user->token=sha1(Input::get('email'));
                        $user->active = 1;
                        $user->skin_id=skin::all()->first()->id;
                        $user->save();
                        Session::put('crypt',Crypt::encrypt($user->password));
                        $myRole = DB::table('roles')->where('name', '=', 'padre-fb')->pluck('id');
                        $user->attachRole($myRole);
                        $persona = new persona();
                        $persona->nombre = Input::get('first_name');
                        $persona->apellido_paterno = Input::get('last_name');
                        if(Input::get('gender') == 'male'){
                            $persona->sexo = 'm';
                        }
                        else
                            $persona->sexo = 'f';
                        $persona->user_id=$user->id;
                        $persona->save();
                        // $membresia = new membresia();
                        // $membresia->token_card=sha1($datos_tarjeta["numero_tarjeta"]);
                        // $membresia->fecha_registro= date("Y-m-d");
                        // $membresia->active=1;
                        // $membresia->save();
                        $padre = new padre();
                        $padre->persona_id   = $persona->id;
                        $padre->email = (Input::get('email') == '')?'Sin email':Input::get('email');
                        $padre->save();
                        $perfil = new perfil();
                        $perfil->foto_perfil=Input::get('picture')['data']['url'];
                        $perfil->gustos="¿Cuáles son tus gustos?";
                        $perfil->users_id=$user->id;
                        $perfil->save();
                        $idSession = $this->generaidSession();
                            if(Auth::attempt($validarAuth)){
                            $idSession = $this->generaidSession();
                            User::where('id','=',Auth::user()->id)->update(array('id_session'=>$idSession));
                            Session::put('sessionId',$idSession);
                            return Response::json(array(0=>'success'));
                      }
                  }
    }
    private function generaidSession(){
		$cadena = "ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890";

		$longitudCadena=strlen($cadena);


		$folio = "";
		$longitudFolio=10;

		for($i=1 ; $i<=$longitudFolio ; $i++){
			$pos=rand(0,$longitudCadena-1);

			$folio .= substr($cadena,$pos,1);
		}
		return $folio;
	}

}
