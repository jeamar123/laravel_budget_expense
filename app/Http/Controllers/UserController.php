<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if( $request->has('search') ) {
            $users = User::where('first_name', 'like', '%'.$request->get('search').'%')
                        ->orWhere('last_name', 'like', '%'.$request->get('search').'%')
                        ->get();

            return response([ 
                'users' => $users,
                'message' => 'Success', 
                'status' => true 
            ], 200);
        }

        $users = User::all();

        return response([ 
            'users' => $users,
            'message' => 'Success', 
            'status' => true 
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $fields = $request->validate([
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'email' => 'required|string',
            'password' => 'required|string'
        ]);

        // Check duplicate email
        $user_email = User::where('email', $fields['email'])->first();

        if($user_email){
            return response([
                'message' => 'Email already exist',
                'status' => false
            ], 409);
        }

        $user = User::create([
            'first_name' => $fields['first_name'],
            'last_name' => $fields['last_name'],
            'email' => $fields['email'],
            'password' => bcrypt($fields['password'])
        ]);

        if($user){
            return response([ 
                'message' => 'Create User Successful.', 
                'status' => true 
            ], 201);
        }

        return response([ 
            'message' => 'Create User Failed.', 
            'status' => false 
        ], 500);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function get(Request $request, $id = null)
    {
        if($id){
            $user = User::find($id);
        }else{
            $user = $request->user();
        }

        if($user){
            return response([ 
                'user' => $user,
                'message' => 'Success', 
                'status' => true 
            ], 200);
        }

        return response([ 
            'message' => 'User not found', 
            'status' => false 
        ], 404);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        // Check if user exist
        $check_user = User::find($id);
        if(!$check_user){
            return response([ 
                'message' => 'User not found', 
                'status' => false 
            ], 404);
        }

        $fields = $request->validate([
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'email' => 'required|string',
        ]);

        // Check duplicate email
        $user_email = User::where('email', $fields['email'])->where('id', '!=', $id)->first();
        if($user_email){
            return response([
                'message' => 'Email already exist',
                'status' => false
            ], 409);
        }

        $user = User::find($id)->update([
            'first_name' => $fields['first_name'],
            'last_name' => $fields['last_name'],
            'email' => $fields['email']
        ]);

        if($user){
            return response([ 
                'message' => 'Update User Successful.', 
                'status' => true 
            ], 200);
        }

        return response([ 
            'message' => 'Update User Failed.', 
            'status' => false 
        ], 500);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        // Check if user exist
        $check_user = User::find($id);
        if(!$check_user){
            return response([ 
                'message' => 'User not found', 
                'status' => false 
            ], 404);
        }

        $user = User::destroy($id);

        if($user){
            return response([ 
                'message' => 'Delete User Successful.', 
                'status' => true 
            ], 200);
        }

        return response([ 
            'message' => 'Delete User Failed.', 
            'status' => false 
        ], 500);
    }
}
