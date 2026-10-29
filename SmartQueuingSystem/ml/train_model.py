import joblib
import pandas as pd
import mysql.connector
from sklearn.ensemble import RandomForestRegressor
from sklearn.metrics import mean_absolute_error, r2_score
from sklearn.model_selection import train_test_split

# ===== SAME VALUES AS generate_data.py =====
DB_HOST = "localhost"
DB_USER = "root"
DB_PASSWORD = ""
DB_NAME = "smart_queue"
# ===========================================

FEATURES = ["service_id", "priority", "hour_of_day", "day_of_week", "queue_length"]


# ---------- 1. Load the data ----------

conn = mysql.connector.connect(
    host=DB_HOST, user=DB_USER, password=DB_PASSWORD, database=DB_NAME
)

cursor = conn.cursor()

cursor.execute(
    "SELECT service_id, priority, hour_of_day, day_of_week, "
    "queue_length, duration_minutes FROM service_history"
)

rows = cursor.fetchall()

cursor.close()
conn.close()

data = pd.DataFrame(
    rows,
    columns=FEATURES + ["duration_minutes"]
)

data["duration_minutes"] = data["duration_minutes"].astype(float)

print("Rows loaded:", len(data))


# ---------- 2. Split: 80% to learn, 20% to test ----------

X = data[FEATURES]
y = data["duration_minutes"]

X_train, X_test, y_train, y_test = train_test_split(
    X, y, test_size=0.2, random_state=42
)


# ---------- 3. Old method: fixed average per service ----------

service_average = y_train.groupby(X_train["service_id"]).mean()

baseline_prediction = X_test["service_id"].map(service_average)

baseline_mae = mean_absolute_error(y_test, baseline_prediction)


# ---------- 4. ML model ----------

model = RandomForestRegressor(
    n_estimators=200,
    max_depth=12,
    random_state=42
)

model.fit(X_train, y_train)

prediction = model.predict(X_test)

model_mae = mean_absolute_error(y_test, prediction)
model_r2 = r2_score(y_test, prediction)


# ---------- 5. Results ----------

print()
print("RESULTS (on 20% unseen data)")
print("-----------------------------")
print("Fixed time per service, average error: %.2f minutes" % baseline_mae)
print("ML model, average error:               %.2f minutes" % model_mae)
print("ML model R2 score:                     %.3f" % model_r2)

improvement = (baseline_mae - model_mae) / baseline_mae * 100

print("Improvement over fixed time:           %.1f%%" % improvement)

print()
print("What the model pays attention to:")

importance = sorted(
    zip(FEATURES, model.feature_importances_),
    key=lambda item: item[1],
    reverse=True
)

for name, value in importance:
    print("  %-14s %.3f" % (name, value))


# ---------- 6. Save the model ----------

joblib.dump(model, "wait_model.pkl")

print()
print("Saved: wait_model.pkl")

# ---------- 7. Save the results for the Insights page ----------

import json
from datetime import datetime

metrics = {
    "rows": int(len(data)),
    "baseline_mae": round(float(baseline_mae), 3),
    "model_mae": round(float(model_mae), 3),
    "r2": round(float(model_r2), 3),
    "improvement": round(float(improvement), 1),
    "importance": [
        {"name": n, "value": round(float(v), 3)} for n, v in importance
    ],
    "trained_at": datetime.now().strftime("%d %b %Y, %I:%M %p")
}

with open("metrics.json", "w") as f:
    json.dump(metrics, f)

print("Saved: metrics.json")