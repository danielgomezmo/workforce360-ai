from datetime import datetime, timezone
import json

import joblib
import numpy as np
import pandas as pd

from sklearn.base import clone

from .dataset import (
    FEATURES,
    build_dataset,
)
from .modeling import (
    MODELS_DIR,
    comparar_holdout,
    crear_modelos,
)


MODEL_PATH = MODELS_DIR / "attendance_model.joblib"
METADATA_PATH = MODELS_DIR / "attendance_model_metadata.json"
TEMPORAL_SUMMARY_PATH = MODELS_DIR / "validacion_temporal_resumen.csv"


def leer_validacion_temporal(nombre_modelo: str) -> dict:
    resultado = {
        "disponible": False,
        "ml_supera_baseline": False,
        "mae_modelo": None,
        "rmse_modelo": None,
        "r2_modelo": None,
        "mae_baseline": None,
        "rmse_baseline": None,
    }

    if not TEMPORAL_SUMMARY_PATH.exists():
        return resultado

    try:
        resumen = pd.read_csv(TEMPORAL_SUMMARY_PATH)
        if resumen.empty:
            return resultado

        baseline_rows = resumen[
            resumen["Modelo"] == "Baseline promedio"
        ]
        model_rows = resumen[
            resumen["Modelo"] == nombre_modelo
        ]

        if baseline_rows.empty or model_rows.empty:
            return resultado

        baseline = baseline_rows.iloc[0]
        modelo = model_rows.iloc[0]

        mae_modelo = float(modelo["MAE_Promedio"])
        rmse_modelo = float(modelo["RMSE_Promedio"])
        r2_modelo = float(modelo["R2_Promedio"])
        mae_baseline = float(baseline["MAE_Promedio"])
        rmse_baseline = float(baseline["RMSE_Promedio"])

        resultado.update(
            {
                "disponible": True,
                "ml_supera_baseline": bool(
                    mae_modelo < mae_baseline
                    and rmse_modelo < rmse_baseline
                ),
                "mae_modelo": mae_modelo,
                "rmse_modelo": rmse_modelo,
                "r2_modelo": r2_modelo,
                "mae_baseline": mae_baseline,
                "rmse_baseline": rmse_baseline,
            }
        )

    except Exception:
        return resultado

    return resultado


def entrenar():
    df = build_dataset()

    resultados, partes = comparar_holdout(
        df,
        FEATURES,
    )

    solo_ml = resultados[
        resultados["Modelo"] != "Baseline promedio"
    ].copy()

    mejor = (
        solo_ml
        .sort_values(
            by=["MAE", "RMSE"]
        )
        .iloc[0]
    )

    nombre_modelo = str(mejor["Modelo"])
    modelos = crear_modelos()

    modelo_evaluacion = modelos[nombre_modelo]

    X_train = partes["X_train"]
    X_test = partes["X_test"]
    y_train = partes["y_train"]
    y_test = partes["y_test"]

    modelo_evaluacion.fit(
        X_train,
        y_train,
    )

    pred_test = np.clip(
        modelo_evaluacion.predict(X_test),
        0,
        100,
    )

    errores_abs = np.abs(
        np.asarray(y_test)
        - pred_test
    )

    error_90 = float(
        np.quantile(
            errores_abs,
            0.90,
        )
    )

    baseline = resultados[
        resultados["Modelo"] == "Baseline promedio"
    ].iloc[0]

    supera_baseline_holdout = bool(
        mejor["MAE"] < baseline["MAE"]
        and mejor["RMSE"] < baseline["RMSE"]
    )

    temporal = leer_validacion_temporal(
        nombre_modelo
    )

    estado_modelo = (
        "VALIDADO"
        if temporal["disponible"]
        and temporal["ml_supera_baseline"]
        else "EXPERIMENTAL"
    )

    modelo_final = clone(
        modelos[nombre_modelo]
    )

    X_total = df[FEATURES]
    y_total = df["indice_asistencia"]

    modelo_final.fit(
        X_total,
        y_total,
    )

    metadata = {
        "version_modelo": "fase7-v1",
        "modelo": nombre_modelo,
        "estado_modelo": estado_modelo,
        "features": FEATURES,
        "registros_entrenamiento": int(len(df)),
        "fecha_historial_inicio": str(
            df["fecha"].min().date()
        ),
        "fecha_historial_fin": str(
            df["fecha"].max().date()
        ),
        "mae_holdout": float(mejor["MAE"]),
        "rmse_holdout": float(mejor["RMSE"]),
        "r2_holdout": float(mejor["R2"]),
        "baseline_mae_holdout": float(baseline["MAE"]),
        "baseline_rmse_holdout": float(baseline["RMSE"]),
        "supera_baseline_holdout": supera_baseline_holdout,
        "validacion_temporal_disponible": temporal["disponible"],
        "validacion_temporal_supera_baseline": temporal["ml_supera_baseline"],
        "mae_temporal": temporal["mae_modelo"],
        "rmse_temporal": temporal["rmse_modelo"],
        "r2_temporal": temporal["r2_modelo"],
        "baseline_mae_temporal": temporal["mae_baseline"],
        "baseline_rmse_temporal": temporal["rmse_baseline"],
        "error_absoluto_percentil_90": error_90,
        "generado_en_utc": datetime.now(
            timezone.utc
        ).isoformat(),
    }

    paquete = {
        "model": modelo_final,
        "features": FEATURES,
        "metadata": metadata,
    }

    joblib.dump(
        paquete,
        MODEL_PATH,
    )

    with open(
        METADATA_PATH,
        "w",
        encoding="utf-8",
    ) as archivo:
        json.dump(
            metadata,
            archivo,
            ensure_ascii=False,
            indent=2,
        )

    print()
    print("================================")
    print(" WORKFORCE360 AI - ENTRENAMIENTO")
    print("================================")
    print()
    print("Modelo seleccionado:", nombre_modelo)
    print("Estado del modelo:", estado_modelo)
    print("Registros:", len(df))
    print("MAE holdout:", round(mejor["MAE"], 3))
    print("RMSE holdout:", round(mejor["RMSE"], 3))
    print("R2 holdout:", round(mejor["R2"], 3))
    print(
        "Supera baseline holdout:",
        "Sí" if supera_baseline_holdout else "No",
    )

    if temporal["disponible"]:
        print(
            "Supera baseline temporal:",
            "Sí"
            if temporal["ml_supera_baseline"]
            else "No",
        )
        print(
            "MAE temporal:",
            round(float(temporal["mae_modelo"]), 3),
        )
        print(
            "MAE baseline temporal:",
            round(float(temporal["mae_baseline"]), 3),
        )

    print(
        "Error absoluto P90:",
        round(error_90, 3),
    )
    print()
    print("Modelo guardado en:")
    print(MODEL_PATH)


if __name__ == "__main__":
    entrenar()
