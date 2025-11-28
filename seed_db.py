#!/usr/bin/env python3
"""
Seed script to insert sample data into ecg_app_db tables.
This uses pymysql. Configure connection via environment variables:
  DB_HOST, DB_USER, DB_PASSWORD, DB_NAME
Defaults: 127.0.0.1, root, '', ecg_app_db

The script will insert multiple rows into: users, patients, duty_roster,
ecg_images, tasks, task_history.
"""
import os
import sys
from datetime import datetime, date, timedelta

try:
    import pymysql
except Exception as e:
    print("Missing dependency: pymysql. Please install it in your Python environment.")
    sys.exit(1)

DB_HOST = os.environ.get('DB_HOST', '127.0.0.1')
DB_USER = os.environ.get('DB_USER', 'root')
DB_PASSWORD = os.environ.get('DB_PASSWORD', '')
DB_NAME = os.environ.get('DB_NAME', 'ecg_app_db')
DB_PORT = int(os.environ.get('DB_PORT', '3306'))

conn = pymysql.connect(host=DB_HOST, user=DB_USER, password=DB_PASSWORD, database=DB_NAME, port=DB_PORT, charset='utf8mb4', cursorclass=pymysql.cursors.DictCursor, autocommit=False)

# A sample bcrypt-like password hash string (not generated here) to satisfy schema requirements.
SAMPLE_HASH = '$2y$10$EXAMPLESAMPLEEXAMPLESAMPLEEXAMPLEEXAMPLEEXAMPLE'

try:
    with conn.cursor() as cur:
        # Insert users (admin, 2 doctors, 2 technicians)
        users = [
            ("Admin Seed", "admin.seed@example.com", 'admin', 0, SAMPLE_HASH),
            ("Dr Alice", "alice.doctor@example.com", 'doctor', 0, SAMPLE_HASH),
            ("Dr Bob", "bob.doctor@example.com", 'doctor', 0, SAMPLE_HASH),
            ("Tech Tom", "tom.tech@example.com", 'technician', 0, SAMPLE_HASH),
            ("Tech Tina", "tina.tech@example.com", 'technician', 0, SAMPLE_HASH),
        ]
        user_ids = []
        sql_users = "INSERT INTO users (name, email, role, is_duty, created_at, password_hash) VALUES (%s, %s, %s, %s, %s, %s)"
        for u in users:
            created_at = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
            cur.execute(sql_users, (u[0], u[1], u[2], u[3], created_at, u[4]))
            user_ids.append(cur.lastrowid)

        print(f"Inserted {len(user_ids)} users: ids={user_ids}")

        # Insert patients (5 patients)
        patients = [
            ("PAT20251105001", "John Doe", 45, 'male', user_ids[1]),
            ("PAT20251105002", "Jane Roe", 37, 'female', user_ids[2]),
            ("PAT20251105003", "Sam Smith", 62, 'male', user_ids[1]),
            ("PAT20251105004", "Olivia Brown", 29, 'female', user_ids[2]),
            ("PAT20251105005", "Chris Green", 55, 'other', None),
        ]
        patient_ids = []
        sql_pat = "INSERT INTO patients (patient_id, name, age, gender, assigned_doctor_id, status, created_at) VALUES (%s, %s, %s, %s, %s, %s, %s)"
        for p in patients:
            created_at = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
            status = 'pending'
            cur.execute(sql_pat, (p[0], p[1], p[2], p[3], p[4], status, created_at))
            patient_ids.append(cur.lastrowid)

        print(f"Inserted {len(patient_ids)} patients: ids={patient_ids}")

        # Insert duty_roster entries for next 5 days assigning different users
        duty_ids = []
        sql_duty = "INSERT INTO duty_roster (user_id, duty_date, is_active, created_at) VALUES (%s, %s, %s, %s)"
        today = date.today()
        for i in range(5):
            # rotate through first 4 users (skip admin for duties sometimes)
            uid = user_ids[(i % 4) + 1] if len(user_ids) > 1 else user_ids[0]
            duty_date = (today + timedelta(days=i)).strftime('%Y-%m-%d')
            created_at = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
            cur.execute(sql_duty, (uid, duty_date, 1, created_at))
            duty_ids.append(cur.lastrowid)

        print(f"Inserted {len(duty_ids)} duty_roster rows: ids={duty_ids}")

        # Insert ecg_images for some patients
        ecg_ids = []
        sql_ecg = "INSERT INTO ecg_images (patient_id, technician_id, image_path, image_name, file_size, mime_type, created_at) VALUES (%s, %s, %s, %s, %s, %s, %s)"
        for i, pid in enumerate(patient_ids):
            tech_id = user_ids[3] if len(user_ids) > 3 else None
            image_name = f"ECG_{pid}_{i}.jpeg"
            image_path = f"uploads/ecg_images/{image_name}"
            file_size = 123456 + i * 100
            mime = 'image/jpeg'
            created_at = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
            cur.execute(sql_ecg, (pid, tech_id, image_path, image_name, file_size, mime, created_at))
            ecg_ids.append(cur.lastrowid)

        print(f"Inserted {len(ecg_ids)} ecg_images rows: ids={ecg_ids}")

        # Insert tasks for patients
        task_ids = []
        sql_task = "INSERT INTO tasks (patient_id, technician_id, assigned_doctor_id, status, priority, technician_notes, assigned_at, created_at) VALUES (%s, %s, %s, %s, %s, %s, %s, %s)"
        statuses = ['pending', 'assigned', 'in_progress', 'completed']
        priorities = ['low','normal','high','urgent']
        for i, pid in enumerate(patient_ids):
            tech = user_ids[3] if i % 2 == 0 else user_ids[4]
            doc = user_ids[1] if i % 2 == 0 else user_ids[2]
            status = statuses[i % len(statuses)]
            priority = priorities[i % len(priorities)]
            notes = f"Initial ECG capture for patient {pid}"
            assigned_at = datetime.now().strftime('%Y-%m-%d %H:%M:%S') if status != 'pending' else None
            created_at = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
            cur.execute(sql_task, (pid, tech, doc, status, priority, notes, assigned_at, created_at))
            task_ids.append(cur.lastrowid)

        print(f"Inserted {len(task_ids)} tasks: ids={task_ids}")

        # Insert task_history entries for each task
        th_ids = []
        sql_th = "INSERT INTO task_history (task_id, changed_by, old_status, new_status, comment, created_at) VALUES (%s, %s, %s, %s, %s, %s)"
        for i, tid in enumerate(task_ids):
            changer = user_ids[1] if i % 2 == 0 else user_ids[2]
            old = 'pending'
            new = statuses[i % len(statuses)]
            comment = f"Status changed from {old} to {new} by user {changer}"
            created_at = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
            cur.execute(sql_th, (tid, changer, old, new, comment, created_at))
            th_ids.append(cur.lastrowid)

        print(f"Inserted {len(th_ids)} task_history rows: ids={th_ids}")

        conn.commit()
        print("All data inserted and committed successfully.")

except Exception as e:
    print("Error occurred:", e)
    try:
        conn.rollback()
        print("Rolled back transaction.")
    except Exception:
        pass
finally:
    conn.close()

