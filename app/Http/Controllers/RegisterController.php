<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Meeting;
use App\User;
use DB;

class RegisterController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt.auth')->only(['store', 'destroy', 'unregister']);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'meeting_id' => 'required',
            'user_id' => 'required'
        ]);

        $meeting_id = $request->input('meeting_id');
        $user_id = $request->input('user_id');

        $meeting = Meeting::findOrfail($meeting_id);
        $user = User::findOrfail($user_id);

        $message = [
            'msg' => 'user is already registered meeting',
            'user' => $user,
            'meeting' => $meeting,
            'unregister' => [
                'href' => 'api/v1/meeting/unregister/'. $meeting->id,
                'method' => 'DELETE'
            ]
        ];

        if ($meeting->users()->where('users.id', $user->id)->first()) {
            return response()->json($message, 404);
        }

        $user->meetings()->attach($meeting);

        $response = [
            'msg' => 'user registered for meeting',
            'meeting' => $meeting,
            'user' => $user,
            'unregister' => [
                'href' => 'api/v1/meeting/unregister/'. $meeting->id,
                'method' => 'DELETE'
            ]
        ];

        return response()->json($response, 201);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($meeting_id, $meeting_user_id)
    {
        $meeting = Meeting::findOrfail($meeting_id);
        $meeting_user = DB::table('meeting_user')
            ->where('id', $meeting_user_id)->delete();

        $response = [
            'msg' => 'user deleted for this meeting',
            'meeting' => $meeting,
            'register' => [
                'href' => 'api/v1/meeting/registration',
                'method' => 'POST',
                'params' => 'user_id, meeting_id'
            ]
        ];

        return response()->json($response, 200);
    }

    public function unregister($id)
    {
        $meeting = Meeting::findOrfail($id);
        $meeting->users()->detach();

        $response = [
            'msg' => 'all users successfuly deleted for this meeting',
            'meeting' => $meeting,
            'register' => [
                'href' => 'api/v1/meeting/registration',
                'method' => 'POST',
                'params' => 'user_id, meeting_id'
            ]
        ];

        return response()->json($response, 200);
    }
}
