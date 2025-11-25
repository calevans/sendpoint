# SendPoint

## 1. Purpose & Vision
SendPoint is a specialized, internal-only API endpoint designed to handle form submissions from StaticForge sites residing on the same server. It decouples form handling from static site generation by accepting raw form data, validating it against strict definitions, and dispatching transactional emails using pre-defined templates. This ensures secure, reliable email delivery for static sites without exposing email infrastructure to the public web.

## 2. Target Users & Personas
- **Primary User:** The StaticForge system (automated process).
- **Secondary User:** System Administrators (configuring forms and templates).
- **Note:** This system is **not** intended for direct human interaction or external third-party developers.

## 3. Goals & Outcomes
- **Secure Form Handling:** Prevent unauthorized use by restricting access strictly to localhost.
- **Strict Data Control:** Ensure only defined data fields are processed and sent, stripping out extraneous or malicious input.
- **Reliable Delivery:** Successfully route form submissions to the correct recipient defined in server-side configuration, never from user input.
- **Simple Configuration:** Enable easy setup of new forms via paired YAML configuration and Twig template files.

## 4. Non-Goals & Boundaries
- **No External Access:** The API will not accept requests from the public internet or external servers.
- **No Dynamic Recipients:** The system will not allow form submitters to specify the "To" address; recipients are hard-coded in configuration.
- **No Detailed Error Reporting:** The API will not provide verbose validation error messages to the client; a simple failure signal is sufficient.
- **No Database Storage:** The system does not persist form submissions to a database; it is a pass-through emailer only.

## 5. Core Concepts & Domain Model (Non-technical)
- **Endpoint:** The single URL that receives all form POST requests.
- **FormID:** A unique identifier submitted with the form data that links the request to a specific configuration and template.
- **Form Configuration (`.yml`):** A file defining the allowed fields, validation rules, and the destination email address for a specific FormID.
- **Email Template (`.twig`):** A template file used to format the email body using the valid form data.
- **Whitelist:** A security rule allowing traffic only from listed IP addresses.

## 6. User Journeys & Key Scenarios
- **Successful Submission:**
    1. StaticForge posts data (including `FORMID`) to the endpoint from localhost.
    2. System validates IP is in the whitelist.
    3. System locates `FORMID.yml` and `FORMID.twig`.
    4. System filters incoming data, keeping only fields defined in the YAML.
    5. System validates data types/requirements.
    6. System renders the email using the Twig template and filtered data.
    7. System sends email to the address defined in the YAML.
    8. System returns `200 OK`.
    9. System logs the successful send.

- **Invalid Input (Validation Failure):**
    1. StaticForge posts data with missing required fields or invalid formats.
    2. System validates data against `FORMID.yml`.
    3. Validation fails.
    4. System returns `400 Bad Request`.
    5. System logs the validation failure.

- **Unauthorized Access:**
    1. An external request attempts to hit the endpoint.
    2. System checks IP address.
    3. IP is not in the whitelist.
    4. System returns `403 Forbidden`.
    5. System logs the unauthorized access attempt.

- **Unknown Form:**
    1. Request arrives with a `FORMID` that has no corresponding config file.
    2. System returns `400 Bad Request`.
    3. System logs the unknown form attempt.

## 7. Capabilities & Feature Set (Conceptual)
- **Security & Access Control**
    - IP Whitelisting.
    - Method enforcement (POST only).

- **Request Processing**
    - `FORMID` extraction and lookup.
    - Input sanitization (stripping undefined fields).
    - Input validation (checking required fields/types against YAML).

- **Email Dispatch**
    - Template rendering (Twig).
    - SMTP integration (via environment config).
    - Fixed recipient routing (from YAML).

- **Observability**
    - Logging of attempts and errors via standard logging utilities.

## 8. Constraints & Assumptions
- **Network:** IP must be in whitelist.
- **Configuration:** SMTP credentials exist in a `.env` file.
- **File System:** `templates/` directory is writable/readable by the application.

## 9. Phasing & Milestones (High-Level)
- **Phase 1: Core Implementation (~1 week)**
    - Setup basic endpoint routing and IP whitelisting.
    - Implement YAML configuration parsing and data filtering.
    - Implement Twig rendering and SMTP sending.
    - Basic logging.

## 10. Risks & Open Questions
- **Risk:** SMTP server availability/latency could slow down the response.
    - *Mitigation:* Ensure SMTP timeouts are configured appropriately.
- **Risk:** Malformed YAML or Twig files could cause 500 errors.
    - *Mitigation:* Add basic checks or try/catch blocks around file parsing.

## 11. Alternatives Considered (Conceptual)
- **Database-driven Configuration:** Storing form rules in a DB.
    - *Rejected:* Adds unnecessary complexity and dependency for a system that can be managed via simple files.
- **Dynamic Recipients:** Allowing a hidden form field to set the recipient.
    - *Rejected:* High security risk for spam relay; hard-coding in YAML is safer.
