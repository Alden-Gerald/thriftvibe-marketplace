# ThriftVibe Marketplace

ThriftVibe is an academic team project exploring how a multi-role thrift marketplace can support buyers, sellers, and administrators in one database-backed web application.

## What it includes

- Account registration, authentication, and profile management
- Product browsing, search, categories, and detail pages
- Wishlist, cart, address, checkout, orders, and reviews
- Seller storefront, products, orders, settings, and subscription management
- Administrative dashboards, content moderation, subscriptions, reports, and product review

## Technology

- PHP 8+
- MySQL / MariaDB
- HTML, CSS, and JavaScript
- PDO with prepared statements

## My contribution

As a team contributor, I helped translate marketplace flows into working pages and connect interface interactions to server-side logic and persistent MySQL data. The project strengthened my understanding of role-based user journeys, connected data, validation, testing, and team coordination.

## Run locally

1. Install PHP, MySQL/MariaDB, and a local web server such as XAMPP.
2. Create a database and import `database/schema.sql`.
3. Configure the environment variables shown below, or rely on the local development defaults.
4. Serve this folder from your web server and open `index.php`.

```text
THRIFTVIBE_DB_HOST=localhost
THRIFTVIBE_DB_NAME=thriftvibe
THRIFTVIBE_DB_USER=root
THRIFTVIBE_DB_PASSWORD=
```

## Privacy and security

This public portfolio copy intentionally excludes real credentials, account data, password hashes, payment evidence, user-uploaded images, profile pictures, and order records. Only the database structure is included.

## Project status

Academic prototype created for learning and portfolio purposes. It is not a production commerce platform.
