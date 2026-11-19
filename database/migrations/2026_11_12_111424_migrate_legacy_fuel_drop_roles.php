<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');
        $rolesTable = $tableNames['roles'];
        $modelRolesTable = $tableNames['model_has_roles'];
        $rolePermissionsTable = $tableNames['role_has_permissions'];
        $rolePivot = $columnNames['role_pivot_key'] ?? 'role_id';
        $teamForeignKey = $columnNames['team_foreign_key'] ?? 'team_id';

        if (! Schema::hasTable($rolesTable)) {
            return;
        }

        $hasTeams = Schema::hasColumn($rolesTable, $teamForeignKey);
        $validRoleNames = ['admin', 'provider', 'driver', 'customer', 'administrator', 'super_admin', 'provider_admin'];
        $unexpectedRoles = DB::table($rolesTable)
            ->where('guard_name', 'web')
            ->whereNotIn('name', $validRoleNames)
            ->pluck('name')
            ->unique()
            ->values()
            ->all();

        if ($unexpectedRoles !== []) {
            throw new RuntimeException(
                'Cannot migrate FuelDrop roles while unsupported web roles exist: '.implode(', ', $unexpectedRoles),
            );
        }

        DB::transaction(function () use (
            $rolesTable,
            $modelRolesTable,
            $rolePermissionsTable,
            $rolePivot,
            $teamForeignKey,
            $hasTeams,
        ): void {
            foreach ([
                'administrator' => 'admin',
                'super_admin' => 'admin',
                'provider_admin' => 'provider',
            ] as $legacyName => $canonicalName) {
                $legacyRoles = DB::table($rolesTable)
                    ->where('guard_name', 'web')
                    ->where('name', $legacyName)
                    ->get();

                foreach ($legacyRoles as $legacyRole) {
                    $canonicalRoleQuery = DB::table($rolesTable)
                        ->where('guard_name', $legacyRole->guard_name)
                        ->where('name', $canonicalName);

                    if ($hasTeams) {
                        $legacyRole->{$teamForeignKey} === null
                            ? $canonicalRoleQuery->whereNull($teamForeignKey)
                            : $canonicalRoleQuery->where($teamForeignKey, $legacyRole->{$teamForeignKey});
                    }

                    $canonicalRole = $canonicalRoleQuery->first();

                    if ($canonicalRole === null) {
                        DB::table($rolesTable)
                            ->where('id', $legacyRole->id)
                            ->update(['name' => $canonicalName]);

                        continue;
                    }

                    $permissions = DB::table($rolePermissionsTable)
                        ->where($rolePivot, $legacyRole->id)
                        ->get();

                    foreach ($permissions as $permission) {
                        $assignment = (array) $permission;
                        $assignment[$rolePivot] = $canonicalRole->id;
                        DB::table($rolePermissionsTable)->insertOrIgnore($assignment);
                    }

                    $assignments = DB::table($modelRolesTable)
                        ->where($rolePivot, $legacyRole->id)
                        ->get();

                    foreach ($assignments as $assignment) {
                        $modelRole = (array) $assignment;
                        $modelRole[$rolePivot] = $canonicalRole->id;
                        DB::table($modelRolesTable)->insertOrIgnore($modelRole);
                    }

                    DB::table($rolePermissionsTable)->where($rolePivot, $legacyRole->id)->delete();
                    DB::table($modelRolesTable)->where($rolePivot, $legacyRole->id)->delete();
                    DB::table($rolesTable)->where('id', $legacyRole->id)->delete();
                }
            }
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration is forward-only because merged role assignments cannot be safely split again.
    }
};
