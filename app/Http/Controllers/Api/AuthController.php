<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthController extends Controller
{
    private $successMessage = "";
    private function renderResponseWithToken(array $credentials){
        $action =  Route::getCurrentRoute()->getActionMethod();
        switch ($action) {
            case "register" : {
                $this->successMessage =  'Votre compte a été créé avec succès. Veuillez consulter votre mail pour valider votre email à fin de pouvoir
                                    vous connecter à votre compte';
            }
            break;

            case "registerWithSocialite" : {
                $this->successMessage =  'Votre compte a été créé avec succès.';
            }
            break;

            case "login" :
            case "loginWithSocialite"  :
                $this->successMessage =  'Authentification réussie';

        }

        try{
            $accessToken = JWTAuth::attempt( [
                'email' => $credentials['email'],
                'password' =>$credentials['password']
            ] );

            if($accessToken) {

                if( $action == "login"  && !Auth::user()->email_verified_at ){
                    return  response()->json([
                        'serverError' =>  'Veuillez confirmer votre adresse mail avant de pouvoir vous connecter',
                        'emailNotVerified' => true
                    ], 403);
                }

                return response()->json([
                    'success' => $this->successMessage,
                    'user' => User::where("email", $credentials['email'] )->first(),
                    'access_token' => $accessToken,
                ], 200);

            } else {

                if( $action == "register" || $action=="registerWithSocialite") {

                    return response()->json([
                        'serverErrorSecret' =>  'Token non généré',
                    ], 500);

                } else if($action == "login" || $action == "loginWithSocialite") {

                    return response()->json([
                        'serverError' =>  'Identifiants incorrects',
                    ], 500);
                }
            }

        } catch (\Exception $e){
            return response()->json([
                'serverErrorSecret' => "Problème survenue lors de la génération du token : " . $e->getMessage()
            ], 500);
        }
    }

    public function register(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'name' => "required|string|between:2,100|regex:/^[-'a-zA-ZÀ-ÿ\s]+$/",
            'email' => 'required|email|max:255|regex:/^[a-zA-Z0-9Ññ]+@\w+\.com/',
            'telephone' => 'required|string|between:5,15|regex:/^[-0-9\+]+$/',
            'adresse' => 'string|required|max:255|regex:/^[a-zA-Z0-9Ññ]/',
            'complement_adresse' => 'string|max:255|regex:/^[a-zA-Z0-9Ññ]/',
            'code_postale' => 'required|digits_between:5,5|regex:/^[0-9]/',
            'ville' => "required|string|max:200|regex:/^[-'a-zA-ZÀ-ÿ\s]+$/",
            'password' => 'required|confirmed'
        ]);

        if ($validation->fails()) {
            return response()->json([
                'serverError' => 'Les informations envoyées sont invalides',
                'validationErrors' => $validation->errors()
            ], 400);
        }

        $data = $validation->validated();

        $data ['password'] = bcrypt($request->password);
        $data ['password2'] = Crypt::encryptString($request->password);
        $data ['role'] = env("SIMPLE_USER_ROLE");

        $user = User::create($data);

        if (empty($user)) {
            return response()->json([
                'serverError' => 'La création de votre compte a échoué veuillez réessayer'
            ], 500);
        }

        try{
            event( new Registered($user) ); // sendEmailVerification
        } catch(\Exception $e) {
            Log::alert("Could\'nt send email verification for user with id : " . $user->id);
        }

        return $this->renderResponseWithToken([
            'email' => $user->email,
            'password' => Crypt::decryptString( $user->password2 )
        ]);
    }

    public function login(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'email' => 'required|email|max:255|regex:/^[a-zA-Z0-9Ññ]+@\w+\.com/',
            'password' => 'required'
        ]);

        if ($validation->fails()) {
            return response()->json([
                'serverError' => 'Les informations envoyées sont invalides',
                'validationErrors' => $validation->errors()
            ], 400);
        }

        $loginData = $validation->validated();
        return $this->renderResponseWithToken($loginData);
    }

    public function loginWithSocialite( Request $request ) {

        $validation = Validator::make($request->all(), [
            'id'=>'required|string|regex:/^[0-9]+$/',
            'name' => "required|string|between:2,100|regex:/^[-'a-zA-ZÀ-ÿ\s]+$/",
            'email' => 'required|email|max:255|regex:/^[a-zA-Z0-9Ññ]+@\w+\.com/',
            'provider'=>'required|string',
        ]);

        if ( $validation->fails() ){
            return response()->json([
                'serverError' => 'Les informations envoyées sont invalides',
                'validationErrors' => $validation->errors()
            ]);
        }

        $provider = $request->input('provider');

        if (!$provider || ($provider != 'GOOGLE' && $provider != 'FACEBOOK')) {
            return response()->json([
                'error' => 'action impossible'
            ], 400);
        }

        $user = User::where('email', $request->input('email'))->first();

        if( empty($user) ){
            return response()->json(['error' => 'Compte inexistant'], 401);
        }

        $idSocialite = $request->input('id');


        //s'il n'est pas encore inscrit avec les réseaux sociaux
        // et qu'il veut se logger on l'inscrit directement
        // ensuite on le log et on renvoie le token (voir la function registerWithSocialite)
        if ( ( $provider == "FACEBOOK" && !$user->facebook_id) || ($provider == "GOOGLE" && !$user->google_id) ) {
            $user->facebook_id = $request->input('id');
            return $this->registerWithSocialite($request);
        }

        if(
            ($provider =="FACEBOOK" && $user->facebook_id == $idSocialite)
            || ($provider =="GOOGLE" && $user->google_id == $idSocialite)
        ){

            return $this->renderResponseWithToken([
                'email' => $user->email,
                'password' => Crypt::decryptString($user->password2)
            ]);


        } else {
            return response()->json([
                'error'=>'Identifiants incorrects'
            ]);
        }
    }

    public function registerWithSocialite(Request $request) {

        $validation = Validator::make($request->all(), [
            'id'=>'required|string|regex:/^[0-9]+$/',
            'name' => "required|string|between:2,100|regex:/^[-'a-zA-ZÀ-ÿ\s]+$/",
            'email' => 'required|email|max:255|regex:/^[a-zA-Z0-9Ññ]+@\w+\.com/',
            'provider'=>'required|string',
        ]);

        if ( $validation->fails() ){
            return response()->json([
                'serverError' => 'Les informations envoyées sont invalides',
                'validationErrors' => $validation->errors()
            ],400);
        }

        $provider = $request->input('provider');

        if (!$provider || ($provider != 'GOOGLE' && $provider != 'FACEBOOK')) {
            return response()->json([
                'error' => 'action impossible'
            ], 400);
        }

        $user = User::where('email', $request->input('email'))->first();

        //si l'utilisateur était déjà enregistré
        //avec le formulaire standard ou l'un des réseaux sociaux
        if ( !empty( $user ) ) {


            // s'il n'est pas encore enregistré avec l'un de ces réseaux sociaux
            if ($provider == "FACEBOOK" && !$user->facebook_id) {
                $user->facebook_id = $request->input('id');
            } else if ($provider == "GOOGLE" && !$user->google_id) {
                $user->google_id =   $request->input('id');
            } else { // si l'utilisateur est déjà enregistré avec les résaux sociaux
                $this->loginWithSocialite($request);
            }

            $isSaved = $user->update();
            if ($isSaved) {

                return $this->renderResponseWithToken([
                    'email' => $user->email,
                    'password' => Crypt::decryptString($user->password2)
                ]);


            } else {
                return response()->json(['error' => 'Erreur interne au serveur '], 500);
            }

        }

        //si l'utilisateur est inexistant
        $newUser = $request->all('email', 'name', 'canLegalyPlay');

        if ($provider == "FACEBOOK") {
            $newUser['facebook_id'] = $request->input('id');
        } else if ($provider == "GOOGLE") {
            $newUser['google_id'] = $request->input('id');
        }

        $password = Str::random(20);
        $userCreated = User::create( array_merge(
            $newUser,
            ['password' => bcrypt( $password )],
            ['password2' => Crypt::encryptString( $password )],
            ['email_verified_at' => Carbon::now()]
        ));

        if ($userCreated->id) {
            return $this->renderResponseWithToken([
                'email' => $userCreated->email,
                'password' => Crypt::decryptString($userCreated->password2)
            ]);

        } else {
            return response()->json(['error' => 'Erreur interne au serveur '], 500);
        }
    }



    public function logout(){
        auth()->logout();
        return response()->json([
            "success" => "Successfully logout "
        ]);
    }



    public function deleteAccount( $idUser ){
        if(!$idUser) {
            return response()->json([
                'error' => 'action impossible'
            ], 400);
        }

        if( (int) $idUser != Auth::id() ){
            return response()->json([
                'error' => 'Accès non autorisé'
            ], 403);
        }

        $userToDelete = User::find($idUser);

        if( !empty($userToDelete) && $userToDelete->name != "neant"){
            $userToDelete->name = 'neant';
            $userToDelete->email = 'neant'.$userToDelete->id;
            $userToDelete->password = 'neant';
            $userToDelete->telephone = 'neant';
            $userToDelete->address = 'neant';
            $userToDelete->additional_address = 'neant';
            $userToDelete->postal_code = 'neant';
            $userToDelete->ville = 'neant';
            $userToDelete->facebook_id = 'neant';
            $userToDelete->google_id = 'neant';
            $userToDelete->password2 = 'neant';

            $isSaved = $userToDelete->save();

            if( $isSaved ){
                return response()->json([
                    'message' => 'Utilisateur supprimé'
                ], 200);
            } else {
                return response()->json([
                    'error' => 'Erreur survenu lors de la suppression du compte . Veuillez reessayer'
                ], 500);
            }

        } else {
            return response()->json([
                'error' => 'Utilisateur inexistant'
            ], 400);
        }
    }


}
