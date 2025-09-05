<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\Server;
use Illuminate\Support\Facades\Log;


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
    public function deleteServer(Request $request, $id){
        $server = Server::find($id);
        if (!$server || $server->user_id !== auth()->id()) {
            return response()->json(['message' => 'Server not found or unauthorized.'], 404);
        }
        $server->delete();
        return response()->json(['message' => 'Server deleted successfully.']);
    }
}