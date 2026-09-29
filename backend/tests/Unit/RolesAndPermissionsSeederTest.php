<?php

namespace Tests\Unit;

use Database\Seeders\RolesAndPermissionsSeeder;
use PHPUnit\Framework\TestCase;

class RolesAndPermissionsSeederTest extends TestCase
{
    private RolesAndPermissionsSeeder $seeder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seeder = new RolesAndPermissionsSeeder;
    }

    public function test_role_catalog_contains_only_the_five_authenticated_roles(): void
    {
        self::assertSame([
            'Administrador',
            'Agente Inmobiliario',
            'Asistente',
            'Director General',
            'Cliente',
        ], $this->seeder->roleNames());
        self::assertNotContains('Público General', $this->seeder->roleNames());
        self::assertNotContains('Publico General', $this->seeder->roleNames());
    }

    public function test_permission_catalog_uses_the_web_guard_and_has_expected_size(): void
    {
        self::assertSame('web', $this->seeder->guardName());
        self::assertCount(87, $this->seeder->permissionNames());
        self::assertCount(87, array_unique($this->seeder->permissionNames()));
        self::assertNotContains('permisos.crear', $this->seeder->permissionNames());
        self::assertNotContains('permisos.actualizar', $this->seeder->permissionNames());
        self::assertNotContains('permisos.eliminar', $this->seeder->permissionNames());
    }

    public function test_administrator_receives_every_defined_permission(): void
    {
        $assignments = $this->seeder->rolePermissions();

        self::assertSame($this->seeder->permissionNames(), $assignments['Administrador']);
    }

    public function test_final_role_restrictions_are_present(): void
    {
        $assignments = $this->seeder->rolePermissions();

        self::assertNotContains('agentes.actualizar', $assignments['Agente Inmobiliario']);
        self::assertNotContains('interacciones.ver', $assignments['Cliente']);
        self::assertNotContains('operaciones.ver', $assignments['Cliente']);
        self::assertNotContains('documentos.ver', $assignments['Asistente']);
        self::assertNotContains('categorias_documentos.ver', $assignments['Asistente']);
        self::assertNotContains('roles.ver', $assignments['Director General']);
        self::assertNotContains('permisos.ver', $assignments['Director General']);
    }

    public function test_sensitive_permissions_are_limited_to_the_approved_roles(): void
    {
        $assignments = $this->seeder->rolePermissions();

        self::assertSame(
            ['Administrador', 'Agente Inmobiliario'],
            $this->rolesWithPermission($assignments, 'documentos.ver')
        );
        self::assertSame(
            ['Administrador', 'Agente Inmobiliario'],
            $this->rolesWithPermission($assignments, 'categorias_documentos.ver')
        );
        self::assertSame(
            ['Administrador'],
            $this->rolesWithPermission($assignments, 'visualizaciones.ver')
        );
        self::assertSame(
            ['Administrador'],
            $this->rolesWithPermission($assignments, 'respaldos.ver')
        );
        self::assertSame(
            ['Administrador'],
            $this->rolesWithPermission($assignments, 'bitacora.ver')
        );
    }

    public function test_executive_and_client_access_matches_the_final_matrix(): void
    {
        $assignments = $this->seeder->rolePermissions();

        self::assertContains('dashboard.ver', $assignments['Director General']);
        self::assertContains('reportes.ver', $assignments['Director General']);
        self::assertContains('reportes.exportar', $assignments['Director General']);
        self::assertNotContains('dashboard.ver', $assignments['Asistente']);
        self::assertNotContains('dashboard.ver', $assignments['Cliente']);
        self::assertNotContains('reportes.ver', $assignments['Agente Inmobiliario']);
        self::assertNotContains('reportes.ver', $assignments['Cliente']);
    }

    /**
     * @param  array<string, list<string>>  $assignments
     * @return list<string>
     */
    private function rolesWithPermission(array $assignments, string $permission): array
    {
        return array_keys(array_filter(
            $assignments,
            static fn (array $permissions): bool => in_array($permission, $permissions, true)
        ));
    }
}
