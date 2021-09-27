<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function askAuthorizationToAddHabitat(Request $request)
    {
        $user = Auth::user();
        if (empty($user)) {
            return response()->json([
                'serverError' => 'Accès non autorisé',
            ], 403);

        } else {
            if ($user->canAddHabitat == 1) { // si l'utilisateur est déjà autorisé à ajouter des habitats
                return response()->json([
                    'success' => 'Vous êtes déjà autorisé à ajouter des habitats'
                ], 200);
            } else {//s'il n'est pas encore autorisé
                //validation des informations envoyées
                $validator = Validator::make($request->all(), [
                    'siren' => 'required|string|min:9|max:11',
                    'nomEntreprise' => 'required|String'
                ]);

                if ($validator->fails()) {
                    return response()->json([
                        'serverError' => 'Veuillez vérifier les données saisies',
                        'validationErrors' => $validator->errors()
                    ], 404);
                }


                $user->wantToAddHabitat = 1;
                $user->siren = $request->siren;
                $user->nomEntreprise = $request->nomEntreprise;
                $isSaved = $user->save();

                if ($isSaved) {
                    return response()->json([
                        'success' => 'Votre demande a été prise en compte. Vous recevrez un mail lors de la validation de votre profil dans un délais de 48h. Si vous n\'avez aucun retour passé ce délais
                        veuillez contacter AtypikHouse',
                    ], 200);

                } else {
                    return response()->json([
                        'serverError' => 'Une erreur interne est survenue. Veuillez renouveller votre demande'
                    ]);
                }
            }
        }
    }

    public function updateProfil(Request $request, $idUser)
    {
        $user = User::find($idUser);

        if ((empty($user) || Auth::id() != $idUser)) {
            return response()->json([
                'serverError' => 'Opération impossible : accès non autorisé.'
            ], 400);
        }

        $validation = Validator::make($request->all(), [
            'name' => 'string',
            'email' => 'email',
            'telephone' => 'string',
            'adresse' => 'string',
            'password' => 'confirmed'
        ]);

        if ($validation->fails()) {
            return response()->json([
                'serverError' => 'Les informations envoyées invalides',
                'validationErrors' => $validation->errors()
            ], 400);
        }


        $data = $validation->validated();
        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->telephone = $data['telephone'];
        $user->adresse = $data['adresse'];
        if($request->input("password")){
            $user->password = bcrypt($data['password']);
        }


        $iSaved = $user->save();
        if ($iSaved) {
            return response()->json([
                'success' => 'Profil mis à jour avec succès',
                'user' => $user,
            ], 200);
        } else {
            return response()->json([
                'serverError' => 'Erreur Interne : La mise à jour du profil a échoué',
            ], 500);
        }
    }
}
