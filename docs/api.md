# LangCoach API

The local API is available at `https://lang-coach-s.test`. All application routes require a Sanctum Bearer token.

## Check a sentence

`POST /api/check` accepts `{ "text": "I didn't knew about this." }` and returns `changed`, `original`, `corrected`, and `errors`. A changed result is persisted with its individual errors for the authenticated user. Unchanged results are not persisted.

The controller delegates to `GrammarCorrectionService`, which calls `GRAMMAR_MODEL_URL` with a JSON `text` request. The current local model returns a corrected sentence and structured errors; Laravel validates and returns them in the LangCoach contract. Connection failures, timeouts, invalid responses, and non-success model responses return HTTP 502 without exposing internal details.

`GET /api/stats` returns user-scoped corrections today/week, error-category counts, and frequent original/replacement pairs. It uses database aggregation only.

`GET /api/recommendations` aggregates up to ten current-user error patterns and sends only those aggregates to OpenAI. It returns `{ "recommendations": [{ "type", "title", "explanation", "practice", "examples" }] }`. OpenAI is never used for grammar checks or statistics. Upstream failures and invalid responses return HTTP 502 without exposing credentials or provider details.

## Local setup

Set MySQL values in `.env` (`DB_CONNECTION=mysql`, host, port, database, user, password), then run `php artisan migrate`. Configure `GRAMMAR_MODEL_URL` and optionally `GRAMMAR_MODEL_TIMEOUT`.

Create a development user and token with `php artisan app:create-development-token`. Copy the displayed token into the macOS app as a Bearer token when its API integration is enabled. Never commit the token.

TODO: This manually provisioned Sanctum token is prototype-only. Production must use Google OAuth 2.0 / OpenID Connect with Authorization Code + PKCE, Keychain credential storage, and appropriate token expiration, revocation, and refresh.

## Manual API test

1. Configure MySQL and run `php artisan migrate`.
2. Create a development token with `php artisan app:create-development-token`. Copy the single token line; do not commit it.
3. Start with `GET https://lang-coach-s.test/api/stats` using `Authorization: Bearer <token>`. A new user receives zero counts and empty arrays.
4. Configure and start a compatible local grammar-model service, set `GRAMMAR_MODEL_URL` to its check endpoint, and run `php artisan config:clear`.
5. Send `POST https://lang-coach-s.test/api/check` with `Authorization: Bearer <token>` and JSON `{ "text": "I didn't knew about this." }`. A changed model result is returned and persisted. A correct sentence returns `changed: false` and is not persisted.
6. Send `GET https://lang-coach-s.test/api/recommendations` with the same Bearer token after corrections exist. Set `OPENAI_API_KEY` before this step; the endpoint aggregates the user's errors and requests structured recommendations from OpenAI.

The bundled local grammar-model service is in `ai/app.py`. It exposes `POST /correct`,
accepts `{ "text": "..." }`, and returns `{ "corrected", "errors" }`. Laravel
normalizes that response into the public API contract.
