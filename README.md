# Smart Queuing System

A web-based queue management system that replaces physical waiting lines with digital tokens. Users generate a token online, track their position in the queue, and get notified when their turn is near. Staff manage the queue from a separate dashboard.

## Live Demo

- **Website:** https://YOUR-APP.onrender.com
- **Source code:** https://github.com/YOUR-USERNAME/YOUR-REPO

> The demo runs on a free hosting plan. If it has been idle, the first page load may take up to a minute.

## Demo Login

| Role  | Username / Email | Password |
|-------|------------------|----------|
| User  | user  | 1234|
| Staff | staff | 1234 |

## Features

- Online token generation for selected services
- Live queue and current-token display
- Staff dashboard to call the next token, complete or skip tokens
- User dashboard to view their own token status
- Notifications when a token is about to be called
- Service history stored for analysis
- ML-based insights on queue data

## Tech Stack

- **Frontend:** HTML, CSS, JavaScript
- **Backend:** PHP
- **Database:** MySQL (hosted on Aiven)
- **Hosting:** Render (Docker, Apache + PHP 8.2)

## Project Structure

```
SmartQueuingSystem/
├── Dockerfile            # Container setup for deployment
├── db.php                # Database connection (reads settings from environment variables)
├── auth.php              # Login / authentication
├── generate_token.php    # Token creation
├── call_next_token.php   # Staff: call next token
├── complete_token.php    # Staff: mark token as completed
├── skip_token.php        # Staff: skip a token
├── staff_dashboard.php   # Staff interface
├── user_dashboard.php    # User interface
├── ml_insights.php       # Queue insights
├── ml/                   # Machine learning files
├── script.js, notify.js  # Client-side logic and notifications
└── style.css             # Styling
```

## Run Locally (XAMPP)

1. Install [XAMPP](https://www.apachefriends.org/) and start **Apache** and **MySQL**.
2. Copy this project into `C:\xampp\htdocs\SmartQueuingSystem`.
3. Open phpMyAdmin, create a database named `smart_queue`, and import the project's SQL file.
4. Open `http://localhost/SmartQueuingSystem/` in your browser.

By default `db.php` connects to `localhost` with user `root` and an empty password.

## Deploy to the Cloud

The app reads its database settings from environment variables, so no password is stored in the code:

| Variable  | Description |
|-----------|-------------|
| `DB_HOST` | Database host |
| `DB_PORT` | Database port |
| `DB_USER` | Database user |
| `DB_PASS` | Database password |
| `DB_NAME` | Database name |
| `DB_SSL`  | Set to `1` for cloud databases that require SSL |

On Render, create a **Web Service** from this repository using the Docker runtime, set the Dockerfile path to `SmartQueuingSystem/Dockerfile`, and add the variables above.

## Author

Varshini S
