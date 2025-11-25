# Technical Specification

## 0. Context & Scope
SendPoint is a lightweight, internal-only API endpoint that processes form submissions from local StaticForge sites. It validates incoming data against strict YAML definitions and dispatches transactional emails using Twig templates.
**In Scope:** POST request handling, IP whitelisting, YAML-based validation, Twig rendering, SMTP dispatch, application logging.
**Out of Scope:** Public internet access, database storage, dynamic recipient selection, complex error reporting to clients.

## 1. Users, Roles, and Permissions (Translated)
- **System (StaticForge):** The only active user. Can submit POST requests to the endpoint.
- **Administrator:** Can create/edit `FORMID.yml` and `FORMID.twig` files on the server.
- **Permissions:**
    - Endpoint access is restricted to a whitlist of IP addresses.
    - No authentication tokens or user sessions are required (IP trust only).

## 2. Data & Information
- **Form Configuration (`FORMID.yml`):** Source of truth for validation rules and recipient email addresses.
- **Email Template (`FORMID.twig`):** Source of truth for email content formatting.
- **Form Data:** Transient data payload from the POST request.
    - **Retention:** Data is processed in-memory and discarded immediately after email dispatch. No database persistence.
    - **Sensitivity:** Treated as confidential in transit; never logged in full detail (PII protection).

## 3. Workflows & Event Triggers
- **Event: Form Submission Received**
    1. **Validate IP:** If not in WhiteList, return `403`.
    2. **Load Config:** Look up `FORMID.yml` (from `$_REQUEST['FORMID']`). If missing, return `400`.
    3. **CORS Check:** Validate `Origin` header against `config['allowed_origins']`.
    4. **Validate Method:** If not `POST`, return `405`.
    5. **Filter & Validate:**
        - **Honeypot Check:** If `honeypot_field` is filled, return `400` (Spam detected).
        - Strip fields not in YAML.
        - Check required fields/types. If invalid, return `400`.
    6. **Render:** Generate HTML email body using `FORMID.twig`.
    7. **Send:** Dispatch via SMTP (HTML with text fallback).
        - If success: Log event, return `200`.
        - If SMTP fails: Log error, return `500`.

## 4. Performance & Reliability Targets
- **Response Time:** Target < 2 seconds for successful processing (dependent on SMTP latency).
- **Timeout:** Hard timeout of 30 seconds for SMTP connections.
- **Throughput:** Designed for low volume (approx. 1-5 requests/hour peak).
- **Availability:** Critical for form function, but acceptable to fail fast if SMTP is down.

## 5. Access, Security, and Compliance
- **Network Security:** Strict IP whitelist.
- **CORS:** Per-form `Origin` header validation against `allowed_origins` in YAML.
- **Spam Protection:** Optional honeypot field validation.
- **Input Sanitization:** Aggressive filtering; only explicitly defined fields are processed.
- **Recipient Locking:** "To" address is hardcoded in YAML; never accepted from user input.
- **Secrets:** SMTP credentials stored in `.env`, never committed to code.

## 6. Integrations
- **SMTP Server:**
    - **Purpose:** Outbound email delivery.
    - **Flow:** Send-only.
    - **Failure Handling:** Log error and return `500` to caller.

## 7. Interfaces & Devices
- **Interface:** HTTP API (REST-like).
- **Client:** Server-side calls from StaticForge (curl/php/node).
- **No UI:** No graphical user interface provided.

## 8. Observability & Operations
- **Logging:** Use `Eicc/Utils` logger.
    - **Success:** Log `FORMID`, timestamp, and success status.
    - **Failure:** Log `FORMID`, error type (Validation/SMTP), and error message.
    - **Security:** Log unauthorized IP attempts.
- **Alerts:** None required (low volume system); log review is sufficient.

## 9. Reporting & Analytics
- **Metrics:** None required.
- **Reporting:** Ad-hoc log analysis if needed.

## 10. Environments & Change Management
- **Environment:** Single production environment (same server as StaticForge).
- **Deployment:** Code updates via git; config updates via file upload/edit.
- **Safe Release:** Configuration files (`.yml`/`.twig`) can be added/updated without restarting the service.

## 11. Open Technical Questions
- None.

## 12. Risks & Mitigations
- **Risk:** SMTP credentials expire or change.
    - *Mitigation:* Centralized `.env` management allows quick updates.
- **Risk:** High volume spam attack (if local security compromised).
    - *Mitigation:* Low throughput expectation makes this obvious in logs; IP restriction is primary defense.

## 13. Decision Log
- **D1:** Use YAML for config — Simple, human-readable, no DB required — 2025-11-24 — System Architect
- **D2:** IP Whitelist only — Sufficient security for internal-only tool — 2025-11-24 — System Architect
- **D3:** Honeypot Field — Simple spam protection without CAPTCHA — 2025-11-24 — System Architect
- **D4:** CORS Validation — Prevent unauthorized cross-origin usage — 2025-11-24 — System Architect
- **D5:** HTML Emails — Support rich content with text fallback — 2025-11-24 — System Architect
