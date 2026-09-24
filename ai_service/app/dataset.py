import json
import os

import numpy as np
import pandas as pd
from dotenv import load_dotenv

from .db import engine


load_dotenv()


FEATURES = [
    "programados",
    "dia_semana",
    "mes",
    "dia_mes",
    "semana_anio",
    "tendencia_dias",
    "es_lunes",
    "es_viernes",
    "es_sabado",
    "asistencia_lag_1",
    "asistencia_lag_7",
    "asistencia_media_7",
    "asistencia_media_30",
    "tardanza_media_7",
]


# Días no laborables detectados en el histórico sintético utilizado
# durante el desarrollo. En producción conviene reemplazar esto por una
# tabla de calendario laboral/feriados configurable.
DIAS_NO_LABORABLES = {
    "2025-05-01",
    "2025-07-28",
    "2025-07-29",
    "2025-12-25",
    "2026-01-01",
    "2026-05-01",
    "2026-07-28",
    "2026-07-29",
}


def parse_dias_semana(value):
    if value is None:
        return set()

    try:
        if isinstance(value, str):
            value = json.loads(value)
        return {int(dia) for dia in value}
    except Exception:
        return set()


def cargar_datos():
    colaboradores = pd.read_sql(
        """
        SELECT
            id_colaborador,
            fecha_ingreso,
            fecha_cese,
            estado
        FROM colaboradores
        """,
        engine,
    )

    asignaciones = pd.read_sql(
        """
        SELECT
            id_asignacion,
            id_colaborador,
            id_horario,
            fecha_inicio,
            fecha_fin,
            es_temporal
        FROM asignacion_horarios
        """,
        engine,
    )

    horarios = pd.read_sql(
        """
        SELECT
            id_horario,
            dias_semana,
            activo
        FROM horarios
        """,
        engine,
    )

    marcaciones = pd.read_sql(
        """
        SELECT
            id_colaborador,
            fecha,
            hora_entrada,
            resultado_entrada
        FROM marcaciones
        """,
        engine,
    )

    incidencias = pd.read_sql(
        """
        SELECT
            i.id_colaborador,
            DATE(i.fecha_inicio) AS fecha_inicio,
            DATE(COALESCE(i.fecha_fin, i.fecha_inicio)) AS fecha_fin,
            t.es_justificada,
            t.requiere_aprobacion
        FROM incidencias i
        INNER JOIN tipos_incidencia t
            ON t.id_tipo_incidencia = i.id_tipo_incidencia
        WHERE t.es_ausencia = 1
          AND (
                (
                    t.requiere_aprobacion = 1
                    AND i.estado = 'APROBADA'
                )
                OR
                (
                    t.requiere_aprobacion = 0
                    AND i.estado IN ('REGISTRADA', 'APROBADA')
                )
          )
        """,
        engine,
    )

    return (
        colaboradores,
        asignaciones,
        horarios,
        marcaciones,
        incidencias,
    )


def obtener_rango_historico(marcaciones):
    fechas = pd.to_datetime(
        marcaciones["fecha"],
        errors="coerce",
    ).dropna()

    if fechas.empty:
        raise ValueError("No existen fechas válidas en marcaciones.")

    fecha_inicio = fechas.min().normalize()
    fecha_fin = fechas.max().normalize()

    cutoff_env = os.getenv(
        "AI_TRAINING_CUTOFF",
        "",
    ).strip()

    if cutoff_env:
        try:
            cutoff = pd.Timestamp(cutoff_env).normalize()
        except Exception as exc:
            raise ValueError(
                "AI_TRAINING_CUTOFF debe usar formato YYYY-MM-DD."
            ) from exc

        fecha_fin = min(
            fecha_fin,
            cutoff,
        )

    if fecha_fin < fecha_inicio:
        raise ValueError(
            "La fecha final del histórico es anterior a la fecha inicial."
        )

    return fecha_inicio, fecha_fin


def construir_calendario_programado(
    colaboradores,
    asignaciones,
    horarios,
    fecha_inicio_global,
    fecha_fin_global,
):
    colaboradores = colaboradores.copy()
    asignaciones = asignaciones.copy()
    horarios = horarios.copy()

    colaboradores["fecha_ingreso"] = pd.to_datetime(
        colaboradores["fecha_ingreso"],
        errors="coerce",
    )

    colaboradores["fecha_cese"] = pd.to_datetime(
        colaboradores["fecha_cese"],
        errors="coerce",
    )

    asignaciones["fecha_inicio"] = pd.to_datetime(
        asignaciones["fecha_inicio"],
        errors="coerce",
    )

    asignaciones["fecha_fin"] = pd.to_datetime(
        asignaciones["fecha_fin"],
        errors="coerce",
    )

    base = asignaciones.merge(
        horarios,
        on="id_horario",
        how="inner",
    )

    base = base[
        base["activo"] == 1
    ].copy()

    base = base.merge(
        colaboradores,
        on="id_colaborador",
        how="inner",
    )

    base = base.dropna(
        subset=[
            "fecha_inicio",
            "fecha_ingreso",
        ]
    )

    registros = []

    for fila in base.itertuples(index=False):
        dias_laborables = parse_dias_semana(
            fila.dias_semana
        )

        if not dias_laborables:
            continue

        inicio = max(
            pd.Timestamp(fila.fecha_inicio),
            pd.Timestamp(fila.fecha_ingreso),
            fecha_inicio_global,
        )

        fin_asignacion = (
            fecha_fin_global
            if pd.isna(fila.fecha_fin)
            else pd.Timestamp(fila.fecha_fin)
        )

        fin_laboral = (
            fecha_fin_global
            if pd.isna(fila.fecha_cese)
            else pd.Timestamp(fila.fecha_cese)
        )

        fin = min(
            fin_asignacion,
            fin_laboral,
            fecha_fin_global,
        )

        if inicio > fin:
            continue

        for fecha in pd.date_range(
            inicio,
            fin,
            freq="D",
        ):
            if fecha.isoweekday() not in dias_laborables:
                continue

            registros.append(
                {
                    "id_colaborador": int(
                        fila.id_colaborador
                    ),
                    "id_horario": int(
                        fila.id_horario
                    ),
                    "id_asignacion": int(
                        fila.id_asignacion
                    ),
                    "fecha": fecha.normalize(),
                    "fecha_inicio_asignacion":
                        fila.fecha_inicio,
                    "es_temporal": int(
                        fila.es_temporal
                    ),
                }
            )

    calendario = pd.DataFrame(
        registros
    )

    if calendario.empty:
        raise ValueError(
            "No fue posible construir el calendario programado."
        )

    calendario = calendario.sort_values(
        [
            "id_colaborador",
            "fecha",
            "es_temporal",
            "fecha_inicio_asignacion",
            "id_asignacion",
        ]
    )

    calendario = (
        calendario
        .drop_duplicates(
            subset=[
                "id_colaborador",
                "fecha",
            ],
            keep="last",
        )
        .reset_index(drop=True)
    )

    return calendario


def procesar_marcaciones(
    marcaciones,
    fecha_inicio_global,
    fecha_fin_global,
):
    marcaciones = marcaciones.copy()

    marcaciones["fecha"] = (
        pd.to_datetime(
            marcaciones["fecha"],
            errors="coerce",
        )
        .dt
        .normalize()
    )

    marcaciones = marcaciones[
        marcaciones["fecha"].between(
            fecha_inicio_global,
            fecha_fin_global,
        )
    ].copy()

    marcaciones["presente"] = (
        marcaciones["hora_entrada"]
        .notna()
        .astype(int)
    )

    marcaciones["tardanza"] = (
        (
            marcaciones["resultado_entrada"]
            == "TARDANZA"
        )
        &
        (
            marcaciones["hora_entrada"]
            .notna()
        )
    ).astype(int)

    return (
        marcaciones
        .groupby(
            [
                "id_colaborador",
                "fecha",
            ]
        )
        .agg(
            presente=(
                "presente",
                "max",
            ),
            tardanza=(
                "tardanza",
                "max",
            ),
        )
        .reset_index()
    )


def procesar_incidencias(
    incidencias,
    fecha_inicio_global,
    fecha_fin_global,
):
    columnas_vacias = [
        "id_colaborador",
        "fecha",
        "incidencia_ausencia",
        "incidencia_justificada",
    ]

    if incidencias.empty:
        return pd.DataFrame(
            columns=columnas_vacias
        )

    incidencias = incidencias.copy()

    incidencias["fecha_inicio"] = pd.to_datetime(
        incidencias["fecha_inicio"],
        errors="coerce",
    )

    incidencias["fecha_fin"] = pd.to_datetime(
        incidencias["fecha_fin"],
        errors="coerce",
    )

    incidencias = incidencias.dropna(
        subset=[
            "fecha_inicio",
            "fecha_fin",
        ]
    )

    registros = []

    for fila in incidencias.itertuples(
        index=False
    ):
        inicio = max(
            pd.Timestamp(fila.fecha_inicio),
            fecha_inicio_global,
        )

        fin = min(
            pd.Timestamp(fila.fecha_fin),
            fecha_fin_global,
        )

        if inicio > fin:
            continue

        for fecha in pd.date_range(
            inicio,
            fin,
            freq="D",
        ):
            registros.append(
                {
                    "id_colaborador": int(
                        fila.id_colaborador
                    ),
                    "fecha": fecha.normalize(),
                    "incidencia_ausencia": 1,
                    "incidencia_justificada":
                        int(fila.es_justificada),
                }
            )

    if not registros:
        return pd.DataFrame(
            columns=columnas_vacias
        )

    resultado = pd.DataFrame(
        registros
    )

    return (
        resultado
        .groupby(
            [
                "id_colaborador",
                "fecha",
            ]
        )
        .agg(
            incidencia_ausencia=(
                "incidencia_ausencia",
                "max",
            ),
            incidencia_justificada=(
                "incidencia_justificada",
                "max",
            ),
        )
        .reset_index()
    )


def excluir_dias_no_laborables(
    diario
):
    diario = diario.copy()

    fechas_excluidas = {
        pd.Timestamp(fecha).date()
        for fecha in DIAS_NO_LABORABLES
    }

    diario = diario[
        ~diario["fecha"]
        .dt
        .date
        .isin(
            fechas_excluidas
        )
    ].copy()

    return (
        diario
        .sort_values("fecha")
        .reset_index(drop=True)
    )


def calcular_programados_fecha(
    fecha
):
    """
    Calcula cuántos colaboradores deberían trabajar
    en una fecha futura según:
    - vigencia laboral,
    - asignación de horario,
    - días de la semana del horario,
    - prioridad de asignación temporal/reciente.
    """
    fecha = pd.Timestamp(fecha).normalize()

    if fecha.strftime("%Y-%m-%d") in DIAS_NO_LABORABLES:
        return 0

    (
        colaboradores,
        asignaciones,
        horarios,
        _,
        _,
    ) = cargar_datos()

    colaboradores = colaboradores.copy()
    asignaciones = asignaciones.copy()
    horarios = horarios.copy()

    colaboradores["fecha_ingreso"] = pd.to_datetime(
        colaboradores["fecha_ingreso"],
        errors="coerce",
    )
    colaboradores["fecha_cese"] = pd.to_datetime(
        colaboradores["fecha_cese"],
        errors="coerce",
    )
    asignaciones["fecha_inicio"] = pd.to_datetime(
        asignaciones["fecha_inicio"],
        errors="coerce",
    )
    asignaciones["fecha_fin"] = pd.to_datetime(
        asignaciones["fecha_fin"],
        errors="coerce",
    )

    base = (
        asignaciones
        .merge(
            horarios,
            on="id_horario",
            how="inner",
        )
        .merge(
            colaboradores,
            on="id_colaborador",
            how="inner",
        )
    )

    base = base[
        base["activo"] == 1
    ].copy()

    base = base[
        (
            base["fecha_ingreso"].notna()
        )
        &
        (
            base["fecha_ingreso"] <= fecha
        )
        &
        (
            base["fecha_cese"].isna()
            |
            (base["fecha_cese"] >= fecha)
        )
        &
        (
            base["fecha_inicio"].notna()
        )
        &
        (
            base["fecha_inicio"] <= fecha
        )
        &
        (
            base["fecha_fin"].isna()
            |
            (base["fecha_fin"] >= fecha)
        )
    ].copy()

    if base.empty:
        return 0

    base["trabaja_fecha"] = base[
        "dias_semana"
    ].apply(
        lambda value:
            fecha.isoweekday()
            in parse_dias_semana(value)
    )

    base = base[
        base["trabaja_fecha"]
    ].copy()

    if base.empty:
        return 0

    base = base.sort_values(
        [
            "id_colaborador",
            "es_temporal",
            "fecha_inicio",
            "id_asignacion",
        ]
    )

    base = base.drop_duplicates(
        subset=[
            "id_colaborador",
        ],
        keep="last",
    )

    return int(
        base["id_colaborador"]
        .nunique()
    )


def build_dataset():
    (
        colaboradores,
        asignaciones,
        horarios,
        marcaciones,
        incidencias,
    ) = cargar_datos()

    if colaboradores.empty:
        raise ValueError(
            "No existen colaboradores."
        )

    if asignaciones.empty:
        raise ValueError(
            "No existen asignaciones de horarios."
        )

    if horarios.empty:
        raise ValueError(
            "No existen horarios."
        )

    if marcaciones.empty:
        raise ValueError(
            "No existen marcaciones para construir el dataset."
        )

    (
        fecha_inicio_global,
        fecha_fin_global,
    ) = obtener_rango_historico(
        marcaciones
    )

    calendario = construir_calendario_programado(
        colaboradores,
        asignaciones,
        horarios,
        fecha_inicio_global,
        fecha_fin_global,
    )

    marcas = procesar_marcaciones(
        marcaciones,
        fecha_inicio_global,
        fecha_fin_global,
    )

    ausencias = procesar_incidencias(
        incidencias,
        fecha_inicio_global,
        fecha_fin_global,
    )

    calendario = calendario.merge(
        marcas,
        on=[
            "id_colaborador",
            "fecha",
        ],
        how="left",
    )

    calendario = calendario.merge(
        ausencias,
        on=[
            "id_colaborador",
            "fecha",
        ],
        how="left",
    )

    for columna in [
        "presente",
        "tardanza",
        "incidencia_ausencia",
        "incidencia_justificada",
    ]:
        calendario[columna] = (
            calendario[columna]
            .fillna(0)
            .astype(int)
        )

    calendario["ausencia_justificada"] = (
        (
            calendario["presente"] == 0
        )
        &
        (
            calendario[
                "incidencia_ausencia"
            ] == 1
        )
        &
        (
            calendario[
                "incidencia_justificada"
            ] == 1
        )
    ).astype(int)

    calendario[
        "ausencia_injustificada_registrada"
    ] = (
        (
            calendario["presente"] == 0
        )
        &
        (
            calendario[
                "incidencia_ausencia"
            ] == 1
        )
        &
        (
            calendario[
                "incidencia_justificada"
            ] == 0
        )
    ).astype(int)

    calendario[
        "ausencia_sin_incidencia"
    ] = (
        (
            calendario["presente"] == 0
        )
        &
        (
            calendario[
                "incidencia_ausencia"
            ] == 0
        )
    ).astype(int)

    diario = (
        calendario
        .groupby("fecha")
        .agg(
            programados=(
                "id_colaborador",
                "count",
            ),
            presentes=(
                "presente",
                "sum",
            ),
            tardanzas=(
                "tardanza",
                "sum",
            ),
            ausencias_justificadas=(
                "ausencia_justificada",
                "sum",
            ),
            ausencias_injustificadas_registradas=(
                "ausencia_injustificada_registrada",
                "sum",
            ),
            ausencias_sin_incidencia=(
                "ausencia_sin_incidencia",
                "sum",
            ),
        )
        .reset_index()
        .sort_values("fecha")
        .reset_index(drop=True)
    )

    diario = excluir_dias_no_laborables(
        diario
    )

    diario["ausentes"] = (
        diario["programados"]
        -
        diario["presentes"]
    )

    diario["puntuales"] = (
        diario["presentes"]
        -
        diario["tardanzas"]
    ).clip(lower=0)

    programados_num = pd.to_numeric(
        diario["programados"],
        errors="coerce",
    ).astype(float)

    presentes_num = pd.to_numeric(
        diario["presentes"],
        errors="coerce",
    ).astype(float)

    ausentes_num = pd.to_numeric(
        diario["ausentes"],
        errors="coerce",
    ).astype(float)

    puntuales_num = pd.to_numeric(
        diario["puntuales"],
        errors="coerce",
    ).astype(float)

    tardanzas_num = pd.to_numeric(
        diario["tardanzas"],
        errors="coerce",
    ).astype(float)

    diario["indice_asistencia"] = np.where(
        programados_num > 0,
        presentes_num
        / programados_num
        * 100,
        np.nan,
    )

    diario["indice_ausentismo"] = np.where(
        programados_num > 0,
        ausentes_num
        / programados_num
        * 100,
        np.nan,
    )

    diario["indice_puntualidad"] = np.where(
        presentes_num > 0,
        puntuales_num
        / presentes_num
        * 100,
        np.nan,
    )

    tasa_tardanza = pd.Series(
        np.where(
            presentes_num > 0,
            tardanzas_num
            / presentes_num
            * 100,
            0.0,
        ),
        index=diario.index,
        dtype="float64",
    )

    diario["dia_semana"] = (
        diario["fecha"]
        .dt
        .dayofweek
    )

    diario["mes"] = (
        diario["fecha"]
        .dt
        .month
    )

    diario["dia_mes"] = (
        diario["fecha"]
        .dt
        .day
    )

    diario["semana_anio"] = (
        diario["fecha"]
        .dt
        .isocalendar()
        .week
        .astype(int)
    )

    diario["tendencia_dias"] = (
        diario["fecha"]
        -
        diario["fecha"].min()
    ).dt.days

    diario["es_lunes"] = (
        diario["dia_semana"] == 0
    ).astype(int)

    diario["es_viernes"] = (
        diario["dia_semana"] == 4
    ).astype(int)

    diario["es_sabado"] = (
        diario["dia_semana"] == 5
    ).astype(int)

    diario["asistencia_lag_1"] = (
        diario["indice_asistencia"]
        .shift(1)
    )

    asistencia_por_fecha = (
        diario
        .set_index("fecha")[
            "indice_asistencia"
        ]
    )

    diario["asistencia_lag_7"] = (
        diario["fecha"]
        .sub(
            pd.Timedelta(days=7)
        )
        .map(
            asistencia_por_fecha
        )
    )

    diario["asistencia_media_7"] = (
        diario["indice_asistencia"]
        .shift(1)
        .rolling(
            window=7,
            min_periods=7,
        )
        .mean()
    )

    diario["asistencia_media_30"] = (
        diario["indice_asistencia"]
        .shift(1)
        .rolling(
            window=30,
            min_periods=30,
        )
        .mean()
    )

    diario["tardanza_media_7"] = (
        tasa_tardanza
        .shift(1)
        .rolling(
            window=7,
            min_periods=7,
        )
        .mean()
    )

    diario = diario.replace(
        [
            np.inf,
            -np.inf,
        ],
        np.nan,
    )

    for feature in FEATURES:
        diario[feature] = pd.to_numeric(
            diario[feature],
            errors="coerce",
        )

    diario["indice_asistencia"] = pd.to_numeric(
        diario["indice_asistencia"],
        errors="coerce",
    )

    diario = (
        diario
        .dropna(
            subset=(
                FEATURES
                +
                ["indice_asistencia"]
            )
        )
        .sort_values("fecha")
        .reset_index(drop=True)
    )

    return diario
