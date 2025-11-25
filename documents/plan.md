# Implementation Plan - SendPoint

## 1. Project Foundation & Environment Setup
- Review `documents/idea.md`, `documents/design.md`, `documents/technical.md`, and `documents/plan.md` completely to understand the plan and scope. ✅
- Review all code about to be edited and any related code. ✅
- Initialize the project structure (if not already present) ensuring `src/`, `config/`, and `templates/` directories exist. ✅
- Verify `composer.json` includes necessary dependencies (Symfony Console, Twig, YAML parser, PHPMailer/Symfony Mailer). ✅
- Create/Verify `.env` file structure for SMTP credentials (host, port, user, pass). ✅
- Create a basic `public/index.php` entry point that initializes the autoloader and DI container. ✅
- Update `documents/plan.md` to show completed tasks and step with ✅ after verification. ✅
- Wait for further instructions.

## 2. Endpoint Routing & Security Layer
- Review `documents/idea.md`, `documents/design.md`, `documents/technical.md`, and `documents/plan.md` completely to understand the plan and scope. ✅
- Review all code about to be edited and any related code. ✅
- Implement a basic Router/Controller to handle the root POST request. ✅
- Implement IP Whitelisting middleware/check: ✅
    - Read allowed IPs from config/env.
    - Return `403 Forbidden` if remote IP is not in the whitelist.
- Implement Method enforcement: ✅
    - Return `405 Method Not Allowed` if request is not POST.
- Update `documents/plan.md` to show completed tasks and step with ✅ after verification. ✅
- Wait for further instructions.

## 3. Configuration & Template Loading
- Review `documents/idea.md`, `documents/design.md`, `documents/technical.md`, and `documents/plan.md` completely to understand the plan and scope. ✅
- Review all code about to be edited and any related code. ✅
- Create a `FormConfigService` to handle loading `FORMID.yml` files. ✅
    - Input: `FORMID` string.
    - Output: Parsed configuration array or null.
    - Error: Return `400 Bad Request` if file not found.
- Configure Twig environment to load templates from `templates/` directory. ✅
- Update `documents/plan.md` to show completed tasks and step with ✅ after verification. ✅
- Wait for further instructions.

## 4. Input Validation & Sanitization
- Review `documents/idea.md`, `documents/design.md`, `documents/technical.md`, and `documents/plan.md` completely to understand the plan and scope. ✅
- Review all code about to be edited and any related code. ✅
- Implement `FormValidatorService`: ✅
    - Accept raw POST data and the loaded configuration.
    - Filter: Remove any keys from POST data that are not defined in the YAML config.
    - Validate: Check for required fields as defined in YAML.
    - Return: Cleaned data array or throw ValidationException.
- Integrate validation into the main Controller flow. ✅
    - Catch ValidationException and return `400 Bad Request`.
- Update `documents/plan.md` to show completed tasks and step with ✅ after verification. ✅
- Wait for further instructions.

## 5. Email Dispatch Implementation
- Review `documents/idea.md`, `documents/design.md`, `documents/technical.md`, and `documents/plan.md` completely to understand the plan and scope. ✅
- Review all code about to be edited and any related code. ✅
- Implement `EmailService`: ✅
    - Accept recipient email (from YAML), subject (from YAML/Template), and rendered body.
    - Configure SMTP transport using `.env` values.
    - Send email with a 30-second timeout.
    - Return success boolean or throw Exception.
- Integrate Twig rendering in the Controller: ✅
    - Render `FORMID.twig` with the cleaned data.
- Call `EmailService` from the Controller. ✅
    - Return `200 OK` on success.
    - Return `500 Internal Server Error` on failure.
- Update `documents/plan.md` to show completed tasks and step with ✅ after verification. ✅
- Wait for further instructions.

## 6. Logging & Observability
- Review `documents/idea.md`, `documents/design.md`, `documents/technical.md`, and `documents/plan.md` completely to understand the plan and scope. ✅
- Review all code about to be edited and any related code. ✅
- Integrate `Eicc/Utils` logger into the Controller. ✅
- Log specific events: ✅
    - `INFO`: Successful email sent (include FORMID).
    - `WARNING`: Validation failure (include FORMID).
    - `WARNING`: Unauthorized IP access (include IP).
    - `ERROR`: SMTP failure (include FORMID and error message).
- Update `documents/plan.md` to show completed tasks and step with ✅ after verification. ✅
- Wait for further instructions.

## 7. Final Verification & Cleanup
- Review `documents/idea.md`, `documents/design.md`, `documents/technical.md`, and `documents/plan.md` completely to understand the plan and scope. ✅
- Review all code about to be edited and any related code. ✅
- Verify all file permissions are correct (especially `templates/` and logs). ✅
- Ensure no debug code or temporary files remain. ✅
- Confirm `.env` is properly ignored in git. ✅
- Update `documents/plan.md` to show completed tasks and step with ✅ after verification. ✅
- Wait for further instructions.
