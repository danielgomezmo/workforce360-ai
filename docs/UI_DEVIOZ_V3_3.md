# Ajuste visual DEVIOZ - Workforce360 AI v3.3

Esta revisión conserva la arquitectura, controladores, modelos, rutas y base de datos de Workforce360 AI v3.2.

Cambios exclusivamente de interfaz:
- Identidad visual DEVIOZ (#053034) aplicada a administrador y colaborador.
- Logo DEVIOZ incorporado al login y cabecera.
- Sidebar lateral colapsable con persistencia en localStorage.
- Cabecera superior estilo Sistema de Control Operativo / Panel de Colaborador.
- Login inspirado en el proyecto anterior, manteniendo el formulario y CSRF actuales.
- Dashboard con tarjetas KPI y estilo del sistema anterior usando los datos existentes.
- Panel de marcación del colaborador reorganizado visualmente como el proyecto de referencia.
- Tablas, formularios, botones, tarjetas, estados y espaciados unificados.
- Bootstrap se mantiene en versión 5 para no romper las vistas actuales.

No se modificó la lógica de negocio ni se requiere migración SQL.
