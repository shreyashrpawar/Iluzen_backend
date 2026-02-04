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
    $newRequest->response_type = $request->response_type ?? 'manual';
    $newRequest->database_name = $request->database_name;
    $newRequest->table_name = $request->table_name;
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

    /**
     * Handle dynamic subdomain requests
     * Routes: {subdomain}.ilusion.one/{path} or /api/{subdomain}/{path}
     * Finds server by subdomain, then request by path, returns response
     */
    public function handleSubdomainRequest(Request $request, $subdomain = null, $path = null){
        // For localhost development: /api/{subdomain}/{path}
        if ($subdomain === null) {
            $segments = $request->segments();
            if (!empty($segments) && $segments[0] === 'api' && !empty($segments[1])) {
                $subdomain = $segments[1];
                // Reconstruct path from remaining segments
                $pathSegments = array_slice($segments, 2);
                $path = !empty($pathSegments) ? '/' . implode('/', $pathSegments) : '/';
            }
        }

        if (!$subdomain) {
            return response()->json([
                'message' => 'Subdomain not found in request.',
            ], 400);
        }

        // Find server by subdomain
        $server = Server::where('subdomain', $subdomain)->first();
        
        if (!$server) {
            return response()->json([
                'message' => "Server '{$subdomain}' not found.",
            ], 404);
        }

        // Get the actual path for the mock request lookup
        if ($path === null) {
            $path = $request->path();
            // Remove subdomain prefix if present
            if (strpos($path, $subdomain) === 0) {
                $path = substr($path, strlen($subdomain));
            }
        }
        
        $path = '/' . ltrim($path, '/');

        // Find matching request by server_id and url path
        $mockRequest = RequestModel::where([
            ['server_id', $server->id],
            ['url', $path]
        ])->first();

        if (!$mockRequest) {
            return response()->json([
                'message' => "Endpoint '{$path}' not found on server '{$subdomain}'.",
                'available' => RequestModel::where('server_id', $server->id)
                    ->pluck('url')
                    ->toArray(),
            ], 404);
        }

        // Decode and return the response
        if ($mockRequest->response_type === 'database') {
            // Database response: query the specified database and table
            if (!$mockRequest->database_name || !$mockRequest->table_name) {
                return response()->json([
                    'message' => 'Database or table name not configured for this endpoint.',
                ], 500);
            }

            try {
                $database = $mockRequest->database_name;
                $table = $mockRequest->table_name;

                // Use fully qualified table name instead of USE statement
                // This prevents breaking Laravel's session management
                $data = DB::select("SELECT * FROM `$database`.`$table`");

                return response()->json($data);
            } catch (\Exception $e) {
                \Log::error("Database query failed: " . $e->getMessage());
                return response()->json([
                    'message' => 'Failed to query database: ' . $e->getMessage(),
                ], 500);
            }
        } else {
            // Manual JSON response (default behavior)
            $responseData = is_string($mockRequest->response) 
                ? json_decode($mockRequest->response, true) 
                : $mockRequest->response;

            return response()->json($responseData);
        }
    }
}