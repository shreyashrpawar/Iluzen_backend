<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Server;
use App\Models\Request as RequestModel;


class ServerController extends Controller{
    public function createServer(Request $request){
        $request->validate([
            'name' => 'required|string|max:255',
            'subdomain' => 'required|string|max:255|unique:servers,subdomain',
        ]);
        $user = auth()->user();
        $server = new Server();
        $server->name = $request->name;
        $server->user_id = $user->id;
        $server->subdomain = $request->subdomain;
        $server->save();
        return response()->json([
            'message' => 'Server created successfully.',
            'server' => $server,
        ]);
    }
    public function getServers(Request $request){
        $user = auth()->user();
        $servers = Server::where('user_id', $user->id)->get();
        return response()->json([
            'servers' => $servers,
        ]);
    }
    public function getRequests(Request $request,$subdomain){
        // $user = auth()->user();
        if (Server::where('subdomain', $subdomain)->exists()) {
            $requests=\App\Models\Request::whereIn('server_id', function ($query) use ($subdomain) {
    $query->select('id')
          ->from('servers')
          ->where('subdomain', $subdomain);
})->get();
        // Log::info($requests);
        return response()->json([
            'requests' => $requests,
        ]);

} else {
    return response()->json([
        'message' => 'Server not found.',
    ], 404);
}

    }
    public function createRequest(Request $request,$subdomain){
    $server = Server::where('subdomain', $subdomain)->firstOrFail();

    $newRequest = new RequestModel();
    $newRequest->name = $request->name;
    $newRequest->server_id = $server->id;
    $newRequest->url = $request->url;
    $newRequest->type = $request->type;
    $newRequest->response = $request->response;
    $newRequest->save();

    return response()->json([
        'message' => 'Request created successfully.'
    ]);    }

        public function deleteRequests(Request $request,$subdomain){
                // $user = auth()->user();
                $server_id=Server::where('subdomain', $subdomain)->first()->id;
                $delete=\App\Models\Request::where([
    ['server_id', $server_id],
    ['id', $request->id]
])->delete();
                return response()->json([
                    'message' => 'Requests deleted successfully.',
                ]);
    }


    public function deleteServer(Request $request){
        $server = Server::find($request->id);
        $requests = RequestModel::where('server_id', $server->id)->delete();
        if (!$server || $server->user_id !== auth()->id()) {
            return response()->json(['message' => 'Server not found or unauthorized.'], 404);
        }
        $server->delete();
        return response()->json(['message' => 'Server deleted successfully.']);
    }
}