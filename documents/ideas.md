This is a single API endpoint. It is called by sites using StaticForge's form handling feature to send emails when a form is submitted. The endpoint accepts POST requests with form data, identifies the appropriate email template based on the FORMID field, and sends the email using the provided data.

Security is handled via a whitelist of IP addresses.

The .env files should contain the SMTP server credentials.

- If the verb is not POST, return a 405 Method Not Allowed.
- If the IP address is not whitelisted, return a 403 Forbidden.
- If the FORMID does not match exactly, return a 400 Bad Request.

Each FORMID corresponds to a specific email template stored in the templates/ directory. The email is sent using the SMTP server configured in the .env file.
- Use Twig to render email templates with the provided form data.
- Use Eicc/Utils for logging email send attempts and errors.
- Return a 200 OK response if the email is sent successfully, otherwise return a 500 Internal Server Error.
