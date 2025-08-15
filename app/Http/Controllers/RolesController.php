<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;

class RolesController extends Controller
{
    /**
     * Obtener todos los roles
     */
    public function index()
    {
        return response()->json([
            'success' => true,
            'message' => 'Roles obtenidos correctamente',
            'data' => Role::all()
        ]);
    }

    /**
     * Obtener un rol específico
     */
    public function show($id)
    {
        $role = Role::find($id);
        if (!$role) {
            return response()->json([
                'success' => false,
                'message' => 'Rol no encontrado'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Rol obtenido correctamente',
            'data' => $role
        ]);
    }

    /**
     * Crear un nuevo rol
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'nombre' => 'required|string|max:255|unique:roles,nombre',
        ]);

        $role = new Role();
        $role->nombre = $request->nombre;
        $role->save();

        return response()->json([
            'success' => true,
            'message' => 'Rol creado correctamente',
            'data' => $role
        ], 201);
    }

    /**
     * Actualizar un rol existente
     */
    public function update(Request $request, $id)
    {
        $role = Role::find($id);
        if (!$role) {
            return response()->json([
                'success' => false,
                'message' => 'Rol no encontrado'
            ], 404);
        }

        $this->validate($request, [
            'nombre' => 'required|string|max:255|unique:roles,nombre,' . $id,
        ]);

        $role->nombre = $request->nombre;
        $role->save();

        return response()->json([
            'success' => true,
            'message' => 'Rol actualizado correctamente',
            'data' => $role
        ]);
    }

    /**
     * Eliminar un rol
     */
    public function destroy($id)
    {
        $role = Role::find($id);
        if (!$role) {
            return response()->json([
                'success' => false,
                'message' => 'Rol no encontrado'
            ], 404);
        }

        // Verificar si hay usuarios con este rol antes de eliminar
        $usuariosConRol = \App\Models\Usuario::where('rol_id', $id)->count();
        if ($usuariosConRol > 0) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar el rol porque hay usuarios asignados a él'
            ], 400);
        }

        $role->delete();

        return response()->json([
            'success' => true,
            'message' => 'Rol eliminado correctamente'
        ]);
    }
}