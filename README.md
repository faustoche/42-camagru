# 📸 Camagru

> Take a photo with your webcam, add a sticker on it, share it with the world and with your friends!

![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?logo=php&logoColor=white)
![MariaDB](https://img.shields.io/badge/MariaDB-11-003545?logo=mariadb&logoColor=white)
![Docker](https://img.shields.io/badge/Docker-Compose-2496ED?logo=docker&logoColor=white)

---

## Getting started

**Requirements:** Docker & Docker Compose

```bash
git clone https://github.com/your-user/camagru.git
cd camagru
cp .env.example .env   # fill in your values ## NOT READY YET
mkdir -p srcs/uploads
make up
```

App is running at **http://localhost:8080**

---

## Environment variables

Copy `.env.example` to `.env` and fill in:

| Variable       | Example           |
|----------------|-------------------|
| `DB_HOST`      | `db`              |
| `DB_NAME`      | `camagru`         |
| `DB_USER`      | `camagru_user`    |
| `DB_PASS`      | `yourpassword`    |
| `DB_ROOT_PASS` | `yourrootpassword`|
| `DB_PORT`      | `3306`            |

> ⚠️ Never commit `.env` — it's already in `.gitignore`.

---

## Commands

```bash
make up      # Start all services
make down    # Stop all services
make logs    # Show logs
make re      # Full rebuild
make clean   # Remove containers, volumes and images
```

---

## Project structure
```
camagru/
├── nginx/          # Nginx config
├── php/            # PHP Dockerfile
├── db/             # init.sql (schema)
└── srcs/
    ├── public/     # Entry point (index.php) + static assets
    ├── core/       # Router, Session, Auth, Database
    ├── controllers/
    ├── models/
    ├── views/
    └── uploads/    # Generated images
```

---

## Authors

[Faustoche - fcrocq]