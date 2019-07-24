<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use JWTAuth;
use JWTException;

class AuthController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
           'name'  => 'required',
           'email' => 'required|email',
           'password' => 'required|min:5'
        ]);

        $name = $request->input('name');
        $email = $request->input('email');
        $password = $request->input('password');

        $user = new User([
            'name' => $name,
            'email' => $email,
            'password' => bcrypt($password)
        ]);

        $credentials = [
            'email' => $email,
            'password' => $password
        ];

        if ($user->save()) {
            
            $token = null;
            try {
                if (!$token = JWTAuth::attempt($credentials)) {
                    return response()->json([
                        'msg' => 'email or password incorrect'
                    ], 404);
                }
            } catch (JWTException $e) {
                return response()->json([
                    'msg' => 'failed to create token'
                ], 400);
            }

            $user->signin = [
                'href' => 'api/v1/signin',
                'method' => 'GET',
                'params' => 'email, passowrd'
            ];

            $response = [
                'data' => $user,
                'msg' => 'User Created',
                'token' => $token
            ];

            return response()->json($response, 201);
        }

        $response = [
            'msg' => 'an error occured'
        ];

        return response()->json($response, 404);
    }

    public function signin(Request $request)
    {
        $this->validate($request, [
            'email' => 'required|email',
            'password' => 'required|min:5'
        ]);

        $email = $request->input('email');
        $password = $request->input('password');

        if ($user = User::where('email', $email)->first()) {
            $credentials = [
                'email' => $email,
                'password' => $password
            ];

            $token = null;
            try {
                if (!$token = JWTAuth::attempt($credentials)) {
                    return response()->json([
                        'msg' => 'email or password incorrect'
                    ], 404);
                }
            } catch (JWTException $e) {
                return response()->json([
                    'msg' => 'failed to create token'
                ], 400);
            }

            $response = [
                'msg' => 'user signin',
                'data' => $user,
                'token' => $token
            ];

            return response()->json($response, 201);
        }

        $response = [
            'msg' => 'an error accured',
        ];

        return response()->json($response, 404);
    }    
}
