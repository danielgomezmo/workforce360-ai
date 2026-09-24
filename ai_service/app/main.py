from datetime import date

import joblib
import pandas as pd

from fastapi import FastAPI, HTTPException, Query
from fastapi.middleware.cors import CORSMiddleware

from .dataset import build_dataset
from .forecast import (
    MODEL_PATH,
    pronosticar_dia,
    pronosticar_varios_dias,
)
from .modeling import MODELS_DIR


TEMPORAL_SUMMARY_PATH = (
    MODELS_DIR
    /
    "validacion_temporal_resumen.csv"
)


app = FastAPI(
    title="Workforce360 AI API",
    version="1.1.0",
    description=(
        "Microservicio de analítica predictiva "
        "para asistencia y disponibilidad."
    ),
)


app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=False,
    allow_methods=["*"],
    allow_headers=["*"],
)


@app.get("/")
def root():
    return {
        "proyecto": "Workforce360 AI",
        "servicio": "API predictiva de asistencia",
        "version": "1.1.0",
        "docs": "/docs",
    }


@app.get("/health")
def health():
    return {
        "status": "ok"
    }


@app.get("/dataset/status")
def dataset_status():
    try:
        df = build_dataset()

        return {
            "registros": int(len(df)),
            "fecha_inicio": str(
                df["fecha"].min().date()
            ),
            "fecha_fin": str(
                df["fecha"].max().date()
            ),
            "asistencia_promedio": round(
                float(
                    df["indice_asistencia"].mean()
                ),
                2,
            ),
            "programados_promedio": round(
                float(
                    df["programados"].mean()
                ),
                2,
            ),
        }

    except Exception as exc:
        raise HTTPException(
            status_code=500,
            detail=str(exc),
        ) from exc


@app.get("/model/status")
def model_status():
    if not MODEL_PATH.exists():
        return {
            "modelo_entrenado": False,
            "ruta": str(MODEL_PATH),
        }

    try:
        paquete = joblib.load(MODEL_PATH)
        metadata = paquete.get(
            "metadata",
            {},
        )

        return {
            "modelo_entrenado": True,
            "modelo": metadata.get(
                "modelo",
                "desconocido",
            ),
            "version_modelo": metadata.get(
                "version_modelo",
                "desconocida",
            ),
            "estado_modelo": metadata.get(
                "estado_modelo",
                "EXPERIMENTAL",
            ),
            "mae_holdout": metadata.get(
                "mae_holdout"
            ),
            "rmse_holdout": metadata.get(
                "rmse_holdout"
            ),
            "r2_holdout": metadata.get(
                "r2_holdout"
            ),
            "supera_baseline_holdout": metadata.get(
                "supera_baseline_holdout",
                metadata.get(
                    "supera_baseline",
                    False,
                ),
            ),
            "validacion_temporal_supera_baseline": metadata.get(
                "validacion_temporal_supera_baseline",
                False,
            ),
            "fecha_historial_inicio": metadata.get(
                "fecha_historial_inicio"
            ),
            "fecha_historial_fin": metadata.get(
                "fecha_historial_fin"
            ),
            "generado_en_utc": metadata.get(
                "generado_en_utc"
            ),
        }

    except Exception as exc:
        raise HTTPException(
            status_code=500,
            detail=str(exc),
        ) from exc


@app.get("/evaluation/status")
def evaluation_status():
    if not TEMPORAL_SUMMARY_PATH.exists():
        return {
            "disponible": False,
            "estado_modelo": "EXPERIMENTAL",
            "mensaje": (
                "Ejecuta python -m app.validar_modelos "
                "para generar la validación temporal."
            ),
        }

    try:
        resumen = pd.read_csv(
            TEMPORAL_SUMMARY_PATH
        )

        if resumen.empty:
            return {
                "disponible": False,
                "estado_modelo": "EXPERIMENTAL",
                "mensaje": "La validación temporal está vacía.",
            }

        baseline_rows = resumen[
            resumen["Modelo"]
            ==
            "Baseline promedio"
        ]

        ml_rows = resumen[
            resumen["Modelo"]
            !=
            "Baseline promedio"
        ].sort_values(
            by=[
                "MAE_Promedio",
                "RMSE_Promedio",
            ]
        )

        if baseline_rows.empty or ml_rows.empty:
            raise ValueError(
                "No se encontró baseline o modelos ML en el resumen."
            )

        baseline = baseline_rows.iloc[0]
        mejor_ml = ml_rows.iloc[0]

        ml_supera = bool(
            float(mejor_ml["MAE_Promedio"])
            <
            float(baseline["MAE_Promedio"])
            and
            float(mejor_ml["RMSE_Promedio"])
            <
            float(baseline["RMSE_Promedio"])
        )

        return {
            "disponible": True,
            "estado_modelo": (
                "VALIDADO"
                if ml_supera
                else "EXPERIMENTAL"
            ),
            "ml_supera_baseline": ml_supera,
            "mejor_modelo_ml": str(
                mejor_ml["Modelo"]
            ),
            "mae_ml": round(
                float(
                    mejor_ml["MAE_Promedio"]
                ),
                3,
            ),
            "rmse_ml": round(
                float(
                    mejor_ml["RMSE_Promedio"]
                ),
                3,
            ),
            "r2_ml": round(
                float(
                    mejor_ml["R2_Promedio"]
                ),
                3,
            ),
            "baseline_mae": round(
                float(
                    baseline["MAE_Promedio"]
                ),
                3,
            ),
            "baseline_rmse": round(
                float(
                    baseline["RMSE_Promedio"]
                ),
                3,
            ),
        }

    except Exception as exc:
        raise HTTPException(
            status_code=500,
            detail=str(exc),
        ) from exc


@app.get("/forecast/daily")
def forecast_daily(
    fecha: date | None = None,
):
    try:
        return pronosticar_dia(
            fecha_objetivo=fecha
        )

    except FileNotFoundError as exc:
        raise HTTPException(
            status_code=409,
            detail=str(exc),
        ) from exc

    except Exception as exc:
        raise HTTPException(
            status_code=500,
            detail=str(exc),
        ) from exc


@app.get("/forecast/weekly")
def forecast_weekly(
    dias: int = Query(
        default=7,
        ge=1,
        le=31,
    ),
    desde: date | None = None,
):
    try:
        return pronosticar_varios_dias(
            dias=dias,
            desde=desde,
        )

    except FileNotFoundError as exc:
        raise HTTPException(
            status_code=409,
            detail=str(exc),
        ) from exc

    except Exception as exc:
        raise HTTPException(
            status_code=500,
            detail=str(exc),
        ) from exc
