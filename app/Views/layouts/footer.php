        </main>
    </div>
    <footer class="app-footer">Workforce360 AI · DEVIOZ · Fase 7</footer>
</div>

<?php
$assistantUser = \App\Core\Auth::user();
$assistantIsAdmin = $assistantUser && \App\Core\Auth::hasRole('ADMINISTRADOR');
$assistantIsCollaborator = $assistantUser && \App\Core\Auth::hasRole('COLABORADOR');
$assistantEnabled = $assistantIsAdmin || $assistantIsCollaborator;
$assistantMode = $assistantIsAdmin ? 'ADMIN' : 'PERSONAL';
?>

<?php if ($assistantEnabled): ?>
<div
    class="wf-assistant-root"
    id="wfAssistant"
    data-endpoint="<?= e(route_url('assistant/query')) ?>"
    data-token="<?= e(\App\Core\Csrf::token()) ?>"
    data-mode="<?= e($assistantMode) ?>"
>
    <section class="wf-assistant-panel" data-wf-assistant-panel aria-hidden="true" aria-label="Asistente Workforce">
        <div class="wf-assistant-header">
            <div class="wf-assistant-title">
                <div class="wf-assistant-avatar"><i class="fa-solid fa-brain"></i></div>
                <div>
                    <strong>Asistente Workforce</strong>
                    <span><?= $assistantIsAdmin ? 'Acceso administrativo' : 'Consulta personal segura' ?></span>
                </div>
            </div>
            <div class="wf-assistant-header-actions">
                <button class="wf-assistant-icon-btn" type="button" data-wf-assistant-clear title="Limpiar conversación" aria-label="Limpiar conversación">
                    <i class="fa-solid fa-broom"></i>
                </button>
                <button class="wf-assistant-icon-btn" type="button" data-wf-assistant-close title="Cerrar" aria-label="Cerrar asistente">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </div>

        <div class="wf-assistant-scope">
            <?php if ($assistantIsAdmin): ?>
                <i class="fa-solid fa-shield-halved"></i>Puede consultar información global, indicadores y predicciones. Los cálculos se ejecutan solo cuando preguntas para no hacer más lenta la carga del sistema.
            <?php else: ?>
                <i class="fa-solid fa-lock"></i>Solo puede consultar tu propia asistencia, faltas, tardanzas, incidencias y marcaciones.
            <?php endif; ?>
        </div>

        <div class="wf-assistant-messages" data-wf-assistant-messages aria-live="polite">
            <div class="wf-assistant-message-row assistant">
                <div class="wf-assistant-message">
                    <?php if ($assistantIsAdmin): ?>Hola. Puedo consultar datos globales de Workforce360 AI y las predicciones del modelo. ¿Qué deseas revisar?<?php else: ?>Hola. Puedo consultar únicamente tus datos personales de asistencia, tardanzas, faltas e incidencias. ¿Qué deseas revisar?<?php endif; ?>
                </div>
            </div>
        </div>

        <div class="wf-assistant-chips">
            <?php if ($assistantIsAdmin): ?>
                <button type="button" class="wf-assistant-chip" data-wf-assistant-chip="Resumen de asistencia de hoy">Resumen de hoy</button>
                <button type="button" class="wf-assistant-chip" data-wf-assistant-chip="Lista de los que asistieron hoy">Lista de hoy</button>
                <button type="button" class="wf-assistant-chip" data-wf-assistant-chip="¿Cuántas tardanzas hubo hoy?">Tardanzas</button>
                <button type="button" class="wf-assistant-chip" data-wf-assistant-chip="Incidencias pendientes">Incidencias</button>
                <button type="button" class="wf-assistant-chip" data-wf-assistant-chip="Predicción de asistencia">Predicción</button>
            <?php else: ?>
                <button type="button" class="wf-assistant-chip" data-wf-assistant-chip="Mi asistencia este mes">Mi asistencia</button>
                <button type="button" class="wf-assistant-chip" data-wf-assistant-chip="Mis tardanzas">Tardanzas</button>
                <button type="button" class="wf-assistant-chip" data-wf-assistant-chip="Mis faltas">Faltas</button>
                <button type="button" class="wf-assistant-chip" data-wf-assistant-chip="Mis incidencias">Incidencias</button>
            <?php endif; ?>
        </div>

        <form class="wf-assistant-form" data-wf-assistant-form autocomplete="off">
            <textarea class="wf-assistant-input" data-wf-assistant-input rows="1" maxlength="500" placeholder="Escribe una pregunta..."></textarea>
            <button class="wf-assistant-send" type="submit" title="Enviar" aria-label="Enviar pregunta">
                <i class="fa-solid fa-paper-plane"></i>
            </button>
        </form>
    </section>

    <button
        class="wf-assistant-toggle"
        type="button"
        data-wf-assistant-toggle
        aria-label="Abrir Asistente Workforce"
        aria-expanded="false"
        title="Asistente Workforce"
    >
        <i class="fa-solid fa-comments"></i>
        <span class="wf-assistant-pulse"></span>
    </button>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.5.0/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/exceljs@4.4.0/dist/exceljs.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.4/jspdf.plugin.autotable.min.js"></script>
<script src="<?= e(asset('js/app.js')) ?>"></script>
<script src="<?= e(asset('js/reports.js')) ?>"></script>
<?php if ($assistantEnabled): ?>
<script src="<?= e(asset('js/assistant.js')) ?>"></script>
<?php endif; ?>
</body>
</html>
