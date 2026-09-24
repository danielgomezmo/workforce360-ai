import joblib
import numpy as np

from .dataset import (
    FEATURES,
    build_dataset,
)
from .modeling import (
    MODELS_DIR,
    calcular_metricas,
)


MODEL_PATH = (
    MODELS_DIR
    /
    "attendance_model.joblib"
)


def diagnosticar():
    df = build_dataset()

    split = int(
        len(df) * 0.80
    )

    train = df.iloc[
        :split
    ]

    test = df.iloc[
        split:
    ]

    y_train = train[
        "indice_asistencia"
    ]

    y_test = test[
        "indice_asistencia"
    ]

    print()
    print(
        "======================================"
    )
    print(
        " WORKFORCE360 AI - DIAGNÓSTICO"
    )
    print(
        "======================================"
    )
    print()

    print(
        "Total de registros:",
        len(df),
    )
    print(
        "Entrenamiento:",
        len(train),
    )
    print(
        "Prueba:",
        len(test),
    )
    print()

    print(
        "RANGO DEL ÍNDICE DE ASISTENCIA"
    )
    print(
        "Mínimo:",
        round(
            df["indice_asistencia"].min(),
            2,
        ),
    )
    print(
        "Máximo:",
        round(
            df["indice_asistencia"].max(),
            2,
        ),
    )
    print(
        "Promedio:",
        round(
            df["indice_asistencia"].mean(),
            2,
        ),
    )
    print(
        "Desviación estándar:",
        round(
            df["indice_asistencia"].std(),
            2,
        ),
    )
    print()

    print(
        "PROMEDIO ENTRENAMIENTO:",
        round(
            y_train.mean(),
            2,
        ),
    )
    print(
        "PROMEDIO PRUEBA:",
        round(
            y_test.mean(),
            2,
        ),
    )
    print()

    pred_baseline = np.full(
        len(y_test),
        y_train.mean(),
    )

    baseline = calcular_metricas(
        y_test,
        pred_baseline,
    )

    print(
        "BASELINE - PROMEDIO HISTÓRICO"
    )
    print("-" * 40)
    print(
        "MAE: ",
        round(
            baseline["MAE"],
            3,
        ),
    )
    print(
        "RMSE:",
        round(
            baseline["RMSE"],
            3,
        ),
    )
    print(
        "R2:  ",
        round(
            baseline["R2"],
            3,
        ),
    )
    print()

    pred_lag = test[
        "asistencia_lag_1"
    ].to_numpy()

    lag = calcular_metricas(
        y_test,
        pred_lag,
    )

    print(
        "BASELINE - DÍA ANTERIOR"
    )
    print("-" * 40)
    print(
        "MAE: ",
        round(
            lag["MAE"],
            3,
        ),
    )
    print(
        "RMSE:",
        round(
            lag["RMSE"],
            3,
        ),
    )
    print(
        "R2:  ",
        round(
            lag["R2"],
            3,
        ),
    )
    print()

    if not MODEL_PATH.exists():
        print(
            "No existe modelo entrenado."
        )
        return

    paquete = joblib.load(
        MODEL_PATH
    )

    modelo = paquete["model"]
    metadata = paquete.get(
        "metadata",
        {},
    )

    # El modelo guardado está reentrenado con todo el dataset.
    # Para evitar presentar una métrica de prueba contaminada,
    # aquí solo mostramos las métricas holdout guardadas antes
    # del reentrenamiento final.
    print(
        "MODELO SELECCIONADO"
    )
    print("-" * 40)
    print(
        "Modelo:",
        metadata.get(
            "modelo",
            "desconocido",
        ),
    )
    print(
        "MAE holdout:",
        round(
            metadata.get(
                "mae_holdout",
                float("nan"),
            ),
            3,
        ),
    )
    print(
        "RMSE holdout:",
        round(
            metadata.get(
                "rmse_holdout",
                float("nan"),
            ),
            3,
        ),
    )
    print(
        "R2 holdout:",
        round(
            metadata.get(
                "r2_holdout",
                float("nan"),
            ),
            3,
        ),
    )
    print(
        "Supera baseline:",
        metadata.get(
            "supera_baseline",
            False,
        ),
    )
    print()

    if hasattr(
        modelo,
        "named_steps",
    ):
        ridge = modelo.named_steps.get(
            "modelo"
        )

        if (
            ridge is not None
            and
            hasattr(
                ridge,
                "coef_",
            )
        ):
            print(
                "COEFICIENTES DEL MODELO"
            )
            print("-" * 40)

            coeficientes = sorted(
                zip(
                    FEATURES,
                    ridge.coef_,
                ),
                key=lambda x:
                    abs(x[1]),
                reverse=True,
            )

            for nombre, valor in coeficientes:
                print(
                    f"{nombre:28s} "
                    f"{valor: .4f}"
                )


if __name__ == "__main__":
    diagnosticar()
