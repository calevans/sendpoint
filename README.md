# SendPoint

SendPoint is a lightweight, internal-only API endpoint designed to handle form submissions from local [StaticForge](https://calevans.com/staticforge) sites. It validates incoming data against strict YAML definitions and dispatches transactional emails using Twig templates.

## Installation

1.  **Clone the repository:**
    ```bash
    git clone <repository-url>
    cd sendpoint
    ```

2.  **Install dependencies:**
    ```bash
    lando composer install
    ```

3.  **Configure Environment:**
    Copy the example environment file (or create one) and configure your SMTP settings and allowed IPs.
    ```bash
    cp .env.example .env
    ```

    Edit `.env`:
    ```dotenv
    # SMTP Configuration
    SMTP_HOST=smtp.example.com
    SMTP_PORT=587
    SMTP_USER=your_username
    SMTP_PASS=your_password
    SMTP_FROM_EMAIL=noreply@example.com
    SMTP_FROM_NAME="SendPoint System"

    # Security
    ```

## Usage

SendPoint accepts `POST` requests to the root endpoint.

**Important for CORS:** If you are making requests from a browser (JavaScript), you **must** include the `FORMID` in the URL query string so that the preflight `OPTIONS` request can validate the origin.

Example: `https://sendpoint.lndo.site/?FORMID=CONTACT_US`

### Request Parameters

*   `FORMID` (Required): A unique identifier for the form. Can be passed in the URL query string (recommended for CORS) or the POST body.
*   `...` (Other Fields): Any other form fields defined in your YAML configuration.

### Example Request (JavaScript / CORS)

```javascript
const url = 'https://sendpoint.lndo.site/?FORMID=CONTACT_US';
const formData = new FormData();
formData.append('email', 'user@example.com');
// ...

fetch(url, { method: 'POST', body: formData });
```

### Example Request (cURL)

```bash
curl -X POST http://localhost:8080/ \
     -d "FORMID=CONTACT_US" \
     -d "email=user@example.com" \
     -d "subject=Hello" \
     -d "body=This is a test message."
```

## Configuration (FORMID)

Each form is defined by two files in the `templates/` directory, named after the `FORMID`.

### 1. Configuration File (`FORMID.yml`)

This file defines the recipient, subject, and validation rules for the form fields.

**Example: `templates/CONTACT_US.yml`**

```yaml
recipient: support@example.com # (Required)
subject: "New Contact Form Submission"
reply_to_field: email  # (Optional) Use the 'email' field value for the Reply-To header
honeypot_field: website_url # (Optional) Field name to use as a honeypot for spam protection
allowed_origins: # (Optional) List of allowed origins for CORS
  - https://example.com
  - https://www.example.com

fields:
  email:
    type: email
    required: true
  name:
    type: string
    required: true
  message:
    type: string
    required: true
```

**Supported Types:**
*   `string`: Basic text.
*   `email`: Validates as a valid email address.
*   `int` / `integer`: Validates as an integer.

**Honeypot Protection:**
If `honeypot_field` is defined, any submission containing a value for that field will be rejected as spam. Ensure this field is hidden in your HTML form so legitimate users do not fill it out.

**Note:** Any field sent in the POST request that is *not* defined in the `fields` section of the YAML file will be ignored and stripped from the data.

### 2. Template File (`FORMID.twig`)

This file defines the body of the email sent. It uses Twig syntax and has access to all the validated data fields. SendPoint supports HTML emails.

**Example: `templates/CONTACT_US.twig`**

```html
<!DOCTYPE html>
<html>
<body>
    <h2>New message from {{ name }}</h2>
    <p><strong>Email:</strong> {{ email }}</p>
    <hr>
    <p>{{ message|nl2br }}</p>
</body>
</html>
```

### 2. Template File (`FORMID.twig`)

This file defines the body of the email sent. It uses Twig syntax and has access to all the validated data fields.

**Example: `templates/CONTACT_US.twig`**

```twig
New message from {{ name }} ({{ email }}):

{{ message }}

---
Sent via SendPoint
```

## Logging

Logs are written to `var/log/app.log`.
*   **INFO**: Successful email sends.
*   **WARNING**: Validation failures, unauthorized IP access, or unknown FORMID attempts.
*   **ERROR**: SMTP failures or template rendering errors.

## Security Features

### Rate Limiting
To prevent abuse, SendPoint implements IP-based rate limiting.
- **Default Limit:** 1 request per 10 minutes (600 seconds).
- **Configuration:** Set `RATE_LIMIT_SECONDS` in your `.env` file.
- **Response:** Returns `429 Too Many Requests` if the limit is exceeded.

### Max Length Validation
To prevent large payload attacks, all fields have a maximum length.
- **Default Limit:** 2048 characters per field.
- **Configuration:** You can override this per-field in your YAML form definition:
  ```yaml
  fields:
    message:
      type: string
      required: true
      max_length: 5000 # Override default
  ```
