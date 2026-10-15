# Smart Home Visitor Entry System

A visitor scans a QR code at your door, enters their name and mobile number, takes a
photo, and submits. You see everyone on a private dashboard and can call or WhatsApp them.

Built with plain PHP, MySQL/MariaDB, Bootstrap, and a little JavaScript. No paid services.
Bootstrap and the QR library are included in `public/assets/vendor`, so it works without internet.

## What's inside

```
visitor-entry/
├── database.sql          Creates the database, tables and a limited DB user
├── create_admin.php      Command-line tool to create your homeowner login
├── app/                  Private code (config, helpers, page header/footer)
│   └── config.php        <- edit your database password here
├── storage/photos/       Visitor photos (private, not reachable from the web)
└── public/               The only folder the web server shows
    ├── index.php         Visitor sign-in page (what the QR code opens)
    ├── submit.php        Saves a visitor (validates everything)
    ├── login.php / logout.php
    ├── dashboard.php     Homeowner dashboard
    ├── qr.php            Make / print / download the QR code
    ├── photo.php         Shows photos to logged-in homeowners only
    ├── delete.php, api_latest.php
    └── assets/           CSS, JavaScript, Bootstrap, QR library
```

## Setup on Linux (Ubuntu / Debian)

### 1. Install what you need

```bash
sudo apt update
sudo apt install php php-cli php-mysql php-gd php-mbstring mariadb-server
```
(Already have PHP and MySQL? Just make sure `php-mysql`, `php-gd` and `php-mbstring` are there.
Needs PHP 7.4 or newer. On Fedora: `sudo dnf install php-cli php-mysqlnd php-gd php-mbstring mariadb-server`.)

### 2. Create the database

First pick a password: open `database.sql`, find `change_this_password`, and replace it with
your own. Then:

```bash
cd visitor-entry
sudo mysql < database.sql
```

### 3. Tell the app the password

Open `app/config.php` and set `db_pass` to the same password. While you're there, check
`timezone`.

### 4. Create your homeowner login

```bash
php create_admin.php yourname
```
It asks for a password (at least 8 characters). Run it again later to change a password
or add another person.

### 5. Start the website

```bash
php -S 0.0.0.0:8000 -t public
```
Leave that window open. If you use a firewall: `sudo ufw allow 8000/tcp`.

Find your PC's network address:
```bash
hostname -I
```
Say it prints `192.168.1.50`. Then:

1. On the PC, open `http://192.168.1.50:8000/login.php` and sign in.
2. Click **QR code** — the address is filled in for you. Print it or download the PNG.
3. Stick it by your door. Phones on your Wi-Fi can now scan it.

Tip: give the PC a fixed IP in your router settings so the QR code never stops working.

## About the camera

Browsers only allow the live in-page camera on **HTTPS** (or `localhost`). Over plain
`http://192.168...` the page automatically offers **"Use your phone's camera app"**, which
opens the selfie camera and works fine. If you want the live camera too, put the site behind
HTTPS (for example with Caddy using `tls internal`, or `mkcert`) — say the word and I'll
add that.

## How it's protected

- Dashboard and photos need a login; photos are stored outside `public/` and served by `photo.php` only to logged-in homeowners.
- Passwords are hashed (`password_hash`); failed logins are limited per IP.
- All database access uses prepared statements; the app's DB user can't change the schema.
- Uploaded photos are checked by real file content, size and dimensions, then re-encoded as a new JPEG (strips hidden data) with a random file name.
- CSRF tokens on every form, a hidden honeypot field, and a per-device limit on sign-ins.
- Everything shown on screen is escaped to prevent script injection.

## Troubleshooting

- **"Something went wrong"** — look at the terminal running `php -S`; the real error is printed there. Usually the DB password in `app/config.php` doesn't match `database.sql`.
- **Phone can't open the page** — the PC and phone must be on the same Wi-Fi, and the QR address must be the PC's network address, not `localhost`.
- **Photo won't save** — make sure `storage/photos` is writable by the user running PHP: `chmod 750 storage/photos`.
- **Using Apache/Nginx instead of `php -S`** — point the site's document root at the `public/` folder only.
