import random
import mysql.connector

# ===== CHECK THESE FOUR LINES AGAINST YOUR db.php =====
DB_HOST = "localhost"
DB_USER = "root"
DB_PASSWORD = ""
DB_NAME = "smart_queue"
# =======================================================

ROWS = 3000

# Average service time in minutes for each service id.
# 1 Fee Payment, 2 Admission, 3 Certificate, 4 General Enquiry
BASE_MINUTES = {1: 5, 2: 10, 3: 4, 4: 6}

random.seed(42)


def make_row():

    service_id = random.choice([1, 1, 1, 2, 2, 3, 4])
    priority = 1 if random.random() < 0.15 else 0

    # Busy hours: 9 to 12 are the most common
    hour = random.choices(
        range(8, 18),
        weights=[6, 10, 12, 12, 7, 5, 8, 9, 7, 4]
    )[0]

    day = random.randint(0, 6)

    queue_length = max(0, int(random.gauss(6, 4)))

    minutes = BASE_MINUTES[service_id]

    # Priority patients usually need a little longer
    if priority == 1:
        minutes *= 1.25

    # Peak hours are slower
    if 10 <= hour <= 12:
        minutes *= 1.20

    # Late afternoon is quicker
    if hour >= 16:
        minutes *= 0.90

    # Staff work faster when the queue is long
    if queue_length > 10:
        minutes *= 0.90

    # Random variation, so no two visits are identical
    minutes *= random.gauss(1.0, 0.18)

    minutes = round(min(max(minutes, 1.0), 40.0), 2)

    return (service_id, priority, hour, day, queue_length, minutes, "SYNTHETIC")


conn = mysql.connector.connect(
    host=DB_HOST, user=DB_USER, password=DB_PASSWORD, database=DB_NAME
)

cursor = conn.cursor()

# Start fresh: remove old generated rows only (real rows are kept)
cursor.execute("DELETE FROM service_history WHERE source = 'SYNTHETIC'")

rows = [make_row() for _ in range(ROWS)]

cursor.executemany(
    """
    INSERT INTO service_history
    (service_id, priority, hour_of_day, day_of_week,
     queue_length, duration_minutes, source)
    VALUES (%s, %s, %s, %s, %s, %s, %s)
    """,
    rows
)

conn.commit()

print("Inserted", len(rows), "rows into service_history.")

cursor.execute(
    "SELECT service_id, COUNT(*), ROUND(AVG(duration_minutes), 2) "
    "FROM service_history GROUP BY service_id ORDER BY service_id"
)

print("service_id | rows | average minutes")

for row in cursor.fetchall():
    print(row[0], "|", row[1], "|", row[2])

cursor.close()
conn.close()