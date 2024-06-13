<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    public function index(): JsonResponse{
        $users = User::latest('id')->get();

        return $this->sendResponse($users, 'All users retrieved.');
    }
}
