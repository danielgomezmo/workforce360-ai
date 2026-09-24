import pandas as pd
import numpy as np

from sklearn.model_selection import TimeSeriesSplit

from .dataset import (
    FEATURES,
    build_dataset,
)
from .modeling import (
    MODELS_DIR,
    calcular_metricas,
    crear_modelos,
)


def validar():
    df = build_dataset()

    X = df[FEATURES]
    y = df["indice_asistencia"]

    tscv = TimeSeriesSplit(
        n_splits=5
    )

    resultados = []

    print()
    print(
        "============================================"
    )
    print(
        " WORKFORCE360 AI - VALIDACIÓN TEMPORAL"
    )
    print(
        "============================================"
    )
    print()

    print(
        "Registros:",
        len(df),
    )
    print(
        "Periodo:",
        df["fecha"].min(),
        "→",
        df["fecha"].max(),
    )
    print()

    for numero_fold, (
        train_index,
        test_index,
    ) in enumerate(
        tscv.split(X),
        start=1,
    ):
        X_train = X.iloc[
            train_index
        ]
        X_test = X.iloc[
            test_index
        ]
        y_train = y.iloc[
            train_index
        ]
        y_test = y.iloc[
            test_index
        ]

        promedio_train = float(
            y_train.mean()
        )

        pred_baseline = np.full(
            len(y_test),
            promedio_train,
        )

        metricas = calcular_metricas(
            y_test,
            pred_baseline,
        )

        resultados.append(
            {
                "Fold": numero_fold,
                "Modelo":
                    "Baseline promedio",
                **metricas,
            }
        )

        for nombre, modelo in crear_modelos().items():
            modelo.fit(
                X_train,
                y_train,
            )

            pred = modelo.predict(
                X_test
            )

            metricas = calcular_metricas(
                y_test,
                pred,
            )

            resultados.append(
                {
                    "Fold":
                        numero_fold,
                    "Modelo":
                        nombre,
                    **metricas,
                }
            )

        print(
            f"Fold {numero_fold}: OK"
        )

    detalle = pd.DataFrame(
        resultados
    )

    resumen = (
        detalle
        .groupby("Modelo")
        .agg(
            MAE_Promedio=(
                "MAE",
                "mean",
            ),
            MAE_Desviacion=(
                "MAE",
                "std",
            ),
            RMSE_Promedio=(
                "RMSE",
                "mean",
            ),
            R2_Promedio=(
                "R2",
                "mean",
            ),
        )
        .reset_index()
        .sort_values(
            by=[
                "MAE_Promedio",
                "RMSE_Promedio",
            ]
        )
    )

    print()
    print(
        "RESULTADO PROMEDIO DE LOS 5 FOLDS"
    )
    print("-" * 86)

    print(
        resumen.round(3).to_string(
            index=False
        )
    )

    ruta_detalle = (
        MODELS_DIR
        /
        "validacion_temporal_detalle.csv"
    )

    ruta_resumen = (
        MODELS_DIR
        /
        "validacion_temporal_resumen.csv"
    )

    detalle.to_csv(
        ruta_detalle,
        index=False,
        encoding="utf-8-sig",
    )

    resumen.to_csv(
        ruta_resumen,
        index=False,
        encoding="utf-8-sig",
    )

    print()
    print(
        "Archivos guardados:"
    )
    print(
        ruta_detalle
    )
    print(
        ruta_resumen
    )


if __name__ == "__main__":
    validar()
