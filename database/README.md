# Base de datos · Workforce360 AI Fase 3.1

## Instalación nueva
Importa `workforce360_v3_1.sql`.

## Si ya tienes Fase 3
Ejecuta una sola vez:

`migrations/fase3_1_corregir_acceso_colaborador.sql`

Este script **no borra la base**. Repara y valida el acceso del colaborador demo.

## Si vienes directamente de Fase 2
Primero ejecuta:

`migrations/fase3_desde_v2.sql`

Luego ejecuta:

`migrations/fase3_1_corregir_acceso_colaborador.sql`

## Accesos de prueba
- Administrador: `admin` / `Admin123*`
- Colaborador: `colaborador` / `Colab123*`

Después de aplicar una migración, cierra sesión y vuelve a entrar para renovar los datos guardados en la sesión.
