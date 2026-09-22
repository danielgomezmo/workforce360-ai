# Workforce360 AI · Fase 3.2 corregida

Esta versión mantiene las Fases 1, 2 y 3 y corrige el flujo de acceso del **COLABORADOR** para que pueda iniciar sesión y registrar su propia asistencia.

## Corrección v3.2 — PDO HY093

Se corrigió el uso repetido de placeholders nombrados en consultas preparadas con PDO nativo (`ATTR_EMULATE_PREPARES = false`).

Afectaba principalmente a `Marcacion::scheduleForDate()` al abrir **Mi asistencia** y podía afectar posteriormente las búsquedas de colaboradores y marcaciones.

**No requiere migración de base de datos.** Basta con reemplazar los archivos del proyecto por esta versión.


## Corrección principal de la v3.1

En la versión anterior, el CRUD podía registrar un colaborador laboral sin crearle necesariamente una cuenta en `usuarios`. Eso hacía que un trabajador existiera en la base de datos, pero no tuviera credenciales vinculadas para entrar a `Mi asistencia`.

Ahora:

- Cada colaborador puede tener usuario de acceso vinculado desde su mismo formulario.
- Al registrar un colaborador nuevo se solicita `usuario + contraseña`.
- La cuenta recibe automáticamente el rol `COLABORADOR`.
- Al editar un colaborador existente sin cuenta, se puede crear su acceso.
- Si ya tiene cuenta, la contraseña puede dejarse vacía para conservarla.
- El listado de colaboradores muestra si el acceso está Activo, Desactivado o Sin acceso.
- Un COLABORADOR vinculado entra directamente a `Mi asistencia` después del login.
- `Mi asistencia` sigue validando estado ACTIVO, horario vigente, día laborable y cese.

## Si YA tienes instalada la Fase 3

1. Haz una copia de seguridad de la base de datos.
2. Reemplaza la carpeta `C:\xampp\htdocs\Workforce360AI` con la incluida en este ZIP.
3. En phpMyAdmin selecciona la base `workforce360_ai`.
4. Ejecuta **una sola vez**:

`database/migrations/fase3_1_corregir_acceso_colaborador.sql`

5. Al final del script debe aparecer una fila de diagnóstico para el usuario `colaborador`, con `id_colaborador` y horario.
6. Cierra cualquier sesión abierta del sistema y vuelve a iniciar sesión.

## Instalación nueva

1. Copia `Workforce360AI` a `C:\xampp\htdocs\`.
2. Inicia Apache y MySQL.
3. Importa `database/workforce360_v3_1.sql`.
4. Abre `http://localhost/Workforce360AI/`.

## Credenciales de comprobación

### Administrador
- Usuario: `admin`
- Contraseña: `Admin123*`

### Colaborador demo
- Usuario: `colaborador`
- Contraseña: `Colab123*`

Con el colaborador demo, después del login debes entrar directamente a **Mi asistencia** y ver el botón `MARCAR INGRESO` si el estado, horario y día permiten iniciar jornada.

## Crear un colaborador que pueda marcar

Como administrador:

1. Abre `Colaboradores`.
2. Pulsa `Nuevo colaborador`.
3. Completa identificación, área, horario, fecha de ingreso y estado `ACTIVO`.
4. En **Acceso para marcar asistencia**, define un usuario y una contraseña de mínimo 8 caracteres.
5. Deja activo `Permitir inicio de sesión y marcación`.
6. Guarda.
7. Cierra sesión del administrador.
8. Entra con las credenciales que acabas de crear.
9. El sistema debe abrir `Mi asistencia`.

## Marcaciones incorporadas en Fase 3

- Ingreso y salida con hora del servidor.
- Tolerancia configurable.
- Puntualidad y tardanza automática.
- Minutos de tardanza.
- Salida anticipada.
- Minutos trabajados.
- Horarios que cruzan medianoche.
- IP de entrada y salida.
- Auditoría.
- Bloqueo por cese/estado laboral.
- Historial personal.
- Supervisión de marcaciones para Administrador/RRHH/Supervisor.

## Regla de ejemplo

Horario 08:00 con 5 minutos de tolerancia:

- 08:04 → PUNTUAL.
- 08:05 → PUNTUAL.
- 08:06 → TARDANZA.

## Importante

No avances todavía a Fase 4 hasta comprobar esta ruta:

`Admin → crear/editar colaborador con acceso → cerrar sesión → login del colaborador → Mi asistencia → marcar ingreso`.


## Fase 4

Esta entrega incorpora el módulo de solicitudes/incidencias y corrige el panel del colaborador. Si vienes de Fase 3.3, ejecuta `database/migrations/fase4_desde_v3_3.sql` una sola vez. Para instalación limpia utiliza `database/workforce360_v4.sql`. Consulta `docs/FASE4.md`.
