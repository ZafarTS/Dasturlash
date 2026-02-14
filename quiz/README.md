# zafarts.uz/quiz Real-Time Quiz Module

Production-oriented PHP + MySQL module for Kahoot-style classroom competition with academic score persistence.

## Core guarantees
- Single active session globally.
- Server-side timestamps and score calculation only.
- Student view exposes only question number + A/B/C/D choices.
- No answer editing; one answer per student/question.
- Session records + semester cumulative score.

## Integration with main LMS database
1. Import your existing LMS SQL first.
2. Run `quiz/migrations/001_quiz_schema.sql`.
3. Adjust `quiz/config/config.php` DB settings.
4. Update `quiz/lib/Auth.php` session keys to match your login system.

## Run-time components
- PHP pages and API endpoints are served under `/quiz`.
- Start websocket daemon separately:
  ```bash
  php quiz/ws/server.php
  ```
- Configure reverse proxy to expose `ws://zafarts.uz:8090` or map behind `/quiz/ws`.

## Real-time and latency fairness
- Client pings WS server every 5s.
- Server measures RTT and stores half-RTT latency estimate.
- Score compensation = `min(150ms, latency/2)`.
- Final score formula for correct answers:
  ```
  round(MAX_SCORE * (1 - effective_time/question_time))
  ```

## Security checklist
- APIs use server-side session auth (`Auth.php`).
- Prepared statements everywhere.
- HTML content sanitized before DB write.
- Student never receives question text via student APIs.
- Session lock rule enforced at DB level via unique index `uq_one_active_session`.

## Deployment notes
- Add CSRF tokens if your main project has centralized middleware.
- Replace lightweight sanitizer with HTML Purifier for strict HTML policy.
- Use TLS (`wss://`) in production.
