<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Meeting;
use DB;

class MeetingController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt.auth')->except('index');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
            $meetings = Meeting::all();
            foreach ($meetings as $meeting) {
                $meeting->view_meeting = [
                    'href' => 'api/v1/meeting/'. $meeting->id,
                    'method' => 'GET'
                ];
            }

            $response = [
                'data' => $meetings,
                'msg' => 'List of all meetings'
            ];

            return response()->json($response, 200);
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
            'title' => 'required',
            'description' => 'required',
            'time' => 'required',
            'user_id' => 'required',
        ]);        

        $title = $request->input('title');
        $description = $request->input('description');
        $time = $request->input('time');
        $user_id = $request->input('user_id');

        $meeting = new Meeting([
            'title'=> $title,
            'description'=> $description,
            'time'=> $time,
        ]);

        if ($meeting->save()) {
            $meeting->users()->attach($user_id);
            $meeting->view_meeting = [
                'href' => 'api/v1/meeting/'. $meeting->id,
                'method' => 'GET'
            ];
            
            $message = [
                'data' => $meeting,
                'msg' =>  'Meeting created'
            ];

            return response()->json($message, 201);
        }

        $response = [
            'msg' => 'error during creating'
        ];

        return response()->json($response, 404);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        // $meeting = Meeting::with('users')->where('id', $id)->firstOrfail();
        $meeting = Meeting::findOrfail($id);
        $meeting->view_meetings = [
            'href' => 'api/v1/meeting',
            'method' => 'GET'
        ];

        $meeting_users = DB::table('users')
            ->select('users.*', 'meeting_user.id as mu_id', 'meeting_user.meeting_id', 'meeting_user.user_id')
            ->leftJoin('meeting_user', 'users.id', '=', 'meeting_user.user_id')
            ->where('meeting_user.meeting_id', $id)
            ->get();
        
        if ($meeting_users->count() > 0) {
            foreach ($meeting_users as $user) {
                $users[] = [
                    'id' => $user->id,
                    'user' => $user->name,
                    'email' => $user->email,
                    'email_verified_at'=> null,
                    'pivot'=> [
                        'meeting_id'=> $user->meeting_id,
                        'user_id'=> $user->user_id
                    ],
                    'delete_from_meeting' => [
                        'href' => 'api/v1/meeting/registration/'. $user->meeting_id .'/meeting-user/'. $user->mu_id,
                        'method' => 'POST'
                    ]                    
                ];
            }
        } else {
            $users = null;
        }
        
        $meeting['users'] = $users;

        $response = [
            'data' => $meeting,
            'msg' => 'meeting information'
        ];

        return response()->json($response, 200);
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
        $this->validate($request, [
            'title' => 'required',
            'description' => 'required',
            'time' => 'required',
            'user_id' => 'required',
        ]);        

        $title = $request->input('title');
        $description = $request->input('description');
        $time = $request->input('time');
        $user_id = $request->input('user_id');

        $meeting = Meeting::with('users')->findOrFail($id);

        if (! $meeting->users()->where('users.id', $user_id)->first()) {
            return response()->json([
                'msg' => 'user not registered for meeting. update not successfull'
            ], 401);
        }

        $meeting->title = $title;
        $meeting->description = $description;
        $meeting->time = $time;
        
        if (! $meeting->update()) {
            return response()->json([
                'msg' => 'error during update',
            ], 404);
        }

        $meeting->view_meetings = [
            'href' => 'api/v1/meeting/'. $meeting->id,
            'method' => 'GET'
        ];

        $response = [
            'data' => $meeting,
            'msg' => 'meeting updated'
        ];

        return response()->json($response, 200); 
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $meeting = Meeting::findOrfail($id);
        $users = $meeting->users;
        $meeting->users()->detach();

        if (! $meeting->delete()) {
            foreach ($users as $user) {
                $meeting->users()->attach($user);
            }

            return response()->json([
                'msg' => 'deletion failed',
            ], 404);
        }

        $response = [
            'msg' => 'meeting deleted',
            'create' => [
                'href' => 'api/v1/meeting',
                'method' => 'POST',
                'params' => 'title, description, time'
            ]
        ];

        return response()->json($response, 200);
    }
}
