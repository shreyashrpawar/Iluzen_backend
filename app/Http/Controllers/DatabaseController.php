<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Server;
use App\Models\Request as RequestModel;
use App\Models\UserDatabase;

class DatabaseController extends Controller{
    public function createDatabase(Request $request){
        $user = auth()->user();
        DB::statement('CREATE DATABASE ' . $request->database_name);
        DB::statement("CREATE USER IF NOT EXISTS'" . $user->name . "'@'%' IDENTIFIED BY '" . $request->password . "'");
        $authorize=DB::statement("GRANT ALL PRIVILEGES ON " . $request->database_name . ".* TO '" . $user->name . "'@'%'");
        UserDatabase::create([
            'user_id' => auth()->id(),
            'database_name' => $request->database_name,
        ]);

        return response()->json([
            'message' => 'Database created successfully.',
        ]);
    }
    public function getDatabases(Request $request){
        $user = auth()->user();
        $databases=DB::select("SELECT DISTINCT DB as DATABASE_NAME FROM mysql.db WHERE USER='" . $user->name."'");
        // $servers = Server::where('user_id', $user->id)->get();
        return response()->json([
            'databases' => $databases,
        ]);
    }
public function getTable(Request $request, $database)
{
    $user = auth()->user();
    $database = trim(strtolower($database));

    // Check if user has access to the database
    $hasAccess = UserDatabase::where('user_id', $user->id)
        ->where('database_name', $database)
        ->exists();

    if ($hasAccess) {
        // Switch to that DB
        DB::statement("USE `$database`");

        // Fetch all tables
        $tables = DB::select("SHOW TABLES FROM `$database`");

        // Clean up output
        $tableList = collect($tables)->map(function ($table) {
            return array_values((array) $table)[0];
        });

        return response()->json([
            'tables' => $tableList,
        ]);
    }

    return response()->json([
        'message' => 'Database not found or access denied.',
    ], 404);
}


public function createTable(Request $request, $database)
{
    $user = auth()->user();
    DB::statement('USE ' . $database);
    $tableName = $request->name;
    if (!$tableName) {
        return response()->json(['error' => 'Table name is required'], 400);
    }
    $createTableQuery = "CREATE TABLE `" . $tableName . "` (id INT AUTO_INCREMENT PRIMARY KEY";
    foreach ($request->columns as $column) {
        $name = $column['name'];
        $type = strtoupper(trim($column['type']));
        $length = isset($column['length']) && $column['length'] ? "(" . intval($column['length']) . ")" : "";
        if (!$name || !$type) continue;

        $createTableQuery .= ", `" . $name . "` " . $type . $length;
        if (!empty($column['nullable']) && $column['nullable'] === false) {
            $createTableQuery .= " NOT NULL";
        }
        if (!empty($column['default'])) {
            $createTableQuery .= " DEFAULT '" . addslashes($column['default']) . "'";
        }
    }

    $createTableQuery .= ")";
    Log::info("Executing query: " . $createTableQuery);

    DB::statement($createTableQuery);

    return response()->json(['message' => 'Table created successfully.']);
}

//         public function deleteRequests(Request $request,$subdomain){
//                 // $user = auth()->user();
//                 $server_id=Server::where('subdomain', $subdomain)->first()->id;
//                 $delete=\App\Models\Request::where([
//     ['server_id', $server_id],
//     ['id', $request->id]
// ])->delete();
//                 return response()->json([
//                     'message' => 'Requests deleted successfully.',
//                 ]);
//     }


//     public function deleteServer(Request $request){
//         $server = Server::find($request->id);
//         $requests = RequestModel::where('server_id', $server->id)->delete();
//         if (!$server || $server->user_id !== auth()->id()) {
//             return response()->json(['message' => 'Server not found or unauthorized.'], 404);
//         }
//         $server->delete();
//         return response()->json(['message' => 'Server deleted successfully.']);
//     }
}