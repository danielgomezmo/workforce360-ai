from .dataset import (
    FEATURES,
    build_dataset,
)
from .modeling import (
    MODELS_DIR,
    comparar_holdout,
)


def comparar():
    df = build_dataset()

    resultados_df, partes = comparar_holdout(
        df,
        FEATURES,
    )

    split = partes["split"]

    print()
    print(
        "=========================================="
    )
    print(
        " WORKFORCE360 AI - COMPARACIÓN DE MODELOS"
    )
    print(
        "=========================================="
    )
    print()

    print(
        "Registros:",
        len(df),
    )
    print(
        "Entrenamiento:",
        len(partes["X_train"]),
    )
    print(
        "Prueba:",
        len(partes["X_test"]),
    )
    print()

    print("Periodo entrenamiento:")
    print(
        df.iloc[0]["fecha"],
        "→",
        df.iloc[split - 1]["fecha"],
    )
    print()

    print("Periodo prueba:")
    print(
        df.iloc[split]["fecha"],
        "→",
        df.iloc[-1]["fecha"],
    )
    print()

    salida = resultados_df.copy()

    for columna in [
        "MAE",
        "RMSE",
        "R2",
        "Mejora_MAE_vs_Baseline",
    ]:
        salida[columna] = (
            salida[columna]
            .round(3)
        )

    print("RESULTADOS")
    print("-" * 78)
    print(
        salida.to_string(
            index=False
        )
    )

    solo_ml = resultados_df[
        resultados_df["Modelo"]
        !=
        "Baseline promedio"
    ].copy()

    mejor = (
        solo_ml
        .sort_values(
            by=[
                "MAE",
                "RMSE",
            ]
        )
        .iloc[0]
    )

    baseline = resultados_df[
        resultados_df["Modelo"]
        ==
        "Baseline promedio"
    ].iloc[0]

    print()
    print(
        "MEJOR MODELO ML:",
        mejor["Modelo"],
    )
    print(
        "MAE:",
        round(
            mejor["MAE"],
            3,
        ),
    )
    print(
        "RMSE:",
        round(
            mejor["RMSE"],
            3,
        ),
    )
    print(
        "R2:",
        round(
            mejor["R2"],
            3,
        ),
    )

    supera = (
        mejor["MAE"]
        <
        baseline["MAE"]
        and
        mejor["RMSE"]
        <
        baseline["RMSE"]
    )

    print(
        "Supera baseline en MAE y RMSE:",
        "Sí" if supera else "No",
    )

    ruta = (
        MODELS_DIR
        /
        "comparacion_modelos.csv"
    )

    salida.to_csv(
        ruta,
        index=False,
        encoding="utf-8-sig",
    )

    print()
    print(
        "Guardado en:",
        ruta,
    )


if __name__ == "__main__":
    comparar()
