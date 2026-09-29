<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function guardName(): string
    {
        return 'web';
    }

    public function roleNames(): array
    {
        return [
            'Administrador',
            'Agente Inmobiliario',
            'Asistente',
            'Director General',
            'Cliente',
        ];
    }

    public function permissionNames(): array
    {
        return [
            'usuarios.ver',
            'usuarios.crear',
            'usuarios.actualizar',
            'usuarios.eliminar',
            'usuarios.roles_asignar',
            'roles.ver',
            'roles.crear',
            'roles.actualizar',
            'roles.eliminar',
            'roles.permisos_asignar',
            'permisos.ver',
            'clientes.ver',
            'clientes.crear',
            'clientes.actualizar',
            'clientes.eliminar',
            'agentes.ver',
            'agentes.crear',
            'agentes.actualizar',
            'agentes.eliminar',
            'propietarios.ver',
            'propietarios.crear',
            'propietarios.actualizar',
            'propietarios.eliminar',
            'categorias.ver',
            'categorias.crear',
            'categorias.actualizar',
            'categorias.eliminar',
            'inmuebles.ver',
            'inmuebles.crear',
            'inmuebles.actualizar',
            'inmuebles.eliminar',
            'imagenes_inmuebles.ver',
            'imagenes_inmuebles.crear',
            'imagenes_inmuebles.actualizar',
            'imagenes_inmuebles.eliminar',
            'intereses.ver',
            'intereses.crear',
            'intereses.actualizar',
            'intereses.eliminar',
            'asignaciones_agente_inmueble.ver',
            'asignaciones_agente_inmueble.crear',
            'asignaciones_agente_inmueble.actualizar',
            'asignaciones_agente_inmueble.eliminar',
            'asignaciones_cliente_agente.ver',
            'asignaciones_cliente_agente.crear',
            'asignaciones_cliente_agente.actualizar',
            'asignaciones_cliente_agente.eliminar',
            'interacciones.ver',
            'interacciones.crear',
            'interacciones.actualizar',
            'interacciones.eliminar',
            'solicitudes.ver',
            'solicitudes.crear',
            'solicitudes.actualizar',
            'solicitudes.eliminar',
            'oportunidades.ver',
            'oportunidades.crear',
            'oportunidades.actualizar',
            'oportunidades.eliminar',
            'citas.ver',
            'citas.crear',
            'citas.actualizar',
            'citas.eliminar',
            'operaciones.ver',
            'operaciones.crear',
            'operaciones.actualizar',
            'operaciones.eliminar',
            'categorias_documentos.ver',
            'categorias_documentos.crear',
            'categorias_documentos.actualizar',
            'categorias_documentos.eliminar',
            'documentos.ver',
            'documentos.crear',
            'documentos.actualizar',
            'documentos.eliminar',
            'visualizaciones.ver',
            'historial_correos.ver',
            'respaldos.ver',
            'respaldos.crear',
            'respaldos.restaurar',
            'configuracion_respaldos.ver',
            'configuracion_respaldos.actualizar',
            'bitacora.ver',
            'bitacora.exportar',
            'dashboard.ver',
            'reportes.ver',
            'reportes.exportar',
        ];
    }

    public function rolePermissions(): array
    {
        $allPermissions = $this->permissionNames();

        return [
            'Administrador' => $allPermissions,
            'Agente Inmobiliario' => [
                'clientes.ver',
                'clientes.crear',
                'clientes.actualizar',
                'agentes.ver',
                'propietarios.ver',
                'categorias.ver',
                'inmuebles.ver',
                'inmuebles.actualizar',
                'imagenes_inmuebles.ver',
                'imagenes_inmuebles.crear',
                'imagenes_inmuebles.actualizar',
                'imagenes_inmuebles.eliminar',
                'intereses.ver',
                'intereses.crear',
                'intereses.actualizar',
                'intereses.eliminar',
                'asignaciones_agente_inmueble.ver',
                'asignaciones_cliente_agente.ver',
                'interacciones.ver',
                'interacciones.crear',
                'interacciones.actualizar',
                'solicitudes.ver',
                'solicitudes.actualizar',
                'oportunidades.ver',
                'oportunidades.crear',
                'oportunidades.actualizar',
                'citas.ver',
                'citas.crear',
                'citas.actualizar',
                'operaciones.ver',
                'categorias_documentos.ver',
                'documentos.ver',
                'documentos.crear',
                'documentos.actualizar',
                'historial_correos.ver',
                'dashboard.ver',
            ],
            'Asistente' => [
                'clientes.ver',
                'clientes.crear',
                'clientes.actualizar',
                'agentes.ver',
                'propietarios.ver',
                'propietarios.crear',
                'propietarios.actualizar',
                'categorias.ver',
                'inmuebles.ver',
                'inmuebles.crear',
                'inmuebles.actualizar',
                'imagenes_inmuebles.ver',
                'imagenes_inmuebles.crear',
                'imagenes_inmuebles.actualizar',
                'intereses.ver',
                'intereses.crear',
                'intereses.actualizar',
                'asignaciones_agente_inmueble.ver',
                'asignaciones_agente_inmueble.crear',
                'asignaciones_agente_inmueble.actualizar',
                'asignaciones_cliente_agente.ver',
                'asignaciones_cliente_agente.crear',
                'asignaciones_cliente_agente.actualizar',
                'interacciones.ver',
                'interacciones.crear',
                'interacciones.actualizar',
                'solicitudes.ver',
                'solicitudes.crear',
                'solicitudes.actualizar',
                'solicitudes.eliminar',
                'oportunidades.ver',
                'oportunidades.crear',
                'oportunidades.actualizar',
                'citas.ver',
                'citas.crear',
                'citas.actualizar',
                'citas.eliminar',
                'operaciones.ver',
                'operaciones.crear',
                'operaciones.actualizar',
            ],
            'Director General' => [
                'clientes.ver',
                'agentes.ver',
                'propietarios.ver',
                'categorias.ver',
                'inmuebles.ver',
                'imagenes_inmuebles.ver',
                'intereses.ver',
                'asignaciones_agente_inmueble.ver',
                'asignaciones_cliente_agente.ver',
                'interacciones.ver',
                'solicitudes.ver',
                'oportunidades.ver',
                'citas.ver',
                'operaciones.ver',
                'dashboard.ver',
                'reportes.ver',
                'reportes.exportar',
            ],
            'Cliente' => [
                'clientes.ver',
                'clientes.actualizar',
                'categorias.ver',
                'inmuebles.ver',
                'imagenes_inmuebles.ver',
                'intereses.ver',
                'intereses.crear',
                'intereses.actualizar',
                'intereses.eliminar',
                'asignaciones_cliente_agente.ver',
                'solicitudes.ver',
                'solicitudes.crear',
                'citas.ver',
            ],
        ];
    }

    public function run(): void
    {
        $permissionRegistrar = app(PermissionRegistrar::class);
        $permissionRegistrar->forgetCachedPermissions();

        foreach ($this->permissionNames() as $permissionName) {
            Permission::findOrCreate($permissionName, $this->guardName());
        }

        foreach ($this->rolePermissions() as $roleName => $permissions) {
            $role = Role::findOrCreate($roleName, $this->guardName());
            $role->syncPermissions($permissions);
        }

        $permissionRegistrar->forgetCachedPermissions();
    }
}
