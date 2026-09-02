# Run Doc — PROMASY Project Management System

## How to reproduce uncommitted artifacts

No build step required. This is a plain PHP project with no compilation needed.

The only prerequisite is:
- PHP binary at `C:\wamp64\bin\php\php8.3.28\php.exe` (WAMP installation)
- MySQL running via WAMP on `localhost:3306`

## How to run the server

Start PHP's built-in development server on port 8080:

```
C:\wamp64\bin\php\php8.3.28\php.exe -S localhost:8080 -t C:\wamp64\www\project_man
```

- **Port:** 8080
- **Document root:** `C:\wamp64\www\project_man`
- **Database:** MySQL via WAMP, database `project_man`, user `root`, no password

If port 8080 is busy, try 8081 or 8082.
