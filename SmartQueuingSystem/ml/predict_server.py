import json
import os
import subprocess
import sys
from datetime import datetime

import joblib
import pandas as pd
from flask import Flask, jsonify, request

app = Flask(__name__)

model = joblib.load("wait_model.pkl")
BASE_DIR = os.path.dirname(os.path.abspath(__file__))

FEATURES = ["service_id", "priority", "hour_of_day", "day_of_week", "queue_length"]


def build_row(item):

    now = datetime.now()

    return {
        "service_id": int(item.get("service_id", 1)),
        "priority": int(item.get("priority", 0)),
        "hour_of_day": int(item.get("hour_of_day", now.hour)),
        "day_of_week": int(item.get("day_of_week", now.weekday())),
        "queue_length": int(item.get("queue_length", 0)),
    }


def predict_minutes(items):

    rows = [build_row(item) for item in items]

    frame = pd.DataFrame(rows, columns=FEATURES)

    predictions = model.predict(frame)

    return [round(float(p), 2) for p in predictions]


# Check that the service is running

@app.route("/")
def home():
    return jsonify({"status": "running", "model": "wait_model.pkl"})


# One prediction, for testing in the browser

@app.route("/predict")
def predict_one():

    minutes = predict_minutes([request.args.to_dict()])[0]

    return jsonify({"success": True, "minutes": minutes})


# Many predictions at once, used by PHP

@app.route("/predict_batch", methods=["POST"])
def predict_batch():

    body = request.get_json(silent=True) or {}

    items = body.get("items", [])

    if not items:
        return jsonify({"success": True, "minutes": []})

    return jsonify({"success": True, "minutes": predict_minutes(items)})

# Model accuracy results, shown on the staff Insights page

@app.route("/metrics")
def metrics():

    path = os.path.join(BASE_DIR, "metrics.json")

    if not os.path.exists(path):
        return jsonify({"success": False, "message": "No results yet. Train the model first."})

    with open(path) as f:
        data = json.load(f)

    data["success"] = True

    return jsonify(data)


# Retrain the model from the service_history table

@app.route("/retrain", methods=["POST"])
def retrain():

    global model

    result = subprocess.run(
        [sys.executable, "train_model.py"],
        cwd=BASE_DIR,
        capture_output=True,
        text=True,
        timeout=120
    )

    if result.returncode != 0:
        return jsonify({"success": False, "message": "Training failed."})

    model = joblib.load(os.path.join(BASE_DIR, "wait_model.pkl"))

    return jsonify({"success": True, "message": "Model retrained."})
if __name__ == "__main__":
    app.run(host="127.0.0.1", port=5001)