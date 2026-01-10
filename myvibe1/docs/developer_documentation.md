# Developer Documentation

## Deployment
This project is designed to work with Apache 2.4 and PHP 8.1+ running on a standard LAMP/XAMPP stack. The root directory contains the entry points for the web server.

- **Database Configuration**: The database connection settings are located in `app/core/db.php`.
- **Environment**: The project assumes a standard Apache environment with `.htaccess` enabled for error handling and directory protection.

## Backend Architecture and Code Structure
The source code is structured to separate logic (controllers), data handling (actions), and presentation (views).

### Directory Structure
- **Root (`/`)**: Contains entry point controllers (e.g., `index.php`, `auth.php`, `profile.php`). These files handle the initial request, setup the environment, and include the appropriate view.
- **`app/core`**: Contains core system files like database connection (`db.php`) and path helpers (`paths.php`).
- **`app/actions`**: Contains scripts that handle form submissions (POST requests). These scripts perform validation, database operations, and redirect the user. They do not output HTML.
- **`api`**: Contains scripts for interacting with external APIs (Jikan, TMDB, iTunes, Google Books) and internal API endpoints (e.g., search hints).
- **`views`**: Contains the HTML templates. Naming convention is `*_view.php`.
- **`assets`**: Contains static assets like CSS, JavaScript, and images.
- **`uploads`**: Stores user-uploaded content.

### Routing Logic
The project uses a direct-file access routing strategy, supplemented by `.htaccess` for error handling.
- **Entry Points**: Specific pages are accessed directly via their PHP files (e.g., `/auth.php`, `/settings.php`).
- **Homepage**: `index.php` serves as the controller for the homepage.
- **Error Handling**: `.htaccess` routes 403, 404, and 500 errors to `error.php`.

## Frontend Implementation
The frontend is built using standard HTML5, CSS3, and Vanilla JavaScript.
- **Views**: HTML structure is located in the `views/` directory. Views are included by the corresponding root controller.
- **Partials**: Reusable UI components (headers, modals) are located in `views/partials/`.
- **Styling**: Main stylesheet is `assets/css/style.css`.
- **JavaScript**: Page-specific logic is located in `assets/js/` (e.g., `auth_flip.js`, `settings.js`).

## Design Guidelines

### Forms
All forms use POST method and are processed by scripts in `app/actions/`.

#### User Facing

**Login (`auth.php`)**
- `user`: Username or Email.
- `password`: User's password.

**Register (`auth.php`)**
- `username`: Unique username (3-50 chars).
- `email`: Valid email address.
- `password`: Password (min 6 chars).
- `confirm_password`: Must match password.
- `avatar`: Optional profile image (JPG, PNG, WEBP, max 2MB).

**User Settings (`settings.php`)**
- **Change Avatar**:
    - `avatar`: Image file upload.
- **Change Display Name**:
    - `display_name`: New display name (max 50 chars).
- **Change Email**:
    - `new_email`: New email address.
    - `current_password`: For verification.
- **Change Password**:
    - `current_password`: For verification.
    - `new_password`: New password (min 6 chars).
    - `confirm_password`: Confirmation.
- **Delete Account**:
    - `email`: For verification.
    - `password`: For verification.

## Database
The project uses a MySQL database named `myvibe_db`.

### Users Table (`users`)
- `username`: VARCHAR (Unique)
- `email`: VARCHAR (Unique)
- `password_hash`: VARCHAR (Hashed password)
- `display_name`: VARCHAR
- `avatar`: VARCHAR (Path to avatar image)

## File System
User-generated content is stored in the `uploads/` directory.

### User Files
- **Directory**: `uploads/{username}/`
- **Avatar**: `uploads/{username}/avatar/avatar_{uniqid}.{ext}`
    - Supported formats: JPG, PNG, WEBP.
    - Permissions: Directories and files are set to `0777` to ensure accessibility.
