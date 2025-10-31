<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\Server;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;


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
        $requests=\App\Models\Request::whereIn('server_id', function ($query) use ($subdomain) {
    $query->select('id')
          ->from('servers')
          ->where('subdomain', $subdomain);
})->get();
        // Log::info($requests);
        return response()->json([
            'requests' => $requests,
        ]);
    }
    public function createRequest(Request $request,$subdomain){
        Log::info($request);
        $response = json_encode($request->response);
        $server_id=DB::select("SELECT id FROM servers WHERE subdomain = ?", [$subdomain]);
        Log::info($response);
        DB::insert('insert into requests (name,server_id,url,type,response) values (?, ?,?,?,?)', [$request->name,$server_id[0]->id,$request->url,$request->type,$response]);
        return response()->json([
            'message' => 'Request created successfully.'
        ]);
    }

    public function deleteServer(Request $request, $id){
        $server = Server::find($id);
        if (!$server || $server->user_id !== auth()->id()) {
            return response()->json(['message' => 'Server not found or unauthorized.'], 404);
        }
        $server->delete();
        return response()->json(['message' => 'Server deleted successfully.']);
    }
}