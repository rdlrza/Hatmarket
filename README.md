# HatMarket E-commerce Website

A modern e-commerce website for selling hats, built with PHP, MySQL, and Bootstrap.

## Features

- User authentication (login/register)
- Product catalog with categories
- Shopping cart functionality
- Checkout system
- Responsive design
- Admin panel (coming soon)

## Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher
- XAMPP (or similar local development environment)

## Installation

1. Clone this repository to your XAMPP's htdocs folder:
```bash
git clone https://github.com/yourusername/hatmarket.git
```

2. Create a new MySQL database named 'hatmarket_db'

3. Import the database structure:
- Open PHPMyAdmin (http://localhost/phpmyadmin)
- Select the 'hatmarket_db' database
- Go to the 'Import' tab
- Choose the file 'database/hatmarket.sql'
- Click 'Go' to import the database structure

4. Configure the database connection:
- Open `config/database.php`
- Update the database credentials if needed:
  ```php
  $servername = "localhost";
  $username = "root";
  $password = "";
  $dbname = "hatmarket_db";
  ```

5. Start your XAMPP server (Apache and MySQL)

6. Access the website:
```
http://localhost/hatmarket
```

## Project Structure

```
hatmarket/
├── assets/
│   ├── css/
│   ├── js/
│   └── images/
├── config/
│   └── database.php
├── includes/
│   ├── header.php
│   ├── footer.php
│   └── ...
├── database/
│   └── hatmarket.sql
├── index.php
├── shop.php
├── cart.php
├── checkout.php
└── README.md
```

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Acknowledgments

- Bootstrap for the responsive design framework
- Font Awesome for the icons
- XAMPP for the local development environment
