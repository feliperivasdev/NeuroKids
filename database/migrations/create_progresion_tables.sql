-- Migración SQL para el sistema de progresión automática estilo Duolingo
-- Ejecutar estas consultas en orden en tu base de datos PostgreSQL
-- 1. Agregar campo nivel_actual a la tabla usuarios
ALTER TABLE usuarios
ADD COLUMN IF NOT EXISTS nivel_actual INTEGER DEFAULT 1;
-- 2. Crear tabla para progresión de tests (prerrequisitos)
CREATE TABLE IF NOT EXISTS test_progresion (
    id SERIAL PRIMARY KEY,
    test_id INTEGER NOT NULL,
    test_prerequisito_id INTEGER,
    orden INTEGER DEFAULT 1,
    nivel_minimo_requerido INTEGER DEFAULT 1,
    activo BOOLEAN DEFAULT true,
    FOREIGN KEY (test_id) REFERENCES pruebas_lectura(id) ON DELETE CASCADE,
    FOREIGN KEY (test_prerequisito_id) REFERENCES pruebas_lectura(id) ON DELETE CASCADE,
    UNIQUE(test_id, test_prerequisito_id)
);
-- 3. Crear tabla para resultados de tests
CREATE TABLE IF NOT EXISTS resultados_test (
    id SERIAL PRIMARY KEY,
    usuario_id INTEGER NOT NULL,
    test_id INTEGER NOT NULL,
    puntuacion DECIMAL(5, 2) NOT NULL,
    puntuacion_maxima DECIMAL(5, 2) NOT NULL,
    porcentaje DECIMAL(5, 2) NOT NULL,
    completado BOOLEAN DEFAULT true,
    fecha_inicio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_completado TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    tiempo_total_segundos INTEGER DEFAULT 0,
    intentos INTEGER DEFAULT 1,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (test_id) REFERENCES pruebas_lectura(id) ON DELETE CASCADE,
    UNIQUE(usuario_id, test_id)
);
-- 4. Crear tabla para condiciones de insignias automáticas
CREATE TABLE IF NOT EXISTS condiciones_insignia (
    id SERIAL PRIMARY KEY,
    insignia_id INTEGER NOT NULL,
    tipo_condicion VARCHAR(50) NOT NULL CHECK (
        tipo_condicion IN (
            'tests_completados',
            'puntuacion_minima',
            'juegos_completados',
            'lecturas_completadas',
            'racha_dias',
            'nivel_alcanzado',
            'tiempo_total'
        )
    ),
    valor_requerido INTEGER NOT NULL,
    descripcion TEXT,
    activo BOOLEAN DEFAULT true,
    FOREIGN KEY (insignia_id) REFERENCES insignias(id) ON DELETE CASCADE
);
-- 5. Crear tabla para tests desbloqueados por usuario
CREATE TABLE IF NOT EXISTS tests_desbloqueados (
    id SERIAL PRIMARY KEY,
    usuario_id INTEGER NOT NULL,
    test_id INTEGER NOT NULL,
    desbloqueado_por INTEGER,
    fecha_desbloqueo TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    activo BOOLEAN DEFAULT true,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (test_id) REFERENCES pruebas_lectura(id) ON DELETE CASCADE,
    FOREIGN KEY (desbloqueado_por) REFERENCES pruebas_lectura(id) ON DELETE
    SET NULL,
        UNIQUE(usuario_id, test_id)
);
-- 6. Crear índices para mejorar rendimiento
CREATE INDEX IF NOT EXISTS idx_test_progresion_test_id ON test_progresion(test_id);
CREATE INDEX IF NOT EXISTS idx_test_progresion_prerequisito_id ON test_progresion(test_prerequisito_id);
CREATE INDEX IF NOT EXISTS idx_resultados_test_usuario_id ON resultados_test(usuario_id);
CREATE INDEX IF NOT EXISTS idx_resultados_test_test_id ON resultados_test(test_id);
CREATE INDEX IF NOT EXISTS idx_resultados_test_completado ON resultados_test(completado);
CREATE INDEX IF NOT EXISTS idx_condiciones_insignia_insignia_id ON condiciones_insignia(insignia_id);
CREATE INDEX IF NOT EXISTS idx_condiciones_insignia_tipo ON condiciones_insignia(tipo_condicion);
CREATE INDEX IF NOT EXISTS idx_tests_desbloqueados_usuario_id ON tests_desbloqueados(usuario_id);
CREATE INDEX IF NOT EXISTS idx_tests_desbloqueados_test_id ON tests_desbloqueados(test_id);
CREATE INDEX IF NOT EXISTS idx_usuarios_nivel_actual ON usuarios(nivel_actual);
-- 7. Insertar datos de ejemplo para configuración inicial
-- Ejemplo de progresión de tests (Test 2 requiere completar Test 1)
INSERT INTO test_progresion (
        test_id,
        test_prerequisito_id,
        orden,
        nivel_minimo_requerido,
        activo
    )
VALUES (2, 1, 2, 1, true),
    (3, 2, 3, 2, true),
    (4, 3, 4, 2, true),
    (5, 4, 5, 3, true) ON CONFLICT (test_id, test_prerequisito_id) DO NOTHING;
-- Ejemplo de condiciones para insignias automáticas
-- Insignia "Principiante" (ID 1) - Completar 3 tests con 70% mínimo
INSERT INTO condiciones_insignia (
        insignia_id,
        tipo_condicion,
        valor_requerido,
        descripcion,
        activo
    )
VALUES (
        1,
        'tests_completados',
        3,
        'Completar 3 tests exitosamente',
        true
    ),
    (
        1,
        'puntuacion_minima',
        70,
        'Obtener al menos 70% en un test',
        true
    ) ON CONFLICT DO NOTHING;
-- Insignia "Intermedio" (ID 2) - Completar 10 tests con 80% mínimo y 5 juegos
INSERT INTO condiciones_insignia (
        insignia_id,
        tipo_condicion,
        valor_requerido,
        descripcion,
        activo
    )
VALUES (
        2,
        'tests_completados',
        10,
        'Completar 10 tests exitosamente',
        true
    ),
    (
        2,
        'puntuacion_minima',
        80,
        'Obtener al menos 80% en un test',
        true
    ),
    (
        2,
        'juegos_completados',
        5,
        'Completar 5 juegos',
        true
    ) ON CONFLICT DO NOTHING;
-- Insignia "Avanzado" (ID 3) - Completar 25 tests con 90% mínimo y nivel 5
INSERT INTO condiciones_insignia (
        insignia_id,
        tipo_condicion,
        valor_requerido,
        descripcion,
        activo
    )
VALUES (
        3,
        'tests_completados',
        25,
        'Completar 25 tests exitosamente',
        true
    ),
    (
        3,
        'puntuacion_minima',
        90,
        'Obtener al menos 90% en un test',
        true
    ),
    (
        3,
        'nivel_alcanzado',
        5,
        'Alcanzar nivel 5',
        true
    ) ON CONFLICT DO NOTHING;
-- 8. Comentarios para documentación
COMMENT ON TABLE test_progresion IS 'Define la progresión y prerrequisitos entre tests';
COMMENT ON TABLE resultados_test IS 'Almacena los resultados de tests completados por usuarios';
COMMENT ON TABLE condiciones_insignia IS 'Define las condiciones automáticas para otorgar insignias';
COMMENT ON TABLE tests_desbloqueados IS 'Rastrea qué tests ha desbloqueado cada usuario';
COMMENT ON COLUMN usuarios.nivel_actual IS 'Nivel actual del usuario en el sistema de progresión';
COMMENT ON COLUMN test_progresion.orden IS 'Orden sugerido de los tests en la progresión';
COMMENT ON COLUMN test_progresion.nivel_minimo_requerido IS 'Nivel mínimo del usuario para acceder al test';
COMMENT ON COLUMN resultados_test.porcentaje IS 'Porcentaje de aciertos en el test (0-100)';
COMMENT ON COLUMN resultados_test.intentos IS 'Número de veces que el usuario ha intentado este test';
COMMENT ON COLUMN condiciones_insignia.tipo_condicion IS 'Tipo de condición: tests_completados, puntuacion_minima, etc.';
COMMENT ON COLUMN condiciones_insignia.valor_requerido IS 'Valor numérico requerido para cumplir la condición';


