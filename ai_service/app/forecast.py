from datetime import date

import joblib
import numpy as np
import pandas as pd

from .dataset import (
    FEATURES,
    build_dataset,
    calcular_programados_fecha,
)
from .modeling import MODELS_DIR


MODEL_PATH = (
    MODELS_DIR
    /
    "attendance_model.joblib"
)


def cargar_modelo():
    if not MODEL_PATH.exists():
        raise FileNotFoundError(
            "No existe attendance_model.joblib. "
            "Ejecuta primero: python -m app.train"
        )

    return joblib.load(
        MODEL_PATH
    )


def _siguiente_dia_operativo(
    fecha_inicio,
    max_busqueda=30,
):
    fecha = pd.Timestamp(
        fecha_inicio
    ).normalize()

    for _ in range(max_busqueda):
        if calcular_programados_fecha(
            fecha
        ) > 0:
            return fecha

        fecha = (
            fecha
            +
            pd.Timedelta(days=1)
        )

    raise ValueError(
        "No se encontró un día operativo "
        "en el rango de búsqueda."
    )


def _crear_feature_row(
    fecha_objetivo,
    programados,
    historial_fechas,
    historial_asistencia,
    fecha_base,
    tardanza_media_7,
):
    fecha_objetivo = pd.Timestamp(
        fecha_objetivo
    ).normalize()

    asistencia_series = pd.Series(
        historial_asistencia,
        index=pd.to_datetime(
            historial_fechas
        ),
        dtype="float64",
    ).sort_index()

    if asistencia_series.empty:
        raise ValueError(
            "No existe histórico de asistencia."
        )

    lag_1 = float(
        asistencia_series.iloc[-1]
    )

    fecha_lag_7 = (
        fecha_objetivo
        -
        pd.Timedelta(days=7)
    )

    if fecha_lag_7 in asistencia_series.index:
        lag_7 = float(
            asistencia_series.loc[
                fecha_lag_7
            ]
        )
    else:
        lag_7 = float(
            asistencia_series
            .tail(7)
            .mean()
        )

    media_7 = float(
        asistencia_series
        .tail(7)
        .mean()
    )

    media_30 = float(
        asistencia_series
        .tail(30)
        .mean()
    )

    fila = {
        "programados": float(programados),
        "dia_semana": int(
            fecha_objetivo.dayofweek
        ),
        "mes": int(fecha_objetivo.month),
        "dia_mes": int(fecha_objetivo.day),
        "semana_anio": int(
            fecha_objetivo.isocalendar().week
        ),
        "tendencia_dias": int(
            (
                fecha_objetivo
                -
                fecha_base
            ).days
        ),
        "es_lunes": int(
            fecha_objetivo.dayofweek == 0
        ),
        "es_viernes": int(
            fecha_objetivo.dayofweek == 4
        ),
        "es_sabado": int(
            fecha_objetivo.dayofweek == 5
        ),
        "asistencia_lag_1": lag_1,
        "asistencia_lag_7": lag_7,
        "asistencia_media_7": media_7,
        "asistencia_media_30": media_30,
        "tardanza_media_7": float(
            tardanza_media_7
        ),
    }

    return pd.DataFrame(
        [fila],
        columns=FEATURES,
    )


def pronosticar_dia(
    fecha_objetivo=None,
    historial_extra=None,
):
    paquete = cargar_modelo()

    modelo = paquete["model"]
    metadata = paquete.get(
        "metadata",
        {},
    )

    df = build_dataset()

    ultima_fecha = pd.Timestamp(
        df["fecha"].max()
    ).normalize()

    if fecha_objetivo is None:
        hoy = pd.Timestamp(
            date.today()
        ).normalize()

        inicio = max(
            hoy,
            ultima_fecha
            +
            pd.Timedelta(days=1),
        )

        fecha_objetivo = _siguiente_dia_operativo(
            inicio
        )
    else:
        fecha_objetivo = pd.Timestamp(
            fecha_objetivo
        ).normalize()

    programados = calcular_programados_fecha(
        fecha_objetivo
    )

    if programados <= 0:
        return {
            "fecha": str(
                fecha_objetivo.date()
            ),
            "dia_operativo": False,
            "programados": 0,
            "mensaje": (
                "La fecha no tiene personal programado."
            ),
        }

    historial_fechas = list(
        pd.to_datetime(
            df["fecha"]
        )
    )

    historial_asistencia = list(
        df[
            "indice_asistencia"
        ].astype(float)
    )

    if historial_extra:
        for item in historial_extra:
            historial_fechas.append(
                pd.Timestamp(
                    item["fecha"]
                )
            )
            historial_asistencia.append(
                float(
                    item[
                        "indice_asistencia"
                    ]
                )
            )

    fecha_base = pd.Timestamp(
        df["fecha"].min()
    ).normalize()

    tardanza_media_7 = float(
        df[
            "tardanza_media_7"
        ].iloc[-1]
    )

    X = _crear_feature_row(
        fecha_objetivo,
        programados,
        historial_fechas,
        historial_asistencia,
        fecha_base,
        tardanza_media_7,
    )

    fila_contexto = X.iloc[0]
    dias_nombre = {
        0: "Lunes",
        1: "Martes",
        2: "Miércoles",
        3: "Jueves",
        4: "Viernes",
        5: "Sábado",
        6: "Domingo",
    }

    factores_contexto = {
        "dia_semana_nombre": dias_nombre.get(
            int(fecha_objetivo.dayofweek),
            "Desconocido",
        ),
        "programados": int(programados),
        "asistencia_ultimo_dia": round(
            float(fila_contexto["asistencia_lag_1"]),
            2,
        ),
        "asistencia_hace_7_dias": round(
            float(fila_contexto["asistencia_lag_7"]),
            2,
        ),
        "asistencia_media_7": round(
            float(fila_contexto["asistencia_media_7"]),
            2,
        ),
        "asistencia_media_30": round(
            float(fila_contexto["asistencia_media_30"]),
            2,
        ),
        "tardanza_media_7": round(
            float(fila_contexto["tardanza_media_7"]),
            2,
        ),
    }

    indice = float(
        np.clip(
            modelo.predict(X)[0],
            0,
            100,
        )
    )

    presentes = int(
        round(
            programados
            *
            indice
            /
            100
        )
    )

    presentes = max(
        0,
        min(
            programados,
            presentes,
        ),
    )

    ausentes = (
        programados
        -
        presentes
    )

    error_90 = float(
        metadata.get(
            "error_absoluto_percentil_90",
            0.0,
        )
    )

    intervalo_min = max(
        0.0,
        indice - error_90,
    )

    intervalo_max = min(
        100.0,
        indice + error_90,
    )

    return {
        "fecha": str(
            fecha_objetivo.date()
        ),
        "dia_operativo": True,
        "modelo": metadata.get(
            "modelo",
            "desconocido",
        ),
        "estado_modelo": metadata.get(
            "estado_modelo",
            "EXPERIMENTAL",
        ),
        "version_modelo": metadata.get(
            "version_modelo",
            "desconocida",
        ),
        "programados": int(programados),
        "factores_contexto": factores_contexto,
        "indice_asistencia_estimado": round(
            indice,
            2,
        ),
        "presentes_estimados": presentes,
        "ausentes_estimados": ausentes,
        "intervalo_aprox_90": {
            "min": round(
                intervalo_min,
                2,
            ),
            "max": round(
                intervalo_max,
                2,
            ),
        },
        "error_mae_holdout": round(
            float(
                metadata.get(
                    "mae_holdout",
                    0.0,
                )
            ),
            3,
        ),
        "supera_baseline_holdout": bool(
            metadata.get(
                "supera_baseline_holdout",
                metadata.get(
                    "supera_baseline",
                    False,
                ),
            )
        ),
        "supera_baseline_temporal": bool(
            metadata.get(
                "validacion_temporal_supera_baseline",
                False,
            )
        ),
    }


def pronosticar_varios_dias(
    dias=7,
    desde=None,
):
    if dias < 1 or dias > 31:
        raise ValueError(
            "dias debe estar entre 1 y 31."
        )

    df = build_dataset()

    if desde is None:
        hoy = pd.Timestamp(
            date.today()
        ).normalize()

        cursor = max(
            hoy,
            pd.Timestamp(
                df["fecha"].max()
            ).normalize()
            +
            pd.Timedelta(days=1),
        )
    else:
        cursor = pd.Timestamp(
            desde
        ).normalize()

    resultados = []
    historial_extra = []
    intentos = 0

    while (
        len(resultados) < dias
        and
        intentos < 90
    ):
        intentos += 1

        if calcular_programados_fecha(
            cursor
        ) > 0:
            pronostico = pronosticar_dia(
                fecha_objetivo=cursor,
                historial_extra=historial_extra,
            )

            resultados.append(
                pronostico
            )

            historial_extra.append(
                {
                    "fecha": cursor,
                    "indice_asistencia": pronostico[
                        "indice_asistencia_estimado"
                    ],
                }
            )

        cursor = (
            cursor
            +
            pd.Timedelta(days=1)
        )

    if len(resultados) < dias:
        raise ValueError(
            "No se encontraron suficientes "
            "días operativos para el pronóstico."
        )

    promedio = float(
        np.mean(
            [
                item[
                    "indice_asistencia_estimado"
                ]
                for item in resultados
            ]
        )
    )

    total_programados = int(
        sum(
            item["programados"]
            for item in resultados
        )
    )

    total_presentes = int(
        sum(
            item["presentes_estimados"]
            for item in resultados
        )
    )

    total_ausentes = int(
        sum(
            item["ausentes_estimados"]
            for item in resultados
        )
    )

    return {
        "dias_operativos": len(resultados),
        "asistencia_promedio_estimada": round(
            promedio,
            2,
        ),
        "programados_acumulados": total_programados,
        "presentes_estimados_acumulados": total_presentes,
        "ausentes_estimados_acumulados": total_ausentes,
        "detalle": resultados,
    }
