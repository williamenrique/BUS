sitgo/
├── app/
│   ├── Config/
│   │   ├── config.php
│   │   └── routes.php
│   ├── Controllers/
│   │   ├── ControllerAuth.php
│   │   └── ControllerDashboard.php
│   ├── Core/
│   │   ├── App.php
│   │   ├── AppException.php
│   │   ├── Controller.php
│   │   ├── Database.php
│   │   ├── Model.php
│   │   └── Validator.php
│   ├── Helpers/
│   │   └── helpers.php
│   ├── Middleware/
│   │   ├── AuthGuard.php
│   │   └── RoleGuard.php
│   ├── Models/
│   │   ├── ModelDashboard.php
│   │   ├── ModelPersonal.php
│   │   └── ModelUsuario.php
│   └── Views/
│       ├── auth/Login.php
│       ├── dashboard/index.php
│       ├── errores/404.php
│       └── inc/
│           ├── header.php
│           └── footer.php
├── public_html/
│   ├── css/styles.css
│   ├── img/logo.png
│   ├── js/
│   │   ├── app.js
│   │   ├── dashboard.js
│   │   └── login.js
│   ├── sound/email.mp3
│   └── index.php
├── sql/README_MIGRACIONES.md
└── .htaccess