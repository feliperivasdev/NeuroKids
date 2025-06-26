<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UsuarioController extends Controller
{
    public function index()
    {
        return response()->json([
            'success' => true,
            'message' => 'Usuarios obtenidos correctamente',
            'data' => [
                'usuarios' => Usuario::all()
            ]
        ]);
    }

    public function show($id)
    {
        $usuario = Usuario::find($id);

        if(!$usuario) {
            return response()->json(['success' => false, 'message' => 'Usuario no encontrado'], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Usuario obtenido correctamente',
            'data' => [
                'usuario' => $usuario
            ]
        ], 200);
    }

    public function store(Request $request)
    {
        $this->validate($request,[
            'nombre' => 'required|string|max:255',
            'correo' => 'required|email|unique:usuarios,correo',
            'contrasena' => 'required|string|min:6',
            'rol_id' => 'required|exists:roles,id',
            'institucion_id' => 'required|exists:instituciones,id',
        ]);

        $usuario = new Usuario();
        $usuario->nombre = $request->nombre;
        $usuario->correo = $request->correo;
        $usuario->contrasena = Hash::make($request->contrasena);
        $usuario->rol_id = $request->rol_id;
        $usuario->institucion_id = $request->institucion_id;
        $usuario->estado = true;
        $usuario->fecha_creacion = now();
        $usuario->save();

        return response()->json([
            'success' => true,
            'message' => 'Usuario creado correctamente',
            'data' => [
                'usuario' => $usuario
            ]
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $usuario = Usuario::find($id);

        if(!$usuario){
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado'], 404);
        }

        $usuario->fill($request->only(['nombre', 'correo', 'rol_id', 'institucion_id']));
        if($request->has('contrasena')){
            $usuario->contrasena = Hash::make($request->contrasena);
        }
        $usuario->save();

        return response()->json([
            'success' => true,
            'message' => 'Usuario actualizado correctamente',
            'data' => [
                'usuario' => $usuario
            ]
        ], 200);
    }

    public function disabled($id){
        $usuario = Usuario::find($id);
        if(!$usuario){
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado'], 404);
        }

        $usuario->estado = false;
        $usuario->save();

        return response()->json([
            'success' => true,
            'message' => 'Usuario deshabilitado correctamente',
            'data' => [
                'usuario' => $usuario
            ]
        ], 200);
    }
}