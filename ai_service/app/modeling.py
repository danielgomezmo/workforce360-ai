from pathlib import Path

import numpy as np
import pandas as pd

from sklearn.ensemble import (
    GradientBoostingRegressor,
    HistGradientBoostingRegressor,
    RandomForestRegressor,
)
from sklearn.linear_model import Ridge
from sklearn.metrics import (
    mean_absolute_error,
    mean_squared_error,
    r2_score,
)
from sklearn.pipeline import Pipeline
from sklearn.preprocessing import StandardScaler


BASE_DIR = (
    Path(__file__)
    .resolve()
    .parents[1]
)

MODELS_DIR = (
    BASE_DIR
    /
    "models"
)

MODELS_DIR.mkdir(
    exist_ok=True
)


def crear_modelos():
    return {
        "Ridge Regression":
            Pipeline(
                [
                    (
                        "scaler",
                        StandardScaler(),
                    ),
                    (
                        "modelo",
                        Ridge(
                            alpha=1.0,
                        ),
                    ),
                ]
            ),

        "Random Forest":
            RandomForestRegressor(
                n_estimators=300,
                min_samples_leaf=3,
                random_state=42,
                n_jobs=-1,
            ),

        "Gradient Boosting":
            GradientBoostingRegressor(
                n_estimators=150,
                learning_rate=0.03,
                max_depth=2,
                min_samples_leaf=4,
                random_state=42,
            ),

        "HistGradientBoosting":
            HistGradientBoostingRegressor(
                max_iter=200,
                learning_rate=0.05,
                max_leaf_nodes=15,
                min_samples_leaf=10,
                l2_regularization=1.0,
                random_state=42,
            ),
    }


def calcular_metricas(
    y_real,
    y_pred,
):
    y_pred = np.clip(
        np.asarray(y_pred),
        0,
        100,
    )

    return {
        "MAE": float(
            mean_absolute_error(
                y_real,
                y_pred,
            )
        ),
        "RMSE": float(
            np.sqrt(
                mean_squared_error(
                    y_real,
                    y_pred,
                )
            )
        ),
        "R2": float(
            r2_score(
                y_real,
                y_pred,
            )
        ),
    }


def dividir_temporal(
    df,
    features,
    train_ratio=0.80,
):
    split = int(
        len(df)
        *
        train_ratio
    )

    if split <= 0 or split >= len(df):
        raise ValueError(
            "No hay suficientes registros "
            "para realizar la división temporal."
        )

    X = df[features]
    y = df["indice_asistencia"]

    return {
        "split": split,
        "X_train": X.iloc[:split],
        "X_test": X.iloc[split:],
        "y_train": y.iloc[:split],
        "y_test": y.iloc[split:],
    }


def comparar_holdout(
    df,
    features,
):
    partes = dividir_temporal(
        df,
        features,
    )

    X_train = partes["X_train"]
    X_test = partes["X_test"]
    y_train = partes["y_train"]
    y_test = partes["y_test"]

    resultados = []

    promedio_train = float(
        y_train.mean()
    )

    pred_baseline = np.full(
        len(y_test),
        promedio_train,
    )

    metricas_baseline = calcular_metricas(
        y_test,
        pred_baseline,
    )

    resultados.append(
        {
            "Modelo": "Baseline promedio",
            **metricas_baseline,
        }
    )

    modelos = crear_modelos()

    for nombre, modelo in modelos.items():
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
                "Modelo": nombre,
                **metricas,
            }
        )

    resultados_df = pd.DataFrame(
        resultados
    )

    baseline_mae = float(
        resultados_df.loc[
            resultados_df["Modelo"]
            ==
            "Baseline promedio",
            "MAE",
        ].iloc[0]
    )

    resultados_df[
        "Mejora_MAE_vs_Baseline"
    ] = (
        (
            baseline_mae
            -
            resultados_df["MAE"]
        )
        /
        baseline_mae
        *
        100
    )

    return (
        resultados_df,
        partes,
    )
