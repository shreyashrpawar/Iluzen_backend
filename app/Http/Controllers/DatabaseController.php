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
    try {
        $user = auth()->user();

        $validated = $request->validate([
            'name' => 'required|string|regex:/^[A-Za-z0-9_]+$/',
            'columns' => 'required|array|min:1',
            'columns.*.name' => 'required|string|regex:/^[A-Za-z0-9_]+$/',
            'columns.*.type' => 'required|string',
            'columns.*.length' => 'nullable|integer|min:1',
            'columns.*.nullable' => 'nullable|boolean',
            'columns.*.default' => 'nullable',
        ]);

        $tableName = $validated['name'];
        $columns = $validated['columns'];

        DB::statement('USE `' . str_replace('`', '``', $database) . '`');

        $queryParts = [];
        $queryParts[] = "`id` INT AUTO_INCREMENT PRIMARY KEY";

        foreach ($columns as $col) {
            $name = "`" . str_replace('`', '``', $col['name']) . "`";
            $type = strtoupper($col['type']);
            $length = isset($col['length']) ? "({$col['length']})" : "";

            $definition = "{$name} {$type}{$length}";

            if (isset($col['nullable']) && $col['nullable'] === false) {
                $definition .= " NOT NULL";
            }

            if (isset($col['default']) && $col['default'] !== null && $col['default'] !== '') {
                $safeDefault = addslashes($col['default']);
                $definition .= " DEFAULT '{$safeDefault}'";
            }

            $queryParts[] = $definition;
        }

$createTableQuery = "CREATE TABLE `{$database}`.`{$tableName}` (" 
    . implode(', ', $queryParts) 
    . ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";


        Log::info("Executing query: " . $createTableQuery);

        DB::statement($createTableQuery);

        return response()->json(['message' => 'Table created successfully.'], 200);

    } catch (\Illuminate\Validation\ValidationException $e) {
        // Input validation errors
        return response()->json(['error' => $e->errors()], 422);

    } catch (\Illuminate\Database\QueryException $e) {
        // SQL or DB errors
        Log::error('Create table failed: ' . $e->getMessage());
        return response()->json(['error' => 'Database error: ' . $e->getMessage()], 500);

    } catch (\Exception $e) {
        // Other exceptions
        Log::error('Unexpected error: ' . $e->getMessage());
        return response()->json(['error' => 'Unexpected server error.'], 500);
    }
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