<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Habitat;
use App\Models\ProprieteTypeHabitat;
use App\Models\ProprieteTypeHabitatValue;
use App\Models\TypeHabitat;
use App\Models\Vue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\Habitat as HabitatResource;

class HabitatController extends Controller
{
    /**
     * @return \Illuminate\Http\JsonResponse
     * recupeère tous les habitats
     */
    public function getAllHabitat()
    {
        $habitats = Habitat::where("valideParAtypik", 1)->paginate(10);
        if ($habitats->isEmpty()) {
            return response()->json([
                'serverError' => 'Aucun habitat trouvé'
            ], 404);
        }

        return response()->json([
            'success' => 'success',
            'habitats' => HabitatResource::collection($habitats)
        ], 200);
    }

    /**
     * @param $habitat_id int
     * Retourne les des détails d'un habitat
     */
    public function getHabitatDetails($habitat_id)
    {
        $habitat = Habitat::find($habitat_id);

        if (empty($habitat) || $habitat->valideParAtypik != 1) {
            return response()->json([
                'serverError' => 'Aucun habitat trouvé'
            ], 404);
        }
        //@TODO addd habitats comments
        return response()->json([
            'success' => 'Informations de l\'habitat',
            'habitat' => new HabitatResource($habitat)
        ], 200);
    }

    public function addHabitat(Request $request)
    {

        //on vérifie si l'utilisateur
        //authentifié est autorisé à ajouter un habitats
        if (Auth::user()->canAddHabitat != 1) {
            return response()->json([
                'serverError' => 'Vous n\êtes pas autorisé à ajouter un habitat !'
            ], 403);
        }

        //validation des données envoyées
        $validation = Validator::make($request->all(), [
            'title' => 'required|string',
            'description' => 'required|string',
            'nombreChambre' => 'required|integer|min:1',
            'prixParNuit' => 'required|numeric',
            'nombreLit' => 'required|integer|min:1',
            'adresse' => 'string|required',
            'hasTelevision' => 'required|in:0,1', //utiliser des radios button "oui"=>1, "non"=>0
            'hasClimatiseur' => 'required|in:0,1',
            'hasChauffage' => 'required|in:0,1',
            'hasInternet' => 'required|in:0,1',
            'typeHabitat' => 'required|integer|min:1',
            'vues' => 'required',
            'vues.*' => 'image|mimes:jpeg,jpg,png'
        ]);

        if ($validation->fails()) {
            return response()->json([
                'serverError' => 'Veuillez vérifier les données saisies',
                'validationErrors' => $validation->errors()
            ], 400);
        }

        if (!TypeHabitat::find($request->typeHabitat)) {
            return response()->json([
                'serverError' => 'Veuillez vérifier les données saisies',
                'validationErrors' => [
                    'typeHabitat' => 'Type habitat inexistant'
                ]
            ], 400);
        }

        $validatedData = $validation->validated(); //récupère toutes les données validées
        $validatedData['proprietaire'] = Auth::id();

        $habitat = Habitat::create($validatedData);

        if (empty($habitat)) {
            return response()->json([
                'serverError' => 'Un erreur est survenue lors de la création de l\'habitat!
                Veuillez réessayer . ', 'data' => $request->all()], 500);
        }

        //enregisterement des images dans la table Vues
        if ($request->hasFile('vues')) {
            foreach ($request->file('vues') as $file) {
                $name = date('d-m-Y') . '-' . $file->getClientOriginalName(); //nom du fichier
                $lienImage = $file->storeAs('vues', $name);
                $vue = Vue::create([
                    'lienImage' => $lienImage,
                    'habitat' => $habitat->id
                ]);

                if (empty($vue)) {
                    Log::alert('Image non enregistrée en bd pour l\'habitat : ' . $habitat->id);
                }
            }
        }

        return response()->json([
            'success' => 'Habitat créé avec succès. Vous recevrai un mail lors de sa validation par AtypikHouse',
            'habitat' => $habitat->id
        ], 201);
    }

    /**
     * @param Request $request
     * @param $habitat_id
     * Nb : Ceci ne modifie que les informations
     * Textuelle d'un habit mais pas les images
     * Pour modifier les images d'un habitats,
     * consulter la function upadateHabitatVues() du controller VueController
     *
     * NB : seul le propriétaire est autorisé à modifier
     * un habitat
     *
     * Modifie un habitat donné
     */
    public function updateHabitat(Request $request, $habitat_id)
    {
        $habitat = Habitat::find($habitat_id);

        if (empty($habitat)) {
            return response()->json([
                'serverError' => 'Aucun habitat ne correspond à l\'id : ' . $habitat_id
            ], 404);
        }

        if (Auth::id() != $habitat->getProprietaire->id) {
            return response()->json([
                'serverError' => 'Vous n\'êtes pas autorisé à effectuer cette action'
            ], 403);
        } else {

            $validation = Validator::make($request->all(), [
                'title' => 'required|string',
                'description' => 'required|string',
                'nombreChambre' => 'required|integer|min:1',
                'prixParNuit' => 'required|numeric',
                'nombreLit' => 'required|integer|min:1',
                'adresse' => 'string|required',
                'hasTelevision' => 'required|in:0,1', //utiliser des radios button "oui"=>1, "non"=>0
                'hasClimatiseur' => 'required|in:0,1',
                'hasChauffage' => 'required|in:0,1',
                'hasInternet' => 'required|in:0,1',
                'typeHabitat' => 'required|integer|min:1',
            ]);

            if ($validation->fails()) {
                return response()->json([
                    'serverError' => 'Veuillez vérifier les données saisies',
                    'validationErrors' => $validation->errors()
                ], 400);
            }

            if (!TypeHabitat::find($request->typeHabitat)) {
                return response()->json([
                    'serverError' => 'Veuillez vérifier les données saisies',
                    'validationErrors' => [
                        'typeHabitat' => 'Type habitat inexistant'
                    ]
                ], 400);
            }


            $habitat->title = $request->title;
            $habitat->description = $request->description;
            $habitat->nombreChambre = $request->nombreChambre;
            $habitat->prixParNuit = $request->prixParNuit;
            $habitat->nombreLit = $request->nombreLit;
            $habitat->adresse = $request->adresse;
            $habitat->hasTelevision = $request->hasTelevision;
            $habitat->hasClimatiseur = $request->hasClimatiseur;
            $habitat->hasChauffage = $request->hasChauffage;
            $habitat->hasInternet = $request->hasInternet;
            $habitat->typeHabitat = $request->typeHabitat;

            $isSaved = $habitat->save();
            if ($isSaved) { // si l'habitat est bien enregisté en bd
                return response()->json([
                    'success' => 'habitat modifié avec succès !',
                    'habitat' => $request->except('vues')
                ], 200);
            } else {

                return response()->json([
                    'serverError' => 'Erreur lors de l\'enregistrement des modifications',
                    'inputs' => $request->all()
                ], 500);
            }
        }
    }

    /**
     * @param $habitat_id
     * @return \Illuminate\Http\JsonResponse
     *
     * Permet de supprimer un habitats
     * NB : Seul le proprietaires peuvent supprimer des habitats
     */
    public function deleteHabitat($habitat_id)
    {
        $habitat = Habitat::find($habitat_id);

        if (empty($habitat)) {
            return response()->json([
                'serverError' => 'Aucun habitat ne correspond à l\'id : ' . $habitat_id
            ], 404);
        }

        if (Auth::id() != $habitat->getProprietaire->id) {
            return response()->json([
                'serverError' => 'Vous n\'êtes pas autorisé à effectuer cette action'
            ], 403);

        } else {
            $habitat->delete();
            return response()->json([
                'success' => 'habitat supprimé avec succès'
            ], 200);
        }

    }


    /**
     * Renvoie les habitat ajouté par un
     * utilisateur lembda
     */
    public function getUserHabitat()
    {
        $habitats = Auth::user()->getHabitats;
        if ($habitats->count() == 0) {
            return response()->json([
                'serverError' => 'Aucun habitat trouvé'
            ], 404);
        }

        return response()->json([
            'success' => 'vos habitats',
            'habitats' => HabitatResource::collection($habitats)
        ], 200);
    }


    public function getAllTypeHabitat()
    {
        $allTypeHabitat = TypeHabitat::where('libelle', '!=', env('DEFAULT_TYPE_HABITAT'))->get();
        if (count($allTypeHabitat) > 0) {
            return response()->json([
                'success' => 'tous les types d\'habitat',
                'typeHabitats' => $allTypeHabitat
            ], 200);
        } else {
            return response()->json([
                'serverError' => 'Auncun type d\'habitat trouvé',
            ], 404);
        }
    }


    public function addNewPropriete(Request $request, $idHabitat)
    {
        $habitat = Habitat::find($idHabitat);
        if (empty($habitat) || $habitat->valideParAtypik != 1) {
            return response()->json([
                'serverError' => 'Impossible d\'ajouter cette propriété : habitat inexistant !'
            ], 404);
        }

        if (Auth::id() != $habitat->getProprietaire->id) {
            return response()->json([
                'serverError' => 'Accès non autorisé !'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'propriete_type_habitat' => 'required|integer|min:1',
            'value' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'serverError' => 'Veuillez vérifier les données saisies',
                'validationErrors' => $validator->errors()
            ], 400);
        }

        $propriete = ProprieteTypeHabitat::find($request->propriete_type_habitat);

        if (empty($propriete)) {
            return response()->json([
                'serverError' => 'Veuillez vérifier les données saisies',
                'validationErrors' => [
                    'propriete_type_habitat' => 'Cette propriété est inexistante '
                ]
            ], 400);
        }

        if ($habitat->getType->id != $propriete->getTypeHabitat->id) {
            return response()->json([
                'serverError' => 'La propriété <' . $request->value . '> n\'est pas définie pour le type d\'habitat <' . $habitat->getType->libelle . '>',
            ], 404);
        }

        $proprieteCreated = ProprieteTypeHabitatValue::create([
            'propriete_type_habitat' => $request->propriete_type_habitat,
            'value' => $request->value,
            'habitat' => $idHabitat
        ]);

        if (empty($proprieteCreated)) {
            return response()->json([
                'serverError' => 'Erreur Interne lors de l\'ajout de la propriété :  Veuillez réessayer',
            ], 500);
        } else {
            return response()->json([
                'success' => 'La proprité a été bien rajouté à l\'habitat'
            ], 200);
        }
    }
}
