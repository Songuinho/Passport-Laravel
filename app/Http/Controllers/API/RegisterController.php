<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class RegisterController extends Controller
{
    public function register(Request $request): JsonResponse{
        // Enregistrement
        $inputs = $request->all();

        $validator = Validator::make($inputs, [
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8',
            'c_password' => 'required|same:password' // Ajout de validation pour vérifier que les mots de passe correspondent
        ],[
            "name.required" => "Le nom est obligatoire.",
            "email.required" => "L'email est obligatoire.",
            "password.required" => "Le mot de passe est obligatoire.",
            "password.min" => "Le mot de passe doit avoir au moins 8 characteres.",
            'c_password.required' => 'Le champ de confirmation du mot de passe est requis.',
            'c_password.same' => 'La confirmation du mot de passe ne correspond pas.'
        ]);

        if($validator->fails()){
            return $this->sendError('Validation Error', $validator->errors());
        }

        $inputs['password'] = bcrypt($inputs['password']);
        $user = User::create($inputs);

        $success['name'] =  $user->name;
        $success['token'] = $user->createToken('payment')->accessToken;

        return $this->sendResponse($success, 'User has been registered successfully!');
    }

    public function login(Request $request): JsonResponse {
        // Connexion
        if(Auth::attempt(['email' => $request->email, 'password' => $request->password])){
            $user = Auth::user();
            $success['token'] = $user->createToken('payment')->accessToken;
            $success['name'] = $user->name;

            return $this->sendResponse($success, 'Login successful!');
        } else {
            return $this->sendError('Unauthorised', ['error' => 'Unauthorised']);
        }
    }
}
